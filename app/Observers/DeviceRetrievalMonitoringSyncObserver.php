<?php

namespace App\Observers;

use App\Models\DeviceRetrieval;
use App\Models\Monitoring;
use Illuminate\Support\Facades\Log;

class DeviceRetrievalMonitoringSyncObserver
{
    /**
     * Handle the DeviceRetrieval "updated" event.
     * Synchronizes critical fields from device_retrievals to monitorings table.
     * 
     * This observer ensures data consistency between the two tables by:
     * 1. ALWAYS syncing overstay_days → monitoring.overstay_days (to handle calculation differences)
     * 2. Syncing retrieval_status → monitoring.retrieval_status
     * 
     * Motivation: Both tables are created together during affixing, but only
     * device_retrievals is updated when calculating overstay. This observer
     * prevents data drift by syncing updates back to monitoring table.
     */
    public function updated(DeviceRetrieval $deviceRetrieval): void
    {
        try {
            // Find the corresponding monitoring record by device_id
            $monitoring = Monitoring::where('device_id', $deviceRetrieval->device_id)->first();

            if (!$monitoring) {
                Log::warning('Monitoring record not found for device_retrieval sync', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'device_id' => $deviceRetrieval->device_id,
                    'reason' => 'No matching monitoring record by device_id'
                ]);
                return;
            }

            $fieldsToSync = [];

            // ALWAYS sync overstay_days to ensure they stay in sync
            // This is critical because overstay_days is calculated dynamically
            // and the two tables may have different calculation methods
            if ($deviceRetrieval->overstay_days !== null) {
                // Only sync if values differ to reduce unnecessary updates
                if ($monitoring->overstay_days != $deviceRetrieval->overstay_days) {
                    $fieldsToSync['overstay_days'] = $deviceRetrieval->overstay_days;
                    
                    Log::info('Syncing overstay_days to monitoring (PROACTIVE)', [
                        'device_retrieval_id' => $deviceRetrieval->id,
                        'monitoring_id' => $monitoring->id,
                        'device_id' => $deviceRetrieval->device_id,
                        'device_retrieval_overstay_days' => $deviceRetrieval->overstay_days,
                        'monitoring_overstay_days_before' => $monitoring->overstay_days,
                        'monitoring_overstay_days_after' => $deviceRetrieval->overstay_days,
                        'timestamp' => now()->toDateTimeString()
                    ]);
                }
            }

            // Sync retrieval_status
            if ($deviceRetrieval->wasChanged('retrieval_status')) {
                $fieldsToSync['retrieval_status'] = $deviceRetrieval->retrieval_status;
                
                Log::info('Syncing retrieval_status to monitoring', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'monitoring_id' => $monitoring->id,
                    'device_id' => $deviceRetrieval->device_id,
                    'retrieval_status' => $deviceRetrieval->retrieval_status,
                    'old_retrieval_status' => $monitoring->retrieval_status,
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            // Perform sync if there are fields to update
            if (!empty($fieldsToSync)) {
                // Add updated_at to track when sync happened
                $fieldsToSync['updated_at'] = now();

                // Use direct update to avoid triggering monitoring model observers
                Monitoring::where('id', $monitoring->id)->update($fieldsToSync);

                Log::info('DeviceRetrieval → Monitoring sync completed', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'monitoring_id' => $monitoring->id,
                    'device_id' => $deviceRetrieval->device_id,
                    'synced_fields' => array_keys($fieldsToSync),
                    'timestamp' => now()->toDateTimeString()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in DeviceRetrievalMonitoringSyncObserver', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'device_id' => $deviceRetrieval->device_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - allow device_retrieval update to succeed even if sync fails
        }
    }

    /**
     * Handle the DeviceRetrieval "deleted" event.
     * Optionally delete corresponding monitoring record (disabled by default).
     */
    public function deleted(DeviceRetrieval $deviceRetrieval): void
    {
        // Currently disabled to preserve monitoring history
        // If enabled, would be:
        // Monitoring::where('device_id', $deviceRetrieval->device_id)->delete();
        
        Log::info('DeviceRetrieval deleted - monitoring record preserved', [
            'device_retrieval_id' => $deviceRetrieval->id,
            'device_id' => $deviceRetrieval->device_id,
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}
