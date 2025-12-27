<?php

namespace App\Console\Commands;

use App\Models\DataEntryAssignment;
use App\Models\AllocationPoint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SetupDataEntryMenuVisibility extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dataentry:setup-menu-visibility 
                            {--dry-run : Show what would be changed without making changes}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup menu visibility for DataEntryAssignment records to prevent duplicates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        $this->info('🔧 Setting up DataEntryAssignment menu visibility...');
        $this->newLine();

        // Check if show_in_menu column exists
        if (!DB::getSchemaBuilder()->hasColumn('data_entry_assignments', 'show_in_menu')) {
            $this->error('❌ The show_in_menu column does not exist. Please run the migration first:');
            $this->line('   php artisan migrate');
            return 1;
        }

        // Get all allocation points with their assignments
        $allocationPoints = AllocationPoint::with(['dataEntryAssignments' => function ($query) {
            $query->orderBy('status')->orderBy('created_at', 'desc');
        }])->get();

        $totalAssignments = DataEntryAssignment::count();
        $changesNeeded = 0;
        $duplicatesFound = 0;

        $this->info("📊 Analysis:");
        $this->line("  - Total allocation points: {$allocationPoints->count()}");
        $this->line("  - Total assignments: {$totalAssignments}");

        $changes = [];

        foreach ($allocationPoints as $allocationPoint) {
            $assignments = $allocationPoint->dataEntryAssignments;
            
            if ($assignments->count() > 1) {
                $duplicatesFound++;
                $this->warn("  📍 {$allocationPoint->name}: {$assignments->count()} assignments (duplicates found)");
                
                // Determine which assignment should show in menu
                $preferredAssignment = $assignments->sortBy([
                    fn ($a) => $a->status === 'RETURNED' ? 1 : 0, // Non-RETURNED first
                    fn ($a) => -$a->created_at->timestamp // Most recent first
                ])->first();

                foreach ($assignments as $assignment) {
                    $shouldShowInMenu = $assignment->id === $preferredAssignment->id;
                    
                    if ($assignment->show_in_menu !== $shouldShowInMenu) {
                        $changesNeeded++;
                        $changes[] = [
                            'id' => $assignment->id,
                            'allocation_point' => $allocationPoint->name,
                            'current_show_in_menu' => $assignment->show_in_menu,
                            'new_show_in_menu' => $shouldShowInMenu,
                            'status' => $assignment->status,
                            'created_at' => $assignment->created_at
                        ];
                        
                        $this->line("    - ID {$assignment->id}: {$assignment->show_in_menu} → {$shouldShowInMenu} ({$assignment->status})");
                    }
                }
            } elseif ($assignments->count() === 1) {
                $assignment = $assignments->first();
                if (!$assignment->show_in_menu) {
                    $changesNeeded++;
                    $changes[] = [
                        'id' => $assignment->id,
                        'allocation_point' => $allocationPoint->name,
                        'current_show_in_menu' => false,
                        'new_show_in_menu' => true,
                        'status' => $assignment->status,
                        'created_at' => $assignment->created_at
                    ];
                    
                    $this->line("  📍 {$allocationPoint->name}: Single assignment needs show_in_menu = true");
                }
            }
        }

        $this->newLine();
        $this->warn("📈 Summary:");
        $this->line("  - Allocation points with duplicates: {$duplicatesFound}");
        $this->line("  - Assignments needing changes: {$changesNeeded}");

        if ($changesNeeded === 0) {
            $this->info('✅ No changes needed! All assignments are properly configured.');
            return 0;
        }

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->info('Run without --dry-run to apply these changes');
            return 0;
        }

        // Confirmation
        if (!$isForced) {
            if (!$this->confirm("Apply {$changesNeeded} changes to fix menu visibility?")) {
                $this->info('❌ Operation cancelled');
                return 0;
            }
        }

        // Apply changes
        $this->info('🔧 Applying changes...');
        
        DB::beginTransaction();
        
        try {
            $updatedCount = 0;
            
            foreach ($changes as $change) {
                DataEntryAssignment::where('id', $change['id'])
                    ->update(['show_in_menu' => $change['new_show_in_menu']]);
                
                $updatedCount++;
                
                Log::info('DataEntryAssignment menu visibility updated', [
                    'assignment_id' => $change['id'],
                    'allocation_point' => $change['allocation_point'],
                    'show_in_menu' => $change['new_show_in_menu'],
                    'status' => $change['status']
                ]);
            }
            
            DB::commit();
            
            $this->newLine();
            $this->info("✅ Setup completed successfully!");
            $this->line("  - Assignments updated: {$updatedCount}");
            $this->line("  - Duplicates resolved: {$duplicatesFound}");
            
            // Verify the setup
            $menuAssignments = DataEntryAssignment::where('show_in_menu', true)->count();
            $uniqueAllocationPoints = DataEntryAssignment::where('show_in_menu', true)
                ->distinct('allocation_point_id')
                ->count();
            
            $this->newLine();
            $this->info("🎉 Verification:");
            $this->line("  - Assignments showing in menu: {$menuAssignments}");
            $this->line("  - Unique allocation points in menu: {$uniqueAllocationPoints}");
            
            if ($menuAssignments === $uniqueAllocationPoints) {
                $this->info("✅ Perfect! One menu item per allocation point achieved.");
            } else {
                $this->warn("⚠️  Warning: Menu items ({$menuAssignments}) don't match unique allocation points ({$uniqueAllocationPoints})");
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error('❌ Error during setup: ' . $e->getMessage());
            Log::error('DataEntryAssignment menu visibility setup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }

        return 0;
    }
}
