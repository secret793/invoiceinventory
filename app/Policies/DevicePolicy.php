<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DevicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole(['Distribution Officer','Super Admin', 'Warehouse Manager', 'Retrieval Officer', 'Data Entry Officer two'])) {
            return true;
        }
        return $user->hasAnyPermission(['view devices', 'create devices', 'edit devices', 'delete devices']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Device $device): bool
    {
        if ($user->hasRole('Distribution Officer')) {
            return false;
        }
        return $user->hasPermissionTo('view devices');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('Distribution Officer')) {
            return false;
        }
        return $user->hasPermissionTo('create devices');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Device $device): bool
    {
        if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Retrieval Officer'])) {
            return true;
        }
        
        // Check if device is already assigned or in transfer
        if ($device->status === 'assigned' || $device->status === 'in_transfer') {
            return $user->hasPermissionTo('manage transfers');
        }

        return $user->hasPermissionTo('edit devices');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Device $device): bool
    {
        // Prevent deletion of assigned or in-transfer devices
        if ($device->status === 'assigned' || $device->status === 'in_transfer') {
            return false;
        }

        return $user->hasPermissionTo('delete devices');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Device $device): bool
    {
        return $user->hasPermissionTo('edit devices');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Device $device): bool
    {
        // Only warehouse manager can force delete
        return $user->hasRole('Warehouse Manager');
    }
}
