<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CreateTestUsers extends Command
{
    protected $signature = 'users:create-test';
    protected $description = 'Create test users with different roles';

    public function handle()
    {
        $this->createUser('Warehouse Manager', 'warehouse@example.com', 'Warehouse Manager');
        $this->createUser('Distribution Officer', 'distribution@example.com', 'Distribution Officer');
        $this->createUser('Allocation Officer', 'allocation@example.com', 'Allocation Officer');
        $this->createUser('Data Entry Officer', 'dataentry@example.com', 'Data Entry Officer');
        $this->createUser('Affixing Officer', 'affixing@example.com', 'Affixing Officer');
        $this->createUser('Retrieval Officer', 'retrieval@example.com', 'Retrieval Officer');
        $this->createUser('Monitoring Officer', 'monitoring@example.com', 'Monitoring Officer');

        $this->info('Test users created successfully.');
    }

    private function createUser($name, $email, $roleName)
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
        ]);

        $role = Role::firstOrCreate(['name' => $roleName]);
        $user->assignRole($role);

        $this->info("Created user: $name with role: $roleName");
    }
}
