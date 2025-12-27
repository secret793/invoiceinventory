<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'device_type',
        'type',
        'serial_number',
        'batch_number',
        'date_received',
        'status',
        'distribution_point_id',
        'allocation_point_id',
        'user_id',
        'sim_number',
        'sim_operator',
        'cancellation_reason',
        'cancelled_at',
        'notes'
    ];

    protected $casts = [
        'date_received' => 'date',
        'cancelled_at' => 'datetime'
    ];

    // Relationships
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function distributionPoint()
    {
        return $this->belongsTo(DistributionPoint::class);
    }

    public function allocationPoint()
    {
        return $this->belongsTo(AllocationPoint::class);
    }
}



