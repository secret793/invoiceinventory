<?php

namespace App\Observers;

use App\Models\Destination;
use App\Models\DeviceRetrieval;
use App\Models\Regime; // Add this line
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DestinationObserver
{
    /**
     * Handle the Destination "created" event.
     */
    public function created(Destination $destination): void
    {
        $this->createPermissions($destination->name);
    }

    /**
     * Handle the Destination "updated" event.
     */
    public function updated(Destination $destination): void
    {
        if ($destination->isDirty('name')) {
            $this->updatePermissions(
                $destination->getOriginal('name'),
                $destination->name
            );
        }
    }

    /**
     * Handle the Destination "deleted" event.
     */
    public function deleted(Destination $destination): void
    {
        $this->deletePermissions($destination->name);
    }

    /**
     * Handle the Destination "retrieved" event.
     */
    public function retrieved(Destination $destination)
    {
        try {
            // Find all DeviceRetrieval records with this destination
            $deviceRetrievals = DeviceRetrieval::where('destination', $destination->name)->get();

            foreach ($deviceRetrievals as $deviceRetrieval) {
                // Find the corresponding Regime
                $regime = Regime::find($deviceRetrieval->regime);

                if ($regime) {
                    // Determine the destination based on the regime
                    switch (strtolower($regime->name)) {
                        case 'warehouse':
                            $deviceRetrieval->destination = 'Ghana';
                            break;
                        case 'transit':
                            $deviceRetrieval->destination = 'Soma';
                            break;
                        default:
                            $deviceRetrieval->destination = 'Unknown';
                            break;
                    }

                    // Save the updated destination
                    $deviceRetrieval->save();

                    // Log the update for debugging
                    Log::info('DeviceRetrieval destination updated based on regime', [
                        'device_id' => $deviceRetrieval->device_id,
                        'regime' => $regime->name,
                        'destination' => $deviceRetrieval->destination,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error updating DeviceRetrieval destination based on regime', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Create permissions for a destination
     */
    private function createPermissions(string $name): void
    {
        $slug = Str::slug($name);
        
        $permissions = [
            // Basic permissions
            'view_destination_' . $slug,
            'edit_destination_' . $slug,
            'delete_destination_' . $slug,
            
            // Device management permissions
            'manage_devices_' . $slug,
            'assign_devices_' . $slug,
            'track_devices_' . $slug,
            'view_devices_' . $slug,
            
            // Route specific permissions
            'manage_routes_' . $slug,
            'create_routes_' . $slug,
            'edit_routes_' . $slug,
            'view_routes_' . $slug
        ];

        try {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(
                    ['name' => $permission],
                    ['guard_name' => 'web']
                );
            }
            Log::info("Created permissions for destination: $name");
        } catch (\Exception $e) {
            Log::error("Failed to create permissions for destination: $name", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update permissions when destination name changes
     */
    private function updatePermissions(string $oldName, string $newName): void
    {
        $permissionPrefixes = [
            'view_destination_',
            'edit_destination_',
            'delete_destination_',
            'manage_devices_',
            'assign_devices_',
            'track_devices_',
            'view_devices_',
            'manage_routes_',
            'create_routes_',
            'edit_routes_',
            'view_routes_'
        ];

        try {
            foreach ($permissionPrefixes as $prefix) {
                $oldPermission = Permission::where('name', $prefix . Str::slug($oldName))->first();
                if ($oldPermission) {
                    $oldPermission->update(['name' => $prefix . Str::slug($newName)]);
                }
            }
            Log::info("Updated permissions for destination from: $oldName to: $newName");
        } catch (\Exception $e) {
            Log::error("Failed to update permissions for destination", [
                'from' => $oldName,
                'to' => $newName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete permissions for a destination
     */
    private function deletePermissions(string $name): void
    {
        $permissionPrefixes = [
            'view_destination_',
            'edit_destination_',
            'delete_destination_',
            'manage_devices_',
            'assign_devices_',
            'track_devices_',
            'view_devices_',
            'manage_routes_',
            'create_routes_',
            'edit_routes_',
            'view_routes_'
        ];

        try {
            $permissions = array_map(function ($prefix) use ($name) {
                return $prefix . Str::slug($name);
            }, $permissionPrefixes);

            Permission::whereIn('name', $permissions)->delete();
            Log::info("Deleted permissions for destination: $name");
        } catch (\Exception $e) {
            Log::error("Failed to delete permissions for destination: $name", [
                'error' => $e->getMessage()
            ]);
        }
    }
}