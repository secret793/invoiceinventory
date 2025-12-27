<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Models\Store;
use App\Models\DistributionPoint;
use App\Models\User; // Import the User model
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use App\Imports\StoresImport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\BelongsTo;
use Filament\Tables\Actions\ActionGroup;
use App\Models\Device;
use Filament\Forms\Get;
use Filament\Forms\Set;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
        ->schema([
            Select::make('device_type')
                ->label('Device Type')
                ->options([
                    'JT701' => 'JT701',
                    'JT709A' => 'JT709A',
                    'JT709C' => 'JT709C',
                ])
                ->required(),
            TextInput::make('serial_number')
                ->label('Device ID')
                ->required()
                ->maxLength(255)
                ->unique(Store::class, 'serial_number', ignoreRecord: true)
                ->rules(['regex:/^[A-Za-z0-9-]+$/'])
                ->placeholder('Enter Device ID'),
            TextInput::make('batch_number')
                ->label('Batch Number')
                ->required(),
            Select::make('status')
                ->label('Status')
                ->options([
                    'UNCONFIGURED' => 'Unconfigured',
                    'CONFIGURED' => 'Configured',
                    'ONLINE' => 'Online',
                    'OFFLINE' => 'Offline',
                    'DAMAGED' => 'Damaged',
                    'FIXED' => 'Fixed',
                    'LOST' => 'Lost',
                ])
                ->default('CONFIGURED')
                ->required(),
            DatePicker::make('date_received')
                ->label('Date Received')
                ->required()
                ->maxDate(now()),
            TextInput::make('user_id')
                ->label('User ID')
                ->default(auth()->id())
                ->hidden(),
           // TextInput::make('sim_number')
                //->required(fn (Get $get): bool => $get('status') !== 'UNCONFIGURED')
              //  ->unique(
                    //table: Store::class,
                    //column: 'sim_number',
                    //ignoreRecord: true,
                    //modifyRuleUsing: function ($rule) {
                        //return $rule->whereNotNull('sim_number')
                            //->ignore(request()->route('record'));
                    //}
                //),
                
           // TextInput::make('sim_operator')
              //  ->label('SIM Operator')
              //  ->required(fn (Get $get): bool => $get('status') !== 'UNCONFIGURED')
                //->hidden(fn (Get $get): bool => $get('status') === 'UNCONFIGURED'),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
        ->columns([
            TextColumn::make('serial_number')
                ->label('Device ID')
                ->sortable()
                ->searchable(),
            TextColumn::make('device_type')
                ->label('Device Type')
                ->sortable()
                ->searchable(),
            TextColumn::make('batch_number')
                ->label('Batch Number')
                ->sortable()
                ->searchable(),
            TextColumn::make('status')
                ->sortable()
                ->searchable()
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'UNCONFIGURED' => 'warning',
                    'ONLINE' => 'success',
                    'OFFLINE' => 'danger',
                    'DAMAGED' => 'danger',
                    'FIXED' => 'success',
                    'LOST' => 'danger',
                    default => 'secondary',
                }),
            TextColumn::make('date_received')
                ->label('Date Received')
                ->date()
                ->sortable(),
                TextColumn::make('sim_number')
                ->label('SIM Number')
                ->sortable()
                ->searchable()
                ->toggleable(),
                TextColumn::make('sim_operator')
                ->label('SIM Operator')
                ->sortable()
                ->searchable()
                ->toggleable(),
            TextColumn::make('user.name')
                ->label('Added By')
                ->sortable()
                ->searchable(),
        ])
        ->filters([
            SelectFilter::make('status')
                ->options([
                    'UNCONFIGURED' => 'Unconfigured',
                    'ONLINE' => 'Online',
                    'OFFLINE' => 'Offline',
                    'DAMAGED' => 'Damaged',
                    'FIXED' => 'Fixed',
                    'LOST' => 'Lost',
                ]),
        ])
        ->actions([
            Action::make('edit')
                ->icon('heroicon-o-pencil')
                ->form([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'UNCONFIGURED' => 'Unconfigured',
                            'CONFIGURED' => 'Configured',
                            'ONLINE' => 'Online',
                            'OFFLINE' => 'Offline',
                            'DAMAGED' => 'Damaged',
                            'FIXED' => 'Fixed',
                            'LOST' => 'Lost',
                        ])
                        ->required()
                        ->live(),
                    
                    TextInput::make('sim_number')
                        ->required(fn (Get $get): bool => $get('status') !== 'UNCONFIGURED')
                        ->unique(
                            table: Store::class,
                            column: 'sim_number',
                            ignoreRecord: true,
                            modifyRuleUsing: function ($rule) {
                                return $rule->whereNotNull('sim_number')
                                    ->ignore(request()->route('record'));
                            }
                        )
                        ->hidden(fn (Get $get): bool => $get('status') === 'UNCONFIGURED'),
                    TextInput::make('sim_operator')
                        ->label('SIM Operator')
                        ->required(fn (Get $get): bool => $get('status') !== 'UNCONFIGURED')
                        ->hidden(fn (Get $get): bool => $get('status') === 'UNCONFIGURED'),
                ])
                ->action(function (Store $record, array $data) {
                    $isConfigured = $data['status'] !== 'UNCONFIGURED';
                    
                    // Update store record
                    $record->update([
                        'status' => $data['status'],
                        'sim_number' => $isConfigured ? ($data['sim_number'] ?? null) : null,
                        'sim_operator' => $isConfigured ? ($data['sim_operator'] ?? null) : null,
                    ]);

                    // Update corresponding device
                    if ($device = Device::where('device_id', $record->serial_number)->first()) {
                        $device->update([
                            'status' => $data['status'],
                            'sim_number' => $isConfigured ? ($data['sim_number'] ?? null) : null,
                            'sim_operator' => $isConfigured ? ($data['sim_operator'] ?? null) : null,
                        ]);
                    }

                    Notification::make()
                        ->title('Device updated successfully')
                        ->success()
                        ->send();
                }),

            // Add Delete Action
            Tables\Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->action(function ($record) {
                    // Delete corresponding device record
                    Device::where('device_id', $record->serial_number)->delete();
                    // Delete store record
                    $record->delete();

                    Notification::make()
                        ->title('Record deleted successfully')
                        ->success()
                        ->send();
                }),
        ])
        ->bulkActions([
            // Bulk Status Change Action
            BulkAction::make('changeStatus')
                ->label('Change Status')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    Select::make('status')
                        ->label('New Status')
                        ->options([
                            'UNCONFIGURED' => 'Unconfigured',
                            'CONFIGURED' => 'Configured',
                            'ONLINE' => 'Online',
                            'OFFLINE' => 'Offline',
                            'DAMAGED' => 'Damaged',
                            'FIXED' => 'Fixed',
                            'LOST' => 'Lost',
                        ])
                        ->required(),
                ])
                ->action(function (Collection $records, array $data) {
                    $records->each(function ($record) use ($data) {
                        $record->update(['status' => $data['status']]);
                        
                        // Update corresponding device
                        if ($device = Device::where('device_id', $record->serial_number)->first()) {
                            $device->update(['status' => $data['status']]);
                        }
                    });

                    Notification::make()
                        ->title('Status updated successfully')
                        ->success()
                        ->send();
                }),

            // Bulk action for assigning to distribution point
            BulkAction::make('assignDistributionPoint')
                ->label('Assign to Distribution Point')
                ->icon('heroicon-o-map-pin')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    Select::make('distribution_point_id')
                        ->label('Distribution Points')
                        ->options(DistributionPoint::pluck('name', 'id'))
                        ->required(),
                    TextInput::make('35cm_cable')
                        ->label('35CM Cable')
                        ->numeric(),
                    TextInput::make('3_meters_cable')
                        ->label('3 METERS Cable')
                        ->numeric(),
                    TextInput::make('60_meters_cable')
                        ->label('60 METERS Cable')
                        ->numeric(),
                ])
                ->action(function (Collection $records, array $data) {
                    // Check for unconfigured devices
                    $unconfiguredDevices = $records->where('status', 'Unconfigured');
                    
                    if ($unconfiguredDevices->isNotEmpty()) {
                        Notification::make()
                            ->title('Cannot distribute unconfigured devices')
                            ->body('Please configure the devices before distribution.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Proceed with distribution for configured devices
                    $records->each(function ($record) use ($data) {
                        $record->update([
                            'distribution_point_id' => $data['distribution_point_id'],
                            '35cm_cable' => $data['35cm_cable'] ?? null,
                            '3_meters_cable' => $data['3_meters_cable'] ?? null,
                            '60_meters_cable' => $data['60_meters_cable'] ?? null,
                        ]);
                    });

                    Notification::make()
                        ->title('Devices assigned to distribution point successfully')
                        ->success()
                        ->send();
                }),

            // Delete Action
            Tables\Actions\DeleteBulkAction::make()
                ->action(function (Collection $records) {
                    // Delete corresponding device records
                    Device::whereIn('device_id', $records->pluck('serial_number'))->delete();
                    // Delete store records
                    $records->each->delete();
                }),
        ])
        ->modifyQueryUsing(function (Builder $query) {
            // Show only devices that are either:
            // 1. Not assigned to a distribution point (NULL)
            // 2. Have UNCONFIGURED status
            $query->where(function ($query) {
                $query->whereNull('distribution_point_id')
                    ->orWhere('status', 'UNCONFIGURED');
            })
            ->orderByRaw("CASE 
                WHEN status = 'UNCONFIGURED' THEN 0 
                ELSE 1 
                END")
            // Sort by newest devices first, after the status priority
            ->orderBy('date_received', 'desc')
            ->orderBy('created_at', 'desc'); // Additional sorting by creation date
        });
    }

    protected function getTableActions(): array
{
    return [
        Action::make('import')
            ->label('Import Excel')
            ->action(function (array $data) {
                // Validate and store the file in storage/app/public/excels
                $filePath = $data['file']->storeAs('excels', $data['file']->getClientOriginalName(), 'public');

                // Process the Excel file after it's stored
                Excel::import(new StoresImport, Storage::disk('public')->path($filePath));

                // Provide feedback to the user
                $this->notify('success', 'File imported successfully.');
            })
            ->form([
                FileUpload::make('file')
                    ->label('Excel File')
                    ->disk('public') // Save in the public disk
                    ->directory('excels') // Store files in storage/app/public/excels
                    ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']) // Only accept Excel files
                    ->required(),
            ]),
        BulkAction::make('changeStatus')
            ->label('Change Status')
            ->icon('heroicon-o-arrow-path')
            ->form([
                Select::make('status')
                    ->label('New Status')
                    ->options([
                        'UNCONFIGURED' => 'Unconfigured',
                        'CONFIGURED' => 'Configured',
                        'ONLINE' => 'Online',
                        'OFFLINE' => 'Offline',
                        'DAMAGED' => 'Damaged',
                        'FIXED' => 'Fixed',
                        'LOST' => 'Lost',
                    ])
                    ->required(),
            ])
    ];
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
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }

    public static function downloadExcel()
    {
        $filePath = storage_path('app/public/Testing 3.xlsx'); // Path to the Excel file

        if (file_exists($filePath)) {
            return response()->download($filePath);
        }

        return response()->json(['message' => 'File not found.'], 404);
    }

    public static function getHeaderActions(): array
    {
        return [
            // Actions::createAction()
            //     ->label('New Store')
            //     ->icon('heroicon-o-plus')
            //     ->form([
            //         // Form fields...
            //     ]),

            // Actions::action('import', function () {
            //     // Import logic...
            // })
            //     ->label('Import Products')
            //     ->icon('heroicon-o-arrow-up-tray'),

            // Actions::action('export', function () {
            //     return Excel::download(new StoresExport, 'stores.xlsx');
            // })
            //     ->label('Download Excel')
            //     ->icon('heroicon-o-arrow-down-tray'),
        ];
    }
}
