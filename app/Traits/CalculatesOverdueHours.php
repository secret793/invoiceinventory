<?php

namespace App\Traits;

use Carbon\Carbon;

trait CalculatesOverdueHours
{
    /**
     * Calculate overdue hours since affixing date
     * 
     * @return int Number of hours since affixing (0 if not affixed)
     */
    public function calculateOverdueHours(): int
    {
        if (!$this->affixing_date) {
            return 0;
        }

        $affixingDate = Carbon::parse($this->affixing_date);
        $currentDate = Carbon::now();

        // Return full hours difference since affixing
        // Grace period will be applied when converting to days
        return $currentDate->diffInHours($affixingDate);
    }

    public function updateOverdueHours(): void
    {
        $this->overdue_hours = $this->calculateOverdueHours();
        $this->save();
    }

    // Add new method for calculating overstay days
    public function calculateOverstayDays(): int
    {
        if (!$this->affixing_date) {
            return 0;
        }

        $affixingDate = Carbon::parse($this->affixing_date);
        $currentDate = Carbon::now();

        // Determine grace period based on route type (in hours)
        // Short route: 24 hours grace period
        // Long route: 48 hours grace period
        $gracePeriodHours = ($this->long_route_id) ? 48 : 24;

        // Calculate hours elapsed since affixing
        $hoursElapsed = $currentDate->diffInHours($affixingDate);

        // Calculate overstay hours (hours exceeding grace period)
        $overstayHours = max(0, $hoursElapsed - $gracePeriodHours);

        // Convert to days: every 24 hours = 1 day overstay
        // If there's any overstay hour, it counts as at least 1 day
        return $overstayHours > 0 ? ceil($overstayHours / 24) : 0;
    }

    public function updateOverstayDays(): void
    {
        $this->overstay_days = $this->calculateOverstayDays();
        $this->save();
    }
}

