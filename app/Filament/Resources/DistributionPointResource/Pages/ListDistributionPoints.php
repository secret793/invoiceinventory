<?php

namespace App\Filament\Resources\DistributionPointResource\Pages;

use App\Filament\Resources\DistributionPointResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Notifications\Notification;

class ListDistributionPoints extends ListRecords
{
    protected static string $resource = DistributionPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('address')
                    ->label('Address')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('contact_person')
                    ->label('Contact Person')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('contact_number')
                    ->label('Contact Number')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Add any filters you need here
            ])
            ->actions([
                EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
