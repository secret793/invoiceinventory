<?php

namespace App\Traits;

trait CalculatesOverstayAmount
{
    /**
     * Calculate the overstay amount based on overstay days
     * 
     * Logic:
     * - D1000 for 49-72 hours (2 days + 1hr to 3 days)
     * - D2000 for 73-96 hours (3 days + 1hr to 4 days)
     * - D3000 for 97-120 hours (4 days + 1hr to 5 days)
     * - And so on...
     * 
     * @param int $overstayDays
     * @return float
     */
    public function calculateOverstayAmount(int $overstayDays): float
    {
        // No charge for 0-1 days
        if ($overstayDays <= 1) {
            return 0.00;
        }
        
        // Calculate the amount based on days
        // D1000 for 2 days, D2000 for 3 days, etc.
        $baseAmount = 1000.00;
        $daysToCharge = $overstayDays - 1; // Subtract 1 because we start charging from day 2
        
        return $baseAmount * $daysToCharge;
    }
    
    /**
     * Update the overstay amount based on current overstay days
     */
    public function updateOverstayAmount(): void
    {
        if (isset($this->overstay_days)) {
            $this->overstay_amount = $this->calculateOverstayAmount($this->overstay_days);
        }
    }
}