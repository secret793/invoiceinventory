<?php

namespace App\Filament\Resources\OtherItemResource\Pages;

use App\Filament\Resources\OtherItemResource;
use Filament\Resources\Pages\EditRecord;

class EditOtherItem extends EditRecord
{
    protected static string $resource = OtherItemResource::class;

    protected function getRedirectUrl(): string
    {
        return '/admin/other-items';
    }
}
