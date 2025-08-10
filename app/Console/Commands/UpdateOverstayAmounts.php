<?php

namespace App\Console\Commands;

use App\Models\DeviceRetrieval;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateOverstayAmounts extends Command
{
    protected $signature = 'app:update-overstay-amounts';
    protected $description = 'Update overstay amounts for all device retrieval records';

    public function handle()
    {
        $this->info('Updating overstay amounts...');
        $count = 0;

        DeviceRetrieval::chunk(100, function ($records) use (&$count) {
            foreach ($records as $record) {
                $oldAmount = $record->overstay_amount;
                $record->updateOverstayAmount();
                
                if ($record->overstay_amount != $oldAmount) {
                    $record->save();
                    $count++;
                    
                    $this->line("Updated record #{$record->id}: {$oldAmount} → {$record->overstay_amount}");
                }
            }
        });

        $this->info("Overstay amounts updated for {$count} records!");
        Log::info("Overstay amounts updated for {$count} records via command");
        
        return Command::SUCCESS;
    }
}