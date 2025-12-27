<?php

namespace App\Exports;

use App\Services\OverstayDeviceFilterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class OverstayInvoicePDFExport
{
    /**
     * @var array
     */
    private array $filters;

    /**
     * @var int
     */
    private int $totalDevices = 0;

    /**
     * @var float
     */
    private float $totalAmount = 0;

    /**
     * @var int
     */
    private int $totalDays = 0;

    /**
     * @var float
     */
    private float $averageDays = 0;

    /**
     * Constructor
     * 
     * @param array $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Generate PDF
     * 
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generate()
    {
        $service = app(OverstayDeviceFilterService::class);
        
        // Get filtered invoices
        $invoices = $service->applyFilters($this->filters)->get();
        
        // Calculate statistics
        $this->totalDevices = $invoices->count();
        $this->totalAmount = $invoices->sum('total_amount');
        $this->totalDays = $invoices->sum('overstay_days');
        $this->averageDays = $this->totalDevices > 0 
            ? round($this->totalDays / $this->totalDevices, 2)
            : 0;
        
        // Get exported data
        $data = $service->export($this->filters);
        
        // Get statistics
        $statistics = $service->getStatistics($this->filters);
        
        // Get filter descriptions for report header
        $filterDescriptions = $this->getFilterDescriptions();
        
        // Generate PDF
        return Pdf::loadView('exports.overstay-devices-pdf', [
            'invoices' => $data,
            'statistics' => $statistics,
            'filterDescriptions' => $filterDescriptions,
            'totalDevices' => $this->totalDevices,
            'totalAmount' => $this->totalAmount,
            'totalDays' => $this->totalDays,
            'averageDays' => $this->averageDays,
            'generatedAt' => now(),
        ])
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
            'logOutputFile' => storage_path('logs/pdf.log'),
        ]);
    }

    /**
     * Download PDF
     * 
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download()
    {
        $filename = 'overstay-devices-' . now()->format('Y-m-d-His') . '.pdf';
        return $this->generate()->download($filename);
    }

    /**
     * Stream PDF to browser
     * 
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream()
    {
        return $this->generate()->stream();
    }

    /**
     * Get human-readable filter descriptions
     * 
     * @return array
     */
    private function getFilterDescriptions(): array
    {
        $descriptions = [];
        
        if (!empty($this->filters['device_id'])) {
            $descriptions[] = 'Device ID: ' . $this->filters['device_id'];
        }
        
        if (!empty($this->filters['boe'])) {
            $descriptions[] = 'SAD/BOE: ' . $this->filters['boe'];
        }
        
        if (!empty($this->filters['invoice_number'])) {
            $descriptions[] = 'Invoice Number: ' . $this->filters['invoice_number'];
        }
        
        if (!empty($this->filters['destination_id'])) {
            $destination = \App\Models\Destination::find($this->filters['destination_id']);
            if ($destination) {
                $descriptions[] = 'Destination: ' . $destination->name;
            }
        }
        
        if (!empty($this->filters['allocation_point_id'])) {
            $allocationPoint = \App\Models\AllocationPoint::find($this->filters['allocation_point_id']);
            if ($allocationPoint) {
                $descriptions[] = 'Allocation Point: ' . $allocationPoint->name;
            }
        }
        
        if (!empty($this->filters['payment_status'])) {
            $statusMap = ['PP' => 'Pending Payment', 'PD' => 'Paid', 'WAIVED' => 'Waived'];
            $status = $statusMap[$this->filters['payment_status']] ?? $this->filters['payment_status'];
            $descriptions[] = 'Payment Status: ' . $status;
        }
        
        if (!empty($this->filters['overstay_amount_min'])) {
            $descriptions[] = 'Minimum Amount: D' . number_format($this->filters['overstay_amount_min'], 2);
        }
        
        if (!empty($this->filters['overstay_amount_max'])) {
            $descriptions[] = 'Maximum Amount: D' . number_format($this->filters['overstay_amount_max'], 2);
        }
        
        if (!empty($this->filters['overstay_days_min'])) {
            $descriptions[] = 'Minimum Days: ' . $this->filters['overstay_days_min'];
        }
        
        if (!empty($this->filters['overstay_days_max'])) {
            $descriptions[] = 'Maximum Days: ' . $this->filters['overstay_days_max'];
        }
        
        if (!empty($this->filters['start_date'])) {
            $descriptions[] = 'Start Date: ' . \Carbon\Carbon::parse($this->filters['start_date'])->format('d/m/Y');
        }
        
        if (!empty($this->filters['end_date'])) {
            $descriptions[] = 'End Date: ' . \Carbon\Carbon::parse($this->filters['end_date'])->format('d/m/Y');
        }
        
        return $descriptions;
    }
}
