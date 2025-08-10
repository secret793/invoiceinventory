<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\CreateTestUsers::class,
        \App\Console\Commands\GenerateAllocationPointPermissions::class,
        \App\Console\Commands\DebugOverstayDays::class,
        \App\Console\Commands\UpdateDeviceStatuses::class,
        \App\Console\Commands\UpdateOverdueCalculations::class,
        \App\Console\Commands\ScheduleOverdueCalculations::class,
        \App\Console\Commands\CleanupDuplicateDataEntryAssignments::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run device status updates hourly
        $schedule->command('devices:update-statuses')->hourly();
        
        // Update overdue calculations every 30 minutes with retry logic
        $schedule->command('monitoring:schedule-overdue-calculations')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping(25) // Prevent overlapping runs (25 minutes)
                 ->runInBackground();    // Run in background to prevent timeouts
        
        // Keep the existing schedules for backward compatibility
        $schedule->command('update:monitoring-current-date')->everyMinute();
        $schedule->command('overstay:recalculate --force')->dailyAt('01:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

