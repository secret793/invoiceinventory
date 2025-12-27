<?php

namespace App\Services;

use App\Models\Monitoring;
use App\Models\DeviceRetrieval;

class OverdueCalculationService
{

    /**
     * Calculate overdue days based on overdue hours and route type
     * 
     * @param int $overdueHours Total hours since affixing
     * @param bool $isLongRoute Whether this is a long route
     * @return int Number of full days overdue (after grace period)
     */
    public function calculateOverdueDays(int $overdueHours, bool $isLongRoute): int
    {
        if ($overdueHours <= 0) {
            \Log::debug('No overdue days - overdue hours is zero or negative', [
                'overdue_hours' => $overdueHours,
                'is_long_route' => $isLongRoute
            ]);
            return 0;
        }
        
        $gracePeriod = $this->getGracePeriod($isLongRoute);
        
        // Calculate total days (including partial days)
        $totalDays = $overdueHours / 24;
        
        // Calculate grace period in days
        $graceDays = $gracePeriod / 24;
        
        // Calculate overdue days after grace period
        $overdueDays = $totalDays - $graceDays;
        
        // Round down to nearest whole day and ensure it's not negative
        $result = max(0, (int)floor($overdueDays));
        
        \Log::debug('Calculated overdue days', [
            'overdue_hours' => $overdueHours,
            'is_long_route' => $isLongRoute,
            'grace_period_hours' => $gracePeriod,
            'grace_days' => $graceDays,
            'total_days' => $totalDays,
            'calculated_days' => $overdueDays,
            'result_days' => $result
        ]);
        
        return $result;
    }

    /**
     * Calculate overdue amount based on overdue days
     * 
     * @param int $overdueDays
     * @return float
     */
    public function calculateOverdueAmount(int $overdueDays): float
    {
        if ($overdueDays <= 0) {
            return 0.0;
        }
        // Calculate amount as days * 1000
        $amount = $overdueDays * 1000.0;
        // Return formatted to 2 decimal places
        return round($amount, 2);
    }

    /**
     * Get grace period in hours based on route type
     * 
     * @param bool $isLongRoute
     * @return int
     */
    public function getGracePeriod(bool $isLongRoute): int
    {
        return $isLongRoute ? 48 : 24;
    }

    /**
     * Directly update DeviceRetrieval records based on Monitoring data
     * This is a more reliable approach than syncing
     */
    public function updateRelatedDeviceRetrievals(int $monitoringId): bool
    {
        try {
            \Log::info("Starting updateRelatedDeviceRetrievals for monitoring ID: " . $monitoringId);
            
            // Get the monitoring record with its device_retrieval
            $monitoring = \App\Models\Monitoring::with('deviceRetrieval')
                ->find($monitoringId);
                
            if (!$monitoring) {
                throw new \Exception("Monitoring record not found with ID: " . $monitoringId);
            }
            
            // Log detailed monitoring information
            \Log::info("Processing monitoring record", [
                'monitoring_id' => $monitoring->id,
                'device_id' => $monitoring->device_id,
                'long_route_id' => $monitoring->long_route_id,
                'route_id' => $monitoring->route_id,
                'is_long_route' => (bool)$monitoring->long_route_id,
                'affixing_date' => $monitoring->affixing_date,
                'overstay_days' => $monitoring->overstay_days,
                'overstay_amount' => $monitoring->overstay_amount,
                'current_time' => now()
            ]);
            
            // Try to find existing device_retrieval by monitoring_id or device_id
            $deviceRetrieval = \App\Models\DeviceRetrieval::where('monitoring_id', $monitoring->id)
                ->orWhere('device_id', $monitoring->device_id)
                ->first();
            
            if ($deviceRetrieval) {
                \Log::info("Found existing DeviceRetrieval record with ID: " . $deviceRetrieval->id);
                
                // Ensure monitoring_id is set
                if (!$deviceRetrieval->monitoring_id) {
                    $deviceRetrieval->monitoring_id = $monitoring->id;
                    $deviceRetrieval->save();
                    \Log::info("Updated monitoring_id on existing DeviceRetrieval record");
                }
            } else {
                \Log::info("No existing DeviceRetrieval found, creating new one...");
                
                // Create new DeviceRetrieval
                $deviceRetrieval = new \App\Models\DeviceRetrieval([
                    'device_id' => $monitoring->device_id,
                    'monitoring_id' => $monitoring->id,
                    'date' => now(),
                    'boe_number' => $monitoring->boe ?? null,
                    'vehicle_number' => $monitoring->vehicle_number,
                    'regime' => $monitoring->regime,
                    'destination' => $monitoring->destination,
                    'agency' => $monitoring->agency,
                    'agent_contact' => $monitoring->agent_contact,
                    'truck_number' => $monitoring->truck_number,
                    'driver_name' => $monitoring->driver_name,
                    'affixing_date' => $monitoring->affixing_date,
                    'status' => $monitoring->status ?? 'active',
                    'retrieval_status' => $monitoring->retrieval_status ?? 'PENDING',
                ]);
                
                if (!$deviceRetrieval->save()) {
                    throw new \Exception('Failed to create new DeviceRetrieval record');
                }
                \Log::info("Created new DeviceRetrieval record with ID: " . $deviceRetrieval->id);
            }
            
            // Get overstay days from monitoring and calculate amount
            $overstayDays = (int)($monitoring->overstay_days ?? 0);
            $overstayAmount = $this->calculateOverdueAmount($overstayDays);
            
            // Update the DeviceRetrieval record directly in the database
            $updateData = [
                'overstay_days' => $overstayDays,
                'overstay_amount' => $overstayAmount,
                'updated_at' => now(),
            ];
            
            \Log::info("Updating DeviceRetrieval with data: ", $updateData);
            
            // Use DB facade to bypass model events
            $updateResult = \DB::table('device_retrievals')
                ->where('id', $deviceRetrieval->id)
                ->update($updateData);
                
            if ($updateResult === 0) {
                throw new \Exception('Failed to update DeviceRetrieval record - no rows affected');
            }
            
            // Refresh the model to reflect changes
            $deviceRetrieval->refresh();
            
            \Log::info("Successfully updated DeviceRetrieval record");
            return true;
            
        } catch (\Exception $e) {
            $errorMsg = 'Failed to update DeviceRetrieval for monitoring ID ' . ($monitoringId ?? 'unknown') . ': ' . $e->getMessage();
            \Log::error($errorMsg);
            \Log::error($e->getTraceAsString());
            return false;
        }
    }

