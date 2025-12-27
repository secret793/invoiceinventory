<?php

namespace App\Console\Commands;

use App\Models\Monitoring;
use App\Services\OverdueCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateDeviceStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devices:update-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update current_date and calculate overdues for monitoring devices';

    /**
     * The overdue calculation service.
     *
     * @var \App\Services\OverdueCalculationService
     */
    protected $calculationService;

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\OverdueCalculationService  $calculationService
     * @return void
     */
    public function __construct(OverdueCalculationService $calculationService)
    {
        parent::__construct();
        $this->calculationService = $calculationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = now();
        $updated = 0;
        
        Log::info('Starting device status update', ['time' => $now]);
        
        try {
            Monitoring::chunk(100, function ($monitorings) use ($now, &$updated) {
                foreach ($monitorings as $monitoring) {
                    // Update the current date
                    $monitoring->current_date = $now;
                    
                    // Save the monitoring record
                    if ($monitoring->save()) {
                        $updated++;
                    }
                }
            });

            $message = "Successfully updated {$updated} monitoring records with current time: {$now}";
            $this->info($message);
            Log::info($message);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $errorMessage = "Error updating device statuses: " . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage, [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
