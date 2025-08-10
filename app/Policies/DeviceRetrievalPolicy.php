<?php

namespace App\Policies;

use App\Models\DeviceRetrieval;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class DeviceRetrievalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
            return true;
        }

        if ($user->hasRole('Retrieval Officer')) {
            return $user->permissions->where('name', 'like', 'view_destination_%')->isNotEmpty();
        }

        if ($user->hasRole('Affixing Officer')) {
            return $user->permissions->where('name', 'like', 'view_destination_%')->isNotEmpty();
        }

        if ($user->hasRole('Finance Officer')) {
            return $user->hasPermissionTo('view_overstay_devices');
        }

        return false;
    }

    public function view(User $user, DeviceRetrieval $deviceRetrieval): bool
    {
        if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
            return true;
        }

        if ($user->hasRole('Retrieval Officer')) {
            // Check if user has permission for the destination
            $hasDestinationPermission = $user->hasPermissionTo('view_destination_' . Str::slug($deviceRetrieval->destination->name));

            // Check if user has permission for the allocation point (if it exists)
            $hasAllocationPointPermission = false;
            if ($deviceRetrieval->allocationPoint) {
                $hasAllocationPointPermission = $user->hasPermissionTo('view_allocationpoint_' . Str::slug($deviceRetrieval->allocationPoint->name));
            }

            // Allow access if user has permission for either the destination or allocation point
            return $hasDestinationPermission || $hasAllocationPointPermission;
        }

        if ($user->hasRole('Affixing Officer')) {
            // Check if user has permission for the destination
            $hasDestinationPermission = $user->hasPermissionTo('view_destination_' . Str::slug($deviceRetrieval->destination->name));

            // Check if user has permission for the allocation point (if it exists)
            $hasAllocationPointPermission = false;
            if ($deviceRetrieval->allocationPoint) {
                $hasAllocationPointPermission = $user->hasPermissionTo('view_allocationpoint_' . Str::slug($deviceRetrieval->allocationPoint->name));
            }

            // Allow access if user has permission for either the destination or allocation point
            return $hasDestinationPermission || $hasAllocationPointPermission;
        }

        if ($user->hasRole('Finance Officer')) {
            return $deviceRetrieval->overstay_days >= 2 && $user->hasPermissionTo('view_overstay_devices');
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
            return true;
        }

        if ($user->hasRole('Retrieval Officer')) {
            return $user->permissions->where('name', 'like', 'manage_devices_%')->isNotEmpty();
        }

        if ($user->hasRole('Affixing Officer')) {
            return $user->permissions->where('name', 'like', 'manage_devices_%')->isNotEmpty();
        }

        return false;
    }

    public function update(User $user, DeviceRetrieval $deviceRetrieval): bool
    {
        if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
            return true;
        }

        if ($user->hasRole('Retrieval Officer')) {
            return $user->hasPermissionTo('manage_devices_' . Str::slug($deviceRetrieval->destination->name));
        }

        if ($user->hasRole('Affixing Officer')) {
            return $user->hasPermissionTo('manage_devices_' . Str::slug($deviceRetrieval->destination->name));
        }

        // Finance Officer can only approve finance-related fields
        if ($user->hasRole('Finance Officer')) {
            return $deviceRetrieval->overstay_days >= 2 && $user->hasPermissionTo('process_finance_approvals');
        }

        return false;
    }
}
