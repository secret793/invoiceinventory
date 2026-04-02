<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
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
        'customs_post',
        'consignee',
        'driver_name',
        'departure',
        'destination',
        'allocation_point_name',
        'route',
        'device_number',
        'asset_number',
        'overstay_days',
        'penalty_amount',
        'total_amount',
        'description',
        'paid_by',
        'received_by',
        'signature',
        'status',
        'logo_path',
        'device_retrieval_id',
        'approved_by',
        'approved_at',
        'waived_by',
        'waived_at',
    ];

    protected $casts = [
        'reference_date' => 'datetime',
        'approved_at' => 'datetime',
        'waived_at' => 'datetime',
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
     * Get the admin who waived the invoice.
     */
    public function waivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }

    /**
     * Get the user who generated/created the invoice.
     */
    public function generatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the waiver history record associated with this invoice.
     */
    public function waiverRecord()
    {
        return $this->hasOne(WaiverHistory::class, 'invoice_id');
    }

    /**
     * Check if invoice is waived.
     */
    public function isWaived(): bool
    {
        return $this->status === 'WAIVED';
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'PD';
    }

    /**
     * Check if invoice is pending payment.
     */
    public function isPending(): bool
    {
        return $this->status === 'PP';
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

    /**
     * Boot method to apply global scopes
     */
    protected static function booted(): void
    {
        static::addGlobalScope('destination-access', function (Builder $query) {
            $user = auth()->user();

            if (!$user) {
                $query->where('id', 0); // No results for non-authenticated users
                return;
            }

            // Super Admin, Warehouse Manager, and Finance Officer have full access
            if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Finance Officer'])) {
                return;
            }

            // Get user's destination view permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();

            // Extract destination names from view_destination_* permissions
            $destinationPermissions = array_filter(
                $permissions,
                fn($perm) => Str::startsWith($perm, 'view_destination_')
            );

            if (empty($destinationPermissions)) {
                $query->where('id', 0); // No results if no destination permissions
                return;
            }

            // Convert permission slugs to destination names
            // Handle 5 naming variations: destination_name -> destination name -> destination-name -> etc.
            $destinations = collect($destinationPermissions)
                ->map(fn($perm) => Str::after($perm, 'view_destination_'))
                ->map(fn($dest) => [
                    $dest,
                    Str::replace('_', ' ', $dest),
                    Str::replace('_', '-', $dest),
                    Str::ucfirst(Str::replace('_', ' ', $dest)),
                    Str::title(Str::replace('_', ' ', $dest)),
                ])
                ->flatten()
                ->unique()
                ->values()
                ->toArray();

            // Filter by destinations via deviceRetrieval relationship
            $query->whereHas('deviceRetrieval', function (Builder $subQuery) use ($destinations) {
                $subQuery->whereIn('destination', $destinations);
            });
        });
    }
}
