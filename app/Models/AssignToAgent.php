<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignToAgent extends Model
{
    use HasFactory;

    protected $table = 'assign_to_agents';

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
        'route_id',
        'long_route_id',
        'manifest_date',
        'agency',
        'agent_contact',
        'truck_number',
        'driver_name',
        'allocation_point_id'  // Add this field
    ];

    protected $casts = [
        'date' => 'datetime',
        'manifest_date' => 'date',
    ];

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

    public function allocationPoint(): BelongsTo
    {
        return $this->belongsTo(AllocationPoint::class);
    }

    public function userHasPermission(User $user, string $type = 'view'): bool
    {
        return $user->hasPermissionTo($this->getPermissionName($type)) || $user->hasRole('Data Entry Officer');
    }
}