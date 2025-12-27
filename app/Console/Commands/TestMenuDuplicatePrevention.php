<?php

namespace App\Console\Commands;

use App\Models\DataEntryAssignment;
use App\Models\AllocationPoint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestMenuDuplicatePrevention extends Command
{
    protected $signature = 'test:menu-duplicates';
    protected $description = 'Test the menu duplicate prevention logic';

    public function handle()
    {
        $this->info('🧪 Testing Menu Duplicate Prevention Logic');
        $this->newLine();

        // Test 1: Show current duplicates
        $this->info('📊 Current DataEntryAssignment Records:');
        $duplicates = DB::table('data_entry_assignments')
            ->select('allocation_point_id', DB::raw('COUNT(*) as count'))
            ->groupBy('allocation_point_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            foreach ($duplicates as $duplicate) {
                $allocationPoint = AllocationPoint::find($duplicate->allocation_point_id);
                $allocationPointName = $allocationPoint ? $allocationPoint->name : "Unknown/Orphaned";

                $assignments = DataEntryAssignment::where('allocation_point_id', $duplicate->allocation_point_id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $this->warn("  📍 {$allocationPointName} (ID: {$duplicate->allocation_point_id}):");
                foreach ($assignments as $assignment) {
                    $status = $assignment->status ?? 'NULL';
                    $createdAt = $assignment->created_at ? $assignment->created_at->format('Y-m-d H:i:s') : 'NULL';
                    $this->line("    - ID {$assignment->id}: {$status} (created: {$createdAt})");
                }
            }
        } else {
            $this->info('  ✅ No duplicates found');
        }

        $this->newLine();

        // Test 2: Show what forMenuGeneration scope returns
        $this->info('🎯 Records Selected by forMenuGeneration Scope:');
        $menuAssignments = DataEntryAssignment::with('allocationPoint')
            ->forMenuGeneration()
            ->get();

        foreach ($menuAssignments as $assignment) {
            $this->line("  📋 {$assignment->allocationPoint->name}: ID {$assignment->id} ({$assignment->status})");
        }

        $this->newLine();

        // Test 3: Compare before and after
        $this->info('📈 Comparison:');
        $totalAssignments = DataEntryAssignment::count();
        $menuAssignments = DataEntryAssignment::forMenuGeneration()->count();
        $reduction = $totalAssignments - $menuAssignments;

        $this->line("  - Total DataEntryAssignment records: {$totalAssignments}");
        $this->line("  - Records for menu generation: {$menuAssignments}");
        $this->line("  - Duplicates prevented: {$reduction}");

        if ($reduction > 0) {
            $this->info("  ✅ Successfully preventing {$reduction} duplicate menu items!");
        } else {
            $this->warn("  ⚠️  No duplicates prevented - check if duplicates exist");
        }

        $this->newLine();

        // Test 4: Show allocation points with devices
        $this->info('🔍 Allocation Points with Device Counts:');
        $allocationPoints = AllocationPoint::withCount('devices')->get();

        foreach ($allocationPoints as $point) {
            $assignmentCount = DataEntryAssignment::where('allocation_point_id', $point->id)->count();
            $menuAssignmentCount = DataEntryAssignment::where('allocation_point_id', $point->id)
                ->forMenuGeneration()
                ->count();

            $this->line("  📍 {$point->name}:");
            $this->line("    - Devices: {$point->devices_count}");
            $this->line("    - Total assignments: {$assignmentCount}");
            $this->line("    - Menu assignments: {$menuAssignmentCount}");
        }

        // Test 5: Check for orphaned assignments
        $orphanedCount = DataEntryAssignment::whereNotExists(function ($query) {
            $query->select('id')
                ->from('allocation_points')
                ->whereRaw('allocation_points.id = data_entry_assignments.allocation_point_id');
        })->count();

        if ($orphanedCount > 0) {
            $this->newLine();
            $this->warn("⚠️  Found {$orphanedCount} orphaned DataEntryAssignment records (allocation_point_id doesn't exist)");
            $this->line("   Run: php artisan dataentry:cleanup-duplicates --clean-orphans --dry-run");
        }

        return 0;
    }
}
