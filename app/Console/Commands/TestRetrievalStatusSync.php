<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeviceRetrieval;
use App\Models\Monitoring;
use App\Models\Device;
use Illuminate\Support\Facades\Log;

class TestRetrievalStatusSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:retrieval-status-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the retrieval status sync between DeviceRetrieval and Monitoring';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== DeviceRetrieval Status Observer Test ===');

        // Get existing records or create test data
        $device = Device::first();
        if (!$device) {
            $this->error('No devices found. Please create some test data first.');
            return 1;
        }

        $monitoring = Monitoring::where('device_id', $device->id)->first();
        $deviceRetrieval = DeviceRetrieval::where('device_id', $device->id)->first();

        // Create test data if it doesn't exist
        if (!$monitoring) {
            $this->info('Creating test monitoring record...');
            $monitoring = Monitoring::create([
                'date' => now(),
                'current_date' => now(),
                'device_id' => $device->id,
                'boe' => 'TEST-BOE-' . time(),
                'sad_number' => 'SAD-' . time(),
                'vehicle_number' => 'VH-' . time(),
                'regime' => 'warehouse',
                'destination' => 'Ghana',
                'route_id' => 1,
                'retrieval_status' => 'NOT_RETRIEVED'
            ]);
        }

        if (!$deviceRetrieval) {
            $this->info('Creating test device retrieval record...');
            $deviceRetrieval = DeviceRetrieval::create([
                'date' => now(),
                'device_id' => $device->id,
                'boe' => 'TEST-BOE-' . time(),
                'sad_number' => 'SAD-' . time(),
                'vehicle_number' => 'VH-' . time(),
                'regime' => 'warehouse',
                'destination' => 'Ghana',
                'retrieval_status' => 'NOT_RETRIEVED'
            ]);
        }

        $this->info('Found test records:');
        $this->line("Device ID: {$device->id}");
        $this->line("DeviceRetrieval ID: {$deviceRetrieval->id}");
        $this->line("Monitoring ID: {$monitoring->id}");
        $this->line("Current DeviceRetrieval Status: {$deviceRetrieval->retrieval_status}");
        $this->line("Current Monitoring Status: {$monitoring->retrieval_status}");

        // Test status change
        $newStatus = $deviceRetrieval->retrieval_status === 'NOT_RETRIEVED' ? 'RETRIEVED' : 'NOT_RETRIEVED';
        $this->info("Changing DeviceRetrieval status to: {$newStatus}");

        // Enable logging for this test
        Log::info('TestRetrievalStatusSync: Starting manual test', [
            'device_retrieval_id' => $deviceRetrieval->id,
            'device_id' => $deviceRetrieval->device_id,
            'old_status' => $deviceRetrieval->retrieval_status,
            'new_status' => $newStatus
        ]);

        $deviceRetrieval->update(['retrieval_status' => $newStatus]);

        // Refresh and check
        $monitoring->refresh();
        $this->info('After update:');
        $this->line("DeviceRetrieval Status: {$deviceRetrieval->retrieval_status}");
        $this->line("Monitoring Status: {$monitoring->retrieval_status}");

        if ($deviceRetrieval->retrieval_status === $monitoring->retrieval_status) {
            $this->info('✅ SUCCESS: Observer is working!');
        } else {
            $this->error('❌ FAILED: Observer is not working!');
        }

        $this->info("\nCheck the logs for detailed information:");
        $this->line("tail -f storage/logs/laravel.log | grep DeviceRetrievalStatusObserver");

        return 0;
    }
}
