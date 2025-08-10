<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FinanceOfficerSimpleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Check if the Finance Officer role already exists
            $roleExists = DB::table('roles')->where('name', 'Finance Officer')->exists();
            
            if (!$roleExists) {
                // Insert the role directly with SQL
                DB::statement("
                    INSERT INTO roles (name, guard_name, created_at, updated_at) 
                    VALUES ('Finance Officer', 'web', NOW(), NOW())
                ");
                
                $this->command->info('Finance Officer role created successfully');
            } else {
                $this->command->info('Finance Officer role already exists');
            }
            
            // Get the role ID
            $role = DB::table('roles')->where('name', 'Finance Officer')->first();
            
            if (!$role) {
                $this->command->error('Failed to retrieve Finance Officer role');
                return;
            }
            
            // Check if the Finance Officer user already exists
            $userExists = DB::table('users')->where('email', 'finance@example.com')->exists();
            
            if (!$userExists) {
                // Insert the user directly with SQL
                DB::statement("
                    INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at) 
                    VALUES ('Finance Officer', 'finance@example.com', '" . Hash::make('password') . "', NOW(), NOW(), NOW())
                ");
                
                $this->command->info('Finance Officer user created successfully');
            } else {
                $this->command->info('Finance Officer user already exists');
            }
            
            // Get the user ID
            $user = DB::table('users')->where('email', 'finance@example.com')->first();
            
            if (!$user) {
                $this->command->error('Failed to retrieve Finance Officer user');
                return;
            }
            
            // Check if the user already has the role
            $hasRole = DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->where('model_id', $user->id)
                ->where('model_type', 'App\\Models\\User')
                ->exists();
                
            if (!$hasRole) {
                // Assign role to user directly with SQL
                DB::statement("
                    INSERT INTO model_has_roles (role_id, model_id, model_type) 
                    VALUES ({$role->id}, {$user->id}, 'App\\\\Models\\\\User')
                ");
                
                $this->command->info('Finance Officer role assigned to user successfully');
            } else {
                $this->command->info('Finance Officer user already has the role');
            }
            
            // Create permissions if they don't exist
            $permissions = [
                'view_device_retrievals',
                'view_overstay_devices',
                'process_finance_approvals',
                'view_dashboard',
                'login_to_admin'
            ];
            
            foreach ($permissions as $permission) {
                $permExists = DB::table('permissions')->where('name', $permission)->exists();
                
                if (!$permExists) {
                    DB::statement("
                        INSERT INTO permissions (name, guard_name, created_at, updated_at) 
                        VALUES ('$permission', 'web', NOW(), NOW())
                    ");
                    
                    $this->command->info("Permission '$permission' created successfully");
                }
                
                // Get the permission ID
                $perm = DB::table('permissions')->where('name', $permission)->first();
                
                if (!$perm) {
                    $this->command->error("Failed to retrieve permission '$permission'");
                    continue;
                }
                
                // Assign permission to role if not already assigned
                $hasPermission = DB::table('role_has_permissions')
                    ->where('permission_id', $perm->id)
                    ->where('role_id', $role->id)
                    ->exists();
                    
                if (!$hasPermission) {
                    DB::statement("
                        INSERT INTO role_has_permissions (permission_id, role_id) 
                        VALUES ({$perm->id}, {$role->id})
                    ");
                    
                    $this->command->info("Permission '$permission' assigned to Finance Officer role");
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error in FinanceOfficerSimpleSeeder: ' . $e->getMessage());
            $this->command->error('Error: ' . $e->getMessage());
        }
    }
}