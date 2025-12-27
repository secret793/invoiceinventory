<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class TestFinanceOfficerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Finance Officer role if it doesn't exist
        $role = Role::firstOrCreate(['name' => 'Finance Officer']);

        // Create a test Finance Officer user
        $user = User::create([
            'name' => 'Test Finance Officer',
            'email' => 'finance@example.com',
            'password' => Hash::make('password'),
        ]);

        // Assign the Finance Officer role
        $user->assignRole($role);

        $this->command->info('Test Finance Officer created successfully.');
        $this->command->info('Email: finance@example.com');
        $this->command->info('Password: password');
    }
}