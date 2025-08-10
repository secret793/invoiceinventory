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
                    // Check if a DeviceRetrievalLog already exists for this device
                    $existingLog = DeviceRetrievalLog::where('device_id', $deviceRetrieval->device_id)
                        ->where('boe', $deviceRetrieval->boe)
                        ->first();

                    if ($existingLog && $newStatus === 'RETURNED') {
                        // Update existing log when device is returned
                        $existingLog->update([
                            'retrieval_status' => $newStatus,
                            'action_type' => $newStatus,
                            'retrieved_by' => auth()->id(),
                            'retrieval_date' => now(),
                            'updated_at' => now(),
                        ]);
                        $retrievalLog = $existingLog;

                        Log::info('📝 DeviceRetrievalAffixLogObserver: Updated existing log entry', [
                            'device_id' => $deviceRetrieval->device_id,
                            'log_id' => $existingLog->id,
                            'action_type' => $newStatus,
                            'updated_fields' => ['retrieval_status', 'action_type', 'retrieved_by', 'retrieval_date']
                        ]);
                    } else {
                        // Create new DeviceRetrievalLog for RETRIEVED or if no existing log found
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
                            'retrieval_date' => now(),
                            'action_type' => $newStatus,
                        ]);

                        Log::info('➕ DeviceRetrievalAffixLogObserver: Created new log entry', [
                            'device_id' => $deviceRetrieval->device_id,
                            'log_id' => $retrievalLog->id,
                            'action_type' => $newStatus,
                            'reason' => $existingLog ? 'No existing log found' : 'First retrieval action'
                        ]);
                    }

                    // Also create ConfirmedAffixLog when device is retrieved or returned
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
                        'status' => $newStatus, // Use the new retrieval status
                        'allocation_point_id' => $deviceRetrieval->allocation_point_id ?? null,
                        'affixed_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $logId = \DB::table('confirmed_affix_logs')->insertGetId($data);

                    Log::info('✅ DeviceRetrievalAffixLogObserver: Successfully processed device retrieval action', [
                        'device_id' => $deviceRetrieval->device_id,
                        'action_type' => $newStatus,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'retrieved_by' => auth()->id(),
                        'device_retrieval_log_id' => $retrievalLog->id,
                        'confirmed_affix_log_id' => $logId,
                        'operation' => $existingLog && $newStatus === 'RETURNED' ? 'updated' : 'created',
                        'timestamp' => now()->toDateTimeString()
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