    /**
     * Sync overstay data from Monitoring to DeviceRetrieval
     * 
     * @param \App\Models\Monitoring $monitoring
     * @return bool True if sync was successful, false otherwise
     */
    public function syncToDeviceRetrieval($monitoring): bool
    {
        try {
            \Log::debug('Starting syncToDeviceRetrieval', [
                'monitoring_id' => $monitoring->id,
                'device_id' => $monitoring->device_id,
                'current_overstay_days' => $monitoring->overstay_days,
                'current_overstay_amount' => $monitoring->overstay_amount
            ]);

            // Find or create the device retrieval record
            $deviceRetrieval = DeviceRetrieval::firstOrNew(
                ['monitoring_id' => $monitoring->id],
                [
                    'device_id' => $monitoring->device_id,
                    'date' => now(),
                    'boe_number' => $monitoring->boe ?? null,
                    'vehicle_number' => $monitoring->vehicle_number,
                    'regime' => $monitoring->regime,
                    'destination' => $monitoring->destination,
                    'agency' => $monitoring->agency,
                    'agent_contact' => $monitoring->agent_contact,
                    'truck_number' => $monitoring->truck_number,
                    'driver_name' => $monitoring->driver_name,
                    'affixing_date' => $monitoring->affixing_date,
                    'status' => $monitoring->status ?? 'active',
                    'retrieval_status' => $monitoring->retrieval_status ?? 'PENDING',
                ]
            );

            $isNew = !$deviceRetrieval->exists;
            \Log::debug($isNew ? 'Creating new DeviceRetrieval' : 'Updating existing DeviceRetrieval', [
                'device_retrieval_id' => $deviceRetrieval->id ?? 'new',
                'monitoring_id' => $monitoring->id
            ]);

            // If this is an existing record, ensure monitoring_id is set
            if (!$isNew && !$deviceRetrieval->monitoring_id) {
                \Log::debug('Setting missing monitoring_id on existing record', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'monitoring_id' => $monitoring->id
                ]);
                $deviceRetrieval->monitoring_id = $monitoring->id;
            }

            // Log before updating days
            if (!$isNew) {
                \Log::debug('Current DeviceRetrieval values before update', [
                    'current_days' => $deviceRetrieval->overstay_days,
                    'current_amount' => $deviceRetrieval->overstay_amount
                ]);
            }

            // Get overstay days from monitoring
            $overstayDays = (int)($monitoring->overstay_days ?? 0);
            $overstayAmount = $this->calculateOverdueAmount($overstayDays);
            
            // Save directly to database to bypass observers
            $result = \DB::table('device_retrievals')
                ->where('id', $deviceRetrieval->id)
                ->update([
                    'overstay_days' => $overstayDays,
                    'overstay_amount' => $overstayAmount,
                    'updated_at' => now()
                ]);
                
            // Refresh the model to reflect changes
            if ($result) {
                $deviceRetrieval->refresh();
            }
            
            // Log after save
            if ($result) {
                \Log::debug('DeviceRetrieval saved successfully', [
                    'device_retrieval_id' => $deviceRetrieval->id,
                    'monitoring_id' => $deviceRetrieval->monitoring_id,
                    'overstay_days' => $deviceRetrieval->overstay_days,
                    'overstay_amount' => $deviceRetrieval->overstay_amount
                ]);
            } else {
                \Log::error('Failed to save DeviceRetrieval', [
                    'monitoring_id' => $monitoring->id,
                    'device_retrieval_id' => $deviceRetrieval->id ?? 'new'
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            \Log::error('Failed to sync to DeviceRetrieval for monitoring ID ' . ($monitoring->id ?? 'unknown') . ': ' . $e->getMessage());
            return false;
        }
    }
}
