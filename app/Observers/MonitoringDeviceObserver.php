<?php

namespace App\Observers;

use App\Models\DeviceRetrieval;
use App\Models\Monitoring;
use Illuminate\Support\Facades\Log;

class MonitoringDeviceObserver
{
    /**
     * Like a magical mirror that copies device retrieval info to monitoring! ✨
     */
    public function created(DeviceRetrieval $deviceRetrieval): void
    {
        try {
            // Create a new monitoring record (like making a copy of our toy!)
            Monitoring::create([
                'date' => $deviceRetrieval->date,
                'current_date' => now(),
                'device_id' => $deviceRetrieval->device_id,
                'boe' => $deviceRetrieval->boe,
                'sad_number' => $deviceRetrieval->sad_number,
                'vehicle_number' => $deviceRetrieval->vehicle_number,
                'regime' => $deviceRetrieval->regime,
                'route_id' => $deviceRetrieval->route_id,
                'long_route_id' => $deviceRetrieval->long_route_id,
                'manifest_date' => $deviceRetrieval->manifest_date,
                'destination' => $deviceRetrieval->destination,
                'agency' => $deviceRetrieval->agency,
                'agent_contact' => $deviceRetrieval->agent_contact,
                'truck_number' => $deviceRetrieval->truck_number,
                'driver_name' => $deviceRetrieval->driver_name,
                'affixing_date' => $deviceRetrieval->affixing_date,
                'status' => 'PENDING',
                'overdue_hours' => 0
            ]);

            Log::info('Successfully created monitoring record for device: ' . $deviceRetrieval->device_id);
        } catch (\Exception $e) {
            Log::error('Failed to create monitoring record: ' . $e->getMessage());
        }
    }

    /**
     * When our device retrieval changes, update monitoring too! 🔄
     */
    public function updated(DeviceRetrieval $deviceRetrieval): void
    {
        try {
            // Find the matching monitoring record (like finding a matching toy!)
            $monitoring = Monitoring::where('device_id', $deviceRetrieval->device_id)
                ->latest()
                ->first();

            if ($monitoring) {
                // Update it with new information (like giving our toy new stickers!)
                $monitoring->update([
                    'date' => $deviceRetrieval->date,
                    'current_date' => now(),
                    'boe' => $deviceRetrieval->boe,
                    'sad_number' => $deviceRetrieval->sad_number,
                    'vehicle_number' => $deviceRetrieval->vehicle_number,
                    'regime' => $deviceRetrieval->regime,
                    'route_id' => $deviceRetrieval->route_id,
                    'long_route_id' => $deviceRetrieval->long_route_id,
                    'manifest_date' => $deviceRetrieval->manifest_date,
                    'destination' => $deviceRetrieval->destination,
                    'agency' => $deviceRetrieval->agency,
                    'agent_contact' => $deviceRetrieval->agent_contact,
                    'truck_number' => $deviceRetrieval->truck_number,
                    'driver_name' => $deviceRetrieval->driver_name,
                    'affixing_date' => $deviceRetrieval->affixing_date
                ]);

                Log::info('Successfully updated monitoring record for device: ' . $deviceRetrieval->device_id);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update monitoring record: ' . $e->getMessage());
        }
    }

    /**
     * When we remove a device retrieval, clean up monitoring too! 🧹
     */
    public function deleted(DeviceRetrieval $deviceRetrieval): void
    {
        try {
            // Find and remove the matching monitoring record (like putting away a toy)
            Monitoring::where('device_id', $deviceRetrieval->device_id)
                ->latest()
                ->first()
                ?->delete();

            Log::info('Successfully deleted monitoring record for device: ' . $deviceRetrieval->device_id);
        } catch (\Exception $e) {
            Log::error('Failed to delete monitoring record: ' . $e->getMessage());
        }
    }
}
