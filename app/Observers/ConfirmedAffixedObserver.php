<?php

namespace App\Observers;

use App\Models\AllocationPoint;
use App\Models\ConfirmedAffixed;
use App\Models\AssignToAgent;

class ConfirmedAffixedObserver
{
    /**
     * Handle the ConfirmedAffixed "created" event.
     */
    public function created(ConfirmedAffixed $confirmedAffixed): void
    {
        // Sync with AssignToAgent
        if ($confirmedAffixed->device_id) {
            AssignToAgent::where('device_id', $confirmedAffixed->device_id)
                ->update([
                    'date' => $confirmedAffixed->date ?? now(),
                    'boe' => $confirmedAffixed->boe,
                    'sad_number' => $confirmedAffixed->sad_number,
                    'vehicle_number' => $confirmedAffixed->vehicle_number,
                    'regime' => $confirmedAffixed->regime,
                    'destination' => $confirmedAffixed->destination,
                    'route_id' => $confirmedAffixed->route_id,
                    'long_route_id' => $confirmedAffixed->long_route_id,
                    'manifest_date' => $confirmedAffixed->manifest_date,
                    'agency' => $confirmedAffixed->agency,
                    'agent_contact' => $confirmedAffixed->agent_contact,
                    'truck_number' => $confirmedAffixed->truck_number,
                    'driver_name' => $confirmedAffixed->driver_name,
                    'affixing_date' => $confirmedAffixed->affixing_date,
                    'status' => 'AFFIXED'
                ]);
        }
    }

    /**
     * Handle the ConfirmedAffixed "updated" event.
     */
    public function updated(ConfirmedAffixed $confirmedAffixed): void
    {
        // Sync with AssignToAgent when any field changes
        if ($confirmedAffixed->isDirty() && $confirmedAffixed->device_id) {
            AssignToAgent::where('device_id', $confirmedAffixed->device_id)
                ->update([
                    'date' => $confirmedAffixed->date ?? now(),
                    'boe' => $confirmedAffixed->boe,
                    'sad_number' => $confirmedAffixed->sad_number,
                    'vehicle_number' => $confirmedAffixed->vehicle_number,
                    'regime' => $confirmedAffixed->regime,
                    'destination' => $confirmedAffixed->destination,
                    'route_id' => $confirmedAffixed->route_id,
                    'long_route_id' => $confirmedAffixed->long_route_id,
                    'manifest_date' => $confirmedAffixed->manifest_date,
                    'agency' => $confirmedAffixed->agency,
                    'agent_contact' => $confirmedAffixed->agent_contact,
                    'truck_number' => $confirmedAffixed->truck_number,
                    'driver_name' => $confirmedAffixed->driver_name,
                    'affixing_date' => $confirmedAffixed->affixing_date,
                    'status' => 'AFFIXED'
                ]);
        }
    }

    /**
     * Handle the ConfirmedAffixed "deleted" event.
     */
    public function deleted(ConfirmedAffixed $confirmedAffixed): void
    {
        // Update AssignToAgent status when ConfirmedAffixed is deleted
        if ($confirmedAffixed->device_id) {
            AssignToAgent::where('device_id', $confirmedAffixed->device_id)
                ->update([
                    'affixing_date' => null,
                    'status' => 'ASSIGNED'
                ]);
        }
    }

    /**
     * Handle the AllocationPoint "created" event.
     */
    public function allocationPointCreated(AllocationPoint $allocationPoint): void
    {
        // Create corresponding ConfirmedAffixed with better title format
        ConfirmedAffixed::create([
            'date' => now(),
            'device_id' => null,
            'status' => 'PENDING'
        ]);
    }

    /**
     * Handle the AllocationPoint "deleted" event.
     */
    public function allocationPointDeleted(AllocationPoint $allocationPoint): void
    {
        // Delete corresponding assignments
        ConfirmedAffixed::where('device_id', null)->delete();
    }
}
