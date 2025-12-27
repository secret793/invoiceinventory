<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Models\Route;

class DeviceRetrievalReport2 extends Model
{
    use HasFactory;

    protected $table = 'device_retrieval_report_2_logs';

    protected $fillable = [
        'device_id',
        'device_full_id',
        'boe',
        'vehicle_number',
        'regime',
        'destination',
        'allocation_point_id',
        'retrieval_status',
        'action_type',
        'retrieved_by',
        'returned_by',
        'retrieval_date',
        'returned_at',
        'overstay_days',
        'overstay_amount',
        'date',
        'route_id',
        'long_route_id',
    ];

    protected $casts = [
        'retrieval_date' => 'datetime',
        'returned_at' => 'datetime',
        'date' => 'date',
        'overstay_amount' => 'decimal:2',
        'overstay_days' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope('destination-access', function (Builder $builder) {
            $user = auth()->user();

            if (!$user) {
                return;
            }

            // Super Admin and Warehouse Manager can see all records
            if ($user->hasRole(['Super Admin', 'Warehouse Manager'])) {
                return;
            }

            // For individual users, filter by destination permissions only
            if ($user->hasRole(['Retrieval Officer', 'Data Entry Officer'])) {
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

                    // Filter query to only include records with matching destinations
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

    public function allocationPoint(): BelongsTo
    {
        return $this->belongsTo(AllocationPoint::class);
    }

    public function retrievedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retrieved_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function longRoute(): BelongsTo
    {
        return $this->belongsTo(LongRoute::class, 'long_route_id');
    }
}
