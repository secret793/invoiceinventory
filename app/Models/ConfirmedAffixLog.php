<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfirmedAffixLog extends Model
{
    use HasFactory;

    protected $table = 'confirmed_affix_logs';

    protected $fillable = [
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
        'allocation_point_id',
        'affixed_by',
    ];

    protected $casts = [
        'manifest_date' => 'date',
        'affixing_date' => 'datetime',
    ];

    // Relationships
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function longRoute()
    {
        return $this->belongsTo(LongRoute::class, 'long_route_id');
    }

    public function allocationPoint()
    {
        return $this->belongsTo(AllocationPoint::class);
    }

    public function affixedBy()
    {
        return $this->belongsTo(User::class, 'affixed_by');
    }
}
