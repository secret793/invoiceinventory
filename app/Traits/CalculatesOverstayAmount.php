<?php

namespace App\Traits;

trait CalculatesOverstayAmount
{
    /**
     * Calculate the overstay amount based on overstay days
     * 
     * Logic:
     * - D1000 for 1-2 days (day 1 starts at 1 hour overstay)
     * - D2000 for 2-3 days
     * - D3000 for 3-4 days
     * - And so on...
     * 
     * @param int $overstayDays
     * @return float
     */
    public function calculateOverstayAmount(int $overstayDays): float
    {
        // No charge for 0 days (no overstay)
        if ($overstayDays <= 0) {
            return 0.00;
        }
        
        // Calculate the amount based on days
        // D1000 for 1 day, D2000 for 2 days, etc.
        $baseAmount = 1000.00;
        $daysToCharge = $overstayDays; // Charge starts from day 1
        
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