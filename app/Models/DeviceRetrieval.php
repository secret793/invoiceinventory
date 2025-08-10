<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasNotifications;
use App\Models\Route;
use App\Models\Device;
use App\Models\LongRoute;
use App\Models\DistributionPoint;
use App\Models\AllocationPoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DeviceRetrieval extends Model
{
    use HasFactory, HasNotifications;

    protected $table = 'device_retrievals';

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
        'transfer_type',
        'transfer_status',
        'transfer_date',
        'destination',
        'destination_id',
        'finance_approval_date',
        'finance_approved_by',
        'finance_notes',
    ];

    protected $casts = [
        'date' => 'datetime',
        'manifest_date' => 'datetime',
        'affixing_date' => 'datetime',
        'current_time' => 'datetime',
        'transfer_date' => 'datetime',
        'retrieval_status' => 'string',
        'overdue_hours' => 'integer',
        'overstay_days' => 'integer',
        'overstay_amount' => 'decimal:2',
        'finance_approval_date' => 'datetime',
    ];

    protected static function booted()
    {
        // Observer removed - now handled by scheduled command

        static::addGlobalScope('destination-access', function (Builder $builder) {
            $user = auth()->user();

            if (!$user) {
                return;
            }

            // Super Admin, Warehouse Manager, and Data Entry Officer can see all device retrievals
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

                    // Filter query to only include device retrievals with matching destinations
                    $builder->where(function ($query) use ($possibleDestinations) {
                        // Check against the destination column (string)
                        $query->whereIn('destination', $possibleDestinations)
                            // Also check against the destination relationship if it exists
                            ->orWhereHas('destination', function ($subQuery) use ($possibleDestinations) {
                                $subQuery->whereIn('name', $possibleDestinations);
                            });
                    });
                } else {
                    // If no destination permissions, show nothing
                    $builder->where('id', 0);
                }

                return;
            }

            // Default: show nothing for other roles
            $builder->where('id', 0);
        });

        // Add creating/saving event to validate destination relationship
        static::creating(function ($deviceRetrieval) {
            if ($deviceRetrieval->destination_id) {
                $destination = Destination::find($deviceRetrieval->destination_id);
                if (!$destination) {
                    throw new \Exception('Invalid destination_id provided');
                }
            }
        });
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
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
        return $this->belongsTo(AllocationPoint::class, 'allocation_point_id');
    }

    /**
     * Get the monitoring record associated with this device retrieval.
     */
    public function monitoring()
    {
        return $this->hasOne(Monitoring::class, 'device_id', 'device_id');
    }

    /**
     * Get all monitoring records for this device
     */
    public function monitorings()
    {
        return $this->hasMany(Monitoring::class, 'device_id', 'device_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'destination_id')
            ->withDefault(['name' => 'Unknown']); // Provides fallback if relation is missing
    }

    /**
     * Get the user who approved the finance request
     */
    public function financeApprovedBy()
    {
        return $this->belongsTo(User::class, 'finance_approved_by');
    }

    /**
     * Get the invoices for the device retrieval
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if the device can be retrieved based on overdue status and payment
     *
     * @return bool
     */
    public function canBeRetrieved(): bool
    {
        // Device can be retrieved if it doesn't have overdue fees (overstay_days < 2)
        // or if payment has been completed (payment_status === 'PD')
        return $this->overstay_days < 2 || $this->payment_status === 'PD';
    }
}




