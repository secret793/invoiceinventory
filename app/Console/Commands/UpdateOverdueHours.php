<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Monitoring;
use App\Models\DeviceRetrieval;

class UpdateOverdueHours extends Command
{
    protected $signature = 'app:update-overdue-hours';
    protected $description = 'Update overdue hours and overstay days for all monitoring and device retrieval records';

    public function handle()
    {
        $this->info('Updating overdue hours and overstay days...');

        // Update Monitoring records
        Monitoring::chunk(100, function ($records) {
            foreach ($records as $record) {
                $record->updateOverdueHours();
                $record->updateOverstayDays();
            }
        });

        // Update DeviceRetrieval records
        DeviceRetrieval::chunk(100, function ($records) {
            foreach ($records as $record) {
                $record->updateOverdueHours();
                $record->updateOverstayDays();
            }
        });

        $this->info('Overdue hours and overstay days updated successfully!');
    }
}

