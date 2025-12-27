<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Receipt;

class TestAllocationPoint extends Command
{
    protected $signature = 'test:allocation-point';
    protected $description = 'Test allocation point loading';

    public function handle()
    {
        $receipt = Receipt::find(36);
        $this->info("Receipt ID: {$receipt->id}");
        $this->info("Allocation Point ID: {$receipt->allocation_point_id}");

        // Load without global scopes
        $receipt->load(['allocationPoint' => function ($q) {
            $q->withoutGlobalScopes();
        }]);

        if ($receipt->allocationPoint) {
            $this->info("✓ Allocation Point Name: {$receipt->allocationPoint->name}");
        } else {
            $this->error("✗ Allocation Point is NULL");
        }
    }
}
