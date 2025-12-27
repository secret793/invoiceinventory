<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'date',
        'consignment_nature',
        'sad_number',
        'route_id',
        'long_route_id',
        'allocation_point_id',
        'destination_id',
        'billing_unit',
        'moving_trucks',
        'base_unit_charge_usd',
        'exchange_rate_used',
        'unit_charge_gmd',
        'total_charge_gmd',
        'agent_name',
        'agent_phone',
        'consignee_details',
        'shipper_details',
        'description_of_goods',
        'used',
        'created_by',
        'generated_by_user',
    ];

    protected $casts = [
        'date' => 'datetime',
        'base_unit_charge_usd' => 'decimal:2',
        'exchange_rate_used' => 'decimal:4',
        'unit_charge_gmd' => 'decimal:2',
        'total_charge_gmd' => 'decimal:2',
        'moving_trucks' => 'integer',
        'used' => 'integer',
    ];

    // Relationships
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

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function generatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user');
    }

    public function assignToAgents(): HasMany
    {
        return $this->hasMany(AssignToAgent::class);
    }

    public function dispatchFinanceRecords(): HasMany
    {
        return $this->hasMany(DispatchFinanceRecord::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('used', '>', 0);
    }

    public function scopeByConsignmentNature($query, $nature)
    {
        return $query->where('consignment_nature', $nature);
    }

    public function scopeByRoute($query, $routeId)
    {
        return $query->where('route_id', $routeId);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Methods
    /**
     * Generate unique receipt number
     * Format: REC-YYYYMMDD-XXXX
     */
    public static function generateReceiptNumber(): string
    {
        $prefix = 'REC-' . now()->format('Ymd');
        $sequence = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$sequence}";
    }

    /**
     * Get all available receipts (used > 0)
     */
    public static function getAvailableReceipts()
    {
        return self::available()->get();
    }

    /**
     * Check if receipt can be used
     */
    public function canBeUsed(): bool
    {
        return $this->used > 0;
    }

    /**
     * Decrement usage count
     */
    public function decrementUsage(int $amount = 1): void
    {
        if ($this->used <= 0) {
            throw new \Exception("Receipt {$this->receipt_number} is fully used");
        }
        
        $this->decrement('used', $amount);
    }

    /**
     * Calculate total charge in GMD
     */
    public function calculateTotalCharge(): float
    {
        return ($this->unit_charge_gmd ?? 0) * ($this->moving_trucks ?? 0);
    }

    /**
     * Get badge color for usage status
     */
    public function getUsageBadgeColor(): string
    {
        if ($this->used == 0) {
            return 'danger'; // red - depleted
        } elseif ($this->used <= ($this->moving_trucks * 0.25)) {
            return 'warning'; // yellow - low
        }
        return 'success'; // green - available
    }

    /**
     * Get display text for usage
     */
    public function getUsageDisplay(): string
    {
        return "{$this->used}/{$this->moving_trucks}";
    }
}
