<?php

namespace App\Filament\Resources\DispatchFinanceRecordResource\Pages;

use App\Filament\Resources\DispatchFinanceRecordResource;
use App\Models\DispatchFinanceRecord;
use App\Models\Destination;
use App\Models\AllocationPoint;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;
use Carbon\Carbon;

class ListDispatchFinanceRecords extends ListRecords
{
    protected static string $resource = DispatchFinanceRecordResource::class;
    protected static string $view = 'filament.resources.dispatch-finance-record-resource.pages.list-dispatch-finance-records';

    // Filter properties
    public string $receiptSearch = '';
    public ?string $destinationFilter = null;
    public ?string $allocationPointFilter = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $sortBy = 'dispatch_date';
    public string $sortDirection = 'desc';
    
    // Search box properties for autocomplete
    public string $destinationSearch = '';
    public string $allocationPointSearch = '';

    #[Computed]
    public function filteredRecords()
    {
        $query = DispatchFinanceRecord::query()
            ->with(['receipt', 'device', 'creator', 'confirmedAffixed']);

        // Search by receipt number or SAD
        if (!empty($this->receiptSearch)) {
            $query->whereHas('receipt', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereRaw('LOWER(receipt_number) LIKE ?', ['%' . strtolower($this->receiptSearch) . '%'])
                         ->orWhereRaw('LOWER(sad_number) LIKE ?', ['%' . strtolower($this->receiptSearch) . '%']);
                });
            });
        }

        // Filter by destination
        if (!empty($this->destinationFilter)) {
            $query->whereHas('receipt.destination', function ($q) {
                $q->where('id', $this->destinationFilter);
            });
        }

        // Filter by allocation point
        if (!empty($this->allocationPointFilter)) {
            $query->whereHas('receipt.allocationPoint', function ($q) {
                $q->where('id', $this->allocationPointFilter);
            });
        }

        // Filter by date range
        if (!empty($this->startDate)) {
            $query->whereDate('dispatch_date', '>=', $this->startDate);
        }
        if (!empty($this->endDate)) {
            $query->whereDate('dispatch_date', '<=', $this->endDate);
        }

        // Sort
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query;
    }

    #[Computed]
    public function availableDestinations()
    {
        return Destination::query()
            ->whereHas('receipts', function ($q) {
                $q->whereHas('dispatchFinanceRecords');
            })
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    #[Computed]
    public function availableAllocationPoints()
    {
        return AllocationPoint::query()
            ->whereHas('receipts', function ($q) {
                $q->whereHas('dispatchFinanceRecords');
            })
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    #[Computed]
    public function filteredDestinations()
    {
        if (empty($this->destinationSearch)) {
            return collect();
        }
        
        return Destination::query()
            ->whereHas('receipts', function ($q) {
                $q->whereHas('dispatchFinanceRecords');
            })
            ->where('name', 'LIKE', '%' . $this->destinationSearch . '%')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->take(10);
    }

    #[Computed]
    public function filteredAllocationPoints()
    {
        if (empty($this->allocationPointSearch)) {
            return collect();
        }
        
        return AllocationPoint::query()
            ->whereHas('receipts', function ($q) {
                $q->whereHas('dispatchFinanceRecords');
            })
            ->where('name', 'LIKE', '%' . $this->allocationPointSearch . '%')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->take(10);
    }

    public function selectDestination(string $destinationId): void
    {
        $this->destinationFilter = $destinationId;
        $this->destinationSearch = '';
    }

    public function clearDestination(): void
    {
        $this->destinationFilter = null;
        $this->destinationSearch = '';
    }

    public function selectAllocationPoint(string $allocationPointId): void
    {
        $this->allocationPointFilter = $allocationPointId;
        $this->allocationPointSearch = '';
    }

    public function clearAllocationPoint(): void
    {
        $this->allocationPointFilter = null;
        $this->allocationPointSearch = '';
    }

    public function resetFilters(): void
    {
        $this->receiptSearch = '';
        $this->destinationFilter = null;
        $this->allocationPointFilter = null;
        $this->startDate = null;
        $this->endDate = null;
        $this->sortBy = 'dispatch_date';
        $this->sortDirection = 'desc';
    }

    public function exportRecords()
    {
        // Build params from filters
        $params = [
            'receipt_search' => $this->receiptSearch,
            'destination_id' => $this->destinationFilter,
            'allocation_point_id' => $this->allocationPointFilter,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];

        // Remove null values
        $params = array_filter($params, fn($value) => $value !== null && $value !== '');

        $this->dispatch('open-export-url', url: route('export.dispatch-finance-records', $params));
    }

    public function applyFilters()
    {
        // Filters are automatically applied through Livewire properties
        // This method can be called from the "Apply" button if needed
        // The data will be updated automatically through computed properties
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        // Calculate statistics
        $statistics = [];
        if (!empty($this->startDate) || !empty($this->endDate)) {
            $statsQuery = $this->filteredRecords();
            $countQuery = clone $statsQuery;
            $amountQuery = clone $statsQuery;
            
            // Get total records and amount
            $total_records = $countQuery->count();
            $total_amount = $amountQuery->sum('dispatch_finance_records.total_amount_gmd') ?? 0;
            
            // Get trucks by joining with receipts
            $trucksQuery = clone $statsQuery;
            $total_trucks = $trucksQuery
                ->join('receipts', 'dispatch_finance_records.receipt_id', '=', 'receipts.id')
                ->sum('receipts.moving_trucks') ?? 0;
            
            // Get total short routes
            $shortRoutesQuery = clone $statsQuery;
            $total_short_routes = $shortRoutesQuery
                ->join('receipts', 'dispatch_finance_records.receipt_id', '=', 'receipts.id')
                ->join('routes', 'receipts.route_id', '=', 'routes.id')
                ->distinct()
                ->count('receipts.route_id');
            
            // Get total long routes
            $longRoutesQuery = clone $statsQuery;
            $total_long_routes = $longRoutesQuery
                ->join('receipts', 'dispatch_finance_records.receipt_id', '=', 'receipts.id')
                ->join('long_routes', 'receipts.long_route_id', '=', 'long_routes.id')
                ->distinct()
                ->count('receipts.long_route_id');
            
            $statistics = [
                'total_records' => $total_records,
                'total_trucks' => $total_trucks,
                'total_amount' => $total_amount,
                'total_short_routes' => $total_short_routes,
                'total_long_routes' => $total_long_routes,
            ];
        }

        $records = $this->filteredRecords()
            ->with(['receipt.route', 'receipt.longRoute', 'receipt.allocationPoint', 'receipt.destination', 'creator', 'confirmedAffixed'])
            ->paginate(perPage: 1000); // Finance users need to see all records

        return [
            'records' => $records,
            'statistics' => $statistics,
            'hasDateFilter' => !empty($this->startDate) || !empty($this->endDate),
            'availableDestinations' => $this->availableDestinations,
            'availableAllocationPoints' => $this->availableAllocationPoints,
            'filteredDestinations' => $this->filteredDestinations,
            'filteredAllocationPoints' => $this->filteredAllocationPoints,
            'receiptSearch' => $this->receiptSearch,
            'destinationFilter' => $this->destinationFilter,
            'allocationPointFilter' => $this->allocationPointFilter,
            'destinationSearch' => $this->destinationSearch,
            'allocationPointSearch' => $this->allocationPointSearch,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
        ];
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view(self::$view, array_merge(parent::render()->getData(), $this->getViewData()));
    }
}
