<?php

namespace App\Policies;

use App\Models\DistributionPoint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DistributionPointPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Distribution Officer') || 
               $user->hasAnyPermission(['view distribution points', 'create distribution points', 'edit distribution points', 'delete distribution points']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DistributionPoint $distributionPoint): bool
    {
        return $user->hasRole('Distribution Officer') || 
               $user->hasPermissionTo('view distribution points');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create distribution points');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DistributionPoint $distributionPoint): bool
    {
        return $user->hasPermissionTo('edit distribution points');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DistributionPoint $distributionPoint): bool
    {
        return $user->hasPermissionTo('delete distribution points');
    }
}
