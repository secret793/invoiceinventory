<?php

namespace App\Filament\Resources\DispatchResource\Pages;

use App\Filament\Resources\DispatchResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDispatch extends CreateRecord
{
    protected static string $resource = DispatchResource::class;

    protected function getRedirectUrl(): string
    {
        return '/admin/dispatches';
    }
}
