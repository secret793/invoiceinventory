<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ManageInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_paid_invoices')
                ->label('Export Paid Invoices')
                ->color('primary')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    // Get all paid invoices
                    $paidInvoices = Invoice::where('status', 'PD')->get();

                    if ($paidInvoices->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->title('No Paid Invoices')
                            ->body('There are no paid invoices to export.')
                            ->warning()
                            ->send();
                        return;
                    }

                    // Create a zip file of PDFs
                    $zipFileName = 'paid_invoices_' . now()->format('YmdHis') . '.zip';
                    $zip = new ZipArchive();
                    $zipPath = storage_path("app/invoices/{$zipFileName}");
                    
                    if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                        foreach ($paidInvoices as $record) {
                            $pdf = Pdf::loadView('pdfs.invoice', [
                                'invoice' => $record,
                                'deviceRetrieval' => $record->deviceRetrieval,
                            ]);
                            
                            $filename = "invoice_{$record->reference_number}.pdf";
                            $zip->addFromString($filename, $pdf->output());
                        }
                        $zip->close();
                    }

                    // Download zip
                    return response()->download($zipPath, $zipFileName);
                })
                ->visible(fn () => Invoice::where('status', 'PD')->exists()),
        ];
    }
}
