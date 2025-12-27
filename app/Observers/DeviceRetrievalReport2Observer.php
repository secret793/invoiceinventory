<?php

namespace App\Observers;

use App\Models\DeviceRetrieval;
use App\Models\DeviceRetrievalReport2;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DeviceRetrievalReport2Observer
{
    /**
     * Handle the DeviceRetrieval "updated" event.
     * Create records in Report #2 table when retrieve/return actions occur
     */
    public function updated(DeviceRetrieval $deviceRetrieval): void
    {
        try {
            // Check if retrieval_status has changed
            if ($deviceRetrieval->isDirty('retrieval_status')) {
                $oldStatus = $deviceRetrieval->getOriginal('retrieval_status');
                $newStatus = $deviceRetrieval->retrieval_status;

                Log::info('DeviceRetrievalReport2Observer: Status change detected', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'device_id' => $deviceRetrieval->device_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);

                // Detect RETRIEVED event (NOT_RETRIEVED -> RETRIEVED)
                if ($oldStatus === 'NOT_RETRIEVED' && $newStatus === 'RETRIEVED') {
                    $this->createReport2Entry($deviceRetrieval, 'RETRIEVED');
                }

                // Detect RETURNED event (RETRIEVED -> RETURNED)
                if ($oldStatus === 'RETRIEVED' && $newStatus === 'RETURNED') {
                    $this->updateReport2Entry($deviceRetrieval, 'RETURNED');
                }
            }
        } catch (\Exception $e) {
            Log::error('DeviceRetrievalReport2Observer: Error processing status change', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'device_retrieval_id' => $deviceRetrieval->id
            ]);
        }
    }

    /**
     * Create a new Report #2 entry for RETRIEVED action
     */
    private function createReport2Entry(DeviceRetrieval $deviceRetrieval, string $actionType): void
    {
        try {
            // Check if this exact event already exists to prevent duplicates
            $existingEntry = DeviceRetrievalReport2::where('device_id', $deviceRetrieval->device_id)
                ->where('action_type', $actionType)
                ->where('boe', $deviceRetrieval->boe)
                ->where('vehicle_number', $deviceRetrieval->vehicle_number)
                ->whereDate('created_at', now()->toDateString())
                ->first();

            if ($existingEntry) {
                Log::info('DeviceRetrievalReport2Observer: Duplicate entry prevented', [
                    'device_id' => $deviceRetrieval->device_id,
                    'action_type' => $actionType,
                    'existing_entry_id' => $existingEntry->id
                ]);
                return;
            }

            // Get full device ID from device relationship
            $fullDeviceId = $deviceRetrieval->device?->device_id ?? null;

            // Create new Report #2 entry
            $report2Data = [
                'device_id' => $deviceRetrieval->device_id,
                'device_full_id' => $fullDeviceId,
                'boe' => $deviceRetrieval->boe,
                'vehicle_number' => $deviceRetrieval->vehicle_number,
                'regime' => strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '', $deviceRetrieval->regime)), // Clean regime to single word
                'route_id' => $deviceRetrieval->route_id,
                'long_route_id' => $deviceRetrieval->long_route_id,
                'route_name' => $deviceRetrieval->route ? $deviceRetrieval->route->name : null,
                'destination' => $deviceRetrieval->destination,
                'allocation_point_id' => $deviceRetrieval->allocation_point_id,
                'retrieval_status' => $deviceRetrieval->retrieval_status,
                'action_type' => $actionType,
                'date' => now()->toDateString(),
                'overstay_days' => $deviceRetrieval->overstay_days ?? 0,
                'overstay_amount' => $deviceRetrieval->overstay_amount ?? 0,
            ];

            // Set action-specific fields
            if ($actionType === 'RETRIEVED') {
                $report2Data['retrieved_by'] = Auth::id();
                $report2Data['retrieval_date'] = now();
            }

            $report2Entry = DeviceRetrievalReport2::create($report2Data);

            Log::info('DeviceRetrievalReport2Observer: Entry created successfully', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'device_id' => $deviceRetrieval->device_id,
                'device_full_id' => $fullDeviceId,
                'action_type' => $actionType,
                'report2_entry_id' => $report2Entry->id,
                'user_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('DeviceRetrievalReport2Observer: Error creating Report #2 entry', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'device_retrieval_id' => $deviceRetrieval->id,
                'action_type' => $actionType
            ]);
        }
    }

    /**
     * Update existing Report #2 entry for RETURNED action
     */
    private function updateReport2Entry(DeviceRetrieval $deviceRetrieval, string $actionType): void
    {
        try {
            // Find the most recent RETRIEVED entry for this device
            $existingEntry = DeviceRetrievalReport2::where('device_id', $deviceRetrieval->device_id)
                ->where('action_type', 'RETRIEVED')
                ->whereNull('returned_by') // Entry not yet completed with return info
                ->latest()
                ->first();

            if ($existingEntry) {
                // Update existing entry with return information
                $existingEntry->update([
                    'retrieval_status' => $deviceRetrieval->retrieval_status,
                    'returned_by' => Auth::id(),
                    'returned_at' => now(),
                    // Update overstay information as it might have changed
                    'overstay_days' => $deviceRetrieval->overstay_days ?? 0,
                    'overstay_amount' => $deviceRetrieval->overstay_amount ?? 0,
                ]);

                Log::info('DeviceRetrievalReport2Observer: Entry updated with return info', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'device_id' => $deviceRetrieval->device_id,
                    'report2_entry_id' => $existingEntry->id,
                    'action_type' => $actionType,
                    'returned_by' => Auth::id()
                ]);
            } else {
                // This shouldn't normally happen, but create a new entry if no RETRIEVED entry found
                Log::warning('DeviceRetrievalReport2Observer: No RETRIEVED entry found for RETURNED action', [
                    'device_id' => $deviceRetrieval->device_id,
                    'creating_new_entry' => true
                ]);

                // Get full device ID from device relationship
                $fullDeviceId = $deviceRetrieval->device?->device_id ?? null;

                // Create new entry for RETURNED action
                DeviceRetrievalReport2::create([
                    'device_id' => $deviceRetrieval->device_id,
                    'device_full_id' => $fullDeviceId,
                    'boe' => $deviceRetrieval->boe,
                    'vehicle_number' => $deviceRetrieval->vehicle_number,
                    'regime' => $deviceRetrieval->regime,
                    'route_id' => $deviceRetrieval->route_id,
                    'long_route_id' => $deviceRetrieval->long_route_id,
                    'destination' => $deviceRetrieval->destination,
                    'allocation_point_id' => $deviceRetrieval->allocation_point_id,
                    'retrieval_status' => $deviceRetrieval->retrieval_status,
                    'action_type' => $actionType,
                    'date' => now()->toDateString(),
                    'returned_by' => Auth::id(),
                    'returned_at' => now(),
                    'overstay_days' => $deviceRetrieval->overstay_days ?? 0,
                    'overstay_amount' => $deviceRetrieval->overstay_amount ?? 0,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('DeviceRetrievalReport2Observer: Error updating Report #2 entry', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'device_retrieval_id' => $deviceRetrieval->id,
                'action_type' => $actionType
            ]);
        }
    }
}
