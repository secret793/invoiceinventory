<?php

namespace App\Filament\Resources;

use App\Models\DispatchFinanceRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\DispatchFinanceRecordResource\Pages;

class DispatchFinanceRecordResource extends Resource
{
    protected static ?string $model = DispatchFinanceRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Overstay Invoice';
    protected static ?string $navigationGroup = 'Finance Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dispatch Information')
                    ->schema([
                        Forms\Components\Select::make('receipt_id')
                            ->label('Receipt Number')
                            ->relationship('receipt', 'receipt_number')
                            ->searchable()
                            ->preload()
                            ->disabled(),

                        Forms\Components\Select::make('device_id')
                            ->label('Device')
                            ->relationship('device', 'device_id')
                            ->searchable()
                            ->preload()
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('dispatch_date')
                            ->label('Dispatch Date')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount_gmd')
                            ->label('Total Amount (GMD)')
                            ->numeric()
                            ->disabled()
                            ->prefix('D'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'PENDING' => 'Pending',
                                'COMPLETED' => 'Completed',
                                'CANCELLED' => 'Cancelled',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Notes')
                    ->schema([
                        Forms\Components\Textarea::make('finance_notes')
                            ->label('Finance Notes')
                            ->maxLength(1000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('receipt.receipt_number')
                    ->label('Receipt No.')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('receipt.sad_number')
                    ->label('SAD')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('device.device_id')
                    ->label('Device_id')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('dispatch_date')
                    ->label('Dispatched_at')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dispatched_by')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('confirmedAffixed.boe')
                    ->label('Boe')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('confirmedAffixed.vehicle_number')
                    ->label('Vehicle')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('confirmedAffixed.regime')
                    ->label('Regime')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('receipt.route.name')
                    ->label('Route')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('receipt.longRoute.name')
                    ->label('Long Route')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('receipt.allocationPoint.name')
                    ->label('Allocation Point')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('dispatch_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'COMPLETED' => 'Completed',
                        'CANCELLED' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListDispatchFinanceRecords::route('/'),
            'view' => Pages\ViewDispatchFinanceRecord::route('/{record}'),
            'edit' => Pages\EditDispatchFinanceRecord::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Auto-created on dispatch
    }
}
