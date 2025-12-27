<?php

namespace App\Filament\Resources\DestinationResource\Pages;

use App\Filament\Resources\DestinationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;

class CreateDestination extends CreateRecord
{
    protected static string $resource = DestinationResource::class;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->unique()
                ->label('Name'),

            Forms\Components\Select::make('regime_id')
                ->required()
                ->relationship('regime', 'name') // Assuming a relationship exists
                ->label('Regime'),

            Forms\Components\Textarea::make('Address')
                ->nullable()
                ->label('Address'),

            Forms\Components\Select::make('status')
                ->required()
                ->options([
                    'Active' => 'Active',
                    'Inactive' => 'Inactive',
                ])
                ->label('Status'),

            Forms\Components\Checkbox::make('default_location')
                ->label('Default Location for Retrieval Officers')
                ->nullable(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return '/admin/destinations';
    }
}
