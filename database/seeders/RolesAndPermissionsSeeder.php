<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Role & Permission Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',

            // Distribution Management
            'manage distribution points',
            'view distribution points',
            'create distribution points',
            'edit distribution points',
            'delete distribution points',

            // Allocation Management
            'manage allocation points',
            'view allocation points',
            'create allocation points',
            'edit allocation points',
            'delete allocation points',

            // Device Management
            'approve transfers',
            'verify devices',
            'distribute devices',
            'assign devices',
            'enter data',
            'affix devices',
            'retrieve devices',
            'monitor devices',
            'view devices',
            'create devices',
            'edit devices',
            'delete devices',

            // Destination Management
            'create destinations',
            'edit destinations',
            'delete destinations',
            'view destinations',

            // Customs Regime Management
            'create customs regimes',
            'edit customs regimes',
            'delete customs regimes',
            'view customs regimes',

            // Route Management
            'create routes',
            'edit routes',
            'delete routes',
            'view routes',

            // Other Items Management
            'allocate other items',
            'view other items',
            'edit other items',
            'delete other items',

            // Confirmed Affix Management
            'view confirmed affix',
            'create confirmed affix',
            'edit confirmed affix',
            'delete confirmed affix',

            // Monitoring Management
            'view monitoring',
            'create monitoring',
            'edit monitoring',
            'delete monitoring',

            // Notification Management
            'view notifications',
            'create notifications',
            'edit notifications',
            'delete notifications',

            // Report Management
            'view reports',
            'create reports',
            'edit reports',
            'delete reports',

            // Device Retrieval Management
            'view device retrievals',
            'create device retrievals',
            'edit device retrievals',
            'delete device retrievals',

            // Assignment Management
            'view assignments',
            'create assignments',
            'edit assignments',
            'delete assignments',

            // Data Entry Officer
            'view data entry',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $roles = [
            'Warehouse Manager' => [
                // Configuration Permissions
                'create distribution points',
                'create allocation points',
                'create destinations',
                'create customs regimes',
                'create routes',
                'view routes',
                'approve transfers',
                'allocate other items',
                'view distribution points',
                'view allocation points',
                'view destinations',
                'view customs regimes',
                'view other items',
                'edit other items',
                'delete other items',
                // Additional inventory management permissions
                'view devices',
                'create devices',
                'edit devices',
                'delete devices',
                'verify devices',
                'distribute devices',
            ],
            'Allocation Officer' => [
                'view allocation points',  // Base permission to access allocation points module
                'view devices',
                'edit devices',
                'distribute devices',
                'approve transfers',
            ],
            'Data Entry Officer' => [
                'view devices',
                'edit devices',
                'view allocation points',
                'view data entry',
                'edit data entry',
                'enter data',
                'view distribution points',
                'view destinations',
                'view customs regimes',
                'view other items',
            ],
        ];

        // Create roles and assign permissions
        // Super Admin
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);

        // Get all permissions
        $allPermissions = Permission::all();

        // Assign all permissions to Super Admin
        $superAdmin->syncPermissions($allPermissions);

        // Create default Super Admin user if it doesn't exist
        $superAdminUser = \App\Models\User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );

        $superAdminUser->assignRole($superAdmin);

        // Warehouse Manager
        $warehouseManager = Role::firstOrCreate(['name' => 'Warehouse Manager']);
        $warehouseManager->syncPermissions($roles['Warehouse Manager']);

        // Warehouse Staff
        $warehouseStaff = Role::firstOrCreate(['name' => 'Warehouse Staff']);
        $warehouseStaff->syncPermissions([
            'view devices',
            'view distribution points',
            'view allocation points',
            'view destinations',
            'view customs regimes',
            'view other items',
        ]);

        // Distribution Officer role
        $distributionOfficer = Role::firstOrCreate(['name' => 'Distribution Officer']);
        $distributionOfficer->givePermissionTo([
            'view distribution points',
            'distribute devices',
            'view devices',
            'edit devices', // For updating device status
        ]);

        // Allocation Officer role
        $allocationOfficer = Role::firstOrCreate(['name' => 'Allocation Officer']);
        $allocationOfficer->syncPermissions($roles['Allocation Officer']);

        // Data Entry Officer role
        $dataEntryOfficer = Role::firstOrCreate(['name' => 'Data Entry Officer']);
        $dataEntryOfficer->syncPermissions($roles['Data Entry Officer']);

        // Retrieval Officer role
        $retrievalOfficer = Role::firstOrCreate(['name' => 'Retrieval Officer']);
        $retrievalOfficer->syncPermissions([
            // Configuration Permissions
            'create distribution points',
            'create allocation points',
            'create destinations',
            'create customs regimes',
            'create routes',
            'view routes',
            'approve transfers',
            'allocate other items',
            'view distribution points',
            'view allocation points',
            'view destinations',
            'view customs regimes',
            'view other items',
            'edit other items',
            'delete other items',
            // Device management permissions
            'view devices',
            'create devices',
            'edit devices',
            'delete devices',
            'verify devices',
            'distribute devices',
            // Device retrieval specific permissions
            'view device retrievals',
            'manage device retrievals',
            'process retrievals',
        ]);

        // Create roles
        $additionalRoles = [
            'Warehouse Manager',
            'Data Entry Officer two',
            // other roles...
        ];

        foreach ($additionalRoles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Assign permissions to Data Entry Officer two
        $wmRole = Role::findByName('Warehouse Manager');
        $deoTwoRole = Role::findByName('Data Entry Officer two');

        $permissions = $wmRole->permissions;

        foreach ($permissions as $permission) {
            $deoTwoRole->givePermissionTo($permission);
        }

        // Remove access to users, roles, and permissions under configuration
        $deoTwoRole->revokePermissionTo('view users');
        $deoTwoRole->revokePermissionTo('view roles');
        $deoTwoRole->revokePermissionTo('view permissions');
    }
}
