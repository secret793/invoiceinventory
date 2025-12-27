<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchFinanceRecord extends Model
{
    use HasFactory;

    protected $table = 'dispatch_finance_records';

    protected $fillable = [
        'receipt_id',
        'assigned_to_agent_id',
        'confirmed_affixed_id',
        'device_id',
        'dispatch_date',
        'total_amount_gmd',
        'status',
        'finance_notes',
        'created_by',
    ];

    protected $casts = [
        'dispatch_date' => 'datetime',
        'total_amount_gmd' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the receipt associated with this record
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    /**
     * Get the device associated with this record
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the assignment to agent associated with this record
     */
    public function assignedToAgent(): BelongsTo
    {
        return $this->belongsTo(AssignToAgent::class);
    }

    /**
     * Get the confirmed affixed associated with this record
     */
    public function confirmedAffixed(): BelongsTo
    {
        return $this->belongsTo(ConfirmedAffixed::class);
    }

    /**
     * Get the user who created this record
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the route associated with this record through receipt
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'receipt.route_id', 'id');
    }

    /**
     * Scope: Get records by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get records for a specific receipt
     */
    public function scopeByReceipt($query, $receiptId)
    {
        return $query->where('receipt_id', $receiptId);
    }

    /**
     * Scope: Get recent records first
     */
    public function scopeRecentFirst($query)
    {
        return $query->orderBy('dispatch_date', 'desc');
    }
}
