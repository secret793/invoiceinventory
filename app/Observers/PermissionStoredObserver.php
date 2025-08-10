<?php

namespace App\Observers;

use App\Models\User;
use App\Models\PermissionStored;

class PermissionStoredObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "saved" event.
     */
    public function saved(User $user)
    {
        // Clear existing stored permissions
       PermissionStored::where('user_id', $user->id)->delete();

        // Store new permissions
        $permissions = $user->permissions->pluck('id')->toArray();
        foreach ($permissions as $permissionId) {
            PermissionStored::create([
                'user_id' => $user->id,
                'permission_id' => $permissionId,
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user)
    {
        PermissionStored::where('user_id', $user->id)->delete();
    }
}
