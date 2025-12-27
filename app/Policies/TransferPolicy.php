<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransferPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['approve transfers', 'manage transfers']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transfer $transfer): bool
    {
        // Users can view transfers they created or are involved with
        if ($transfer->created_by === $user->id || 
            $transfer->source_user_id === $user->id || 
            $transfer->destination_user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('approve transfers');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage transfers');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transfer $transfer): bool
    {
        // Only allow updates if transfer is pending
        if ($transfer->status !== 'pending') {
            return false;
        }

        // Creator can update pending transfers
        if ($transfer->created_by === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('approve transfers');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transfer $transfer): bool
    {
        // Only allow deletion of pending transfers
        if ($transfer->status !== 'pending') {
            return false;
        }

        // Creator can delete pending transfers
        if ($transfer->created_by === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('approve transfers');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transfer $transfer): bool
    {
        return $user->hasPermissionTo('approve transfers');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transfer $transfer): bool
    {
        // Only warehouse manager can force delete
        return $user->hasRole('Warehouse Manager');
    }

    /**
     * Determine whether the user can approve the transfer.
     */
    public function approve(User $user, Transfer $transfer): bool
    {
        // Can't approve own transfers
        if ($transfer->created_by === $user->id) {
            return false;
        }

        return $user->hasPermissionTo('approve transfers');
    }

    /**
     * Determine whether the user can reject the transfer.
     */
    public function reject(User $user, Transfer $transfer): bool
    {
        // Can't reject own transfers
        if ($transfer->created_by === $user->id) {
            return false;
        }

        return $user->hasPermissionTo('approve transfers');
    }
}
