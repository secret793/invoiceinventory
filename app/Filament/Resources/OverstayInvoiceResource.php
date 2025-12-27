<?php

namespace App\Filament\Resources;

use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\OverstayInvoiceResource\Pages;

class OverstayInvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Overstay Receipts';
    protected static ?string $navigationGroup = 'Finance Management';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole(['Finance Officer', 'Super Admin', 'Warehouse Manager']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($q) {
                $q->where('overstay_days', '>', 0)
                  ->orWhere('status', 'WAIVED');
            })
            ->with(['deviceRetrieval', 'deviceRetrieval.destination', 'deviceRetrieval.allocationPoint', 'approver']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->disabled(),

                        Forms\Components\DatePicker::make('reference_date')
                            ->label('Reference Date')
                            ->disabled(),

                        Forms\Components\TextInput::make('sad_boe')
                            ->label('SAD/BOE')
                            ->disabled(),

                        Forms\Components\TextInput::make('regime')
                            ->label('Regime')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Overstay Information')
                    ->schema([
                        Forms\Components\TextInput::make('overstay_days')
                            ->label('Overstay Days')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('penalty_amount')
                            ->label('Penalty Amount (D)')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'PP' => 'Pending Payment',
                                'PD' => 'Paid',
                                'WAIVED' => 'Waived',
                            ])
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Receipt #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sad_boe')
                    ->label('SAD')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('deviceRetrieval.destination.name')
                    ->label('Destination')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('deviceRetrieval.allocationPoint.name')
                    ->label('Allocation Point')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('deviceRetrieval.device_id')
                    ->label('Device')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('overstay_days')
                    ->label('Days')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('penalty_amount')
                    ->label('Amount (D)')
                    ->money('GMD')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'PP',
                        'success' => 'PD',
                        'info' => 'WAIVED',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference_date')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('reference_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PP' => 'Pending Payment',
                        'PD' => 'Paid',
                        'WAIVED' => 'Waived',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListOverstayInvoices::route('/'),
            'view' => Pages\ViewOverstayInvoice::route('/{record}'),
        ];
    }
}
