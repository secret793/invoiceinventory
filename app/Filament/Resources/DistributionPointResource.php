<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DistributionPointResource\Pages;
use App\Filament\Resources\DistributionPointResource\RelationManagers;
use App\Models\DistributionPoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class DistributionPointResource extends Resource
{
    protected static ?string $model = DistributionPoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('location')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistributionPoints::route('/'),
            'create' => Pages\CreateDistributionPoint::route('/create'),
            'edit' => Pages\EditDistributionPoint::route('/{record}/edit'),
            'view' => Pages\ViewDistributionPoint::route('/{record}'),
        ];
    }    

    public function collectDevices()
    {
        // Logic to collect devices from the received pool
    }

    public function sendToAnotherDistributionPoint()
    {
        // Logic to send selected devices to another distribution point
    }

    public function returnDeviceToInventory()
    {
        // Logic to return selected devices to the main inventory
    }

    public function changeDeviceStatus()
    {
        // Logic to change the status of selected devices
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer']);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer']);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer']);
    }
}
