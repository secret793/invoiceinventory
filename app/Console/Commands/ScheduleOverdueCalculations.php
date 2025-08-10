<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduleOverdueCalculations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:schedule-overdue-calculations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule the overdue calculations command with retry logic';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $maxAttempts = 2;
        $attempt = 1;
        $success = false;
        
        // Log command start
        \Log::info('Starting ScheduleOverdueCalculations command', [
            'attempts' => $maxAttempts,
            'timestamp' => now()
        ]);
        
        while ($attempt <= $maxAttempts && !$success) {
            try {
                $this->info("\nAttempt $attempt of $maxAttempts...");
                
                // Clear the terminal screen
                if (function_exists('system')) {
                    system('clear');
                } else {
                    $this->output->write("\033[2J\033[;H");
                }
                
                // Execute the original command
                $exitCode = \Artisan::call('monitoring:update-overdue-calculations');
                $success = ($exitCode === 0);
                
                if ($success) {
                    $this->info("\n✅ Command completed successfully on attempt $attempt");
                    \Log::info('ScheduleOverdueCalculations completed successfully', [
                        'attempt' => $attempt,
                        'timestamp' => now()
                    ]);
                    return Command::SUCCESS;
                }
                
                if ($attempt < $maxAttempts) {
                    $this->warn("\n⚠️  Attempt $attempt failed. Retrying in 5 seconds...");
                    sleep(5);
                }
                
                $attempt++;
                
            } catch (\Exception $e) {
                $this->error("\n❌ Attempt $attempt failed: " . $e->getMessage());
                Log::error('ScheduleOverdueCalculations error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                
                if ($attempt >= $maxAttempts) {
                    $this->error("\n❌ All $maxAttempts attempts failed. Please check the logs for details.");
                    return Command::FAILURE;
                }
                
                $attempt++;
                sleep(5);
            }
        }
        
        return $success ? Command::SUCCESS : Command::FAILURE;
    }
}
