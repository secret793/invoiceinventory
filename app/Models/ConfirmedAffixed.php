<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\AllocationPoint;

class ConfirmedAffixed extends Model
{
    use HasFactory;

    protected $table = 'confirmed_affixeds';

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'date',
        'device_id',
        'boe',
        'sad_number',
        'vehicle_number',
        'regime',
        'destination',
        'destination_id',
        'route_id',
        'long_route_id',
        'manifest_date',
        'agency',
        'agent_contact',
        'truck_number',
        'driver_name',
        'affixing_date',
        'status',
        'allocation_point_id'
    ];

    protected $casts = [
        'date' => 'datetime',
        'manifest_date' => 'date',
        'affixing_date' => 'datetime'
    ];

    protected $appends = ['destination_name'];

    protected static function booted()
    {
        parent::booted();

        // Step 1: Add Global Scope for Allocation Point-Based Access Control
        static::addGlobalScope('allocation-point-access', function (Builder $builder) {
            $user = auth()->user();

            if (!$user) {
                Log::info('ConfirmedAffixed Global Scope: No authenticated user found');
                return;
            }

            Log::info('ConfirmedAffixed Global Scope: Processing for user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $user->roles->pluck('name')->toArray()
            ]);

            // Only Super Admin and Warehouse Manager can see all confirmed affixed records
            if ($user->hasRole(['Super Admin', 'Warehouse Manager'])) {
                Log::info('ConfirmedAffixed Global Scope: User has admin access, no filtering applied', [
                    'user_id' => $user->id,
                    'roles' => $user->roles->pluck('name')->toArray()
                ]);
                return;
            }

            // For Retrieval Officer and Affixing Officer, filter by allocation point permissions
            if ($user->hasRole(['Retrieval Officer', 'Affixing Officer'])) {
                Log::info('ConfirmedAffixed Global Scope: Processing Retrieval Officer/Affixing Officer access', [
                    'user_id' => $user->id,
                    'user_roles' => $user->roles->pluck('name')->toArray()
                ]);

                // Get all permissions starting with 'view_allocationpoint_'
                $permissions = $user->permissions->pluck('name')->toArray();
                $allocationPointPermissions = array_filter($permissions, function ($permission) {
                    return Str::startsWith($permission, 'view_allocationpoint_');
                });

                Log::info('ConfirmedAffixed Global Scope: Allocation point permissions found', [
                    'user_id' => $user->id,
                    'allocation_point_permissions' => $allocationPointPermissions
                ]);

                // Extract allocation point names from permissions
                $allocationPointNames = array_map(function ($permission) {
                    return Str::after($permission, 'view_allocationpoint_');
                }, $allocationPointPermissions);

                Log::info('ConfirmedAffixed Global Scope: Allocation point names extracted', [
                    'user_id' => $user->id,
                    'allocation_point_names' => $allocationPointNames
                ]);

                if (!empty($allocationPointNames)) {
                    Log::info('ConfirmedAffixed Global Scope: Looking up allocation points for names', [
                        'user_id' => $user->id,
                        'search_names' => $allocationPointNames
                    ]);

                    try {
                        // Get allocation points directly with raw query for reliability
                        $allocationPoints = collect(\DB::table('allocation_points')->get())
                            ->map(function($item) {
                                return (object)[
                                    'id' => $item->id,
                                    'name' => $item->name,
                                    'location' => $item->location,
                                    'status' => $item->status
                                ];
                            });

                        Log::debug('Allocation points loaded:', [
                            'count' => $allocationPoints->count(),
                            'points' => $allocationPoints->pluck('name', 'id')
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error fetching allocation points:', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $allocationPoints = collect();
                    }

                    // Find matching allocation points by name (case insensitive)
                    $matchingPoints = $allocationPoints->filter(function($point) use ($allocationPointNames) {
                        $pointName = strtolower($point->name);
                        foreach ($allocationPointNames as $searchName) {
                            if (str_contains($pointName, strtolower($searchName))) {
                                return true;
                            }
                        }
                        return false;
                    });

                    $allocationPointIds = $matchingPoints->pluck('id')->toArray();

                    // Remove duplicates
                    $allocationPointIds = array_unique($allocationPointIds);
                    
                    Log::info('ConfirmedAffixed Global Scope: Matching allocation point IDs found', [
                        'user_id' => $user->id,
                        'allocation_point_ids' => $allocationPointIds,
                        'matching_names' => $matchingPoints->pluck('name')
                    ]);

                    if (!empty($allocationPointIds)) {
                        $builder->whereIn('allocation_point_id', $allocationPointIds);
                        
                        Log::info('ConfirmedAffixed Global Scope: Applied allocation point filtering', [
                            'user_id' => $user->id,
                            'allocation_point_ids' => $allocationPointIds,
                            'matching_names' => $matchingPoints->pluck('name')
                        ]);
                    } else {
                        Log::warning('ConfirmedAffixed Global Scope: No matching allocation points found for permissions', [
                            'user_id' => $user->id,
                            'allocation_point_names' => $allocationPointNames,
                            'available_points' => $allocationPoints->pluck('name', 'id')
                        ]);
                        $builder->where('id', 0); // Show nothing if no matching allocation points
                    }
                } else {
                    Log::warning('ConfirmedAffixed Global Scope: User has no allocation point permissions', [
                        'user_id' => $user->id,
                        'user_roles' => $user->roles->pluck('name')->toArray()
                    ]);
                    $builder->where('id', 0); // Show nothing if no permissions
                }

                return;
            }

            // Default: show nothing for other roles
            Log::info('ConfirmedAffixed Global Scope: User has no recognized role, showing no records', [
                'user_id' => $user->id,
                'roles' => $user->roles->pluck('name')->toArray()
            ]);
            $builder->where('id', 0);
        });

        // Handle cleanup when a ConfirmedAffixed record is deleted
        static::deleting(function ($confirmedAffixed) {
            Log::info('ConfirmedAffixed: Deleting record', [
                'confirmed_affixed_id' => $confirmedAffixed->id,
                'device_id' => $confirmedAffixed->device_id,
                'status' => $confirmedAffixed->status
            ]);

            // Delete related assign_to_agents record if it exists
            DB::table('assign_to_agents')
                ->where('device_id', $confirmedAffixed->device_id)
                ->delete();

            // If the record is being deleted after affixing, ensure there's a device retrieval record
            if ($confirmedAffixed->status === 'AFFIXED' && !DeviceRetrieval::where('device_id', $confirmedAffixed->device_id)->exists()) {
                Log::info('ConfirmedAffixed: Creating DeviceRetrieval record for affixed device', [
                    'device_id' => $confirmedAffixed->device_id,
                    'destination' => $confirmedAffixed->destination
                ]);

                DeviceRetrieval::create([
                    'date' => now(),
                    'device_id' => $confirmedAffixed->device_id,
                    'boe' => $confirmedAffixed->boe,
                    'vehicle_number' => $confirmedAffixed->vehicle_number,
                    'regime' => $confirmedAffixed->regime,
                    'destination' => $confirmedAffixed->destination,
                    'route_id' => $confirmedAffixed->route_id,
                    'long_route_id' => $confirmedAffixed->long_route_id,
                    'manifest_date' => $confirmedAffixed->manifest_date,
                    'agency' => $confirmedAffixed->agency,
                    'agent_contact' => $confirmedAffixed->agent_contact,
                    'truck_number' => $confirmedAffixed->truck_number,
                    'driver_name' => $confirmedAffixed->driver_name,
                    'affixing_date' => $confirmedAffixed->affixing_date,
                    'allocation_point_id' => $confirmedAffixed->allocation_point_id,
                    'retrieval_status' => 'NOT_RETRIEVED',
                    'transfer_status' => 'pending'
                ]);
            }
        });
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function longRoute(): BelongsTo
    {
        return $this->belongsTo(LongRoute::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'destination_id')
            ->withDefault(['name' => 'Unknown']); // Provides fallback if relation is missing
    }

    /**
     * Get the allocation point that the device was transferred from.
     */
    public function allocationPoint(): BelongsTo
    {
        return $this->belongsTo(AllocationPoint::class, 'allocation_point_id');
    }

    public function getDestinationNameAttribute()
    {
        // If we have a destination_id and the relationship exists, use that
        if ($this->destination_id) {
            $destination = $this->destination()->first();
            return $destination ? $destination->name : null;
        }
        // Fall back to the string destination field for legacy data
        return $this->attributes['destination'] ?? null;
    }
}
