<?php

namespace App\Filament\Resources\AllocationPointResource\Pages;

use App\Filament\Resources\AllocationPointResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAllocationPoint extends EditRecord
{
    protected static string $resource = AllocationPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return '/admin/allocation-points';
    }
}

