<?php

namespace App\Filament\Resources\OtherItemResource\Pages;

use App\Filament\Resources\OtherItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOtherItem extends CreateRecord
{
    protected static string $resource = OtherItemResource::class;

    protected function getRedirectUrl(): string
    {
        return '/admin/other-items';
    }
}
