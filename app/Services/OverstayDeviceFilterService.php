<?php

namespace App\Services;

use App\Models\AllocationPoint;
use App\Models\Destination;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        // Filter by destination (lives on device_retrievals, not invoices)
        if (!empty($filters['destination_id'])) {
            $destId = $filters['destination_id'];
            $query->whereHas('deviceRetrieval', function (Builder $q) use ($destId) {
                $q->where('destination_id', $destId);
            });
        }

        // Filter by allocation point (lives on device_retrievals, not invoices)
        if (!empty($filters['allocation_point_id'])) {
            $allocId = $filters['allocation_point_id'];
            $query->whereHas('deviceRetrieval', function (Builder $q) use ($allocId) {
                $q->where('allocation_point_id', $allocId);
            });
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
        // Each call to applyFilters() creates a fresh Builder — no mutation between calls.
        return [
            'total_devices'         => (int)   $this->applyFilters($filters)->count(),
            'total_overstay_amount' => (float) $this->applyFilters($filters)->sum('total_amount'),
            'total_overstay_days'   => (int)   $this->applyFilters($filters)->sum('overstay_days'),
            'total_pending_payment' => (float) $this->applyFilters($filters)->where('status', 'PP')->sum('total_amount'),
            'average_overstay_days' => round((float) $this->applyFilters($filters)->avg('overstay_days'), 1),
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
        // Use WHERE EXISTS correlated subquery via device_retrievals → invoices.
        // This mirrors the original relationship path, avoiding any assumption
        // that invoices.destination_id exists or is populated.
        // withoutGlobalScopes() bypasses the Destination role-based access scope
        // so all destinations with overstay records are returned regardless of user role.
        return Destination::withoutGlobalScopes()
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('device_retrievals')
                    ->join('invoices', 'device_retrievals.id', '=', 'invoices.device_retrieval_id')
                    ->whereColumn('device_retrievals.destination_id', 'destinations.id')
                    ->where(function ($q) {
                        $q->where('invoices.overstay_days', '>', 0)
                          ->orWhere('invoices.status', 'WAIVED');
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
        // Same WHERE EXISTS approach via device_retrievals → invoices.
        return AllocationPoint::whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('device_retrievals')
                    ->join('invoices', 'device_retrievals.id', '=', 'invoices.device_retrieval_id')
                    ->whereColumn('device_retrievals.allocation_point_id', 'allocation_points.id')
                    ->where(function ($q) {
                        $q->where('invoices.overstay_days', '>', 0)
                          ->orWhere('invoices.status', 'WAIVED');
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
                'id',
                'reference_number',
                'sad_boe',
                'destination',
                'device_number',
                'allocation_point_name',
                'overstay_days',
                'total_amount',
                'status',
                'reference_date',
                'device_retrieval_id',
            ])
            ->with(['deviceRetrieval.device', 'deviceRetrieval.destination', 'deviceRetrieval.allocationPoint'])
            ->get()
            ->map(function ($invoice) {
                return [
                    'Invoice Number' => $invoice->reference_number,
                    'Device ID' => $invoice->deviceRetrieval?->device?->device_id ?? $invoice->device_number ?? 'N/A',
                    'SAD/BOE' => $invoice->sad_boe ?? 'N/A',
                    'Destination' => $invoice->deviceRetrieval?->destination?->name ?? $invoice->destination ?? 'N/A',
                    'Allocation Point' => $invoice->deviceRetrieval?->allocationPoint?->name ?? $invoice->allocation_point_name ?? '—',
                    'Overstay Days' => $invoice->overstay_days,
                    'Overstay Amount (GMD)' => number_format($invoice->total_amount, 2),
                    'Payment Status' => $this->formatPaymentStatus($invoice->status),
                    'Invoice Date' => $invoice->reference_date ? $invoice->reference_date->format('Y-m-d') : 'N/A',
                    'Created By' => $invoice->received_by ?? 'System',
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
