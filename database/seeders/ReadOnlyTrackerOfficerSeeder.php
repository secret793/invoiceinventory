<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ReadOnlyTrackerOfficerSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'Read Only Tracker Officer',
            'guard_name' => 'web',
        ]);

        $basePermissions = [
            'view devices',
            'view device retrievals',
            'view confirmed affix',
        ];

        foreach ($basePermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $scopedPermissions = Permission::query()
            ->where(function ($query) {
                $query->where('name', 'like', 'view_destination_%')
                    ->orWhere('name', 'like', 'view_allocationpoint_%');
            })
            ->pluck('name')
            ->all();

        $role->syncPermissions(array_values(array_unique(array_merge($basePermissions, $scopedPermissions))));

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
