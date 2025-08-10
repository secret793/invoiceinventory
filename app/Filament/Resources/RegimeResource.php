<?php

// app/Filament/Resources/RegimeResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\RegimeResource\Pages;
use App\Models\Regime;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;


class RegimeResource extends Resource
{
    protected static ?string $model = Regime::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->maxLength(1000),

                    Select::make('status')
                    ->required()
                    ->options([
                        'Active' => 'Active',
                        'Inactive' => 'Inactive',
                    ])
                    ->label('Status'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegimes::route('/'),
            'create' => Pages\CreateRegime::route('/create'),
            'edit' => Pages\EditRegime::route('/{record}/edit'),
        ];
    }
}