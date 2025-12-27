<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class PermissionCheckService
{
    /**
     * Safely check if a user has a permission
     * Creates the permission if it doesn't exist and creates it
     * 
     * @param \App\Models\User $user
     * @param string $permissionName
     * @param bool $createIfMissing
     * @return bool
     */
    public static function userHasPermission($user, string $permissionName, bool $createIfMissing = false): bool
    {
        try {
            return $user->hasPermissionTo($permissionName);
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            // Permission doesn't exist in database
            if ($createIfMissing) {
                try {
                    Permission::firstOrCreate([
                        'name' => $permissionName,
                        'guard_name' => 'web'
                    ]);
                    // Refresh user's permissions cache
                    $user->fresh();
                    return $user->hasPermissionTo($permissionName);
                } catch (\Exception $ex) {
                    \Log::error('Failed to create permission', [
                        'permission' => $permissionName,
                        'error' => $ex->getMessage()
                    ]);
                    return false;
                }
            }
            return false;
        } catch (\Exception $e) {
            \Log::error('Unexpected error checking permission', [
                'permission' => $permissionName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate all expected allocation point permissions
     * 
     * @param \App\Models\AllocationPoint $allocationPoint
     * @return array
     */
    public static function generateAllocationPointPermissions($allocationPoint): array
    {
        $slug = Str::slug($allocationPoint->name);
        return [
            'view_allocationpoint_' . $slug,
            'edit_allocationpoint_' . $slug,
            'delete_allocationpoint_' . $slug,
            'view_data_entry_' . $slug,
            'edit_data_entry_' . $slug,
            'delete_data_entry_' . $slug,
        ];
    }

    /**
     * Ensure all allocation point permissions exist
     * 
     * @param \App\Models\AllocationPoint $allocationPoint
     */
    public static function ensureAllocationPointPermissions($allocationPoint): void
    {
        $permissions = self::generateAllocationPointPermissions($allocationPoint);
        
        foreach ($permissions as $permission) {
            try {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web'
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create allocation point permission', [
                    'allocation_point' => $allocationPoint->name,
                    'permission' => $permission,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get all permissions for an allocation point that exist in the database
     * 
     * @param \App\Models\AllocationPoint $allocationPoint
     * @return array
     */
    public static function getExistingAllocationPointPermissions($allocationPoint): array
    {
        $slug = Str::slug($allocationPoint->name);
        
        return Permission::where('guard_name', 'web')
            ->where(function ($query) use ($slug) {
                $query->where('name', 'like', '%_allocationpoint_' . $slug)
                      ->orWhere('name', 'like', '%_data_entry_' . $slug);
            })
            ->pluck('name')
            ->toArray();
    }
}
