<?php

namespace App\Console\Commands;

use App\Models\AllocationPoint;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class VerifyAllocationPointPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:verify-allocation-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify allocation point permissions and user assignments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verifying allocation point permissions...');
        $this->line('');

        $allocationPoints = AllocationPoint::all();
        $totalPermissions = 0;
        $missingPermissions = 0;

        foreach ($allocationPoints as $point) {
            $this->info("Allocation Point: {$point->name}");
            $slug = Str::slug($point->name);
            
            $expectedPermissions = [
                'view_allocationpoint_' . $slug,
                'edit_allocationpoint_' . $slug,
                'delete_allocationpoint_' . $slug,
                'view_data_entry_' . $slug,
                'edit_data_entry_' . $slug,
                'delete_data_entry_' . $slug,
            ];

            foreach ($expectedPermissions as $permissionName) {
                $totalPermissions++;
                $exists = Permission::where('name', $permissionName)->where('guard_name', 'web')->exists();
                
                if ($exists) {
                    $this->line("  ✓ {$permissionName}");
                } else {
                    $this->error("  ✗ MISSING: {$permissionName}");
                    $missingPermissions++;
                }
            }

            // Check users assigned to this allocation point
            $assignedUsers = $point->users()->count();
            $this->line("  Users assigned: {$assignedUsers}");
            
            if ($assignedUsers > 0) {
                foreach ($point->users as $user) {
                    $userPermissions = $user->permissions()
                        ->where('name', 'like', '%_allocationpoint_' . $slug)
                        ->orWhere('name', 'like', '%_data_entry_' . $slug)
                        ->count();
                    
                    $status = $userPermissions > 0 ? '✓' : '✗';
                    $this->line("    {$status} {$user->name} ({$user->email}) - {$userPermissions} permissions");
                }
            }

            $this->line('');
        }

        $this->info('Summary:');
        $this->line("  Total permissions checked: {$totalPermissions}");
        $this->line("  Missing permissions: {$missingPermissions}");

        if ($missingPermissions > 0) {
            $this->warn("\n⚠ Some permissions are missing! Run: php artisan permissions:sync-allocation-points");
        } else {
            $this->info("\n✓ All permissions are in place!");
        }

        return 0;
    }
}
