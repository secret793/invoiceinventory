<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemSettingResource\Pages;
use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SystemSettingResource extends Resource
{
    protected static ?string $model = SystemSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'System Settings';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Information')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Setting Key')
                            ->disabled()
                            ->required(),

                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->required()
                            ->numeric()
                            ->minValue(50)
                            ->maxValue(100)
                            ->step(0.0001)
                            ->helperText('Enter the exchange rate: GMD per 1 USD'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->disabled()
                            ->rows(2),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Setting')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords($state, '_'))),

                Tables\Columns\TextColumn::make('value')
                    ->label('Current Value')
                    ->formatStateUsing(function (string $state, Model $record): string {
                        if ($record->key === 'exchange_rate_gmd_usd') {
                            return number_format((float) $state, 4) . ' GMD/USD';
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->wrap(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Disable bulk actions
            ])
            ->defaultSort('key', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemSettings::route('/'),
            'edit' => Pages\EditSystemSetting::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Only allow editing existing settings
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Cannot delete system settings
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('Super Admin');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole('Super Admin');
    }
}
