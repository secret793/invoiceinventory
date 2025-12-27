<?php

namespace App\Filament\Resources\DispatchLogResource\Pages;

use App\Filament\Resources\DispatchLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDispatchLog extends EditRecord
{
    protected static string $resource = DispatchLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
