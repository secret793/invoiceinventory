<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CheckUserRoles extends Command
{
    protected $signature = 'users:check-roles {email?}';
    protected $description = 'Check roles and permissions for users';

    public function handle()
    {
        $email = $this->argument('email');
        
        if ($email) {
            $users = User::where('email', $email)->get();
        } else {
            $users = User::all();
        }

        foreach ($users as $user) {
            $this->info("\nUser: {$user->name} ({$user->email})");
            $this->info("Roles: " . $user->getRoleNames()->implode(', '));
            $this->info("Direct Permissions: " . $user->getDirectPermissions()->pluck('name')->implode(', '));
            $this->info("All Permissions: " . $user->getAllPermissions()->pluck('name')->implode(', '));
        }

        $this->info("\nAll Roles:");
        foreach (Role::all() as $role) {
            $this->info("\nRole: {$role->name}");
            $this->info("Permissions: " . $role->permissions->pluck('name')->implode(', '));
        }
    }
}
