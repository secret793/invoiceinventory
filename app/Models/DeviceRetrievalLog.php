<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DeviceRetrievalLog extends Model
{
    use HasFactory;

    protected $table = 'device_retrieval_logs';

    protected $fillable = [
        'date',
        'device_id',
        'boe',
        'sad_number',
        'vehicle_number',
        'regime',
        'destination',
        'destination_id',
        'current_time',
        'route_id',
        'long_route_id',
        'manifest_date',
        'note',
        'agency',
        'agent_contact',
        'truck_number',
        'driver_name',
        'affixing_date',
        'status',
        'retrieval_status',
        'overdue_hours',
        'overstay_days',
        'overstay_amount',
        'payment_status',
        'receipt_number',
        'distribution_point_id',
        'allocation_point_id',
        'retrieved_by',
        'retrieval_date',
        'action_type',
    ];

    protected $casts = [
        'date' => 'date',
        'current_time' => 'datetime',
        'manifest_date' => 'date',
        'affixing_date' => 'date',
        'retrieval_date' => 'datetime',
        'overstay_amount' => 'decimal:2',
        'overdue_hours' => 'integer',
        'overstay_days' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope('destination-access', function (Builder $builder) {
            $user = auth()->user();

            if (!$user) {
                return;
            }

            // Super Admin, Warehouse Manager, and Data Entry Officer can see all device retrieval logs
            if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
                return;
            }

            // For Finance Officer, only show devices with overstay_days >= 2
            if ($user->hasRole('Finance Officer')) {
                $builder->where('overstay_days', '>=', 2);
                return;
            }

            // For Retrieval Officer, filter by destination permissions
            if ($user->hasRole('Retrieval Officer')) {
                // Get all permissions that start with 'view_destination_'
                $permissions = $user->permissions->pluck('name')->toArray();

                $destinationPermissions = array_filter($permissions, function ($permission) {
                    return Str::startsWith($permission, 'view_destination_');
                });

                $destinationSlugs = array_map(function ($permission) {
                    return Str::after($permission, 'view_destination_');
                }, $destinationPermissions);

                // If user has destination permissions, filter by those
                if (!empty($destinationSlugs)) {
                    // Convert permission slugs to possible destination names
                    $possibleDestinations = [];

                    foreach ($destinationSlugs as $slug) {
                        // Add variations of the destination name to check against the database
                        $possibleDestinations[] = $slug;                     // Original slug
                        $possibleDestinations[] = ucfirst($slug);            // First letter capitalized
                        $possibleDestinations[] = strtoupper($slug);         // All uppercase
                        $possibleDestinations[] = Str::title($slug);         // Title case
                        $possibleDestinations[] = Str::title(str_replace('-', ' ', $slug));  // With spaces
                    }

                    // Remove duplicates
                    $possibleDestinations = array_unique($possibleDestinations);

                    // Filter query to only include device retrieval logs with matching destinations
                    $builder->whereIn('destination', $possibleDestinations);
                } else {
                    // If no destination permissions, show nothing
                    $builder->where('id', 0);
                }

                return;
            }

            // Default: show nothing for other roles
            $builder->where('id', 0);
        });
    }

    // Relationships
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function longRoute(): BelongsTo
    {
        return $this->belongsTo(LongRoute::class);
    }

    public function distributionPoint(): BelongsTo
    {
        return $this->belongsTo(DistributionPoint::class);
    }

    public function allocationPoint(): BelongsTo
    {
        return $this->belongsTo(AllocationPoint::class);
    }

    public function retrievedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retrieved_by');
    }
}
