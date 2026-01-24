<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptResource\Pages;
use App\Models\Receipt;
use App\Models\Route;
use App\Models\LongRoute;
use App\Models\AllocationPoint;
use App\Models\Destination;
use App\Services\ExchangeRateService;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\MultiSelectFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Finance Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section 1: Basic Information
                Section::make('Basic Information')
                    ->description('Receipt date and consignment details')
                    ->schema([
                        DateTimePicker::make('date')
                            ->label('Receipt Date')
                            ->default(now())
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Select::make('consignment_nature')
                            ->label('Consignment Nature')
                            ->options([
                                'CN' => 'CN – Containers',
                                'FT' => 'FT – Fuel Tanker',
                                'GC' => 'GC – General Cargo',
                                'OV' => 'OV – Overland Vehicles',
                            ])
                            ->required()
                            ->searchable(),

                        TextInput::make('sad_number')
                            ->label('SAD Number')
                            ->required()
                            ->maxLength(50)
                            ->unique('receipts', 'sad_number', ignoreRecord: true),
                    ])
                    ->columns(3),

                // Section 2: Route Selection
                Section::make('Route Selection')
                    ->description('Select either Route OR Long Route (at least one is required)')
                    ->schema([
                        Select::make('route_id')
                            ->label('Route')
                            ->relationship('route', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $route = Route::find($state);
                                    $set('base_unit_charge_usd', $route?->base_usd_amount);
                                    $set('billing_unit', $route?->billing_unit);
                                }
                            }),

                        Select::make('long_route_id')
                            ->label('Long Route')
                            ->relationship('longRoute', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive(),

                        TextInput::make('billing_unit')
                            ->label('Billing Unit')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('moving_trucks')
                            ->label('Moving Trucks')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set, $get) {
                                $unitCharge = $get('unit_charge_gmd');
                                if ($unitCharge && $state) {
                                    $set('total_charge_gmd', $unitCharge * $state);
                                    $set('used', $state);
                                }
                            }),
                    ])
                    ->columns(2),

                // Section 3: Location
                Section::make('Location')
                    ->description('Select allocation point and destination')
                    ->schema([
                        Select::make('allocation_point_id')
                            ->label('Allocation Point')
                            ->relationship('allocationPoint', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('destination_id')
                            ->label('Destination')
                            ->relationship('destination', 'name', fn ($query) => $query->where('status', 'Active'))
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                // Section 4: Pricing (Auto-Calculated)
                Section::make('Pricing Calculation')
                    ->description('Auto-calculated based on route and exchange rate. Exchange rate is editable.')
                    ->schema([
                        TextInput::make('base_unit_charge_usd')
                            ->label('Base Unit Charge (USD)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$'),

                        TextInput::make('exchange_rate_used')
                            ->label('Exchange Rate (GMD/USD)')
                            ->numeric()
                            ->required()
                            ->minValue(50)
                            ->maxValue(100)
                            ->step(0.0001)
                            ->helperText('Auto-fetched from API. Editable if rate drops below standard.')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set, $get) {
                                if ($state) {
                                    $baseCharge = $get('base_unit_charge_usd');
                                    if ($baseCharge) {
                                        $unitCharge = $baseCharge * $state;
                                        $set('unit_charge_gmd', $unitCharge);
                                        
                                        $trucks = $get('moving_trucks');
                                        if ($trucks) {
                                            $set('total_charge_gmd', $unitCharge * $trucks);
                                        }
                                    }
                                }
                            }),

                        TextInput::make('unit_charge_gmd')
                            ->label('Unit Charge (GMD)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('D'),

                        TextInput::make('total_charge_gmd')
                            ->label('Total Charge (GMD)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('D'),
                    ])
                    ->columns(2),

                // Section 5: Details
                Section::make('Agent & Consignment Details')
                    ->description('Agent information and consignment details')
                    ->schema([
                        TextInput::make('agent_name')
                            ->label('Agent Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('agent_phone')
                            ->label('Agent Phone')
                            ->tel()
                            ->required()
                            ->maxLength(20),

                        Textarea::make('consignee_details')
                            ->label('Consignee Details')
                            ->required()
                            ->maxLength(500),

                        Textarea::make('shipper_details')
                            ->label('Shipper Details')
                            ->maxLength(500),

                        Textarea::make('description_of_goods')
                            ->label('Description of Goods')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->columns(2),

                // Section 6: System Generated
                Section::make('System Generated')
                    ->description('Auto-populated fields')
                    ->schema([
                        TextInput::make('used')
                            ->label('Available Usage Count')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0),

                        Hidden::make('created_by')
                            ->default(Auth::id())
                            ->dehydrated(),

                        Hidden::make('generated_by_user')
                            ->default(Auth::id())
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
                    ->sortable(),

                TextColumn::make('consignment_nature')
                    ->label('Type')
                    ->sortable()
                    ->badge()
                    ->colors([
                        'primary' => 'CN',
                        'success' => 'FT',
                        'warning' => 'GC',
                        'danger' => 'OV',
                    ]),

                TextColumn::make('route.name')
                    ->label('Route')
                    ->sortable()
                    ->searchable(),

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

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->used > 0 ? 'Available' : 'Fully Used')
                    ->colors([
                        'success' => 'Available',
                        'danger' => 'Fully Used',
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
                        'FT' => 'FT – Full Truck',
                        'GC' => 'GC – General Cargo',
                        'OV' => 'OV – Oversized',
                    ]),

                SelectFilter::make('status')
                    ->label('Receipt Status')
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Receipt $record) => $record->used > 0),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Receipt $record) => $record->used === $record->moving_trucks),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListReceipts::route('/'),
            'create' => Pages\CreateReceipt::route('/create'),
            'view' => Pages\ViewReceipt::route('/{record}'),
            'edit' => Pages\EditReceipt::route('/{record}/edit'),
        ];
    }
}
