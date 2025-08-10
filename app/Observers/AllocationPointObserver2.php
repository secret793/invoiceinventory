<?php

namespace App\Observers;

use App\Models\AllocationPoint;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class AllocationPointObserver2
{
    /**
     * Create permissions for an allocation point
     */
    private function createPermissions(string $name): void
    {
        try {
            $slug = Str::slug($name);
            
            $permissions = [
                'view_allocationpoint_' . $slug,
                'edit_allocationpoint_' . $slug,
                'delete_allocationpoint_' . $slug,
                'manage_data_entry_' . $slug,
                'create_data_entry_' . $slug,
                'edit_data_entry_' . $slug,
                'view_data_entry_' . $slug,
                'manage_allocation_' . $slug,
                'assign_devices_' . $slug,
                'transfer_devices_' . $slug,
                'view_inventory_' . $slug
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
            
            Log::info("Created permissions for allocation point: $name");
        } catch (\Exception $e) {
            Log::error("Failed to create permissions for allocation point: $name", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update permissions for an allocation point
     */
    private function updatePermissions(string $oldName, string $newName): void
    {
        $permissionPrefixes = [
            'view_allocationpoint_',
            'edit_allocationpoint_',
            'delete_allocationpoint_',
            'manage_data_entry_',
            'create_data_entry_',
            'edit_data_entry_',
            'view_data_entry_',
            'manage_allocation_',
            'assign_devices_',
            'transfer_devices_',
            'view_inventory_'
        ];

        try {
            foreach ($permissionPrefixes as $prefix) {
                $oldPermission = $prefix . Str::slug($oldName);
                $newPermission = $prefix . Str::slug($newName);

                if ($permission = Permission::where('name', $oldPermission)->first()) {
                    $permission->update(['name' => $newPermission]);
                }
            }
            Log::info("Updated permissions from {$oldName} to {$newName}");
        } catch (\Exception $e) {
            Log::error("Failed to update permissions for allocation point", [
                'old_name' => $oldName,
                'new_name' => $newName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete permissions for an allocation point
     */
    private function deletePermissions(string $name): void
    {
        $permissionPrefixes = [
            'view_allocationpoint_',
            'edit_allocationpoint_',
            'delete_allocationpoint_',
            'manage_data_entry_',
            'create_data_entry_',
            'edit_data_entry_',
            'view_data_entry_',
            'manage_allocation_',
            'assign_devices_',
            'transfer_devices_',
            'view_inventory_'
        ];

        try {
            $permissions = array_map(function ($prefix) use ($name) {
                return $prefix . Str::slug($name);
            }, $permissionPrefixes);

            Permission::whereIn('name', $permissions)->delete();
            Log::info("Deleted permissions for allocation point: $name");
        } catch (\Exception $e) {
            Log::error("Failed to delete permissions for allocation point: $name", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the AllocationPoint "created" event.
     */
    public function created(AllocationPoint $allocationPoint): void
    {
        $this->createPermissions($allocationPoint->name);
    }

    /**
     * Handle the AllocationPoint "updated" event.
     */
    public function updated(AllocationPoint $allocationPoint): void
    {
        if ($allocationPoint->isDirty('name')) {
            $this->updatePermissions(
                $allocationPoint->getOriginal('name'),
                $allocationPoint->name
            );
        }
    }

    /**
     * Handle the AllocationPoint "deleted" event.
     */
    public function deleted(AllocationPoint $allocationPoint): void
    {
        $this->deletePermissions($allocationPoint->name);
    }
}
