<?php

namespace App\Observers;

use App\Models\DeviceRetrieval;
use App\Models\DeviceRetrievalLog;
use App\Models\ConfirmedAffixLog;
use Illuminate\Support\Facades\Log;

class DeviceRetrievalAffixLogObserver
{
    /**
     * Handle the DeviceRetrieval "created" event.
     * Create ConfirmedAffixLog record when DeviceRetrieval is created.
     */
    public function created(DeviceRetrieval $deviceRetrieval)
    {
        try {
            $data = [
                'device_id' => $deviceRetrieval->device_id,
                'boe' => $deviceRetrieval->boe,
                'sad_number' => $deviceRetrieval->sad_number ?? null,
                'vehicle_number' => $deviceRetrieval->vehicle_number,
                'regime' => $deviceRetrieval->regime,
                'destination' => $deviceRetrieval->destination,
                'destination_id' => $deviceRetrieval->destination_id ?? null,
                'route_id' => $deviceRetrieval->route_id,
                'long_route_id' => $deviceRetrieval->long_route_id,
                'manifest_date' => $deviceRetrieval->manifest_date,
                'agency' => $deviceRetrieval->agency,
                'agent_contact' => $deviceRetrieval->agent_contact,
                'truck_number' => $deviceRetrieval->truck_number,
                'driver_name' => $deviceRetrieval->driver_name,
                'affixing_date' => $deviceRetrieval->affixing_date,
                'status' => $deviceRetrieval->retrieval_status ?? 'AFFIXED',
                'allocation_point_id' => $deviceRetrieval->allocation_point_id ?? null,
                'affixed_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $id = \DB::table('confirmed_affix_logs')->insertGetId($data);
            if (!$id) {
                Log::error('DeviceRetrievalAffixLogObserver: Failed to insert ConfirmedAffixLog for device_id: ' . $deviceRetrieval->device_id);
            } else {
                Log::info('DeviceRetrievalAffixLogObserver: Inserted ConfirmedAffixLog', [
                    'device_id' => $deviceRetrieval->device_id,
                    'log_id' => $id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('DeviceRetrievalAffixLogObserver: Exception inserting ConfirmedAffixLog: ' . $e->getMessage(), [
                'device_id' => $deviceRetrieval->device_id
            ]);
        }
    }

    /**
     * Handle the DeviceRetrieval "updated" event.
     * Log when retrieval_status changes to RETRIEVED or RETURNED
     */
    public function updated(DeviceRetrieval $deviceRetrieval): void
    {
        // Check if retrieval_status was changed
        if ($deviceRetrieval->isDirty('retrieval_status')) {
            $newStatus = $deviceRetrieval->retrieval_status;
            $oldStatus = $deviceRetrieval->getOriginal('retrieval_status');

            Log::info('DeviceRetrievalAffixLogObserver: Status change detected', [
                'device_id' => $deviceRetrieval->device_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'should_log' => in_array($newStatus, ['RETRIEVED', 'RETURNED']),
                'timestamp' => now()->toDateTimeString()
            ]);

            // Only log if status changed to RETRIEVED or RETURNED
            if (in_array($newStatus, ['RETRIEVED', 'RETURNED']) && $newStatus !== $oldStatus) {
                try {
                    if ($newStatus === 'RETRIEVED') {
                        // For RETRIEVED action: Find incomplete log or create new one
                        $existingLog = DeviceRetrievalLog::where('device_id', $deviceRetrieval->device_id)
                            ->whereNull('returned_by')
                            ->latest()
                            ->first();

                        if ($existingLog) {
                            // Update existing incomplete log with new retrieval data
                            $existingLog->update([
                                'retrieval_status' => $newStatus,
                                'action_type' => $newStatus,
                                'retrieved_by' => auth()->id(),
                                'retrieval_date' => now(),
                                'returned_by' => null, // Ensure it's null for incomplete transaction
                                'returned_at' => null,
                                'updated_at' => now(),
                            ]);
                            $retrievalLog = $existingLog;

                            Log::info('📝 DeviceRetrievalAffixLogObserver: Updated existing incomplete log for retrieval', [
                                'device_id' => $deviceRetrieval->device_id,
                                'log_id' => $existingLog->id,
                                'action_type' => $newStatus,
                                'updated_fields' => ['retrieval_status', 'action_type', 'retrieved_by', 'retrieval_date']
                            ]);
                        } else {
                            // Create new log record for retrieval
                            $retrievalLog = DeviceRetrievalLog::create([
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
                                'retrieval_status' => $newStatus,
                                'overdue_hours' => $deviceRetrieval->overdue_hours ?? 0,
                                'overstay_days' => $deviceRetrieval->overstay_days ?? 0,
                                'overstay_amount' => $deviceRetrieval->overstay_amount ?? 0,
                                'payment_status' => $deviceRetrieval->payment_status,
                                'receipt_number' => $deviceRetrieval->receipt_number,
                                'distribution_point_id' => $deviceRetrieval->distribution_point_id,
                                'allocation_point_id' => $deviceRetrieval->allocation_point_id,
                                'retrieved_by' => auth()->id(),
                                'returned_by' => null, // Will be filled when device is returned
                                'retrieval_date' => now(),
                                'returned_at' => null,
                                'action_type' => $newStatus,
                            ]);

                            Log::info('➕ DeviceRetrievalAffixLogObserver: Created new log entry for retrieval', [
                                'device_id' => $deviceRetrieval->device_id,
                                'log_id' => $retrievalLog->id,
                                'action_type' => $newStatus,
                                'reason' => 'No incomplete log found - creating new record'
                            ]);
                        }
                    } else if ($newStatus === 'RETURNED') {
                        // For RETURNED action: Find incomplete log and complete it
                        $existingLog = DeviceRetrievalLog::where('device_id', $deviceRetrieval->device_id)
                            ->whereNull('returned_by')
                            ->latest()
                            ->first();

                        if ($existingLog) {
                            // Complete the existing log with return information
                            $existingLog->update([
                                'retrieval_status' => $newStatus,
                                'action_type' => $newStatus,
                                'returned_by' => auth()->id(),
                                'returned_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $retrievalLog = $existingLog;

                            Log::info('✅ DeviceRetrievalAffixLogObserver: Completed existing log with return action', [
                                'device_id' => $deviceRetrieval->device_id,
                                'log_id' => $existingLog->id,
                                'action_type' => $newStatus,
                                'returned_by' => auth()->id(),
                                'updated_fields' => ['retrieval_status', 'action_type', 'returned_by', 'returned_at']
                            ]);
                        } else {
                            // This shouldn't happen, but create a new log if no incomplete log found
                            $retrievalLog = DeviceRetrievalLog::create([
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
                                'retrieval_status' => $newStatus,
                                'overdue_hours' => $deviceRetrieval->overdue_hours ?? 0,
                                'overstay_days' => $deviceRetrieval->overstay_days ?? 0,
                                'overstay_amount' => $deviceRetrieval->overstay_amount ?? 0,
                                'payment_status' => $deviceRetrieval->payment_status,
                                'receipt_number' => $deviceRetrieval->receipt_number,
                                'distribution_point_id' => $deviceRetrieval->distribution_point_id,
                                'allocation_point_id' => $deviceRetrieval->allocation_point_id,
                                'retrieved_by' => null, // No retrieval user for this case
                                'returned_by' => auth()->id(),
                                'retrieval_date' => null,
                                'returned_at' => now(),
                                'action_type' => $newStatus,
                            ]);

                            Log::warning('⚠️ DeviceRetrievalAffixLogObserver: Created new log for return without retrieval', [
                                'device_id' => $deviceRetrieval->device_id,
                                'log_id' => $retrievalLog->id,
                                'action_type' => $newStatus,
                                'reason' => 'No incomplete log found for return action'
                            ]);
                        }
                    }

                    Log::info('✅ DeviceRetrievalAffixLogObserver: Successfully processed device retrieval action', [
                        'device_id' => $deviceRetrieval->device_id,
                        'action_type' => $newStatus,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'retrieved_by' => $retrievalLog->retrieved_by,
                        'returned_by' => $retrievalLog->returned_by,
                        'device_retrieval_log_id' => $retrievalLog->id,
                        'operation' => $newStatus === 'RETURNED' ? 'completed' : 'created/updated',
                        'timestamp' => now()->toDateTimeString(),
                    ]);

                } catch (\Exception $e) {
                    Log::error('❌ DeviceRetrievalAffixLogObserver: Failed to create logs', [
                        'device_id' => $deviceRetrieval->device_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                Log::info('DeviceRetrievalAffixLogObserver: Status change ignored', [
                    'device_id' => $deviceRetrieval->device_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => 'Not a RETRIEVED/RETURNED status or no actual change'
                ]);
            }
        } else {
            Log::debug('DeviceRetrievalAffixLogObserver: No status change detected', [
                'device_id' => $deviceRetrieval->device_id,
                'current_status' => $deviceRetrieval->retrieval_status,
                'dirty_fields' => array_keys($deviceRetrieval->getDirty())
            ]);
        }
    }
}
