<?php

namespace App\Services;

use App\Models\AllocationPoint;
use App\Models\Destination;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OverstayDeviceFilterService
{
    /**
     * Apply filters to invoices query
     * Queries invoices with overstay (overstay_days > 0 OR status = WAIVED)
     * 
     * @param array $filters Filter parameters
     * @return Builder
     */
    public function applyFilters(array $filters): Builder
    {
        $query = Invoice::query()
            ->with(['deviceRetrieval.device', 'deviceRetrieval.destination', 'deviceRetrieval.allocationPoint'])
            ->where(function (Builder $q) {
                // Include invoices with overstay or waived status
                $q->where('overstay_days', '>', 0)
                  ->orWhere('status', 'WAIVED');
            });

        // Search by device ID (wildcard)
        if (!empty($filters['device_id'])) {
            $searchTerm = $filters['device_id'];
            $query->whereHas('device', function (Builder $q) use ($searchTerm) {
                $q->where('device_id', 'like', "%{$searchTerm}%");
            });
        }

        // Search by BOE (wildcard)
        if (!empty($filters['boe'])) {
            $searchTerm = $filters['boe'];
            $query->where('sad_boe', 'like', "%{$searchTerm}%");
        }

        // Search by invoice number (wildcard)
        if (!empty($filters['invoice_number'])) {
            $searchTerm = $filters['invoice_number'];
            $query->where('reference_number', 'like', "%{$searchTerm}%");
        }

        // Filter by destination
        if (!empty($filters['destination_id'])) {
            $query->where('destination_id', $filters['destination_id']);
        }

        // Filter by allocation point
        if (!empty($filters['allocation_point_id'])) {
            $query->where('allocation_point_id', $filters['allocation_point_id']);
        }

        // Filter by payment status (PP, PD, WAIVED)
        if (!empty($filters['payment_status'])) {
            $query->where('status', $filters['payment_status']);
        }

        // Filter by overstay amount range
        if (!empty($filters['overstay_amount_min'])) {
            $query->where('total_amount', '>=', $filters['overstay_amount_min']);
        }

        if (!empty($filters['overstay_amount_max'])) {
            $query->where('total_amount', '<=', $filters['overstay_amount_max']);
        }

        // Filter by overstay days range
        if (!empty($filters['overstay_days_min'])) {
            $query->where('overstay_days', '>=', $filters['overstay_days_min']);
        }

        if (!empty($filters['overstay_days_max'])) {
            $query->where('overstay_days', '<=', $filters['overstay_days_max']);
        }

        // Filter by date range (invoice creation date)
        if (!empty($filters['start_date'])) {
            $startDate = $filters['start_date'];
            $query->where('reference_date', '>=', $startDate . ' 00:00:00');
        }

        if (!empty($filters['end_date'])) {
            $endDate = $filters['end_date'];
            $query->where('reference_date', '<=', $endDate . ' 23:59:59');
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);

        return $query;
    }

    /**
     * Get statistics for filtered invoices
     * Only calculate when filters are active
     * 
     * @param array $filters Filter parameters
     * @return array
     */
    public function getStatistics(array $filters): array
    {
        // Single aggregation query — 1 DB round-trip instead of 5.
        // toBase() strips Eloquent eager-loads so only the WHERE conditions travel to the DB.
        $row = $this->applyFilters($filters)
            ->toBase()
            ->selectRaw("
                COUNT(*) as total_devices,
                COALESCE(SUM(total_amount), 0) as total_overstay_amount,
                COALESCE(SUM(overstay_days), 0) as total_overstay_days,
                COALESCE(SUM(CASE WHEN status = 'PP' THEN total_amount ELSE 0 END), 0) as total_pending_payment,
                COALESCE(AVG(overstay_days), 0) as average_overstay_days
            ")
            ->first();

        return [
            'total_devices'         => (int)   ($row->total_devices          ?? 0),
            'total_overstay_amount' => (float) ($row->total_overstay_amount  ?? 0),
            'total_overstay_days'   => (int)   ($row->total_overstay_days    ?? 0),
            'total_pending_payment' => (float) ($row->total_pending_payment  ?? 0),
            'average_overstay_days' => round((float) ($row->average_overstay_days ?? 0), 1),
        ];
    }

    /**
     * Check if any filters are active
     * 
     * @param array $filters Filter parameters
     * @return bool
     */
    public function hasActiveFilters(array $filters): bool
    {
        return !empty($filters['device_id'])
            || !empty($filters['boe'])
            || !empty($filters['invoice_number'])
            || !empty($filters['destination_id'])
            || !empty($filters['allocation_point_id'])
            || !empty($filters['payment_status'])
            || !empty($filters['overstay_amount_min'])
            || !empty($filters['overstay_amount_max'])
            || !empty($filters['overstay_days_min'])
            || !empty($filters['overstay_days_max'])
            || !empty($filters['start_date'])
            || !empty($filters['end_date']);
    }

    /**
     * Get all destinations that have overstay invoices
     * 
     * @return Collection
     */
    public function getAvailableDestinations(): Collection
    {
        // Single DB query using EXISTS subquery — no PHP-memory scanning.
        return Destination::whereIn('id', function ($sub) {
                $sub->select('destination_id')
                    ->from('invoices')
                    ->whereNotNull('destination_id')
                    ->where(function ($q) {
                        $q->where('overstay_days', '>', 0)
                          ->orWhere('status', 'WAIVED');
                    });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all allocation points that have overstay invoices
     * 
     * @return Collection
     */
    public function getAvailableAllocationPoints(): Collection
    {
        // Single DB query using EXISTS subquery — no PHP-memory scanning.
        return AllocationPoint::whereIn('id', function ($sub) {
                $sub->select('allocation_point_id')
                    ->from('invoices')
                    ->whereNotNull('allocation_point_id')
                    ->where(function ($q) {
                        $q->where('overstay_days', '>', 0)
                          ->orWhere('status', 'WAIVED');
                    });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Export filtered invoices as collection
     * 
     * @param array $filters Filter parameters
     * @return Collection
     */
    public function export(array $filters): Collection
    {
        return $this->applyFilters($filters)
            ->select([
                'reference_number',
                'sad_boe',
                'destination_id',
                'allocation_point_id',
                'overstay_days',
                'total_amount',
                'status',
                'reference_date',
                'created_by',
                'device_retrieval_id',
            ])
            ->with(['deviceRetrieval.device', 'deviceRetrieval.destination', 'deviceRetrieval.allocationPoint'])
            ->get()
            ->map(function ($invoice) {
                return [
                    'Invoice Number' => $invoice->reference_number,
                    'Device ID' => $invoice->deviceRetrieval?->device?->device_id ?? 'N/A',
                    'SAD/BOE' => $invoice->sad_boe ?? 'N/A',
                    'Destination' => $invoice->deviceRetrieval?->destination?->name ?? 'N/A',
                    'Allocation Point' => $invoice->deviceRetrieval?->allocationPoint?->name ?? 'N/A',
                    'Overstay Days' => $invoice->overstay_days,
                    'Overstay Amount (GMD)' => number_format($invoice->total_amount, 2),
                    'Payment Status' => $this->formatPaymentStatus($invoice->status),
                    'Invoice Date' => $invoice->reference_date ? $invoice->reference_date->format('Y-m-d') : 'N/A',
                    'Created By' => $invoice->created_by ?? 'System',
                ];
            });
    }

    /**
     * Format payment status for display
     * 
     * @param string $status
     * @return string
     */
    private function formatPaymentStatus(string $status): string
    {
        return match($status) {
            'PP' => 'Pending Payment',
            'PD' => 'Paid',
            'WAIVED' => 'Waived',
            default => $status,
        };
    }
}
