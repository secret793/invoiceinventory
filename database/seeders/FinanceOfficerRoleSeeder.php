<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class FinanceOfficerRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Finance Officer role
        $financeOfficer = Role::firstOrCreate(['name' => 'Finance Officer']);
        
        // Create permissions specific to Finance Officer
        $permissions = [
            // Device retrieval specific permissions
            'view_device_retrievals',
            'view_overstay_devices',
            'process_finance_approvals',
            
            // Basic permissions
            'view_dashboard',
            'login_to_admin',
        ];
        
        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        // Assign permissions to Finance Officer role
        $financeOfficer->syncPermissions($permissions);
        
        Log::info('Finance Officer role and permissions created successfully');
    }
}