<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasNotifications;
use App\Traits\CalculatesOverstayAmount;
use App\Models\Route;
use App\Models\Device;
use App\Models\LongRoute;
use App\Models\DistributionPoint;
use App\Models\AllocationPoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DeviceRetrieval extends Model
{
    use HasFactory, HasNotifications, CalculatesOverstayAmount;

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
        'user_id',
        'finance_approval_date',
        'finance_approved_by',
        'finance_notes',
        'is_archived',
        'archived_at',
        'archive_reason',
    ];

    protected $casts = [
        'date' => 'datetime',
        'manifest_date' => 'datetime',
        'affixing_date' => 'datetime',
        'current_time' => 'datetime',
        'transfer_date' => 'datetime',
        'archived_at' => 'datetime',
        'retrieval_status' => 'string',
        'overdue_hours' => 'integer',
        'overstay_days' => 'integer',
        'overstay_amount' => 'decimal:2',
        'finance_approval_date' => 'datetime',
        'is_archived' => 'boolean',
    ];

    protected static function booted()
    {
        // Register the monitoring sync observer
        static::observe(\App\Observers\DeviceRetrievalMonitoringSyncObserver::class);

        static::addGlobalScope('destination-access', function (Builder $builder) {
            $user = auth()->user();

            if (!$user) {
                return;
            }

            // Super Admin, Warehouse Manager, and Data Entry Officer can see all device retrievals
            if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
                return;
            }

        // For Finance Officer, only show devices with overstay_days >= 1
        if ($user->hasRole('Finance Officer')) {
            $builder->where('overstay_days', '>=', 1)
                    ->where('retrieval_status', '!=', 'RETRIEVED');
            return;
        }

            // For Retrieval Officer and Read Only Tracker Officer, filter by destination permissions
            if ($user->hasRole(['Retrieval Officer', 'Read Only Tracker Officer'])) {
                // Get all permissions that start with 'view_destination_'
                $permissions = $user->getAllPermissions()->pluck('name')->toArray();

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
        return $this->belongsTo(AllocationPoint::class, 'allocation_point_id')
            ->withoutGlobalScope('user-allocation-points');
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
     * Override getAttribute to resolve the naming conflict between the 'destination'
     * string column and the 'destination' BelongsTo relationship. Laravel normally
     * returns the column value first, preventing ->destination->name from working.
     * When the relation is eager-loaded, we return the model instead.
     */
    public function getAttribute($key)
    {
        if ($key === 'destination' && $this->relationLoaded('destination')) {
            return $this->relations['destination'];
        }
        return parent::getAttribute($key);
    }

    /**
     * Override setAttribute so that writing a plain string to 'destination'
     * unloads the relation, ensuring observers and other string-expecting code
     * always get the raw column value back on subsequent getAttribute calls.
     */
    public function setAttribute($key, $value)
    {
        if ($key === 'destination' && !($value instanceof Destination)) {
            $this->unsetRelation('destination');
        }
        return parent::setAttribute($key, $value);
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
        // If device is waived, it can be retrieved regardless of overstay
        if ($this->isWaived()) {
            return true;
        }
        
        // If device has any overstay (>= 1 day), payment MUST be completed
        if ($this->overstay_days >= 1) {
            return $this->payment_status === 'PD';
        }
        
        // No overstay - can be retrieved
        return true;
    }

    /**
     * Get the waiver history for this device retrieval.
     */
    public function waiverHistory()
    {
        return $this->hasMany(WaiverHistory::class);
    }

    /**
     * Check if this device retrieval has been waived.
     */
    public function isWaived(): bool
    {
        return $this->waiverHistory()->exists();
    }

    /**
     * Get the latest waiver for this device.
     */
    public function getLatestWaiver()
    {
        return $this->waiverHistory()->latest()->first();
    }

    /**
     * Get the invoice associated with this device retrieval.
     */
    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'device_retrieval_id');
    }
}




