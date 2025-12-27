<?php

namespace App\Observers;

use App\Models\Monitoring;
use Carbon\Carbon;

class CurrentDateObserver
{
    /**
     * Calculate overdue hours based on route type and update the monitoring record
     */
    private function calculateOverdueHours(Monitoring $monitoring): void
    {
        // Skip if affixing_date is not set
        if (!$monitoring->affixing_date) {
            $monitoring->overdue_hours = 0;
            return;
        }

        // Update current_date
        $monitoring->current_date = now();

        // Calculate the time difference in hours
        $hoursDifference = $monitoring->affixing_date->diffInHours($monitoring->current_date, false);

        // Determine the grace period based on route type
        $gracePeriod = 48; // Default grace period (48 hours for no routes or long routes)
        
        if ($monitoring->route_id && !$monitoring->long_route_id) {
            $gracePeriod = 24; // Normal routes get 24 hours grace period
        }

        // Calculate overdue hours (will be 0 or negative if within grace period)
        $overdueHours = $hoursDifference - $gracePeriod;
        
        // Only set positive overdue hours, otherwise set to 0
        $monitoring->overdue_hours = max(0, $overdueHours);
    }

    /**
     * Handle the Monitoring "retrieved" event.
     */
    public function retrieved(Monitoring $monitoring): void
    {
        $this->calculateOverdueHours($monitoring);
        
        // Save without triggering events to avoid infinite loop
        Monitoring::withoutEvents(function () use ($monitoring) {
            $monitoring->save();
        });
    }

    /**
     * Handle the Monitoring "created" event.
     */
    public function created(Monitoring $monitoring): void
    {
        $this->calculateOverdueHours($monitoring);
        $monitoring->save();
    }

    /**
     * Handle the Monitoring "updated" event.
     */
    public function updated(Monitoring $monitoring): void
    {
        $this->calculateOverdueHours($monitoring);
        $monitoring->save();
    }
}
