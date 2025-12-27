<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WaiverHistory extends Model
{
    use HasFactory;

    protected $table = 'waiver_history';

    protected $fillable = [
        'device_retrieval_id',
        'invoice_id',
        'admin_user_id',
        'reason',
        'original_overstay_days',
        'original_amount',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the device retrieval associated with the waiver.
     */
    public function deviceRetrieval(): BelongsTo
    {
        return $this->belongsTo(DeviceRetrieval::class);
    }

    /**
     * Get the invoice associated with the waiver (if any).
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the admin user who applied the waiver.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Get the admin's full name.
     */
    public function getAdminName(): string
    {
        return $this->admin?->name ?? 'Unknown';
    }

    /**
     * Format the reason for display.
     */
    public function getFormattedReason(): string
    {
        return $this->reason;
    }

    /**
     * Get formatted original amount.
     */
    public function getFormattedAmount(): string
    {
        return 'D ' . number_format($this->original_amount, 2);
    }
}
