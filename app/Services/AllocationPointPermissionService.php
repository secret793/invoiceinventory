<?php

namespace App\Services;

use App\Models\AllocationPoint;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class AllocationPointPermissionService
{
    public function generatePermissions()
    {
        $allocationPoints = AllocationPoint::all();
        $permissions = [];

        foreach ($allocationPoints as $point) {
            $permissionName = 'view_allocationpoint_' . Str::slug($point->name);
            $permissions[] = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        return $permissions;
    }

    public function syncUserAllocationPoints($user, array $allocationPointIds)
    {
        // Sync the pivot table
        $user->allocationPoints()->sync($allocationPointIds);

        // Get the permission names for these allocation points
        $permissions = AllocationPoint::whereIn('id', $allocationPointIds)
            ->get()
            ->map(function ($point) {
                return 'view_allocationpoint_' . Str::slug($point->name);
            })
            ->toArray();

        // Sync the permissions
        $user->syncPermissions($permissions);
    }

    protected function syncPermissionsForType($user, array $pointIds, string $type)
    {
        // Get the permission names for these allocation points
        $permissions = AllocationPoint::whereIn('id', $pointIds)
            ->get()
            ->map(function ($point) use ($type) {
                return "{$type}_allocationpoint_" . Str::slug($point->name);
            })
            ->toArray();

        // Get user's current permissions
        $currentPermissions = $user->permissions->pluck('name')->toArray();

        // Filter out permissions of the current type
        $otherPermissions = collect($currentPermissions)
            ->filter(function ($permission) use ($type) {
                return !str_starts_with($permission, "{$type}_allocationpoint_");
            })
            ->toArray();

        // Merge and sync permissions
        $user->syncPermissions(array_merge($otherPermissions, $permissions));
    }
}
