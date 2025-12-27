<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Models\Receipt;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry as InfoTextEntry;
use Filament\Infolists\Infolist;

class ListReceipts extends ListRecords
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Generate Receipt')
                ->icon('heroicon-o-plus'),
            
            Action::make('viewReceipts')
                ->label('View Generated Receipts')
                ->icon('heroicon-o-eye')
                ->modalHeading('Generated Receipts')
                ->modalWidth('7xl')
                ->action(fn () => null)
                ->form([
                    Section::make('All Receipts')
                        ->schema([
                            Repeater::make('receipts')
                                ->schema([
                                    TextEntry::make('receipt_number')
                                        ->label('Receipt Number'),
                                    TextEntry::make('date')
                                        ->label('Date')
                                        ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('Y-m-d H:i') : 'N/A'),
                                    TextEntry::make('moving_trucks')
                                        ->label('Trucks'),
                                    TextEntry::make('used')
                                        ->label('Used'),
                                    TextEntry::make('total_charge_gmd')
                                        ->label('Amount (GMD)')
                                        ->formatStateUsing(fn ($state) => 'D ' . number_format($state, 2)),
                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->formatStateUsing(fn ($state) => $state ?? 'ACTIVE'),
                                ])
                                ->disabled()
                                ->defaultItems(0)
                                ->state(fn () => Receipt::all()->toArray()),
                        ]),
                ]),
        ];
    }
}
