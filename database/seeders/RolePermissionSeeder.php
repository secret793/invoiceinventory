<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Create roles using firstOrCreate to avoid duplicates
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $warehouseManager = Role::firstOrCreate(['name' => 'Warehouse Manager']);
        $allocationOfficer = Role::firstOrCreate(['name' => 'Allocation Officer']);
        $dataEntryOfficer = Role::firstOrCreate(['name' => 'Data Entry Officer']);
        $distributionOfficer = Role::firstOrCreate(['name' => 'Distribution Officer']);
        $monitoringOfficer = Role::firstOrCreate(['name' => 'Monitoring Officer']);
        $affixingOfficer = Role::firstOrCreate(['name' => 'Affixing Officer']);
        $deviceRetrievalOfficer = Role::firstOrCreate(['name' => 'Device Retrieval Officer']);

        // Distribution Officer Permissions
        $distributionOfficerPermissions = [
            'view_distribution_points',
            'manage_distribution_points',
            'transfer_devices',
            'accept_returns',
        ];

        // Monitoring Officer Permissions
        $monitoringOfficerPermissions = [
            'view_monitoring',
            'manage_monitoring',
            'view_devices',
            'manage_devices',
            'view_transfers',
            'manage_transfers',
            'view_reports',
            'manage_reports',
        ];

        // Affixing Officer Permissions
        $affixingOfficerPermissions = [
            'view_dispatches',
            'manage_dispatches',
            'view_devices',
            'manage_devices',
            'confirm_dispatches',
            'view_transfers',
            'manage_transfers',
        ];

        // Device Retrieval Officer Permissions
        $deviceRetrievalOfficerPermissions = [
            'view_device_retrievals',
            'manage_device_retrievals',
            'view_devices',
            'manage_devices',
            'view_transfers',
            'manage_transfers',
            'process_retrievals',
            'view_destination_accra',
            'view_destination_soma',
            'view_destination_farafeni'
        ];

        // Create permissions using firstOrCreate to avoid duplicates
        foreach ($distributionOfficerPermissions as $permission) {
            Permission::firstOrCreate(['name' => strtolower($permission)]);
        }

        foreach ($monitoringOfficerPermissions as $permission) {
            Permission::firstOrCreate(['name' => strtolower($permission)]);
        }

        foreach ($affixingOfficerPermissions as $permission) {
            Permission::firstOrCreate(['name' => strtolower($permission)]);
        }

        foreach ($deviceRetrievalOfficerPermissions as $permission) {
            Permission::firstOrCreate(['name' => strtolower($permission)]);
        }

        // Super Admin permissions
        $superAdminPermissions = [
            'view_device_retrievals',
            'manage_device_retrievals',
            'process_retrievals',
        ];

        // Warehouse Manager permissions
        $warehouseManagerPermissions = [
            'view_device_retrievals',
            'manage_device_retrievals',
            'process_retrievals',
        ];

        // Data Entry Officer permissions
        $dataEntryOfficerPermissions = [
            'view_device_retrievals',
            'manage_device_retrievals',
            'process_retrievals',
            'view_devices',
            'edit_devices',
            'manage_devices',
            'view_transfers',
            'manage_transfers',
        ];

        // Create these permissions too
        foreach (array_merge($superAdminPermissions, $warehouseManagerPermissions, $dataEntryOfficerPermissions) as $permission) {
            Permission::firstOrCreate(['name' => strtolower($permission)]);
        }

        // Assign permissions to roles
        $distributionOfficer->givePermissionTo($distributionOfficerPermissions);
        $monitoringOfficer->givePermissionTo($monitoringOfficerPermissions);
        $affixingOfficer->givePermissionTo($affixingOfficerPermissions);
        $deviceRetrievalOfficer->givePermissionTo($deviceRetrievalOfficerPermissions);
        $superAdmin->givePermissionTo($superAdminPermissions);
        $warehouseManager->givePermissionTo($warehouseManagerPermissions);
        $dataEntryOfficer->givePermissionTo($dataEntryOfficerPermissions);
    }
}