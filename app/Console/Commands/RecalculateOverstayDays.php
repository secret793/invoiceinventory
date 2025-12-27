<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeviceRetrieval;
use Illuminate\Support\Facades\Log;

class RecalculateOverstayDays extends Command
{
    protected $signature = 'overstay:recalculate {--device_id=} {--force}';
    protected $description = 'Recalculate overstay days and amounts for all or specific device retrievals';

    public function handle()
    {
        $deviceId = $this->option('device_id');
        $force = $this->option('force');

        if ($deviceId) {
            $this->recalculateForDevice($deviceId);
        } else {
            $this->recalculateForAllDevices($force);
        }

        return Command::SUCCESS;
    }

    private function recalculateForDevice($deviceId)
    {
        $this->info("Recalculating overstay for device: {$deviceId}");

        $deviceRetrieval = DeviceRetrieval::whereHas('device', function($query) use ($deviceId) {
            $query->where('device_id', $deviceId);
        })->first();

        if (!$deviceRetrieval) {
            $this->error("No device retrieval found for device ID: {$deviceId}");
            return;
        }

        $this->processDeviceRetrieval($deviceRetrieval);
        $this->info("Recalculation completed for device: {$deviceId}");
    }

    private function recalculateForAllDevices($force)
    {
        if (!$force && !$this->confirm('This will recalculate overstay days for ALL device retrievals. Continue?')) {
            $this->info('Operation cancelled.');
            return;
        }

        $this->info('Recalculating overstay days for all device retrievals...');

        $totalRecords = DeviceRetrieval::count();
        $this->info("Processing {$totalRecords} records...");

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $processed = 0;
        $updated = 0;

        DeviceRetrieval::chunk(100, function ($deviceRetrievals) use ($bar, &$processed, &$updated) {
            foreach ($deviceRetrievals as $deviceRetrieval) {
                $oldDays = $deviceRetrieval->overstay_days;
                $oldAmount = $deviceRetrieval->overstay_amount;

                $this->processDeviceRetrieval($deviceRetrieval);

                if ($deviceRetrieval->overstay_days != $oldDays || $deviceRetrieval->overstay_amount != $oldAmount) {
                    $updated++;
                }

                $processed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Recalculation completed!");
        $this->info("Processed: {$processed} records");
        $this->info("Updated: {$updated} records");

        Log::info('Overstay recalculation completed', [
            'processed' => $processed,
            'updated' => $updated
        ]);
    }

    private function processDeviceRetrieval(DeviceRetrieval $deviceRetrieval)
    {
        try {
            $oldDays = $deviceRetrieval->overstay_days;
            $oldAmount = $deviceRetrieval->overstay_amount;

            // Force recalculation by touching a relevant field
            $deviceRetrieval->touch();

            // The observer will automatically recalculate overstay days and amount
            $deviceRetrieval->refresh();

            $deviceId = $deviceRetrieval->device->device_id ?? $deviceRetrieval->device_id;
            $this->line("Device {$deviceId}: {$oldDays} → {$deviceRetrieval->overstay_days} days, D{$oldAmount} → D{$deviceRetrieval->overstay_amount}");

        } catch (\Exception $e) {
            $this->error("Error processing device retrieval ID {$deviceRetrieval->id}: " . $e->getMessage());
            
            Log::error('Error recalculating overstay for device retrieval', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
