<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource\Pages;
use App\Models\Device;
use App\Models\DistributionPoint;
use App\Models\Transfer;
use App\Models\AllocationPoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter as TablesSelectFilter;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('configured')
                    ->label('Configured')
                    ->default(true)
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        if (!$state) {
                            $set('status', 'UNCONFIGURED');
                            $set('sim_number', null);
                            $set('sim_operator', null);
                        } else {
                            $set('status', 'CONFIGURED');
                        }
                    }),

                Select::make('device_type')
                    ->label('D-Type')
                    ->options([
                        'JT701' => 'JT701',
                        'JT709A' => 'JT709A',
                        'JT709C' => 'JT709C',
                    ])
                    ->required(),

                TextInput::make('device_id')
                    ->label('Device ID')
                    ->required()
                    ->unique(
                        table: Device::class,
                        column: 'device_id',
                        ignoreRecord: false,
                        modifyRuleUsing: fn ($rule) => $rule->ignore(request()->route('record'))
                    ),

                TextInput::make('batch_number')
                    ->label('Batch Number')
                    ->required()
                    ->default(fn () => 'BATCH-' . now()->format('Ymd')),

                Forms\Components\DatePicker::make('date_received')
                    ->label('Date Received')
                    ->required()
                    ->maxDate(now()),

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
                    ->hidden(fn (Get $get): bool => !$get('configured')),

                //Select::make('distribution_point_id')
                 //   ->relationship('distributionPoint', 'name')
                   // ->label('Distribution Point')
                   // ->hidden(fn (Get $get): bool => !$get('configured'))
                   // ->required(false),

                TextInput::make('sim_number')
                    ->required(fn (Get $get): bool => $get('configured'))
                    ->unique(
                        table: Device::class,
                        column: 'sim_number',
                        ignoreRecord: true,
                        modifyRuleUsing: function ($rule) {
                            return $rule->whereNotNull('sim_number')
                                ->ignore(request()->route('record'));
                        }
                    )
                    ->hidden(fn (Get $get): bool => !$get('configured')),

                TextInput::make('sim_operator')
                    ->required(fn (Get $get): bool => $get('configured'))
                    ->hidden(fn (Get $get): bool => !$get('configured')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device_id')
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
                    ->searchable(),
                TextColumn::make('date_received')
                    ->label('Receipt Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('distributionPoint.name')
                    ->label('Distribution Point')
                    ->sortable()
                    ->searchable(),
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
                TextColumn::make('allocationPoint.name')
                    ->label('Allocation Point')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cancellation_reason')
                    ->label('Cancel Note')
                    ->description(fn ($record) => $record->cancelled_at ? 'On: ' . $record->cancelled_at->format('Y-m-d H:i') : '')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->toggleable()
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Exclude devices with UNCONFIGURED and DAMAGED status from Device list
                $query->whereNotIn('status', ['UNCONFIGURED'])
                    // Sort by newest devices first
                    ->orderBy('date_received', 'desc');
            })
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'ONLINE' => 'Online',
                        'OFFLINE' => 'Offline',
                        'DAMAGED' => 'Damaged',
                        'FIXED' => 'Fixed',
                        'LOST' => 'Lost',
                        'RECEIVED' => 'Received',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['values'],
                            fn (Builder $query, $values): Builder => $query->whereIn('status', $values)
                        );
                    })
                    ->label('Status')
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['values'] ?? null) {
                            $indicators[] = 'Status: ' . collect($data['values'])->join(', ');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                // Bulk action for changing device status
                BulkAction::make('changeStatus')
                    ->label('Change Device Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Select::make('status')
                            ->label('New Status')
                            ->options([
                                'UNCONFIGURED' => 'UNCONFIGURED',
                                'CONFIGURED' => 'CONFIGURED',
                                'ONLINE' => 'ONLINE',
                                'OFFLINE' => 'OFFLINE',
                                'DAMAGED' => 'DAMAGED',
                                'FIXED' => 'FIXED',
                                'LOST' => 'LOST',
                            ])
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $records->each(function ($record) use ($data) {
                            $record->update(['status' => $data['status']]);
                        });

                        Notification::make()
                            ->title('Device status updated successfully')
                            ->success()
                            ->send();
                    }),

                // Bulk action for transferring devices
                BulkAction::make('transferToDistributionPoint')
                    ->label('Transfer to Distribution Point')
                    ->icon('heroicon-o-arrow-right')
                    ->requiresConfirmation()
                    ->form([
                        Select::make('distribution_point_id')
                            ->label('Select Distribution Point')
                            ->options(DistributionPoint::pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        // Check for devices with invalid statuses or existing assignments
                        $invalidDevices = $records->filter(function ($device) {
                            return in_array($device->status, ['OFFLINE', 'LOST', 'DAMAGED']) || 
                                   !$device->canBeTransferred() ||
                                   $device->hasActiveAssignment();
                        });

                        if ($invalidDevices->isNotEmpty()) {
                            $deviceIds = $invalidDevices->pluck('device_id')->join(', ');
                            
                            Notification::make()
                                ->title('Transfer Request Failed')
                                ->body("Unable to transfer selected devices for the following reasons:
                                    \n• Devices must not be OFFLINE, LOST, or DAMAGED
                                    \n• Devices must not be already assigned to a Distribution or Allocation point
                                    \n• Devices must not have any pending transfers
                                    \n\nAffected devices: {$deviceIds}")
                                ->danger()
                                ->send();
                                
                            return;
                        }

                        // Check for already transferred devices
                        $alreadyTransferred = $records->filter(function ($device) {
                            // Check both distribution_point_id and existing pending transfers
                            return $device->distribution_point_id !== null || 
                                   Transfer::where('device_id', $device->id)
                                         ->where('status', 'PENDING')
                                         ->exists();
                        });

                        if ($alreadyTransferred->isNotEmpty()) {
                            $deviceIds = $alreadyTransferred->pluck('device_id')->join(', ');
                            
                            Notification::make()
                                ->title('Transfer Failed')
                                ->body("The following devices are already assigned or have pending transfers: {$deviceIds}")
                                ->danger()
                                ->send();
                                
                            return;
                        }

                        // Proceed with transfer for valid devices
                        $records->each(function ($record) use ($data) {
                            Transfer::create([
                                'device_id' => $record->id,
                                'device_serial' => $record->device_id,
                                'from_location' => $record->distribution_point_id,
                                'to_location' => $data['distribution_point_id'],
                                'status' => 'PENDING',
                                'transfer_type' => 'DISTRIBUTION',
                                'distribution_point_status' => $record->status,
                                'quantity' => 1,
                            ]);
                        });

                        Notification::make()
                            ->title('Transfer request created successfully')
                            ->success()
                            ->send();
                    }),

        

                // Bulk action for deleting devices
                BulkAction::make('delete')
                    ->label('Delete Devices')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        DB::transaction(function () use ($records) {
                            // Get all device IDs including hidden ones
                            $deviceIds = $records->pluck('device_id')->toArray();

                            // Delete from store first (observer will handle this)
                            Device::whereIn('device_id', $deviceIds)
                                ->withoutGlobalScopes() // Include hidden devices
                                ->each(function ($device) {
                                    // This will trigger the DeviceObserver deleted event
                                    $device->delete();
                                });

                            Notification::make()
                                ->title('Devices deleted successfully')
                                ->success()
                                ->send();
                        });
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }

    public static function downloadExcel()
    {
        $fileName = 'Device Import Sheet(1).xlsx';
        $filePath = storage_path('app/public/' . $fileName);

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'Template file not found. Please ensure "Device Import Sheet(1).xlsx" is placed in the storage/app/public directory.'], 404);
        }

        return response()->download($filePath, $fileName);
    }

    public static function getExcelTemplate()
    {
        $headers = [
            'device_type',
            'device_id',
            'batch_number',
            'status',
            'date_received',
            'sim_number',
            'sim_operator',
            'added_by'
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Add headers
        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        // Add sample data
        $sampleData = [
            'JT701',
            'DEVICE-001',
            'BATCH-20240101',
            'UNCONFIGURED',
            now()->format('Y-m-d'),
            '1234567890',
            'OPERATOR-1',
            auth()->user()->name
        ];

        foreach ($sampleData as $index => $value) {
            $sheet->setCellValueByColumnAndRow($index + 1, 2, $value);
        }

        return $spreadsheet;
    }
}
