<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AllocationPointPermissionService;
use App\Models\AllocationPoint;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $user = $this->record;

        try {
            \DB::beginTransaction();

            // Sync roles first - using Role models directly
            if (isset($this->data['roles'])) {
                // Get the actual Role models by their IDs
                $roles = \Spatie\Permission\Models\Role::whereIn('id', $this->data['roles'])->get();

                // Sync using the Role models
                $user->roles()->sync($roles->pluck('id'));
            }

            // Then sync permissions
            if (isset($this->data['permissions'])) {
                $user->syncPermissions($this->data['permissions']);
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error syncing user permissions: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'roles_data' => $this->data['roles'] ?? [],
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}


