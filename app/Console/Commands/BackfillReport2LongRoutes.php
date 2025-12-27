<?php

namespace App\Console\Commands;

use App\Models\DeviceRetrievalReport2;
use App\Models\DeviceRetrieval;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillReport2LongRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report2:backfill-long-routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill long_route_id in device_retrieval_report_2_logs from device_retrievals source data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting backfill of long routes in Report #2...');
        
        try {
            // Get all Report 2 records that don't have long_route_id set
            $report2Records = DeviceRetrievalReport2::whereNull('long_route_id')
                ->get();

            $this->info("📊 Found " . $report2Records->count() . " records without long_route_id");

            if ($report2Records->isEmpty()) {
                $this->info("✅ All Report #2 records already have long_route_id set!");
                return Command::SUCCESS;
            }

            $updated = 0;
            $skipped = 0;

            foreach ($report2Records as $report2Record) {
                try {
                    // Find the corresponding DeviceRetrieval record
                    $deviceRetrieval = DeviceRetrieval::where('device_id', $report2Record->device_id)
                        ->where('boe', $report2Record->boe)
                        ->where('vehicle_number', $report2Record->vehicle_number)
                        ->latest()
                        ->first();

                    if ($deviceRetrieval && $deviceRetrieval->long_route_id) {
                        // Update the Report 2 record with long_route_id
                        $report2Record->update([
                            'long_route_id' => $deviceRetrieval->long_route_id,
                        ]);
                        
                        $updated++;
                        
                        $this->line("✓ Updated Report2 ID {$report2Record->id} with long_route_id {$deviceRetrieval->long_route_id}");
                    } else {
                        $skipped++;
                        $this->line("⊘ Skipped Report2 ID {$report2Record->id} - No matching DeviceRetrieval or no long_route_id");
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error processing Report2 ID {$report2Record->id}: " . $e->getMessage());
                    Log::error('Backfill error', ['record_id' => $report2Record->id, 'error' => $e->getMessage()]);
                }
            }

            $this->info('');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("✅ Backfill Complete!");
            $this->info("   Updated: {$updated} records");
            $this->info("   Skipped: {$skipped} records");
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            Log::info('Report2 long routes backfill completed', [
                'updated' => $updated,
                'skipped' => $skipped,
                'total' => $report2Records->count()
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Fatal error during backfill: ' . $e->getMessage());
            Log::error('Fatal backfill error', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }
}
