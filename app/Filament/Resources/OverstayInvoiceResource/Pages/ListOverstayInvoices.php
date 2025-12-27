<?php

namespace App\Filament\Resources\OverstayInvoiceResource\Pages;

use App\Filament\Resources\OverstayInvoiceResource;
use App\Models\Invoice;
use App\Models\Destination;
use App\Models\AllocationPoint;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Computed;
use Carbon\Carbon;

class ListOverstayInvoices extends ListRecords
{
    protected static string $resource = OverstayInvoiceResource::class;
    protected static string $view = 'filament.resources.overstay-invoice-resource.pages.list-overstay-invoices';

    // Filter properties
    public string $receiptSearch = '';
    public ?string $destinationFilter = null;
    public ?string $allocationPointFilter = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    
    // Search box properties for autocomplete
    public string $destinationSearch = '';
    public string $allocationPointSearch = '';

    #[Computed]
    public function filteredRecords()
    {
        $query = Invoice::query()
            ->where(function ($q) {
                $q->where('overstay_days', '>', 0)
                  ->orWhere('status', 'WAIVED');
            })
            ->with(['deviceRetrieval', 'deviceRetrieval.destination', 'deviceRetrieval.allocationPoint', 'approver']);

        // Search by receipt number or SAD
        if (!empty($this->receiptSearch)) {
            $query->where(function ($q) {
                $q->whereRaw('LOWER(reference_number) LIKE ?', ['%' . strtolower($this->receiptSearch) . '%'])
                  ->orWhereRaw('LOWER(sad_boe) LIKE ?', ['%' . strtolower($this->receiptSearch) . '%']);
            });
        }

        // Filter by destination (via device_retrieval)
        if (!empty($this->destinationFilter)) {
            $query->whereHas('deviceRetrieval', function ($q) {
                $q->where('destination_id', $this->destinationFilter);
            });
        }

        // Filter by allocation point (via device_retrieval)
        if (!empty($this->allocationPointFilter)) {
            $query->whereHas('deviceRetrieval', function ($q) {
                $q->where('allocation_point_id', $this->allocationPointFilter);
            });
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
            ->whereHas('deviceRetrievals', function ($q) {
                $q->whereHas('invoices', function ($innerQ) {
                    $innerQ->where(function ($cq) {
                        $cq->where('overstay_days', '>', 0)
                          ->orWhere('status', 'WAIVED');
                    });
                });
            })
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    #[Computed]
    public function availableAllocationPoints()
    {
        return AllocationPoint::query()
            ->whereHas('deviceRetrievals', function ($q) {
                $q->whereHas('invoices', function ($innerQ) {
                    $innerQ->where(function ($cq) {
                        $cq->where('overstay_days', '>', 0)
                          ->orWhere('status', 'WAIVED');
                    });
                });
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
            ->whereHas('deviceRetrievals', function ($q) {
                $q->whereHas('invoices', function ($innerQ) {
                    $innerQ->where(function ($cq) {
                        $cq->where('overstay_days', '>', 0)
                          ->orWhere('status', 'WAIVED');
                    });
                });
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
            ->whereHas('deviceRetrievals', function ($q) {
                $q->whereHas('invoices', function ($innerQ) {
                    $innerQ->where(function ($cq) {
                        $cq->where('overstay_days', '>', 0)
                          ->orWhere('status', 'WAIVED');
                    });
                });
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
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
    }

    public function exportRecords()
    {
        $params = [
            'receipt_search' => $this->receiptSearch,
            'destination_id' => $this->destinationFilter,
            'allocation_point_id' => $this->allocationPointFilter,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];
        
        $params = array_filter($params, fn($value) => $value !== null && $value !== '');
        
        return redirect(route('export.overstay-invoices', $params));
    }

    public function getViewData(): array
    {
        // Calculate statistics
        $statistics = [];
        $hasDateFilter = !empty($this->startDate) || !empty($this->endDate);

        if ($hasDateFilter) {
            $statsQuery = clone $this->filteredRecords();
            
            // Count invoices
            $total_invoices = $statsQuery->count();
            
            // Sum overstay_days to get total trucks (assuming overstay_days represents trucks)
            $trucksQuery = clone $this->filteredRecords();
            $total_trucks = $trucksQuery->sum('overstay_days') ?? 0;
            
            // Sum penalty_amount for total amount
            $amountQuery = clone $this->filteredRecords();
            $total_amount = $amountQuery->sum('penalty_amount') ?? 0;
            
            $statistics = [
                'total_invoices' => $total_invoices,
                'total_trucks' => $total_trucks,
                'total_amount' => $total_amount,
            ];
        }

        $records = $this->filteredRecords()
            ->paginate(perPage: 50);

        return [
            'records' => $records,
            'statistics' => $statistics,
            'hasDateFilter' => $hasDateFilter,
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

