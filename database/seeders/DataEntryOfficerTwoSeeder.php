<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DataEntryOfficerTwoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the role exists
        $deoTwoRole = Role::firstOrCreate(['name' => 'Data Entry Officer two']);

        // Find the Warehouse Manager role
        $wmRole = Role::findByName('Warehouse Manager');

        // Assign permissions from Warehouse Manager to Data Entry Officer two
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
