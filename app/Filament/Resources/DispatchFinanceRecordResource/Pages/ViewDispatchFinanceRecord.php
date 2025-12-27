<?php

namespace App\Filament\Resources\DispatchFinanceRecordResource\Pages;

use App\Filament\Resources\DispatchFinanceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDispatchFinanceRecord extends ViewRecord
{
    protected static string $resource = DispatchFinanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
