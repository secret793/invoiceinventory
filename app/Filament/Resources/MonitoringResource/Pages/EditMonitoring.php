<?php

namespace App\Filament\Resources\MonitoringResource\Pages;

use App\Filament\Resources\MonitoringResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonitoring extends EditRecord
{
    protected static string $resource = MonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions in header
        ];
    }
}
