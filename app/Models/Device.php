<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_type',
        'device_id',
        'serial_number',
        'batch_number',
        'date_received',
        'status',
        'distribution_point_id',
        'sim_number',
        'sim_operator',
        'is_configured',
        'user_id',
        'allocation_point_id',
        'cancellation_reason',
        'cancelled_at',
        'notes'
    ];

    /**
     * Get the dispatch logs for the device.
     */
    public function dispatchLogs()
    {
        return $this->hasMany(DispatchLog::class);
    }
    
    /**
     * Get the confirmed affixed record associated with the device.
     */
    public function confirmedAffixed()
    {
        return $this->hasOne(ConfirmedAffixed::class, 'device_id', 'id');
    }

    protected $casts = [
        'is_configured' => 'boolean',
        'date_received' => 'date',
        'cancelled_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {
            if ($device->device_id && !$device->serial_number) {
                $device->serial_number = $device->device_id;
            }
        });

        static::created(function ($device) {
            // Create a corresponding store record when a device is created
            Store::create([
                'device_id' => $device->id,
                'device_type' => $device->device_type,
                'type' => $device->type,
                'serial_number' => $device->serial_number,
                'batch_number' => $device->batch_number,
                'date_received' => $device->date_received,
                'status' => $device->status,
                'distribution_point_id' => $device->distribution_point_id,
                'allocation_point_id' => $device->allocation_point_id,
                'user_id' => $device->user_id,
                'sim_number' => $device->sim_number,
                'sim_operator' => $device->sim_operator,
                'cancellation_reason' => $device->cancellation_reason,
                'cancelled_at' => $device->cancelled_at,
                'notes' => $device->notes
            ]);
        });

        static::updated(function ($device) {
            // Update the corresponding store record when a device is updated
            if ($device->store) {
                $device->store->update([
                    'device_type' => $device->device_type,
                    'type' => $device->type,
                    'serial_number' => $device->serial_number,
                    'batch_number' => $device->batch_number,
                    'date_received' => $device->date_received,
                    'status' => $device->status,
                    'distribution_point_id' => $device->distribution_point_id,
                    'allocation_point_id' => $device->allocation_point_id,
                    'user_id' => $device->user_id,
                    'sim_number' => $device->sim_number,
                    'sim_operator' => $device->sim_operator,
                    'cancellation_reason' => $device->cancellation_reason,
                    'cancelled_at' => $device->cancelled_at,
                    'notes' => $device->notes
                ]);
            }
        });
    }

    // Relationships
    public function store()
    {
        return $this->hasOne(Store::class);
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

    public function retrievals()
    {
        return $this->hasMany(DeviceRetrieval::class);
    }

    public function monitorings()
    {
        return $this->hasMany(Monitoring::class);
    }

    public function assignToAgents()
    {
        return $this->hasMany(AssignToAgent::class);
    }

    public function dataEntryAssignment(): HasOne
    {
        return $this->hasOne(DataEntryAssignment::class, 'device_id', 'id');
    }

    public function canBeTransferred(): bool
    {
        // Logic to determine if a device can be transferred
        // For example, check if it's not in a state that prevents transfer
        return !in_array($this->status, ['OFFLINE', 'LOST', 'DAMAGED']) &&
               $this->distribution_point_id === null &&
               $this->allocation_point_id === null;
    }

    public function hasActiveAssignment(): bool
    {
        // Check if the device is already assigned to a distribution or allocation point
        return $this->distribution_point_id !== null || $this->allocation_point_id !== null;
    }
}


