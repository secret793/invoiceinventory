<?php

namespace App\Console\Commands;

use App\Models\Monitoring;
use App\Models\DeviceRetrieval;
use App\Services\OverdueCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateOverdueCalculations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:update-overdue-calculations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update overdue calculations for all monitoring records';

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
        $this->info('Starting overdue calculations update...');
        
        $maxAttempts = 2; // Maximum number of attempts to process
        $attempt = 1;
        $success = false;
        
        while ($attempt <= $maxAttempts && !$success) {
            try {
                $this->info("\nAttempt $attempt of $maxAttempts...");
                $success = $this->processMonitorings();
                
                if (!$success && $attempt < $maxAttempts) {
                    $this->warn("\nRetrying after a short delay...");
                    sleep(5); // Wait 5 seconds before retry
                }
                
                $attempt++;
            } catch (\Exception $e) {
                $this->error("\nAttempt $attempt failed: " . $e->getMessage());
                if ($attempt >= $maxAttempts) {
                    throw $e; // Re-throw on last attempt
                }
                $attempt++;
            }
        }
        
        return $success ? Command::SUCCESS : Command::FAILURE;
    }
    
    protected function processMonitorings()
    {
        try {
            // Get count first for progress bar
            $total = Monitoring::where('status', 'active')
                ->whereNotNull('affixing_date')
                ->whereDoesntHave('deviceRetrieval', function($query) {
                    $query->where('retrieval_status', 'returned');
                })
                ->where(function($query) {
                    $query->whereHas('deviceRetrieval', function($q) {
                        $q->where('retrieval_status', '!=', 'returned')
                          ->orWhereNull('retrieval_status');
                    })->orDoesntHave('deviceRetrieval');
                })
                ->count();
                
            if ($total === 0) {
                $this->info('No active monitorings with affixing date found.');
                return Command::SUCCESS;
            }
            
            $this->info("Found {$total} monitorings to process...");
            
            $updated = 0;
            $errors = 0;
            $batchSize = 50; // Process in batches to manage memory
            $processed = 0;
            
            $bar = $this->output->createProgressBar($total);
            $bar->start();
            
            // Process in batches
            Monitoring::with(['device', 'deviceRetrieval'])
                ->where('status', 'active')
                ->whereNotNull('affixing_date')
                ->where(function($query) {
                    $query->whereHas('deviceRetrieval', function($q) {
                        $q->where('retrieval_status', '!=', 'returned')
                          ->orWhereNull('retrieval_status');
                    })->orDoesntHave('deviceRetrieval');
                })
                ->chunk($batchSize, function ($monitorings) use (&$updated, &$errors, $bar, &$processed, $total) {
                    foreach ($monitorings as $monitoring) {
                        try {
                            // Get route type
                            $isLongRoute = (bool) $monitoring->long_route_id;
                            
                            // Log route information
                            Log::info("Processing monitoring ID: {$monitoring->id}", [
                                'device_id' => $monitoring->device_id,
                                'is_long_route' => $isLongRoute,
                                'long_route_id' => $monitoring->long_route_id,
                                'route_id' => $monitoring->route_id,
                                'affixing_date' => $monitoring->affixing_date,
                                'current_time' => now()
                            ]);
                            
                            // Calculate overdue hours (starts counting immediately after affixing)
                            $overdueHours = $monitoring->calculateOverdueHours();
                            
                            // Log overdue hours calculation
                            Log::info("Overdue hours calculation", [
                                'monitoring_id' => $monitoring->id,
                                'overdue_hours' => $overdueHours,
                                'is_long_route' => $isLongRoute
                            ]);
                            
                            // Calculate overdue days (applies grace period here)
                            $overstayDays = $this->calculationService->calculateOverdueDays($overdueHours, $isLongRoute);
                            
                            // Log overdue days calculation
                            Log::info("Overstay days calculation", [
                                'monitoring_id' => $monitoring->id,
                                'overdue_hours' => $overdueHours,
                                'overstay_days' => $overstayDays,
                                'is_long_route' => $isLongRoute,
                                'grace_period' => $isLongRoute ? 48 : 24
                            ]);
                            
                            // Calculate overdue amount
                            $overstayAmount = $this->calculationService->calculateOverdueAmount($overstayDays);
                            
                            // Log before update
                            Log::info("Updating monitoring record", [
                                'monitoring_id' => $monitoring->id,
                                'overdue_hours' => $overdueHours,
                                'overstay_days' => $overstayDays,
                                'overstay_amount' => $overstayAmount,
                                'is_long_route' => $isLongRoute
                            ]);
                            
                            // Update the monitoring record with overdue calculations
                            $monitoring->update([
                                'overdue_hours' => $overdueHours,
                                'overstay_days' => $overstayDays,
                                'overstay_amount' => $overstayAmount,
                                'current_date' => now()
                            ]);
                            
                            // Update the related DeviceRetrieval record
                            $deviceRetrieval = DeviceRetrieval::where('monitoring_id', $monitoring->id)
                                ->orWhere('device_id', $monitoring->device_id)
                                ->first();
                                
                            if ($deviceRetrieval) {
                                // Force the amount to be recalculated
                                $deviceRetrieval->overstay_days = $overstayDays;
                                $deviceRetrieval->overstay_amount = $overstayAmount;
                                $deviceRetrieval->save();
                                
                                $this->info("\n✅ Updated DeviceRetrieval for monitoring ID: " . $monitoring->id . 
                                         " - Days: " . $overstayDays . 
                                         ", Amount: " . $overstayAmount);
                            } else {
                                // If no record exists, create one
                                $this->calculationService->updateRelatedDeviceRetrievals($monitoring->id);
                                $this->info("\n✅ Created new DeviceRetrieval for monitoring ID: " . $monitoring->id);
                            }
                            
                            $updated++;
                            
                        } catch (\Exception $e) {
                            $errors++;
                            $this->error("\nError updating monitoring ID {$monitoring->id}: " . $e->getMessage());
                            \Log::error('Error in UpdateOverdueCalculations command', [
                                'monitoring_id' => $monitoring->id ?? null,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                        
                        $bar->advance();
                        $processed++;
                        
                        // Clear memory every 100 records
                        if ($processed % 100 === 0) {
                            gc_collect_cycles();
                        }
                    }
                    
                    // Clear the query log to prevent memory issues
                    DB::connection()->disableQueryLog();
                    DB::flushQueryLog();
                    DB::connection()->reconnect();
                    
                    return true; // Continue processing next batch
                });
            
            $bar->finish();
            
            $this->info("\n\n✅ Processing complete!");
            $this->info("✓ Successfully updated: {$updated} records");
            $this->info("✗ Errors encountered: {$errors} records");
            $this->info("Total processed: {$processed} out of {$total} records");
            
            if ($errors > 0) {
                $this->warn("Warning: Some records had errors. Check the logs for details.");
            }
            
            $success = $errors === 0;
            if (!$success) {
                $this->warn("\nSome records had errors. Check the logs for details.");
            }
            
            return $success;
            
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            \Log::error('Critical error in UpdateOverdueCalculations command: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
