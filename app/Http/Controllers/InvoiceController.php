<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\DeviceRetrieval;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Download the invoice as PDF
     */
    public function download(Invoice $invoice)
    {
        $pdf = PDF::loadView('invoices.template', compact('invoice'));

        return $pdf->download("invoice-{$invoice->reference_number}.pdf");
    }

    /**
     * Download invoice for a device retrieval
     */
    public function downloadForRetrieval($deviceRetrievalId)
    {
        $deviceRetrieval = DeviceRetrieval::findOrFail($deviceRetrievalId);
        $invoice = Invoice::where('device_retrieval_id', $deviceRetrievalId)
            ->where('status', 'approved')
            ->first();

        if (!$invoice) {
            return back()->with('error', 'No approved invoice found for this device retrieval');
        }

        $pdf = PDF::loadView('invoices.template', compact('invoice'));

        return $pdf->download("invoice-{$invoice->reference_number}.pdf");
    }
}
