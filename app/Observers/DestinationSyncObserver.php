<?php

namespace App\Observers;

use App\Models\ConfirmedAffixed;
use App\Models\DeviceRetrieval;
use Illuminate\Support\Facades\Log;

class DestinationSyncObserver
{
    /**
     * Handle the ConfirmedAffixed "saved" event.
     */
    public function saved(ConfirmedAffixed $confirmedAffixed): void
    {
        try {
            // Check if destination is empty
            if (empty($confirmedAffixed->destination)) {
                // Find the corresponding DeviceRetrieval
                $deviceRetrieval = DeviceRetrieval::where('device_id', $confirmedAffixed->device_id)->first();

                if ($deviceRetrieval) {
                    // Sync the destination
                    $confirmedAffixed->destination = $deviceRetrieval->destination;
                    $confirmedAffixed->save();

                    // Log the sync for debugging
                    Log::info('Destination synced from DeviceRetrieval to ConfirmedAffixed', [
                        'device_id' => $confirmedAffixed->device_id,
                        'destination' => $confirmedAffixed->destination,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error syncing destination from DeviceRetrieval to ConfirmedAffixed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
