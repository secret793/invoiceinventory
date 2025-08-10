<?php

namespace App\Filament\Resources\DestinationResource\Pages;

use App\Filament\Resources\DestinationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDestinations extends ListRecords
{
    protected static string $resource = DestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'regime.name' => 'Regime', // Assuming a relationship exists
            'description' => 'Address',
            'default_location' => 'Default Location',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
