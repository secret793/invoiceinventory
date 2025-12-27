<?php

namespace App\Filament\Resources\GeneratedReceiptResource\Pages;

use App\Filament\Resources\GeneratedReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGeneratedReceipt extends ViewRecord
{
    protected static string $resource = GeneratedReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_pdf')
                ->label('View PDF')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => route('receipts.pdf', $this->record), shouldOpenInNewTab: true),

            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => route('receipts.pdf', $this->record) . '?download=true', shouldOpenInNewTab: true),
        ];
    }
}
