<?php

namespace App\Filament\Resources\AllocationPointResource\Pages;

use App\Filament\Resources\AllocationPointResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;


class CreateAllocationPoint extends CreateRecord
{
    protected static string $resource = AllocationPointResource::class;

    protected function getRedirectUrl(): string
    {
        return '/admin/allocation-points';
    }
}
