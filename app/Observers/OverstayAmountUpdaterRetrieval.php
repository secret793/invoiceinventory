<?php

namespace App\Observers;

use App\Models\DeviceRetrieval;

class OverstayAmountUpdaterRetrieval
{
    /**
     * Handle the DeviceRetrieval "saving" event.
     */
    public function saving(DeviceRetrieval $deviceRetrieval): void
    {
        // Only update if overstay_days is being changed
        if ($deviceRetrieval->isDirty('overstay_days')) {
            $this->updateOverstayAmount($deviceRetrieval);
        }
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
     * Update the overstay amount based on overstay days
     */
    protected function updateOverstayAmount(DeviceRetrieval $deviceRetrieval, bool $forceSave = false): void
    {
        try {
            // Skip if overstay_days is not set
            if (is_null($deviceRetrieval->overstay_days)) {
                \Log::debug('Skipping amount update - overstay_days is null', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'monitoring_id' => $deviceRetrieval->monitoring_id
                ]);
                return;
            }
            
            // Calculate the amount (1000 GHS per day)
            $amount = $deviceRetrieval->overstay_days * 1000;
            
            // Debug log before update
            \Log::debug('Updating overstay amount', [
                'device_retrieval_id' => $deviceRetrieval->id,
                'monitoring_id' => $deviceRetrieval->monitoring_id,
                'current_days' => $deviceRetrieval->overstay_days,
                'current_amount' => $deviceRetrieval->overstay_amount,
                'new_amount' => $amount,
                'force_save' => $forceSave
            ]);
            
            // Only update if the amount has changed or we're forcing the save
            if ($amount != $deviceRetrieval->overstay_amount || $forceSave) {
                $deviceRetrieval->overstay_amount = $amount;
                
                // Prevent infinite loop by saving without events
                if ($forceSave) {
                    $deviceRetrieval->saveQuietly();
                    \Log::info('Force-saved overstay amount', [
                        'device_retrieval_id' => $deviceRetrieval->id,
                        'amount' => $amount,
                        'days' => $deviceRetrieval->overstay_days
                    ]);
                }
            } else {
                \Log::debug('Skipping update - amount already up to date', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'amount' => $amount
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error in updateOverstayAmount', [
                'device_retrieval_id' => $deviceRetrieval->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to allow the observer to handle it
        }
    }
}
