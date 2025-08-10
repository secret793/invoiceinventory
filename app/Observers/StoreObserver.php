<?php

namespace App\Observers;

use App\Models\Store;
use App\Models\Device;

class StoreObserver
{
    /**
     * Handle the Store "creating" event.
     * Called before a new record is created
     */
    public function creating(Store $store): void
    {
        if (is_null($store->status)) {
            $store->status = 'UNCONFIGURED';
        }
    }

    /**
     * Handle the Store "created" event.
     */
    public function created(Store $store): void
    {
        if (is_null($store->status)) {
            $store->update(['status' => 'UNCONFIGURED']);
        }

        // Sync with device
        $device = Device::where('device_id', $store->device_id)->first();
        if ($device) {
            $device->update([
                'status' => $store->status,
                'sim_number' => $store->status === 'UNCONFIGURED' ? null : $store->sim_number,
                'sim_operator' => $store->status === 'UNCONFIGURED' ? null : $store->sim_operator,
            ]);
        }
    }

    /**
     * Handle the Store "updated" event.
     * Triggered when a store record is updated
     */
    public function updated(Store $store): void
    {
        // Check if status is null and set to UNCONFIGURED
        if (is_null($store->status)) {
            $store->update(['status' => 'UNCONFIGURED']);
        }

        // Check if the status has changed
        if ($store->isDirty('status')) {
            // Get the corresponding device
            $device = Device::where('device_id', $store->device_id)->first();

            if ($device) {
                // Update the device status based on the store's status
                $device->status = $store->status;

                // Determine if the device is configured based on the new status
                if ($store->status !== 'UNCONFIGURED') {
                    // Update additional fields with values from the device
                    $device->sim_number = $store->sim_number;
                    $device->sim_operator = $store->sim_operator;
                } else {
                    // Set to null if the status is UNCONFIGURED
                    $device->sim_number = null;
                    $device->sim_operator = null;
                }

                // Save the device record
                $device->save();
            }
        }
    }

    /**
     * Handle the Store "saving" event.
     */
    public function saving(Store $store): void
    {
        if (is_null($store->status)) {
            $store->status = 'UNCONFIGURED';
        }
    }
} 