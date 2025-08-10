<?php

namespace App\Observers;

use App\Models\Device;
use App\Models\DistributionPoint;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceObserver
{
    /**
     * Handle the Device "created" event.
     */
    public function created(Device $device): void
    {
        $this->clearRelatedCaches($device);
    }

    /**
     * Handle the Device "updated" event.
     */
    public function updated(Device $device): void
    {
        $wasStatusChanged = $device->isDirty('status');

        // If distribution_point_id or status changed, clear caches
        if ($device->isDirty('distribution_point_id') || $wasStatusChanged) {
            // Clear cache for old distribution point if it changed
            if ($device->isDirty('distribution_point_id') && $device->getOriginal('distribution_point_id')) {
                $this->clearCacheForDistributionPoint($device->getOriginal('distribution_point_id'));
            }

            $this->clearRelatedCaches($device);
        }

        // Define statuses that should not trigger a move to the store.
        $nonMovableStatuses = ['ONLINE', 'OFFLINE'];

        // Check if the status has been updated to a "movable" status.
        if ($wasStatusChanged && !in_array($device->status, $nonMovableStatuses)) {
            DB::transaction(function () use ($device) {
                // Check if a store record already exists for this device
                $existingStore = Store::where('device_id', $device->id)->first();

                if ($existingStore) {
                    // Update the existing store record
                    $existingStore->update([
                        'status' => $device->status,
                        'sim_number' => $device->sim_number,
                        'sim_operator' => $device->sim_operator,
                        'device_type' => $device->device_type,
                        'batch_number' => $device->batch_number,
                        'date_received' => $device->date_received,
                        'distribution_point_id' => $device->distribution_point_id,
                        'user_id' => $device->user_id,
                        'is_configured' => $device->is_configured ?? false,
                        'is_visible' => true,
                    ]);
                } else {
                    // Create a new record in the stores table
                    Store::create([
                        'device_id' => $device->id,
                        'status' => $device->status,
                        'sim_number' => $device->sim_number,
                        'sim_operator' => $device->sim_operator,
                        'device_type' => $device->device_type,
                        'batch_number' => $device->batch_number,
                        'date_received' => $device->date_received,
                        'distribution_point_id' => $device->distribution_point_id,
                        'user_id' => $device->user_id,
                        'is_configured' => $device->is_configured ?? false,
                        'is_visible' => true,
                    ]);
                }

                // Delete the original device record. This will trigger the "deleted" event.
                // $device->delete(); // Commented out to prevent deletion until we're sure this works
            });
        }
    }

    /**
     * Handle the Device "deleted" event.
     */
    public function deleted(Device $device): void
    {
        $this->clearRelatedCaches($device);
    }

    /**
     * Clear caches related to this device
     */
    private function clearRelatedCaches(Device $device): void
    {
        if ($device->distribution_point_id) {
            $this->clearCacheForDistributionPoint($device->distribution_point_id);
        }
    }

    /**
     * Clear cache for a specific distribution point
     */
    private function clearCacheForDistributionPoint($distributionPointId): void
    {
        try {
            $point = DistributionPoint::find($distributionPointId);
            if ($point) {
                $point->clearDeviceCountCache();
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear distribution point cache: ' . $e->getMessage(), [
                'distribution_point_id' => $distributionPointId
            ]);
        }
    }
}




