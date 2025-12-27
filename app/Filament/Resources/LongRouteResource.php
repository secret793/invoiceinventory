<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LongRouteResource\Pages;
use App\Models\LongRoute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LongRouteResource extends Resource
{
    protected static ?string $model = LongRoute::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('amount')
                    ->nullable()
                    ->numeric()
                    ->step(0.01)
                    ->prefix('$')
                    ->label('Amount'),
                Forms\Components\TextInput::make('base_usd_amount')
                    ->nullable()
                    ->numeric()
                    ->step(0.01)
                    ->prefix('$')
                    ->label('Base Unit Amount (USD)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_usd_amount')
                    ->money('USD')
                    ->sortable()
                    ->label('Base Unit (USD)'),
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
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListLongRoutes::route('/'),
            'create' => Pages\CreateLongRoute::route('/create'),
            'edit' => Pages\EditLongRoute::route('/{record}/edit'),
        ];
    }    
}
