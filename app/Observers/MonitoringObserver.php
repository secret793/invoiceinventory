<?php

namespace App\Observers;

use App\Models\Monitoring;
use App\Models\DeviceRetrieval;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitoringObserver
{
    /**
     * Handle the Monitoring "retrieved" event.
     */
    public function retrieved(Monitoring $monitoring): void
    {
        // Update the current_date without triggering events to avoid infinite loop
        Monitoring::withoutEvents(function () use ($monitoring) {
            $monitoring->forceFill(['current_date' => now()])->save();
        });
    }

    /**
     * Handle the Monitoring "created" event.
     */
    public function created(Monitoring $monitoring): void
    {
        $this->calculateAndSyncOverdueDays($monitoring);
    }

    /**
     * Handle the Monitoring "updated" event.
     */
    public function updated(Monitoring $monitoring): void
    {
        // Only recalculate if affixing_date changed
        if ($monitoring->isDirty('affixing_date')) {
            $this->calculateAndSyncOverdueDays($monitoring);
        }
    }

    private function calculateAndSyncOverdueDays(Monitoring $monitoring): void
    {
        try {
            DB::beginTransaction();

            // Get the latest device retrieval for this device
            $deviceRetrieval = DeviceRetrieval::where('device_id', $monitoring->device_id)
                ->orderBy('id', 'desc')
                ->first();

            if ($deviceRetrieval) {
                // Calculate grace period based on route type
                $gracePeriod = $deviceRetrieval->long_route_id ? 2 : 1; // 2 days for long route, 1 for normal

                // Calculate days difference
                $daysDiff = now()->startOfDay()->diffInDays(Carbon::parse($monitoring->affixing_date)->startOfDay());
                $overstayDays = max(0, $daysDiff - $gracePeriod);

                // Update both monitoring and device retrieval
                $monitoring->overstay_days = $overstayDays;
                $monitoring->save();

                $deviceRetrieval->overstay_days = $overstayDays;
                $deviceRetrieval->save();

                Log::info('Overstay days synchronized', [
                    'monitoring_id' => $monitoring->id,
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'overstay_days' => $overstayDays,
                    'grace_period' => $gracePeriod,
                    'days_diff' => $daysDiff
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error calculating and syncing overstay days', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

