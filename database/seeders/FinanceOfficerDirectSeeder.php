<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FinanceOfficerDirectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // First, let's examine the roles table structure
            $roleColumns = DB::select('SHOW COLUMNS FROM roles');
            $roleColumnNames = array_column($roleColumns, 'Field');
            
            $this->command->info('Roles table columns: ' . implode(', ', $roleColumnNames));
            
            // Check if id is auto_increment
            $idColumn = array_filter($roleColumns, function($col) {
                return $col->Field === 'id';
            });
            
            if (!empty($idColumn)) {
                $idColumn = reset($idColumn);
                $this->command->info('ID column type: ' . $idColumn->Type . ', Extra: ' . $idColumn->Extra);
                
                // If id is not auto_increment, we need to provide a value
                $needsIdValue = strpos($idColumn->Extra, 'auto_increment') === false;
            } else {
                $needsIdValue = false;
            }
            
            // Check if the Finance Officer role already exists
            $roleExists = DB::table('roles')->where('name', 'Finance Officer')->exists();
            
            if (!$roleExists) {
                // Prepare the role data
                $roleData = [
                    'name' => 'Finance Officer',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                // If we need to provide an ID value
                if ($needsIdValue) {
                    // Get the max ID and increment by 1
                    $maxId = DB::table('roles')->max('id');
                    $roleData['id'] = $maxId ? $maxId + 1 : 1;
                }
                
                // Insert the role
                DB::table('roles')->insert($roleData);
                $this->command->info('Finance Officer role created successfully');
            } else {
                $this->command->info('Finance Officer role already exists');
            }
            
            // Get the role
            $role = DB::table('roles')->where('name', 'Finance Officer')->first();
            
            if (!$role) {
                $this->command->error('Failed to retrieve Finance Officer role');
                return;
            }
            
            // Now examine the users table structure
            $userColumns = DB::select('SHOW COLUMNS FROM users');
            $userColumnNames = array_column($userColumns, 'Field');
            
            $this->command->info('Users table columns: ' . implode(', ', $userColumnNames));
            
            // Check if id is auto_increment
            $idColumn = array_filter($userColumns, function($col) {
                return $col->Field === 'id';
            });
            
            if (!empty($idColumn)) {
                $idColumn = reset($idColumn);
                $this->command->info('ID column type: ' . $idColumn->Type . ', Extra: ' . $idColumn->Extra);
                
                // If id is not auto_increment, we need to provide a value
                $needsIdValue = strpos($idColumn->Extra, 'auto_increment') === false;
            } else {
                $needsIdValue = false;
            }
            
            // Check if the Finance Officer user already exists
            $userExists = DB::table('users')->where('email', 'finance@example.com')->exists();
            
            if (!$userExists) {
                // Prepare the user data
                $userData = [
                    'name' => 'Finance Officer',
                    'email' => 'finance@example.com',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                // If we need to provide an ID value
                if ($needsIdValue) {
                    // Get the max ID and increment by 1
                    $maxId = DB::table('users')->max('id');
                    $userData['id'] = $maxId ? $maxId + 1 : 1;
                }
                
                // Insert the user
                DB::table('users')->insert($userData);
                $this->command->info('Finance Officer user created successfully');
            } else {
                $this->command->info('Finance Officer user already exists');
            }
            
            // Get the user
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
                // Assign role to user
                DB::table('model_has_roles')->insert([
                    'role_id' => $role->id,
                    'model_id' => $user->id,
                    'model_type' => 'App\\Models\\User'
                ]);
                
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
            
            // Examine the permissions table structure
            $permColumns = DB::select('SHOW COLUMNS FROM permissions');
            $permColumnNames = array_column($permColumns, 'Field');
            
            $this->command->info('Permissions table columns: ' . implode(', ', $permColumnNames));
            
            // Check if id is auto_increment
            $idColumn = array_filter($permColumns, function($col) {
                return $col->Field === 'id';
            });
            
            if (!empty($idColumn)) {
                $idColumn = reset($idColumn);
                $this->command->info('ID column type: ' . $idColumn->Type . ', Extra: ' . $idColumn->Extra);
                
                // If id is not auto_increment, we need to provide a value
                $needsIdValue = strpos($idColumn->Extra, 'auto_increment') === false;
            } else {
                $needsIdValue = false;
            }
            
            foreach ($permissions as $permission) {
                $permExists = DB::table('permissions')->where('name', $permission)->exists();
                
                if (!$permExists) {
                    // Prepare the permission data
                    $permData = [
                        'name' => $permission,
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    
                    // If we need to provide an ID value
                    if ($needsIdValue) {
                        // Get the max ID and increment by 1
                        $maxId = DB::table('permissions')->max('id');
                        $permData['id'] = $maxId ? $maxId + 1 : 1;
                    }
                    
                    // Insert the permission
                    DB::table('permissions')->insert($permData);
                    $this->command->info("Permission '$permission' created successfully");
                }
                
                // Get the permission
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
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $perm->id,
                        'role_id' => $role->id
                    ]);
                    
                    $this->command->info("Permission '$permission' assigned to Finance Officer role");
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error in FinanceOfficerDirectSeeder: ' . $e->getMessage());
            $this->command->error('Error: ' . $e->getMessage());
        }
    }
}