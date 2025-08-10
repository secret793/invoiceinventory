<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AllocationPoint;
use App\Models\DataEntryAssignment;
use Illuminate\Support\Facades\DB;

class SyncDataEntryAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $allocationPoint;

    public function __construct($allocationPoint)
    {
        $this->allocationPoint = $allocationPoint;
    }

    public function handle(): void
    {
        DB::transaction(function () {
            // Ensure DataEntryAssignment exists for this allocation point
            DataEntryAssignment::updateOrCreate(
                ['allocation_point_id' => $this->allocationPoint->id],
                [
                    'title' => $this->allocationPoint->name . ' - Data Entry',
                    'description' => 'Data entry assignment for ' . $this->allocationPoint->name,
                    'status' => 'PENDING',
                    'user_id' => auth()->id() ?? 1
                ]
            );
        });
    }
} 