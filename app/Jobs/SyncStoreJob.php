<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Device;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $devices;

    /**
     * Create a new job instance.
     * @param Device|Collection $devices Single device or collection of devices
     */
    public function __construct($devices)
    {
        // Ensure we're working with a collection
        $this->devices = $devices instanceof Collection ? $devices : collect([$devices]);
    }

    /**
     * Execute the job.
     * Synchronizes devices with store records
     */
    public function handle(): void
    {
        DB::transaction(function () {
            // Convert any IDs to Device models and ensure we're working with strings
            $devices = $this->devices->map(function ($item) {
                if ($item instanceof Device) {
                    return $item;
                }
                if (is_numeric($item) || is_string($item)) {
                    return Device::where('id', $item)
                        ->orWhere('device_id', (string) $item)
                        ->first();
                }
                return null;
            })->filter();

            // Prepare store data with strict type handling
            $storeData = $devices->mapWithKeys(function ($device) {
                // Skip devices assigned to distribution points unless UNCONFIGURED
                if ($device->distribution_point_id !== null && $device->status !== 'UNCONFIGURED') {
                    return [];
                }

                // Ensure status is never null and device_id is string
                $status = $device->status ?? 'UNCONFIGURED';
                $deviceId = (string) $device->device_id;
                
                // If device is not configured, force UNCONFIGURED status
                if (!($device->configured ?? true)) {
                    $status = 'UNCONFIGURED';
                }

                return [
                    $deviceId => [
                        'device_id' => $deviceId,
                        'status' => $status,
                        'sim_number' => $status === 'UNCONFIGURED' ? null : (string) $device->sim_number,
                        'sim_operator' => $status === 'UNCONFIGURED' ? null : $device->sim_operator,
                        'device_type' => $device->device_type,
                        'batch_number' => $device->batch_number,
                        'date_received' => $device->date_received,
                        'user_id' => $device->user_id,
                        'is_configured' => $status !== 'UNCONFIGURED',
                    ]
                ];
            });

            try {
                // Update or create store records with explicit type casting
                if ($storeData->isNotEmpty()) {
                    Store::upsert(
                        $storeData->values()->toArray(),
                        ['device_id'],
                        array_keys($storeData->first())
                    );

                    // Double-check for any null statuses with explicit string casting
                    DB::table('stores')
                        ->whereIn('device_id', $storeData->keys()->map(fn($id) => (string) $id))
                        ->whereNull('status')
                        ->update([
                            'status' => 'UNCONFIGURED',
                            'updated_at' => now()
                        ]);
                }

                // Remove store records with explicit string casting
                Store::whereIn('device_id', $devices->pluck('device_id')->map(fn($id) => (string) $id))
                    ->whereNotIn('device_id', $storeData->keys()->map(fn($id) => (string) $id))
                    ->delete();

            } catch (\Exception $e) {
                \Log::error('Store sync failed: ' . $e->getMessage(), [
                    'devices' => $devices->pluck('device_id'),
                    'store_data' => $storeData
                ]);
                throw $e;
            }
        });
    }
}
