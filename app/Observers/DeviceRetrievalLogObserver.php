<?php

namespace App\Observers;

use App\Models\DeviceRetrieval;
use App\Models\DeviceRetrievalLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DeviceRetrievalLogObserver
{
    /**
     * Handle the DeviceRetrieval "updated" event.
     * Detects when retrieval_status changes to log unique retrieve/return events
     */
    public function updated(DeviceRetrieval $deviceRetrieval): void
    {
        try {
            // Check if retrieval_status has changed
            if ($deviceRetrieval->isDirty('retrieval_status')) {
                $oldStatus = $deviceRetrieval->getOriginal('retrieval_status');
                $newStatus = $deviceRetrieval->retrieval_status;

                Log::info('DeviceRetrievalLogObserver: Status change detected', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'device_id' => $deviceRetrieval->device_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);

                // Detect RETRIEVED event (NOT_RETRIEVED -> RETRIEVED)
                if ($oldStatus === 'NOT_RETRIEVED' && $newStatus === 'RETRIEVED') {
                    $this->logRetrievalEvent($deviceRetrieval, 'RETRIEVED');
                }

                // Detect RETURNED event (RETRIEVED -> RETURNED)
                if ($oldStatus === 'RETRIEVED' && $newStatus === 'RETURNED') {
                    $this->logRetrievalEvent($deviceRetrieval, 'RETURNED');
                }
            }
        } catch (\Exception $e) {
            Log::error('DeviceRetrievalLogObserver: Error processing status change', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'device_retrieval_id' => $deviceRetrieval->id
            ]);
        }
    }

    /**
     * Log a retrieval event to the device_retrieval_logs table
     */
    private function logRetrievalEvent(DeviceRetrieval $deviceRetrieval, string $actionType): void
    {
        try {
            // Check if this exact event already exists to prevent duplicates
            $existingLog = DeviceRetrievalLog::where('device_id', $deviceRetrieval->device_id)
                ->where('action_type', $actionType)
                ->where('boe', $deviceRetrieval->boe)
                ->where('vehicle_number', $deviceRetrieval->vehicle_number)
                ->whereDate('created_at', now()->toDateString())
                ->first();

            if ($existingLog) {
                Log::info('DeviceRetrievalLogObserver: Duplicate event prevented', [
                    'device_id' => $deviceRetrieval->device_id,
                    'action_type' => $actionType,
                    'existing_log_id' => $existingLog->id
                ]);
                return;
            }

            // Create new log entry
            $logData = [
                'date' => $deviceRetrieval->date,
                'device_id' => $deviceRetrieval->device_id,
                'boe' => $deviceRetrieval->boe,
                'sad_number' => $deviceRetrieval->sad_number,
                'vehicle_number' => $deviceRetrieval->vehicle_number,
                'regime' => $deviceRetrieval->regime,
                'destination' => $deviceRetrieval->destination,
                'destination_id' => $deviceRetrieval->destination_id,
                'current_time' => $deviceRetrieval->current_time,
                'route_id' => $deviceRetrieval->route_id,
                'long_route_id' => $deviceRetrieval->long_route_id,
                'manifest_date' => $deviceRetrieval->manifest_date,
                'note' => $deviceRetrieval->note,
                'agency' => $deviceRetrieval->agency,
                'agent_contact' => $deviceRetrieval->agent_contact,
                'truck_number' => $deviceRetrieval->truck_number,
                'driver_name' => $deviceRetrieval->driver_name,
                'affixing_date' => $deviceRetrieval->affixing_date,
                'status' => $deviceRetrieval->status,
                'retrieval_status' => $deviceRetrieval->retrieval_status,
                'overdue_hours' => $deviceRetrieval->overdue_hours,
                'overstay_days' => $deviceRetrieval->overstay_days,
                'overstay_amount' => $deviceRetrieval->overstay_amount,
                'payment_status' => $deviceRetrieval->payment_status,
                'receipt_number' => $deviceRetrieval->receipt_number,
                'distribution_point_id' => $deviceRetrieval->distribution_point_id,
                'allocation_point_id' => $deviceRetrieval->allocation_point_id,
                'action_type' => $actionType,
            ];

            // Set specific fields based on action type
            if ($actionType === 'RETRIEVED') {
                $logData['retrieved_by'] = Auth::id();
                $logData['retrieval_date'] = now();
            } elseif ($actionType === 'RETURNED') {
                $logData['returned_by'] = Auth::id();
                $logData['returned_at'] = now();
            }

            $retrievalLog = DeviceRetrievalLog::create($logData);

            Log::info('DeviceRetrievalLogObserver: Event logged successfully', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'device_id' => $deviceRetrieval->device_id,
                'action_type' => $actionType,
                'log_id' => $retrievalLog->id,
                'user_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('DeviceRetrievalLogObserver: Error creating log entry', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'device_retrieval_id' => $deviceRetrieval->id,
                'action_type' => $actionType
            ]);
        }
    }
}
