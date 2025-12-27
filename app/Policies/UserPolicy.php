<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $hasRole = $user->hasRole('Super Admin');
        Log::info('UserPolicy::viewAny check', [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_super_admin_role' => $hasRole,
            'roles' => $user->getRoleNames(),
        ]);
        return $hasRole;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        $hasRole = $user->hasRole('Super Admin');
        Log::info('UserPolicy::view check', [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_super_admin_role' => $hasRole,
            'roles' => $user->getRoleNames(),
        ]);
        return $hasRole;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $hasRole = $user->hasRole('Super Admin');
        Log::info('UserPolicy::create check', [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_super_admin_role' => $hasRole,
            'roles' => $user->getRoleNames(),
        ]);
        return $hasRole;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        $hasRole = $user->hasRole('Super Admin');
        Log::info('UserPolicy::update check', [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_super_admin_role' => $hasRole,
            'roles' => $user->getRoleNames(),
        ]);
        return $hasRole;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if (!$user->hasRole('Super Admin')) {
            Log::info('UserPolicy::delete check failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'has_super_admin_role' => false,
                'roles' => $user->getRoleNames(),
            ]);
            return false;
        }

        // Prevent deleting your own account
        $canDelete = $user->id !== $model->id;
        Log::info('UserPolicy::delete check', [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_super_admin_role' => true,
            'roles' => $user->getRoleNames(),
            'can_delete' => $canDelete,
        ]);
        return $canDelete;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        $hasRole = $user->hasRole('Super Admin');
        Log::info('UserPolicy::restore check', [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_super_admin_role' => $hasRole,
            'roles' => $user->getRoleNames(),
        ]);
        return $hasRole;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        if (!$user->hasRole('Super Admin')) {
            Log::info('UserPolicy::forceDelete check failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'has_super_admin_role' => false,
                'roles' => $user->getRoleNames(),
            ]);
            return false;
        }

        // Prevent force deleting your own account
        $canForceDelete = $user->id !== $model->id;
        Log::info('UserPolicy::forceDelete check', [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_super_admin_role' => true,
            'roles' => $user->getRoleNames(),
            'can_force_delete' => $canForceDelete,
        ]);
        return $canForceDelete;
    }

    /**
     * Determine whether the user can impersonate another user.
     */
    public function impersonate(User $user, User $model): bool
    {
        Log::info('UserPolicy::impersonate check', [
            'user_id' => $user->id,
            'email' => $user->email,
            'can_impersonate' => false,
            'roles' => $user->getRoleNames(),
        ]);
        return false; // Disable impersonation for security
    }
}
