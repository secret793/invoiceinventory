<?php

namespace App\Filament\Resources\GeneratedReceiptResource\Pages;

use App\Filament\Resources\GeneratedReceiptResource;
use App\Models\Receipt;
use App\Models\Destination;
use App\Models\AllocationPoint;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;
use Carbon\Carbon;

class ListGeneratedReceipts extends ListRecords
{
    protected static string $resource = GeneratedReceiptResource::class;
    protected static string $view = 'filament.resources.generated-receipt-resource.pages.list-generated-receipts';

    // Filter properties
    public string $receiptSearch = '';
    public ?string $destinationFilter = null;
    public ?string $allocationPointFilter = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    #[Computed]
    public function filteredReceipts()
    {
        $query = Receipt::query()
            ->whereNotNull('generated_by_user');

        // Search by receipt number or SAD
        if (!empty($this->receiptSearch)) {
            $query->where(function ($q) {
                $q->whereRaw('LOWER(receipt_number) LIKE ?', ['%' . strtolower($this->receiptSearch) . '%'])
                  ->orWhereRaw('LOWER(sad_number) LIKE ?', ['%' . strtolower($this->receiptSearch) . '%']);
            });
        }

        // Filter by destination
        if (!empty($this->destinationFilter)) {
            $query->whereHas('destination', function ($q) {
                $q->where('id', $this->destinationFilter);
            });
        }

        // Filter by allocation point
        if (!empty($this->allocationPointFilter)) {
            $query->where('allocation_point_id', $this->allocationPointFilter);
        }

        // Filter by date range
        if (!empty($this->startDate)) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }
        if (!empty($this->endDate)) {
            $query->whereDate('created_at', '<=', $this->endDate);
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
                $q->whereNotNull('generated_by_user');
            })
            ->distinct()
            ->orderBy('destinations.name')
            ->pluck('destinations.name', 'destinations.id');
    }

    #[Computed]
    public function availableAllocationPoints()
    {
        return AllocationPoint::query()
            ->withoutGlobalScopes()
            ->whereHas('receipts', function ($q) {
                $q->whereNotNull('generated_by_user');
            })
            ->distinct()
            ->orderBy('allocation_points.name')
            ->pluck('allocation_points.name', 'allocation_points.id');
    }

    public function resetFilters(): void
    {
        $this->receiptSearch = '';
        $this->destinationFilter = null;
        $this->allocationPointFilter = null;
        $this->startDate = null;
        $this->endDate = null;
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
    }

    public function exportReceipts()
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

        // Dispatch a browser event so JS opens the URL in a new tab
        // (redirect() causes a full-page navigation and breaks file downloads in Livewire)
        $this->dispatch('open-export-url', url: route('export.generated-receipts', $params));
    }

    public function exportReceiptsPdf()
    {
        $params = [
            'receipt_search' => $this->receiptSearch,
            'destination_id' => $this->destinationFilter,
            'allocation_point_id' => $this->allocationPointFilter,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];

        $params = array_filter($params, fn($value) => $value !== null && $value !== '');

        $this->dispatch('open-export-url', url: route('export.generated-receipts-pdf', $params));
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
            $statsQuery = $this->filteredReceipts();
            $statistics = [
                'total_receipts' => $statsQuery->count(),
                'total_trucks' => $statsQuery->sum('moving_trucks') ?? 0,
                'total_amount' => $statsQuery->sum('total_charge_gmd') ?? 0,
                'total_short_routes' => $statsQuery->whereHas('route')->distinct('route_id')->count(),
                'total_long_routes' => $statsQuery->whereHas('longRoute')->distinct('long_route_id')->count(),
            ];
        }

        $receipts = $this->filteredReceipts()
            ->with([
                'route',
                'longRoute',
                'destination',
                'allocationPoint' => function ($query) {
                    $query->withoutGlobalScopes();
                },
                'generatedByUser'
            ])
            ->paginate(perPage: 1000); // Finance users need to see all records

        return [
            'receipts' => $receipts,
            'statistics' => $statistics,
            'hasDateFilter' => !empty($this->startDate) || !empty($this->endDate),
            'availableDestinations' => $this->availableDestinations,
            'availableAllocationPoints' => $this->availableAllocationPoints,
            'receiptSearch' => $this->receiptSearch,
            'destinationFilter' => $this->destinationFilter,
            'allocationPointFilter' => $this->allocationPointFilter,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
        ];
    }
}
