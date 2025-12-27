<?php

namespace App\Filament\Resources\LongRouteResource\Pages;

use App\Filament\Resources\LongRouteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLongRoutes extends ListRecords
{
    protected static string $resource = LongRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
