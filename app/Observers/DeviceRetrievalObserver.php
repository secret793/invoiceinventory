<?php

namespace App\Observers;

use App\Models\DeviceRetrieval;
use App\Models\Monitoring;
use App\Models\Regime;
use App\Models\Destination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceRetrievalObserver
{
    /**
     * Handle the DeviceRetrieval "created" event.
     */
    public function created(DeviceRetrieval $deviceRetrieval): void
    {
        $this->syncOverdueDays($deviceRetrieval);
    }

    /**
     * Handle the DeviceRetrieval "updated" event.
     */
    public function updated(DeviceRetrieval $deviceRetrieval): void
    {
        try {
            Log::info('DeviceRetrieval updating', [
                'id' => $deviceRetrieval->id,
                'dirty_fields' => $deviceRetrieval->getDirty()
            ]);

            // Sync overdue days if route type changed
            if ($deviceRetrieval->isDirty('long_route_id')) {
                $this->syncOverdueDays($deviceRetrieval);
            }
        } catch (\Exception $e) {
            Log::error('Error in DeviceRetrieval updating observer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle the DeviceRetrieval "deleted" event.
     */
    public function deleted(DeviceRetrieval $deviceRetrieval): void
    {
        // Old code commented out - we're using MonitoringDeviceObserver now!
        /*
        Monitoring::where('device_id', $deviceRetrieval->device_id)
            ->latest()
            ->first()
            ?->delete();
        */
    }

    public function creating(DeviceRetrieval $deviceRetrieval)
    {
        // Set default retrieval status if not set
        if (!isset($deviceRetrieval->retrieval_status)) {
            $deviceRetrieval->retrieval_status = 'NOT_RETRIEVED';
        }

        // Set destination based on regime
        $regime = Regime::find($deviceRetrieval->regime);
        if ($regime) {
            switch (strtolower($regime->name)) {
                case 'warehouse':
                    $deviceRetrieval->destination = 'Ghana';
                    break;
                case 'transit':
                    $deviceRetrieval->destination = 'Soma';
                    break;
                default:
                    $deviceRetrieval->destination = 'Unknown';
                    break;
            }
        }

        // Log the destination for debugging
        Log::info('DeviceRetrieval creating', [
            'device_id' => $deviceRetrieval->device_id,
            'regime' => $deviceRetrieval->regime,
            'destination' => $deviceRetrieval->destination,
        ]);
    }

    public function updating(DeviceRetrieval $deviceRetrieval)
    {
        try {
            Log::info('DeviceRetrieval updating', [
                'id' => $deviceRetrieval->id,
                'dirty_fields' => $deviceRetrieval->getDirty(),
                'is_status_changing' => $deviceRetrieval->isDirty('retrieval_status'),
                'is_route_changing' => $deviceRetrieval->isDirty('long_route_id'),
                'is_affixing_date_changing' => $deviceRetrieval->isDirty('affixing_date'),
                'old_status' => $deviceRetrieval->getOriginal('retrieval_status'),
                'new_status' => $deviceRetrieval->retrieval_status
            ]);

            // Calculate overdue if status, route, or affixing date is changing
            if ($deviceRetrieval->isDirty(['retrieval_status', 'long_route_id', 'affixing_date'])) {
                Log::info('DeviceRetrieval: Recalculating overstay days due to field changes', [
                    'id' => $deviceRetrieval->id,
                    'changed_fields' => array_keys($deviceRetrieval->getDirty())
                ]);
                $this->calculateOverdueDays($deviceRetrieval);
            }
        } catch (\Exception $e) {
            Log::error('Error in DeviceRetrieval updating observer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function calculateOverdueDays(DeviceRetrieval $deviceRetrieval)
    {
        Log::info('DeviceRetrieval: Starting overstay calculation', [
            'device_retrieval_id' => $deviceRetrieval->id,
            'device_id' => $deviceRetrieval->device_id,
            'current_overstay_days' => $deviceRetrieval->overstay_days,
            'long_route_id' => $deviceRetrieval->long_route_id,
            'long_route_exists' => $deviceRetrieval->long_route_id ? true : false,
            'current_time' => now()->toDateTimeString(),
            'dirty_attributes' => $deviceRetrieval->getDirty()
        ]);

        // First check if DeviceRetrieval has its own affixing_date
        $affixingDate = $deviceRetrieval->affixing_date;
        $source = 'device_retrieval';

        // If not, get from monitoring table
        if (!$affixingDate) {
            $monitoring = DB::select('
                SELECT affixing_date, current_date
                FROM monitorings
                WHERE device_id = ?
                ORDER BY id DESC
                LIMIT 1
            ', [$deviceRetrieval->device_id]);

            if (empty($monitoring)) {
                Log::warning('DeviceRetrieval: No affixing date found in DeviceRetrieval or Monitoring', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'device_id' => $deviceRetrieval->device_id
                ]);
                return;
            }

            $affixingDate = $monitoring[0]->affixing_date;
            $source = 'monitoring';
        }

        if (!$affixingDate) {
            Log::warning('DeviceRetrieval: Affixing date is null', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'device_id' => $deviceRetrieval->device_id,
                'source' => $source
            ]);
            return;
        }

        $isLongRoute = !empty($deviceRetrieval->long_route_id);
        $gracePeriodDays = $isLongRoute ? 2 : 1; // 2 days for long route, 1 day for short route
        $gracePeriodHours = $isLongRoute ? 48 : 24; // For logging/debugging
        
        $affixingDateCarbon = Carbon::parse($affixingDate);
        $now = now();
        
        // Calculate total hours difference
        $totalHours = $now->diffInHours($affixingDateCarbon, false);
        
        // Calculate days difference (whole days only)
        $daysDiff = $now->startOfDay()->diffInDays($affixingDateCarbon->startOfDay());
        
        // Calculate overdue days after grace period
        $newOverstayDays = max(0, $daysDiff - $gracePeriodDays);
        
        // Log detailed calculation
        Log::debug('Overdue calculation details', [
            'affixing_date' => $affixingDateCarbon->toDateTimeString(),
            'current_time' => $now->toDateTimeString(),
            'total_hours_diff' => $totalHours,
            'days_diff' => $daysDiff,
            'grace_period_days' => $gracePeriodDays,
            'grace_period_hours' => $gracePeriodHours,
            'is_long_route' => $isLongRoute,
            'calculated_overstay_days' => $newOverstayDays
        ]);

        Log::info('DeviceRetrieval: Overstay calculation details', [
            'device_retrieval_id' => $deviceRetrieval->id,
            'device_id' => $deviceRetrieval->device_id,
            'affixing_date' => $affixingDateCarbon->toDateString(),
            'affixing_date_source' => $source,
            'current_date' => now()->toDateString(),
            'days_diff' => $daysDiff,
            'grace_period' => $gracePeriod,
            'old_overstay_days' => $deviceRetrieval->overstay_days,
            'new_overstay_days' => $newOverstayDays,
            'long_route_id' => $deviceRetrieval->long_route_id
        ]);

        $deviceRetrieval->overstay_days = $newOverstayDays;
        $deviceRetrieval->updateOverstayAmount(); // Calculate overstay amount

        Log::info('DeviceRetrieval: Overstay calculation completed', [
            'device_retrieval_id' => $deviceRetrieval->id,
            'final_overstay_days' => $deviceRetrieval->overstay_days,
            'final_overstay_amount' => $deviceRetrieval->overstay_amount
        ]);
    }

    private function syncOverdueDays($deviceRetrieval)
    {
        DB::beginTransaction();
        
        try {
            Log::info('Starting syncOverdueDays', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'device_id' => $deviceRetrieval->device_id,
                'long_route_id' => $deviceRetrieval->long_route_id,
                'current_overstay_days' => $deviceRetrieval->overstay_days,
                'current_overstay_amount' => $deviceRetrieval->overstay_amount
            ]);

            // Get the latest monitoring for this device
            $monitoring = Monitoring::where('device_id', $deviceRetrieval->device_id)
                ->orderBy('id', 'desc')
                ->first();

            if (!$monitoring) {
                Log::warning('No monitoring record found for device', [
                    'device_id' => $deviceRetrieval->device_id
                ]);
                return;
            }

            if (empty($monitoring->affixing_date)) {
                Log::warning('Monitoring record has no affixing_date', [
                    'monitoring_id' => $monitoring->id,
                    'device_id' => $monitoring->device_id
                ]);
                return;
            }

            // Calculate grace period based on route type
            $isLongRoute = !empty($deviceRetrieval->long_route_id);
            $gracePeriodDays = $isLongRoute ? 2 : 1;
            $gracePeriodHours = $isLongRoute ? 48 : 24;

            // Parse dates
            $affixingDate = Carbon::parse($monitoring->affixing_date);
            $now = now();
            
            // Calculate differences
            $totalHours = $now->diffInHours($affixingDate, false);
            $daysDiff = $now->startOfDay()->diffInDays($affixingDate->startOfDay());
            $overstayDays = max(0, $daysDiff - $gracePeriodDays);
            
            // Log detailed calculation
            Log::debug('syncOverdueDays calculation', [
                'affixing_date' => $affixingDate->toDateTimeString(),
                'current_time' => $now->toDateTimeString(),
                'total_hours_diff' => $totalHours,
                'days_diff' => $daysDiff,
                'grace_period_days' => $gracePeriodDays,
                'grace_period_hours' => $gracePeriodHours,
                'is_long_route' => $isLongRoute,
                'calculated_overstay_days' => $overstayDays
            ]);

            // Update both records
            $deviceRetrieval->overstay_days = $overstayDays;
            $deviceRetrieval->updateOverstayAmount();
            $deviceRetrieval->save();

            $monitoring->overstay_days = $overstayDays;
            $monitoring->save();

            Log::info('Overstay days synchronized', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'monitoring_id' => $monitoring->id,
                'overstay_days' => $overstayDays,
                'overstay_amount' => $deviceRetrieval->overstay_amount,
                'grace_period_days' => $gracePeriodDays,
                'is_long_route' => $isLongRoute,
                'calculation_time' => now()->toDateTimeString()
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing overstay days', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle the DeviceRetrieval "retrieved" event.
     */
    public function retrieved(DeviceRetrieval $deviceRetrieval): void
    {
        try {
            // Skip if the current_time was updated very recently (within the last 30 seconds)
            if ($deviceRetrieval->current_time && $deviceRetrieval->current_time->diffInSeconds(now()) < 30) {
                return;
            }
            
            // Update destination based on regime
            $regime = Regime::find($deviceRetrieval->regime);
            $destination = 'Unknown';
            
            if ($regime) {
                switch (strtolower($regime->name)) {
                    case 'warehouse':
                        $destination = 'Ghana';
                        break;
                    case 'transit':
                        $destination = 'Soma';
                        break;
                }
            }

            // Update the current_time and destination without triggering events to avoid infinite loop
            DeviceRetrieval::withoutEvents(function () use ($deviceRetrieval, $destination) {
                $updates = [
                    'current_time' => now(),
                    'destination' => $destination
                ];
                
                $deviceRetrieval->forceFill($updates)->save();
                
                // Log the update for debugging
                Log::debug('DeviceRetrieval current_time and destination updated', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'device_id' => $deviceRetrieval->device_id,
                    'new_current_time' => now()->toDateTimeString(),
                    'previous_current_time' => $deviceRetrieval->current_time?->toDateTimeString(),
                    'destination' => $destination
                ]);
            });
            
        } catch (\Exception $e) {
            Log::error('Error in DeviceRetrieval retrieved observer', [
                'device_retrieval_id' => $deviceRetrieval->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Register the observer for the retrieved event
     */
    public static function boot()
    {
        parent::boot();
        
        // Register the observer for the retrieved event
        static::retrieved(function ($model) {
            (new static)->retrieved($model);
        });
    }
}



