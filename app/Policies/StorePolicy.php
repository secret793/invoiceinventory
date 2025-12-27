<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StorePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Warehouse Manager', 'Retrieval Officer']) ||
               $user->hasAnyPermission(['view devices', 'create devices', 'manage distribution points']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Store $store): bool
    {
        return $user->hasAnyPermission(['view devices', 'manage distribution points']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create devices');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Store $store): bool
    {
        if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Retrieval Officer'])) {
            return true;
        }

        if ($store->status === 'in_transfer') {
            return $user->hasPermissionTo('manage transfers');
        }

        return $user->hasPermissionTo('edit devices');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Store $store): bool
    {
        // Prevent deletion if store is active or in transfer
        if ($store->status === 'active' || $store->status === 'in_transfer') {
            return false;
        }

        return $user->hasPermissionTo('delete devices');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Store $store): bool
    {
        return $user->hasPermissionTo('edit devices');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Store $store): bool
    {
        // Only warehouse manager can force delete
        return $user->hasRole('Warehouse Manager');
    }
}
