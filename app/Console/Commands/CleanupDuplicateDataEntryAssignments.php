<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DataEntryAssignment;
use Illuminate\Support\Facades\Log;

class CleanupDuplicateDataEntryAssignments extends Command
{
    protected $signature = 'dataentry:cleanup-duplicates';
    protected $description = 'Remove duplicate DataEntryAssignment records for the same allocation_point_id, keeping only the first.';

    public function handle()
    {
        $this->info('Starting duplicate cleanup for DataEntryAssignment...');
        $duplicates = DataEntryAssignment::select('allocation_point_id')
            ->groupBy('allocation_point_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('allocation_point_id');

        $totalDeleted = 0;
        foreach ($duplicates as $allocationPointId) {
            $assignments = DataEntryAssignment::where('allocation_point_id', $allocationPointId)
                ->orderBy('id')
                ->get();
            $toDelete = $assignments->slice(1); // Keep the first, delete the rest
            $count = $toDelete->count();
            if ($count > 0) {
                $ids = $toDelete->pluck('id')->toArray();
                DataEntryAssignment::whereIn('id', $ids)->delete();
                $this->info("Deleted $count duplicate(s) for allocation_point_id $allocationPointId");
                Log::info("Deleted $count duplicate DataEntryAssignment(s) for allocation_point_id $allocationPointId", ['ids' => $ids]);
                $totalDeleted += $count;
            }
        }
        $this->info("Cleanup complete. Total duplicates deleted: $totalDeleted");
        return 0;
    }
} 