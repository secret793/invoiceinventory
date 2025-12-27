<?php

namespace App\Filament\Resources\RegimeResource\Pages;

use App\Filament\Resources\RegimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegimes extends ListRecords
{
    protected static string $resource = RegimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
