<?php

namespace App\Policies;

use App\Models\AllocationPoint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Str;

class AllocationPointPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer'])) { // Correct naming
            return true;
        }

        if ($user->hasRole('Allocation Officer')) {
            return $user->permissions->where('name', 'like', 'view_allocationpoint_%')->isNotEmpty();
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AllocationPoint $allocationPoint): bool
    {
        if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer'])) { // Correct naming
            return true;
        }

        if ($user->hasRole('Allocation Officer')) {
            $permissionName = 'view_allocationpoint_' . Str::slug($allocationPoint->name);
            return $user->hasPermissionTo($permissionName);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AllocationPoint $allocationPoint): bool
    {
        if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer'])) {
            return true;
        }

        return $user->hasPermissionTo($allocationPoint->getPermissionName('edit'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AllocationPoint $allocationPoint): bool
    {
        if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer'])) {
            return true;
        }

        return $user->hasPermissionTo($allocationPoint->getPermissionName('delete'));
    }

    /**
     * Determine whether the user can manage data entry.
     */
    public function manageDataEntry(User $user, AllocationPoint $allocationPoint): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasPermissionTo($allocationPoint->getPermissionName('manage_data_entry'));
    }

    /**
     * Determine whether the user can create data entries.
     */
    public function createDataEntry(User $user, AllocationPoint $allocationPoint): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasPermissionTo($allocationPoint->getPermissionName('create_data_entry'));
    }

    /**
     * Determine whether the user can manage device allocations.
     */
    public function manageAllocation(User $user, AllocationPoint $allocationPoint): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasPermissionTo($allocationPoint->getPermissionName('manage_allocation'));
    }

    /**
     * Determine whether the user can assign devices.
     */
    public function assignDevices(User $user, AllocationPoint $allocationPoint): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasPermissionTo($allocationPoint->getPermissionName('assign_devices'));
    }

    /**
     * Determine whether the user can transfer devices.
     */
    public function transferDevices(User $user, AllocationPoint $allocationPoint): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasPermissionTo($allocationPoint->getPermissionName('transfer_devices'));
    }

    /**
     * Determine whether the user can view inventory.
     */
    public function viewInventory(User $user, AllocationPoint $allocationPoint): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasPermissionTo($allocationPoint->getPermissionName('view_inventory'));
    }
}
