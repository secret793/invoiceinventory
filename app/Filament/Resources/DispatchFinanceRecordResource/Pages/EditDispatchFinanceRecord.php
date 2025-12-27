<?php

namespace App\Filament\Resources\DispatchFinanceRecordResource\Pages;

use App\Filament\Resources\DispatchFinanceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDispatchFinanceRecord extends EditRecord
{
    protected static string $resource = DispatchFinanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
