<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AllocationPointPermissionService;

class GenerateAllocationPointPermissions extends Command
{
    protected $signature = 'permissions:generate-allocation-points';
    protected $description = 'Generate permissions for all allocation points';

    public function handle(AllocationPointPermissionService $service)
    {
        $permissions = $service->generatePermissions();
        $this->info('Generated ' . count($permissions) . ' allocation point permissions.');
        
        return Command::SUCCESS;
    }
}
