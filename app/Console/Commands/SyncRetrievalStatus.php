<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeviceRetrieval;
use App\Models\Monitoring;
use Illuminate\Support\Facades\DB;

class SyncRetrievalStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:retrieval-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync retrieval status from device_retrievals to monitorings table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting retrieval status sync...');

        try {
            // Get all device retrievals with their latest retrieval status
            $deviceRetrievals = DeviceRetrieval::select('device_id', 'retrieval_status')
                ->whereIn('id', function($query) {
                    $query->select(DB::raw('MAX(id)'))
                          ->from('device_retrievals')
                          ->groupBy('device_id');
                })
                ->get();

            $this->info("Found {$deviceRetrievals->count()} device retrievals to sync.");

            $synced = 0;
            $notFound = 0;

            foreach ($deviceRetrievals as $deviceRetrieval) {
                // Find corresponding monitoring record
                $monitoring = Monitoring::where('device_id', $deviceRetrieval->device_id)->first();

                if ($monitoring) {
                    // Update the monitoring record if status is different
                    if ($monitoring->retrieval_status !== $deviceRetrieval->retrieval_status) {
                        $oldStatus = $monitoring->retrieval_status;
                        $monitoring->retrieval_status = $deviceRetrieval->retrieval_status;
                        $monitoring->save();

                        $this->line("Synced device_id {$deviceRetrieval->device_id}: {$oldStatus} -> {$deviceRetrieval->retrieval_status}");
                        $synced++;
                    }
                } else {
                    $this->warn("No monitoring record found for device_id: {$deviceRetrieval->device_id}");
                    $notFound++;
                }
            }

            $this->info("\nSync completed!");
            $this->info("Records synced: {$synced}");
            $this->info("Monitoring records not found: {$notFound}");

        } catch (\Exception $e) {
            $this->error("Error during sync: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
