<?php

namespace App\Filament\Resources\DeviceRetrievalResource\Pages;

use App\Filament\Resources\DeviceRetrievalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeviceRetrieval extends EditRecord
{
    protected static string $resource = DeviceRetrievalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
