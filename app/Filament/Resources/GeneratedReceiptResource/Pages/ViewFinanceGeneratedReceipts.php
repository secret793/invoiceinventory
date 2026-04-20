<?php

namespace App\Filament\Resources\GeneratedReceiptResource\Pages;

use App\Filament\Resources\GeneratedReceiptResource;
use App\Models\Receipt;
use App\Models\Destination;
use App\Models\AllocationPoint;
use App\Services\ReceiptFilterService;
use Filament\Resources\Pages\Page;
use Illuminate\Pagination\Paginator;

class ViewFinanceGeneratedReceipts extends Page
{
    protected static string $resource = GeneratedReceiptResource::class;

    protected static string $view = 'filament.resources.generated-receipt.pages.finance-receipts-modal';

    // Receipt filter properties
    public array $receiptFilters = [
        'receipt_number' => null,
        'destination_id' => null,
        'allocation_point_id' => null,
        'start_date' => null,
        'start_time' => null,
        'end_date' => null,
        'end_time' => null,
        'sort_by' => 'date',
        'sort_direction' => 'desc',
    ];

    public string $destinationSearch = '';

    /**
     * Get filtered receipts with pagination
     */
    public function getFilteredReceiptsProperty()
    {
        $filterService = app(ReceiptFilterService::class);
        
        $query = $filterService->applyFilters(
            Receipt::query(),
            $this->receiptFilters
        );

        return $query->paginate(15);
    }

    /**
     * Get statistics for filtered receipts (only if date filter set)
     */
    public function getReceiptStatisticsProperty()
    {
        if (!$this->receiptFilters['start_date'] && !$this->receiptFilters['end_date']) {
            return null;
        }

        $filterService = app(ReceiptFilterService::class);
        return $filterService->getStatistics(
            Receipt::query(),
            $this->receiptFilters
        );
    }

    /**
     * Get all available destinations for filter
     */
    public function getReceiptDestinationsProperty()
    {
        $filterService = app(ReceiptFilterService::class);
        return $filterService->getAvailableDestinations();
    }

    /**
     * Get filtered destinations based on search query
     */
    public function getFilteredDestinationsProperty()
    {
        $destinations = $this->receiptDestinations;
        
        if (!$this->destinationSearch) {
            return collect();
        }
        
        return collect($destinations)
            ->filter(fn($name) => str_contains(strtolower($name), strtolower($this->destinationSearch)))
            ->take(10);
    }

    /**
     * Get all allocation points for optional filter
     */
    public function getAllocationPointsProperty()
    {
        return AllocationPoint::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Apply receipt filters
     */
    public function applyReceiptFilters(): void
    {
        // Filters are applied automatically via wire:model
    }

    /**
     * Reset all receipt filters
     */
    public function resetReceiptFilters(): void
    {
        $this->receiptFilters = [
            'receipt_number' => null,
            'destination_id' => null,
            'allocation_point_id' => null,
            'start_date' => null,
            'start_time' => null,
            'end_date' => null,
            'end_time' => null,
            'sort_by' => 'date',
            'sort_direction' => 'desc',
        ];
        $this->destinationSearch = '';
    }

    /**
     * Sort receipts by column
     */
    public function sortReceiptsBy($column): void
    {
        if ($this->receiptFilters['sort_by'] === $column) {
            $this->receiptFilters['sort_direction'] = 
                $this->receiptFilters['sort_direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            $this->receiptFilters['sort_by'] = $column;
            $this->receiptFilters['sort_direction'] = 'asc';
        }
    }

    /**
     * Select a destination from search results
     */
    public function selectDestination(string $destinationId): void
    {
        $this->receiptFilters['destination_id'] = $destinationId;
        $this->destinationSearch = '';
    }

    /**
     * Clear selected destination
     */
    public function clearDestination(): void
    {
        $this->receiptFilters['destination_id'] = null;
        $this->destinationSearch = '';
    }

    /**
     * Export receipts to Excel
     */
    public function exportReceipts()
    {
        $params = array_merge($this->receiptFilters, []);

        // Remove null values
        $params = array_filter($params, fn($value) => $value !== null);

        $this->dispatch('open-export-url', url: route('export.receipts', $params));
    }
}
