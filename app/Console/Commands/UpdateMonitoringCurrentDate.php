<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Monitoring;
use Carbon\Carbon;

class UpdateMonitoringCurrentDate extends Command
{
    protected $signature = 'update:monitoring-current-date';
    protected $description = 'Update current_date column in monitorings table';

    public function handle()
    {
        $currentDate = Carbon::now();
        
        try {
            $updated = Monitoring::query()->update(['current_date' => $currentDate]);
            $this->info("Successfully updated {$updated} records with current date: {$currentDate}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Error updating current date: " . $e->getMessage());
            return 1;
        }
    }
}
