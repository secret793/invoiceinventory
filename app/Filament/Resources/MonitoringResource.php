<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonitoringResource\Pages;
use App\Models\Monitoring;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MonitoringResource extends Resource
{
    protected static ?string $model = Monitoring::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Device Management';

    protected static bool $canCreate = false;
    protected static bool $canDelete = false;
    protected static bool $canDeleteAny = false;
    protected static bool $canEdit = false;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('device.device_id')
                    ->label('Device ID')
                    ->searchable(),
                TextColumn::make('boe')
                    ->label('BOE')
                    ->searchable(),
                TextColumn::make('vehicle_number')
                    ->label('Vehicle Number')
                    ->searchable(),
                TextColumn::make('regime')
                    ->label('Regime')
                    ->searchable(),
                TextColumn::make('destination')
                    ->label('Destination')
                    ->searchable()
                    ->sortable(),
                // Overdue and Overstay Information
                TextColumn::make('overdue_hours')
                    ->label('Overdue Hours')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state, $record) => $state > 0 ? "{$state} hour" . ($state > 1 ? 's' : '') : 'On time')
                    ->description(fn ($record) => $record->affixing_date ? 'Since ' . $record->affixing_date->diffForHumans() : '')
                    ->searchable()
                    ->toggleable(),
                    
                TextColumn::make('overstay_days')
                    ->label('Overstay Days')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state, $record) => $state > 0 ? "{$state} day" . ($state > 1 ? 's' : '') : 'On time')
                    ->description(fn ($record) => $record->affixing_date ? 'Since ' . $record->affixing_date->diffForHumans() : '')
                    ->searchable()
                    ->toggleable(),
                    
                TextColumn::make('overstay_amount')
                    ->label('Overstay Amount')
                    ->money('GMD')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state, 2) : '0.00')
                    ->toggleable(),
                    
                // Retrieval Status
                TextColumn::make('retrieval_status')
                    ->label('Retrieval Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'RETRIEVED' => 'success',
                        'RETURNED' => 'info',
                        'NOT_RETRIEVED' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'RETRIEVED' => 'Retrieved',
                        'RETURNED' => 'Returned',
                        'NOT_RETRIEVED' => 'Not Retrieved',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PP' => 'danger',
                        'PD' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'PP' => 'Pending Payment',
                        'PD' => 'Paid',
                        default => $state,
                    }),
                TextColumn::make('retrieval_status')
                    ->label('Retrieval Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'NOT_RETRIEVED' => 'danger',
                        'RETRIEVED' => 'success',
                        'RETURNED' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'NOT_RETRIEVED' => 'Not Retrieved',
                        'RETRIEVED' => 'Retrieved',
                        'RETURNED' => 'Returned',
                        default => $state,
                    })
                    ->sortable(),
            ])
            ->filters([
                Filter::make('overstay_days')
                    ->form([
                        Forms\Components\TextInput::make('overstay_days_min')
                            ->label('Minimum Overstay Days')
                            ->numeric(),
                        Forms\Components\TextInput::make('overstay_days_max')
                            ->label('Maximum Overstay Days')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['overstay_days_min'],
                                fn (Builder $query, $min): Builder => $query->where('overstay_days', '>=', $min)
                            )
                            ->when(
                                $data['overstay_days_max'],
                                fn (Builder $query, $max): Builder => $query->where('overstay_days', '<=', $max)
                            );
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->actions([
                // No actions - view, edit, and delete are all disabled
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitorings::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Monitoring Officer']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Monitoring Officer']);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Monitoring Officer']);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Monitoring Officer']);
    }
}


