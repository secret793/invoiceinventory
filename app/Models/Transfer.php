<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'device_serial',
        'from_location',
        'to_location',
        'to_allocation_point_id',
        'status',
        'transfer_type',
        'quantity',
        'received',
        'original_status',
        'distribution_point_status',
        'original_allocation_point_id',
        'cancellation_reason',
        'cancelled_at',
        'transfer_status',
    ];

    const STATUS_PENDING = 'PENDING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_REJECTED = 'REJECTED';

    protected $casts = [
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transfer) {
            if (!$transfer->device_serial && $transfer->device_id) {
                $device = Device::find($transfer->device_id);
                if ($device) {
                    $transfer->device_serial = $device->device_id;
                }
            }
            
            // Set default transfer status if not set
            if (!$transfer->transfer_status) {
                $transfer->transfer_status = self::STATUS_PENDING;
            }
        });
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id', 'id');
    }
    
    public function fromLocation()
    {
        return $this->belongsTo(DistributionPoint::class, 'from_location');
    }
    
    public function toLocation()
    {
        return $this->belongsTo(DistributionPoint::class, 'to_location');
    }
    
    public function allocationPoint()
    {
        return $this->belongsTo(AllocationPoint::class, 'to_allocation_point_id');
    }
    
    public function validateStock($deviceId, $fromLocationId, $quantity) {
        $device = Device::find($deviceId);
        return $device->getCurrentStock($fromLocationId) >= $quantity;
    }

    public function isCancellable(): bool
    {
        return $this->transfer_status === self::STATUS_PENDING;
    }

    public function cancel(string $reason): void
    {
        $this->update([
            'transfer_status' => self::STATUS_CANCELLED,
            'status' => self::STATUS_CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);
    }
}
