<?php

namespace App\Filament\Resources\DispatchLogResource\Pages;

use App\Filament\Resources\DispatchLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDispatchLogs extends ListRecords
{
    protected static string $resource = DispatchLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('New Dispatch Log'),
            Actions\ExportAction::make()
                ->icon('heroicon-o-arrow-down-tray')
                ->label('Export')
                ->color('success'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->latest('dispatched_at');
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'dispatched_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }
}
