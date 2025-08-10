<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'reference_date',
        'sad_boe',
        'regime',
        'agent',
        'route',
        'device_number',
        'total_amount',
        'description',
        'paid_by',
        'received_by',
        'status',
        'logo_path',
        'device_retrieval_id',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'reference_date' => 'datetime',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the device retrieval associated with the invoice.
     */
    public function deviceRetrieval(): BelongsTo
    {
        return $this->belongsTo(DeviceRetrieval::class);
    }

    /**
     * Get the user who approved the invoice.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Generate a unique reference number for a new invoice.
     */
    public static function generateReferenceNumber(): string
    {
        $prefix = 'INV-';
        $date = now()->format('Ymd');
        $random = Str::upper(Str::random(4));

        return "{$prefix}{$date}-{$random}";
    }

    /**
     * Calculate total amount based on device retrieval data
     */
    public function calculateTotalAmount(): float
    {
        if ($this->deviceRetrieval) {
            return $this->deviceRetrieval->overstay_amount ?? 0;
        }
        return 0;
    }

    /**
     * Get the total amount attribute from the database or calculate it
     */
    public function getTotalAmountAttribute($value)
    {
        if ($value === null) {
            return $this->calculateTotalAmount();
        }
        return $value;
    }

    /**
     * Scope to find pending payment invoices
     */
    public function scopePendingPayment($query)
    {
        return $query->where('status', 'PP');
    }
}
