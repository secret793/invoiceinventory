<?php

namespace App\Observers;

use App\Models\DeviceRetrieval;
use App\Models\Monitoring;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeviceRetrievalStatusSyncObserver
{
    /**
     * Handle the DeviceRetrieval "created" event.
     */
    public function created(DeviceRetrieval $deviceRetrieval)
    {
        $logContext = [
            'device_retrieval_id' => $deviceRetrieval->id,
            'device_id' => $deviceRetrieval->device_id,
            'retrieval_status' => $deviceRetrieval->retrieval_status,
            'timestamp' => now()->toDateTimeString()
        ];

        Log::info('DeviceRetrievalStatusSyncObserver: Created event triggered', $logContext);

        // Add browser debugging
        if (app()->environment(['local', 'staging'])) {
            error_log('DeviceRetrievalStatusSyncObserver: Created event - Device ID: ' . $deviceRetrieval->device_id . ', Status: ' . $deviceRetrieval->retrieval_status);
        }

        try {
            Log::info('DeviceRetrievalStatusSyncObserver: Attempting to sync initial retrieval_status to Monitoring', $logContext);

            // Find the related monitoring record
            $monitoring = Monitoring::where('device_id', $deviceRetrieval->device_id)->first();

            if ($monitoring) {
                $oldStatus = $monitoring->retrieval_status;

                // Update using direct DB query to avoid potential model events loop
                $updated = Monitoring::where('id', $monitoring->id)
                    ->update([
                        'retrieval_status' => $deviceRetrieval->retrieval_status,
                        'updated_at' => now()
                    ]);

                if ($updated) {
                    $successContext = array_merge($logContext, [
                        'monitoring_id' => $monitoring->id,
                        'old_monitoring_status' => $oldStatus,
                        'new_monitoring_status' => $deviceRetrieval->retrieval_status,
                        'rows_affected' => $updated
                    ]);

                    Log::info('DeviceRetrievalStatusSyncObserver: Successfully synced initial retrieval_status to Monitoring', $successContext);

                    // Browser debugging for success
                    if (app()->environment(['local', 'staging'])) {
                        error_log('SUCCESS: Monitoring status synced - Device ID: ' . $deviceRetrieval->device_id . ', Old: ' . $oldStatus . ', New: ' . $deviceRetrieval->retrieval_status);
                    }
                } else {
                    $errorContext = array_merge($logContext, [
                        'monitoring_id' => $monitoring->id,
                        'error_type' => 'no_rows_affected'
                    ]);

                    Log::error('DeviceRetrievalStatusSyncObserver: Failed to sync initial retrieval_status - no rows affected', $errorContext);

                    // Browser debugging for failure
                    if (app()->environment(['local', 'staging'])) {
                        error_log('ERROR: No rows affected when syncing monitoring status - Device ID: ' . $deviceRetrieval->device_id);
                    }
                }
            } else {
                $warningContext = array_merge($logContext, [
                    'error_type' => 'monitoring_not_found'
                ]);

                Log::warning('DeviceRetrievalStatusSyncObserver: No related Monitoring found for device on create', $warningContext);

                // Browser debugging for missing monitoring
                if (app()->environment(['local', 'staging'])) {
                    error_log('WARNING: No monitoring record found for device ID: ' . $deviceRetrieval->device_id);
                }
            }
        } catch (\Exception $e) {
            $errorContext = array_merge($logContext, [
                'error' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            Log::error('DeviceRetrievalStatusSyncObserver: Error syncing initial retrieval_status to Monitoring', $errorContext);

            // Browser debugging for exceptions
            if (app()->environment(['local', 'staging'])) {
                error_log('EXCEPTION: DeviceRetrievalStatusSyncObserver created - ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            }
        }
    }

    /**
     * Handle the DeviceRetrieval "updated" event.
     */
    public function updated(DeviceRetrieval $deviceRetrieval)
    {
        $changes = $deviceRetrieval->getChanges();
        $oldStatus = $deviceRetrieval->getOriginal('retrieval_status');
        $newStatus = $deviceRetrieval->retrieval_status;

        $logContext = [
            'device_retrieval_id' => $deviceRetrieval->id,
            'device_id' => $deviceRetrieval->device_id,
            'original_retrieval_status' => $oldStatus,
            'new_retrieval_status' => $newStatus,
            'changes' => array_keys($changes),
            'retrieval_status_changed' => array_key_exists('retrieval_status', $changes),
            'timestamp' => now()->toDateTimeString()
        ];

        Log::info('DeviceRetrievalStatusSyncObserver: Updated event triggered', $logContext);

        // Browser debugging for all updates
        if (app()->environment(['local', 'staging'])) {
            error_log('DeviceRetrievalStatusSyncObserver: Updated event - Device ID: ' . $deviceRetrieval->device_id . ', Status changed: ' . (array_key_exists('retrieval_status', $changes) ? 'YES' : 'NO'));
        }

        // Only act if retrieval_status changed (use getChanges() since model is already saved in updated event)
        if (array_key_exists('retrieval_status', $changes)) {
            // Browser debugging for status changes
            if (app()->environment(['local', 'staging'])) {
                error_log('RETRIEVAL STATUS CHANGE DETECTED - Device ID: ' . $deviceRetrieval->device_id . ', Old: ' . $oldStatus . ', New: ' . $newStatus);
            }

            try {
                Log::info('DeviceRetrievalStatusSyncObserver: Attempting to update Monitoring retrieval_status', $logContext);

                // Find the related monitoring record
                $monitoring = Monitoring::where('device_id', $deviceRetrieval->device_id)->first();

                if ($monitoring) {
                    $oldMonitoringStatus = $monitoring->retrieval_status;

                    // Update using direct DB query to avoid potential model events loop
                    $updated = Monitoring::where('id', $monitoring->id)
                        ->update([
                            'retrieval_status' => $newStatus,
                            'updated_at' => now()
                        ]);

                    if ($updated) {
                        $successContext = array_merge($logContext, [
                            'monitoring_id' => $monitoring->id,
                            'old_monitoring_status' => $oldMonitoringStatus,
                            'new_monitoring_status' => $newStatus,
                            'rows_affected' => $updated
                        ]);

                        Log::info('DeviceRetrievalStatusSyncObserver: Successfully updated Monitoring retrieval_status', $successContext);

                        // Browser debugging for successful sync
                        if (app()->environment(['local', 'staging'])) {
                            error_log('SUCCESS: Monitoring status updated - Device ID: ' . $deviceRetrieval->device_id . ', Monitoring Old: ' . $oldMonitoringStatus . ', Monitoring New: ' . $newStatus);
                        }
                    } else {
                        $errorContext = array_merge($logContext, [
                            'monitoring_id' => $monitoring->id,
                            'error_type' => 'no_rows_affected'
                        ]);

                        Log::error('DeviceRetrievalStatusSyncObserver: Failed to update Monitoring retrieval_status - no rows affected', $errorContext);

                        // Browser debugging for update failure
                        if (app()->environment(['local', 'staging'])) {
                            error_log('ERROR: No rows affected when updating monitoring status - Device ID: ' . $deviceRetrieval->device_id . ', Monitoring ID: ' . $monitoring->id);
                        }
                    }
                } else {
                    $warningContext = array_merge($logContext, [
                        'error_type' => 'monitoring_not_found'
                    ]);

                    Log::warning('DeviceRetrievalStatusSyncObserver: No related Monitoring found for device', $warningContext);

                    // Browser debugging for missing monitoring
                    if (app()->environment(['local', 'staging'])) {
                        error_log('WARNING: No monitoring record found for device ID: ' . $deviceRetrieval->device_id . ' during update');
                    }
                }
            } catch (\Exception $e) {
                $errorContext = array_merge($logContext, [
                    'error' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);

                Log::error('DeviceRetrievalStatusSyncObserver: Error updating Monitoring retrieval_status', $errorContext);

                // Browser debugging for exceptions
                if (app()->environment(['local', 'staging'])) {
                    error_log('EXCEPTION: DeviceRetrievalStatusSyncObserver updated - ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                }
            }
        } else {
            // Browser debugging when no status change
            if (app()->environment(['local', 'staging'])) {
                error_log('NO ACTION: Retrieval status not changed for device ID: ' . $deviceRetrieval->device_id . ', Changes: ' . implode(', ', array_keys($changes)));
            }
        }
    }
}
