<?php

namespace App\Observers;

use App\Models\DeviceRetrieval;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OverstayAmountUpdaterRetrieval
{
    /**
     * Handle the DeviceRetrieval "saving" event.
     * PROACTIVE: Automatically recalculate overstay_days from timestamps on EVERY save.
     * SKIP: If device status is RETRIEVED, do not update overstay_days
     */
    public function saving(DeviceRetrieval $deviceRetrieval): void
    {
        // Skip overstay calculation if device is already RETRIEVED
        if ($deviceRetrieval->retrieval_status === 'RETRIEVED') {
            Log::info('Skipping overstay recalculation - device already retrieved', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'device_id' => $deviceRetrieval->device_id,
                'retrieval_status' => $deviceRetrieval->retrieval_status,
                'timestamp' => now()->toDateTimeString()
            ]);
            return;
        }

        // Skip overstay calculation if payment is already settled (PD or WAIVED)
        if (in_array($deviceRetrieval->payment_status, ['PD', 'WAIVED'])) {
            Log::info('Skipping overstay recalculation - payment settled', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'device_id' => $deviceRetrieval->device_id,
                'payment_status' => $deviceRetrieval->payment_status,
                'timestamp' => now()->toDateTimeString()
            ]);
            return;
        }
        
        // STEP 1: Always recalculate overstay_days first (PROACTIVE)
        // This ensures calculations are always current based on affixing_date vs current time
        $this->recalculateOverstayDays($deviceRetrieval);
        
        // STEP 2: Then update amount based on new days (REACTIVE)
        // This keeps amount in sync with days
        $this->updateOverstayAmount($deviceRetrieval);
    }

    /**
     * Handle the DeviceRetrieval "updated" event.
     */
    public function updated(DeviceRetrieval $deviceRetrieval): void
    {
        // If overstay_days was changed but amount wasn't updated in the saving event
        if ($deviceRetrieval->wasChanged('overstay_days') && 
            !$deviceRetrieval->wasChanged('overstay_amount')) {
            $this->updateOverstayAmount($deviceRetrieval, true);
        }
    }

    /**
     * NEW METHOD: Recalculate overstay_days from timestamps (PROACTIVE CALCULATION)
     * 
     * This method implements the precise overstay calculation based on:
     * - affixing_date: exact timestamp when device was affixed
     * - current_time: now()
     * - grace_period: 24h for short routes, 48h for long routes
     * 
     * Algorithm uses CEILING logic: even 1 second past grace period = 1 day charged
     * 
     * Example:
     * - Device affixed: 2025-12-01 10:00:00
     * - Short route (24h grace): Grace ends at 2025-12-02 10:00:00
     * - At 2025-12-02 10:00:01: overstay_days = 1 (1 second past grace)
     * - At 2025-12-03 10:00:01: overstay_days = 2 (24+ hours into next cycle)
     */
    protected function recalculateOverstayDays(DeviceRetrieval $deviceRetrieval): void
    {
        try {
            // Skip if no affixing_date set
            if (!$deviceRetrieval->affixing_date) {
                Log::debug('Skipping overstay calculation - no affixing_date', [
                    'device_retrieval_id' => $deviceRetrieval->id
                ]);
                return;
            }
            
            // Get current timestamp (precise to seconds)
            $currentTime = now();
            
            // Get affixing timestamp (precise to seconds)
            $affixingTime = Carbon::parse($deviceRetrieval->affixing_date);
            
            // Determine grace period in seconds
            // Long route: 48 hours = 172,800 seconds
            // Short route: 24 hours = 86,400 seconds
            $gracePeriodSeconds = $deviceRetrieval->long_route_id ? (48 * 3600) : (24 * 3600);
            $routeType = $deviceRetrieval->long_route_id ? 'Long' : 'Short';
            
            // Calculate total seconds since affixing
            $totalSeconds = $currentTime->diffInSeconds($affixingTime);
            
            // Calculate seconds into overstay period (after grace period)
            $overstaySeconds = $totalSeconds - $gracePeriodSeconds;
            
            // If still in grace period, no overstay
            if ($overstaySeconds <= 0) {
                $oldDays = $deviceRetrieval->overstay_days;
                $deviceRetrieval->overstay_days = 0;
                
                Log::debug('Device still in grace period', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'route_type' => $routeType,
                    'affixing_date' => $affixingTime->toDateTimeString(),
                    'grace_period_seconds' => $gracePeriodSeconds,
                    'total_seconds_since_affixing' => $totalSeconds,
                    'seconds_into_grace' => abs($overstaySeconds),
                    'overstay_days_before' => $oldDays,
                    'overstay_days_after' => 0
                ]);
                return;
            }
            
            // Calculate number of complete 24-hour cycles (CEILING LOGIC)
            // IMPORTANT: Even 1 second into a new cycle = a full day charge
            $secondsPerDay = 24 * 3600; // 86,400 seconds
            $overstayDays = (int) ceil($overstaySeconds / $secondsPerDay);
            
            // Ensure not negative
            $overstayDays = max(0, $overstayDays);
            
            // Store old value for logging
            $oldDays = $deviceRetrieval->overstay_days;
            
            // Update the model
            $deviceRetrieval->overstay_days = $overstayDays;
            
            // Log the calculation
            Log::info('Overstay days recalculated (PROACTIVE)', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'device_id' => $deviceRetrieval->device_id,
                'route_type' => $routeType,
                'grace_period_hours' => $gracePeriodSeconds / 3600,
                'affixing_date' => $affixingTime->toDateTimeString(),
                'current_time' => $currentTime->toDateTimeString(),
                'total_seconds_since_affixing' => $totalSeconds,
                'grace_period_seconds' => $gracePeriodSeconds,
                'seconds_into_overstay' => $overstaySeconds,
                'overstay_days_before' => $oldDays,
                'overstay_days_after' => $overstayDays,
                'changed' => ($oldDays !== $overstayDays ? true : false)
            ]);
        } catch (\Exception $e) {
            Log::error('Error in recalculateOverstayDays', [
                'device_retrieval_id' => $deviceRetrieval->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - allow save to continue even if calculation fails
        }
    }

    /**
     * Update the overstay amount based on overstay days
     * This is REACTIVE: only updates when overstay_days changes
     * Amount = overstay_days × 1000 (penalty per day)
     */
    protected function updateOverstayAmount(DeviceRetrieval $deviceRetrieval, bool $forceSave = false): void
    {
        try {
            // Skip if overstay_days is not set
            if (is_null($deviceRetrieval->overstay_days)) {
                Log::debug('Skipping amount update - overstay_days is null', [
                    'device_retrieval_id' => $deviceRetrieval->id
                ]);
                return;
            }
            
            // Calculate the amount (D1,000 per day)
            $amount = $deviceRetrieval->overstay_days * 1000;
            
            // Store old value for logging
            $oldAmount = $deviceRetrieval->overstay_amount;
            
            // Debug log before update
            Log::debug('Updating overstay amount', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'overstay_days' => $deviceRetrieval->overstay_days,
                'current_amount' => $deviceRetrieval->overstay_amount,
                'new_amount' => $amount,
                'force_save' => $forceSave
            ]);
            
            // Only update if the amount has changed or we're forcing the save
            if ($amount != $deviceRetrieval->overstay_amount || $forceSave) {
                $deviceRetrieval->overstay_amount = $amount;
                
                // Prevent infinite loop by saving without events if force-saving
                if ($forceSave) {
                    $deviceRetrieval->saveQuietly();
                    Log::info('Force-saved overstay amount', [
                        'device_retrieval_id' => $deviceRetrieval->id,
                        'amount_before' => $oldAmount,
                        'amount_after' => $amount,
                        'days' => $deviceRetrieval->overstay_days
                    ]);
                }
            } else {
                Log::debug('Skipping update - amount already up to date', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'amount' => $amount
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in updateOverstayAmount', [
                'device_retrieval_id' => $deviceRetrieval->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to allow the observer to handle it
        }
    }
}
