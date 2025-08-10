<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
