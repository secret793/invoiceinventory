<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\AllocationPoint;
use App\Models\Invoice;
use App\Models\Regime;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Computed;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;
    protected static string $view = 'filament.resources.invoice-resource.pages.list-invoices';

    // ═════════════════════════════════════════════════════════
    // FILTER PROPERTIES (Bound to wire directives in Blade)
    // ═════════════════════════════════════════════════════════
    public string $referenceSearch = '';           // Search invoices by ref #
    public ?string $destinationFilter = null;      // Filter by destination
    public ?string $regimeFilter = null;           // Filter by regime
    public ?string $allocationPointFilter = null;  // Filter by allocation point
    public ?string $statusFilter = null;           // Filter by PP/PD/WAIVED/RJ
    public ?string $startDate = null;              // Date range start
    public ?string $endDate = null;                // Date range end
    public string $sortBy = 'created_at';          // Sort column
    public string $sortDirection = 'desc';         // ASC or DESC
    
    // Search box properties for autocomplete
    public string $destinationSearch = '';
    public string $regimeSearch = '';
    public string $allocationPointSearch = '';

    // ═════════════════════════════════════════════════════════
    // COMPUTED PROPERTIES (Auto-rebuilds when filters change)
    // ═════════════════════════════════════════════════════════
    #[Computed]
    public function filteredRecords()
    {
        $query = Invoice::query()->with(['deviceRetrieval']);

        // Search filter
        if (!empty($this->referenceSearch)) {
            $query->where(function ($q) {
                $q->whereRaw('LOWER(reference_number) LIKE ?', ['%' . strtolower($this->referenceSearch) . '%'])
                  ->orWhereRaw('LOWER(sad_boe) LIKE ?', ['%' . strtolower($this->referenceSearch) . '%'])
                  ->orWhereRaw('LOWER(device_number) LIKE ?', ['%' . strtolower($this->referenceSearch) . '%']);
            });
        }

        // Destination filter
        if (!empty($this->destinationFilter)) {
            $query->where('destination', $this->destinationFilter);
        }

        // Regime filter
        if (!empty($this->regimeFilter)) {
            $query->where('regime_id', $this->regimeFilter);
        }

        // Allocation Point filter
        if (!empty($this->allocationPointFilter)) {
            $query->whereHas('deviceRetrieval.allocationPoint', 
                fn($q) => $q->where('id', $this->allocationPointFilter)
            );
        }

        // Status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Date range filters
        if (!empty($this->startDate)) {
            $query->whereDate('reference_date', '>=', $this->startDate);
        }
        if (!empty($this->endDate)) {
            $query->whereDate('reference_date', '<=', $this->endDate);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query;
    }

    #[Computed]
    public function availableRegimes()
    {
        return Regime::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    #[Computed]
    public function availableDestinations()
    {
        return Invoice::query()
            ->whereNotNull('destination')
            ->distinct()
            ->orderBy('destination')
            ->pluck('destination', 'destination');
    }

    #[Computed]
    public function filteredDestinations()
    {
        if (empty($this->destinationSearch)) {
            return collect();
        }
        
        return Invoice::query()
            ->whereNotNull('destination')
            ->where('destination', 'LIKE', '%' . $this->destinationSearch . '%')
            ->distinct()
            ->orderBy('destination')
            ->pluck('destination', 'destination')
            ->take(10);
    }

    #[Computed]
    public function filteredRegimes()
    {
        if (empty($this->regimeSearch)) {
            return collect();
        }
        
        return Regime::query()
            ->where('is_active', true)
            ->where('name', 'LIKE', '%' . $this->regimeSearch . '%')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->take(10);
    }

    #[Computed]
    public function availableAllocationPoints()
    {
        return AllocationPoint::query()
            ->whereHas('deviceRetrievals', function ($query) {
                $query->whereHas('invoices');
            })
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    #[Computed]
    public function filteredAllocationPoints()
    {
        if (empty($this->allocationPointSearch)) {
            return collect();
        }
        
        return AllocationPoint::query()
            ->whereHas('deviceRetrievals', function ($query) {
                $query->whereHas('invoices');
            })
            ->where('status', 'ACTIVE')
            ->where('name', 'LIKE', '%' . $this->allocationPointSearch . '%')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->take(10);
    }

    // ═════════════════════════════════════════════════════════
    // ACTION METHODS (Called from Blade via wire:click)
    // ═════════════════════════════════════════════════════════
    public function selectRegime(string $regimeId): void
    {
        $this->regimeFilter = $regimeId;
        $this->regimeSearch = '';
    }

    public function clearRegime(): void
    {
        $this->regimeFilter = null;
        $this->regimeSearch = '';
    }
    public function selectDestination(string $destination): void
    {
        $this->destinationFilter = $destination;
        $this->destinationSearch = '';
    }

    public function clearDestination(): void
    {
        $this->destinationFilter = null;
        $this->destinationSearch = '';
    }
    public function selectAllocationPoint(string $apId): void
    {
        $this->allocationPointFilter = $apId;
        $this->allocationPointSearch = '';
    }

    public function clearAllocationPoint(): void
    {
        $this->allocationPointFilter = null;
        $this->allocationPointSearch = '';
    }

    public function resetFilters(): void
    {
        $this->referenceSearch = '';
        $this->destinationFilter = null;
        $this->regimeFilter = null;
        $this->allocationPointFilter = null;
        $this->allocationPointSearch = '';
        $this->statusFilter = null;
        $this->startDate = null;
        $this->endDate = null;
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
    }

    public function exportRecords()
    {
        $params = [
            'reference_search' => $this->referenceSearch,
            'regime_id' => $this->regimeFilter,
            'allocation_point_id' => $this->allocationPointFilter,
            'status' => $this->statusFilter,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];

        $params = array_filter($params, fn($v) => $v !== null && $v !== '');

        $this->dispatch('open-export-url', url: route('export.overstay-receipts', $params));
    }

    public function applyFilters()
    {
        // Filters are automatically applied through Livewire properties
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
            
            // Get total records
            $total_records = $statsQuery->count();
            
            // Get total amount
            $total_amount = $statsQuery->sum('total_amount') ?? 0;
            
            // Get status counts
            $paidCount = (clone $statsQuery)->where('status', 'PD')->count();
            $pendingCount = (clone $statsQuery)->where('status', 'PP')->count();
            $waivedCount = (clone $statsQuery)->where('status', 'WAIVED')->count();
            
            $statistics = [
                'total_records' => $total_records,
                'total_amount' => $total_amount,
                'paid_count' => $paidCount,
                'pending_count' => $pendingCount,
                'waived_count' => $waivedCount,
            ];
        }

        $records = $this->filteredRecords()
            ->with(['deviceRetrieval'])
            ->paginate(perPage: 1000);

        return [
            'records' => $records,
            'statistics' => $statistics,
            'hasDateFilter' => !empty($this->startDate) || !empty($this->endDate),
            'availableDestinations' => $this->availableDestinations,
            'availableRegimes' => $this->availableRegimes,
            'filteredRegimes' => $this->filteredRegimes,
            'availableAllocationPoints' => $this->availableAllocationPoints,
            'filteredAllocationPoints' => $this->filteredAllocationPoints,
            'referenceSearch' => $this->referenceSearch,
            'destinationFilter' => $this->destinationFilter,
            'regimeFilter' => $this->regimeFilter,
            'allocationPointFilter' => $this->allocationPointFilter,
            'statusFilter' => $this->statusFilter,
            'regimeSearch' => $this->regimeSearch,
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