<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use App\Services\AllocationPointPermissionService;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function afterSave(): void
    {
        $user = $this->record;

        \Log::info('Role data before processing', [
            'roles' => $this->data['roles'] ?? [],
            'user_id' => $this->record->id
        ]);

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

            // Sync allocation points
            $allocationPoints = $this->data['allocation_points'] ?? [];
            app(AllocationPointPermissionService::class)->syncUserAllocationPoints(
                $this->record,
                $allocationPoints
            );

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


