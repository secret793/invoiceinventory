<?php

namespace App\Filament\Resources\DistributionPointResource\Pages;

use App\Filament\Resources\DistributionPointResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDistributionPoint extends EditRecord
{
    protected static string $resource = DistributionPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
