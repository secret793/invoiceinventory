<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class FinanceOfficerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default Finance Officer user
        $user = User::firstOrCreate(
            ['email' => 'finance@example.com'],
            [
                'name' => 'Finance Officer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        
        // Assign Finance Officer role
        $role = Role::findByName('Finance Officer');
        $user->assignRole($role);
        
        Log::info('Finance Officer user created successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}