<?php

namespace App\Services;

use App\Models\Receipt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReceiptFilterService
{
    /**
     * Apply filters to receipts query
     * 
     * @param array $filters Filter parameters
     * @return Builder
     */
    public function applyFilters(array $filters): Builder
    {
        $query = Receipt::query()
            ->with(['destination', 'route', 'longRoute'])
            ->where('allocation_point_id', $filters['allocation_point_id']);

        // Search by receipt number or SAD number
        if (!empty($filters['receipt_number'])) {
            $searchTerm = $filters['receipt_number'];
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('receipt_number', 'like', "%{$searchTerm}%")
                  ->orWhere('sad_number', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by destination
        if (!empty($filters['destination_id'])) {
            $query->where('destination_id', $filters['destination_id']);
        }

        // Filter by start date and time
        if (!empty($filters['start_date'])) {
            $startDateTime = $filters['start_date'];
            
            // Add start time if provided
            if (!empty($filters['start_time'])) {
                $startDateTime .= ' ' . $filters['start_time'];
            } else {
                $startDateTime .= ' 00:00:00';
            }
            
            $query->where('created_at', '>=', $startDateTime);
        }

        // Filter by end date and time
        if (!empty($filters['end_date'])) {
            $endDateTime = $filters['end_date'];
            
            // Add end time if provided
            if (!empty($filters['end_time'])) {
                $endDateTime .= ' ' . $filters['end_time'];
            } else {
                $endDateTime .= ' 23:59:59';
            }
            
            $query->where('created_at', '<=', $endDateTime);
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
     * Get statistics for filtered receipts
     * Only calculate when filters are active
     * 
     * @param array $filters Filter parameters
     * @return array
     */
    public function getStatistics(array $filters): array
    {
        $query = $this->applyFilters($filters);
        
        return [
            'total_receipts' => $query->count(),
            'total_trucks' => (int) $query->sum('moving_trucks'),
            'total_amount' => (float) $query->sum('total_charge_gmd'),
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
        return !empty($filters['receipt_number'])
            || !empty($filters['destination_id'])
            || !empty($filters['start_date'])
            || !empty($filters['end_date']);
    }

    /**
     * Get all destinations that have receipts for an allocation point
     * 
     * @param int $allocationPointId
     * @return Collection
     */
    public function getAvailableDestinations(int $allocationPointId): Collection
    {
        return Receipt::query()
            ->where('allocation_point_id', $allocationPointId)
            ->whereNotNull('destination_id')
            ->distinct()
            ->with('destination')
            ->get()
            ->map(function ($receipt) {
                return $receipt->destination;
            })
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Export filtered receipts as collection
     * 
     * @param array $filters Filter parameters
     * @return Collection
     */
    public function export(array $filters): Collection
    {
        return $this->applyFilters($filters)
            ->select([
                'receipt_number',
                'sad_number',
                'route_id',
                'long_route_id',
                'destination_id',
                'date',
                'created_at',
                'moving_trucks',
                'used',
                'total_charge_gmd',
            ])
            ->with(['destination', 'route', 'longRoute'])
            ->get()
            ->map(function ($receipt) {
                return [
                    'Receipt Number' => $receipt->receipt_number,
                    'SAD/T1' => $receipt->sad_number ?? 'N/A',
                    'Route (Short)' => $receipt->route?->name ?? 'N/A',
                    'Route (Long)' => $receipt->longRoute?->name ?? 'N/A',
                    'Destination' => $receipt->destination?->name ?? 'N/A',
                    'Date & Time' => $receipt->created_at ? $receipt->created_at->format('Y-m-d H:i') : 'N/A',
                    'Trucks' => $receipt->moving_trucks,
                    'Available Usage' => $receipt->used,
                    'Total Charged (GMD)' => number_format($receipt->total_charge_gmd, 2),
                ];
            });
    }
}
