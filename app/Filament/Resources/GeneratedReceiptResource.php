<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeneratedReceiptResource\Pages;
use App\Models\Receipt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\MultiSelectFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GeneratedReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Generated Receipts';

    protected static ?string $navigationGroup = 'Finance Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Receipt Information')
                    ->schema([
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('Receipt Number')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\DateTimePicker::make('date')
                            ->label('Receipt Date')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('consignment_nature')
                            ->label('Consignment Nature')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('sad_number')
                            ->label('SAD Number')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Route & Destination')
                    ->schema([
                        Forms\Components\TextInput::make('route.name')
                            ->label('Route')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('destination.name')
                            ->label('Destination')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('billing_unit')
                            ->label('Billing Unit')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('moving_trucks')
                            ->label('Moving Trucks')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('total_charge_gmd')
                            ->label('Total Charge (GMD)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->prefix('D'),

                        Forms\Components\TextInput::make('used')
                            ->label('Usage Count')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Agent & Consignment')
                    ->schema([
                        Forms\Components\TextInput::make('agent_name')
                            ->label('Agent Name')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('agent_phone')
                            ->label('Agent Phone')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Textarea::make('consignee_details')
                            ->label('Consignee Details')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Textarea::make('description_of_goods')
                            ->label('Description of Goods')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Generated Information')
                    ->schema([
                        Forms\Components\TextInput::make('generatedByUser.name')
                            ->label('Generated By')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Created At')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')
                    ->label('Receipt Number')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('sad_number')
                    ->label('SAD')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('route.name')
                    ->label('Route')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('longRoute.name')
                    ->label('Long Route')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('allocationPoint.name')
                    ->label('Allocation Point')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('destination.name')
                    ->label('Destination')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('moving_trucks')
                    ->label('Trucks')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_charge_gmd')
                    ->label('Total (GMD)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'D ' . number_format($state, 2)),

                BadgeColumn::make('used')
                    ->label('Usage Status')
                    ->getStateUsing(fn ($record) => "{$record->used} remaining")
                    ->colors([
                        'success' => fn ($state, $record) => $record->used > 0,
                        'danger' => fn ($state, $record) => $record->used === 0,
                    ]),

                TextColumn::make('generatedByUser.name')
                    ->label('Generated By')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                MultiSelectFilter::make('consignment_nature')
                    ->label('Consignment Nature')
                    ->options([
                        'CN' => 'CN – Containers',
                        'FT' => 'FT – Fuel Tanker',
                        'GC' => 'GC – General Cargo',
                        'OV' => 'OV – Overland Vehicles',
                    ]),

                SelectFilter::make('status')
                    ->label('Usage Status')
                    ->options([
                        'available' => 'Available',
                        'used' => 'Fully Used',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'available') {
                            return $query->where('used', '>', 0);
                        } elseif ($data['value'] === 'used') {
                            return $query->where('used', '=', 0);
                        }
                        return $query;
                    }),

                Filter::make('created_at')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Action::make('view_pdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Receipt $record) => route('receipts.pdf', $record), shouldOpenInNewTab: true),

                Action::make('download_pdf')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Receipt $record) => route('receipts.pdf', $record) . '?download=true', shouldOpenInNewTab: true),

                Tables\Actions\ViewAction::make()
                    ->label('Details'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No bulk delete for generated receipts in finance view
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No receipts generated yet')
            ->emptyStateDescription('Generated receipts will appear here once they are created.');
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
            'index' => Pages\ListGeneratedReceipts::route('/'),
            'view' => Pages\ViewGeneratedReceipt::route('/{record}'),
        ];
    }

    /**
     * Scope to only show receipts with generated_by_user set
     * This ensures we're only showing generated receipts, not legacy data
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('generated_by_user')
            ->with(['route', 'longRoute', 'destination', 'allocationPoint', 'generatedByUser']);
    }

    /**
     * Grant access to Finance Officers and Admin roles only
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $user->hasRole(['Super Admin', 'Warehouse Manager', 'Finance Officer']);
    }

    /**
     * Grant view any permission to Finance Officers
     */
    public static function canViewAny(): bool
    {
        return self::canAccess();
    }

    /**
     * Grant view permission to Finance Officers
     */
    public static function canView(Model $record): bool
    {
        return self::canAccess();
    }

    /**
     * Prevent creation in Generated Receipts resource
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Prevent editing in Generated Receipts resource
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Prevent deletion in Generated Receipts resource
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
