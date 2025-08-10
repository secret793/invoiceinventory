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

        // Determine grace period based on route type (in days)
        $gracePeriod = ($this->long_route_id) ? 2 : 1; // 2 days for long route, 1 for normal

        $daysDiff = $currentDate->startOfDay()->diffInDays($affixingDate->startOfDay());

        // Return days exceeding grace period
        return max(0, $daysDiff - $gracePeriod);
    }

    public function updateOverstayDays(): void
    {
        $this->overstay_days = $this->calculateOverstayDays();
        $this->save();
    }
}

