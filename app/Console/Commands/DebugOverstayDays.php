<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeviceRetrieval;
use App\Models\Monitoring;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DebugOverstayDays extends Command
{
    protected $signature = 'debug:overstay-days {device_id?}';
    protected $description = 'Debug overstay days calculation for a specific device or all devices';

    public function handle()
    {
        $deviceId = $this->argument('device_id');

        if ($deviceId) {
            $this->debugSingleDevice($deviceId);
        } else {
            $this->debugAllDevices();
        }

        return Command::SUCCESS;
    }

    private function debugSingleDevice($deviceId)
    {
        $this->info("Debugging overstay days for device ID: {$deviceId}");
        $this->line('');

        // Get DeviceRetrieval record
        $deviceRetrieval = DeviceRetrieval::where('device_id', $deviceId)->first();
        if (!$deviceRetrieval) {
            $this->error("No DeviceRetrieval record found for device ID: {$deviceId}");
            return;
        }

        // Get Monitoring record
        $monitoring = Monitoring::where('device_id', $deviceId)->latest()->first();

        $this->table(['Field', 'Value'], [
            ['Device ID', $deviceId],
            ['DeviceRetrieval ID', $deviceRetrieval->id],
            ['Current Overstay Days', $deviceRetrieval->overstay_days ?? 'NULL'],
            ['Current Overstay Amount', $deviceRetrieval->overstay_amount ?? 'NULL'],
            ['Long Route ID', $deviceRetrieval->long_route_id ?? 'NULL'],
            ['Grace Period', $deviceRetrieval->long_route_id ? '2 days' : '1 day'],
            ['DeviceRetrieval Affixing Date', $deviceRetrieval->affixing_date ?? 'NULL'],
            ['Monitoring ID', $monitoring?->id ?? 'NULL'],
            ['Monitoring Affixing Date', $monitoring?->affixing_date ?? 'NULL'],
            ['Monitoring Overstay Days', $monitoring?->overstay_days ?? 'NULL'],
        ]);

        // Determine which affixing date to use
        $affixingDate = $deviceRetrieval->affixing_date ?? $monitoring?->affixing_date;
        
        if (!$affixingDate) {
            $this->error('No affixing date found in either DeviceRetrieval or Monitoring records!');
            return;
        }

        // Calculate overstay days
        $gracePeriod = $deviceRetrieval->long_route_id ? 2 : 1;
        $affixingDateCarbon = Carbon::parse($affixingDate);
        $daysDiff = now()->startOfDay()->diffInDays($affixingDateCarbon->startOfDay());
        $calculatedOverstayDays = max(0, $daysDiff - $gracePeriod);

        $this->line('');
        $this->info('Calculation Details:');
        $this->table(['Field', 'Value'], [
            ['Affixing Date Used', $affixingDateCarbon->toDateString()],
            ['Current Date', now()->toDateString()],
            ['Days Difference', $daysDiff],
            ['Grace Period', $gracePeriod],
            ['Calculated Overstay Days', $calculatedOverstayDays],
            ['Expected Overstay Amount', 'D' . number_format($this->calculateOverstayAmount($calculatedOverstayDays), 2)],
        ]);

        // Update the record
        if ($this->confirm('Do you want to update the overstay days for this device?')) {
            $deviceRetrieval->overstay_days = $calculatedOverstayDays;
            $deviceRetrieval->updateOverstayAmount();
            $deviceRetrieval->save();

            if ($monitoring) {
                $monitoring->overstay_days = $calculatedOverstayDays;
                $monitoring->save();
            }

            $this->info('Overstay days updated successfully!');
            $this->line("New overstay days: {$deviceRetrieval->overstay_days}");
            $this->line("New overstay amount: D{$deviceRetrieval->overstay_amount}");
        }
    }

    private function debugAllDevices()
    {
        $this->info('Debugging overstay days for all devices...');
        $this->line('');

        $deviceRetrievals = DeviceRetrieval::with('device')->get();
        $issues = [];

        foreach ($deviceRetrievals as $deviceRetrieval) {
            $monitoring = Monitoring::where('device_id', $deviceRetrieval->device_id)->latest()->first();
            $affixingDate = $deviceRetrieval->affixing_date ?? $monitoring?->affixing_date;

            if (!$affixingDate) {
                $issues[] = [
                    'Device ID' => $deviceRetrieval->device_id,
                    'Issue' => 'No affixing date',
                    'DeviceRetrieval ID' => $deviceRetrieval->id,
                    'Current Overstay Days' => $deviceRetrieval->overstay_days ?? 'NULL'
                ];
                continue;
            }

            $gracePeriod = $deviceRetrieval->long_route_id ? 2 : 1;
            $affixingDateCarbon = Carbon::parse($affixingDate);
            $daysDiff = now()->startOfDay()->diffInDays($affixingDateCarbon->startOfDay());
            $calculatedOverstayDays = max(0, $daysDiff - $gracePeriod);

            if ($calculatedOverstayDays != $deviceRetrieval->overstay_days) {
                $issues[] = [
                    'Device ID' => $deviceRetrieval->device_id,
                    'Issue' => 'Overstay days mismatch',
                    'Current' => $deviceRetrieval->overstay_days ?? 'NULL',
                    'Calculated' => $calculatedOverstayDays,
                    'Affixing Date' => $affixingDateCarbon->toDateString(),
                    'Days Diff' => $daysDiff,
                    'Grace Period' => $gracePeriod
                ];
            }
        }

        if (empty($issues)) {
            $this->info('No issues found! All overstay days are correctly calculated.');
        } else {
            $this->error('Found ' . count($issues) . ' issues:');
            $this->table(array_keys($issues[0]), $issues);

            if ($this->confirm('Do you want to fix all overstay day calculations?')) {
                $this->call('app:update-overdue-hours');
                $this->info('Overstay days updated for all devices!');
            }
        }
    }

    private function calculateOverstayAmount(int $overstayDays): float
    {
        if ($overstayDays <= 1) {
            return 0.00;
        }
        
        $baseAmount = 1000.00;
        $daysToCharge = $overstayDays - 1;
        
        return $baseAmount * $daysToCharge;
    }
}
