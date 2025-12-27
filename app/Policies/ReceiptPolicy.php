<?php

namespace App\Policies;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReceiptPolicy
{
    /**
     * Determine whether the user can view any receipts.
     */
    public function viewAny(User $user): Response|bool
    {
        return $user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer', 'Finance Officer']);
    }

    /**
     * Determine whether the user can view a specific receipt.
     */
    public function view(User $user, Receipt $receipt): Response|bool
    {
        return $user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer', 'Finance Officer']);
    }

    /**
     * Determine whether the user can create receipts.
     */
    public function create(User $user): Response|bool
    {
        return $user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    /**
     * Determine whether the user can update a receipt.
     */
    public function update(User $user, Receipt $receipt): Response|bool
    {
        return $user->hasRole(['Super Admin', 'Warehouse Manager']);
    }

    /**
     * Determine whether the user can delete a receipt.
     */
    public function delete(User $user, Receipt $receipt): Response|bool
    {
        return $user->hasRole(['Super Admin']);
    }

    /**
     * Determine whether the user can restore a receipt.
     */
    public function restore(User $user, Receipt $receipt): Response|bool
    {
        return $user->hasRole(['Super Admin']);
    }

    /**
     * Determine whether the user can permanently delete a receipt.
     */
    public function forceDelete(User $user, Receipt $receipt): Response|bool
    {
        return $user->hasRole(['Super Admin']);
    }
}
