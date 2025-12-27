<?php

namespace App\Console\Commands;

use App\Models\AllocationPoint;
use App\Services\PermissionCheckService;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class SyncAllocationPointPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync-allocation-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure all allocation point permissions exist in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing allocation point permissions...');

        try {
            $allocationPoints = AllocationPoint::all();
            $createdCount = 0;
            $skippedCount = 0;

            foreach ($allocationPoints as $point) {
                $permissions = PermissionCheckService::generateAllocationPointPermissions($point);
                
                foreach ($permissions as $permission) {
                    try {
                        $created = Permission::firstOrCreate(
                            ['name' => $permission, 'guard_name' => 'web']
                        );
                        
                        if ($created->wasRecentlyCreated) {
                            $this->line("  ✓ Created: {$permission}");
                            $createdCount++;
                        } else {
                            $skippedCount++;
                        }
                    } catch (\Exception $e) {
                        $this->error("  ✗ Failed to create permission '{$permission}': {$e->getMessage()}");
                    }
                }
            }

            $this->info("\nSummary:");
            $this->line("  Created: {$createdCount}");
            $this->line("  Already exists: {$skippedCount}");
            $this->info("\n✓ Allocation point permissions synced successfully!");

        } catch (\Exception $e) {
            $this->error("Error syncing permissions: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
