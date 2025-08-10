<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransferResource\Pages;
use App\Models\Transfer;
use App\Models\Device;
use App\Models\DistributionPoint;
use App\Models\AllocationPoint;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tab;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TransferResource extends Resource
{
    protected static ?string $model = Transfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-exchange';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Tabs::make('Transfer Details')
                    ->tabs([
                        Tab::make('Distribution Transfer')
                            ->schema([
                                Select::make('device_id')
                                    ->label('ID')
                                    ->options(Device::query()
                                        ->pluck('device_id', 'id'))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $device = Device::find($state);
                                            if ($device) {
                                                $set('device_serial', $device->device_id);
                                            }
                                        }
                                    })
                                    ->placeholder('Select a device'),
                                TextInput::make('device_serial')
                                    ->label('Device Serial')
                                    ->disabled()
                                    ->dehydrated(true),
                                Select::make('from_location')
                                    ->label('From Distribution Point')
                                    ->options(DistributionPoint::pluck('name', 'id'))
                                    ->required()
                                    ->placeholder('Select origin'),
                                Select::make('to_location')
                                    ->label('To Distribution Point')
                                    ->options(DistributionPoint::pluck('name', 'id'))
                                    ->required()
                                    ->placeholder('Select destination'),
                                Select::make('status')
                                    ->label('Device Status')
                                    ->options([
                                        'ONLINE' => 'ONLINE',
                                        'OFFLINE' => 'OFFLINE',
                                        'DAMAGED' => 'DAMAGED',
                                        'FIXED' => 'FIXED',
                                        'LOST' => 'LOST',
                                    ])
                                    ->required()
                                    ->placeholder('Select status'),
                                TextInput::make('quantity')
                                    ->label('Quantity Transferred')
                                    ->numeric()
                                    ->required(),
                            ]),
                        Tab::make('Allocation Transfer')
                            ->schema([
                                Select::make('device_id')
                                    ->label('ID')
                                    ->options(Device::query()
                                        ->pluck('device_id', 'id'))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $device = Device::find($state);
                                            if ($device) {
                                                $set('device_serial', $device->device_id);
                                            }
                                        }
                                    })
                                    ->placeholder('Select a device'),
                                TextInput::make('device_serial')
                                    ->label('Device Serial')
                                    ->disabled()
                                    ->dehydrated(true),
                                Select::make('from_location')
                                    ->label('From Allocation Point')
                                    ->options(AllocationPoint::pluck('name', 'id'))
                                    ->required()
                                    ->placeholder('Select origin'),
                                Select::make('to_location')
                                    ->label('To Allocation Point')
                                    ->options(AllocationPoint::pluck('name', 'id'))
                                    ->required()
                                    ->placeholder('Select destination'),
                                TextInput::make('quantity')
                                    ->label('Quantity Allocated')
                                    ->numeric()
                                    ->required(),
                            ]),
                    ]),
            ])
            ->beforeSave(function ($data) {
                // Get the device and set the serial before saving
                if (isset($data['device_id'])) {
                    $device = Device::find($data['device_id']);
                    if ($device) {
                        $data['device_serial'] = $device->device_id;
                    }
                }
                return $data;
            });
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('device_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('device_serial')
                    ->label('Device Serial')
                    ->sortable()
                    ->searchable(),
                //Tables\Columns\TextColumn::make('from_location')
                //    ->label('From Location')
                //    ->sortable()
               //     ->searchable(),
               // Tables\Columns\TextColumn::make('to_location')
               //     ->label('To Location')
               //     ->sortable()
               //     ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Current Status')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Transfer Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transfer_type')
                    ->label('Transfer Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ALLOCATION' => 'success',
                        'DISTRIBUTION' => 'primary',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('transfer_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'CANCELLED' => 'danger',
                        'COMPLETED' => 'success',
                        default => 'secondary',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('cancelled_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'ONLINE' => 'ONLINE',
                        'OFFLINE' => 'OFFLINE',
                        'DAMAGED' => 'DAMAGED',
                        'FIXED' => 'FIXED',
                        'LOST' => 'LOST',
                    ]),
            ])
            ->actions([])  // Removing edit and delete actions
            ->bulkActions([
                BulkAction::make('cancelTransfers')
                    ->label('Cancel Transfer')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Reason for Cancellation')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->modalHeading('Cancel Transfer')
                    ->modalDescription('Are you sure you want to cancel the selected transfers? This will return devices to their original locations.')
                    ->modalSubmitActionLabel('Yes, cancel transfers')
                    ->action(function (Collection $records, array $data) {
                        try {
                            DB::beginTransaction();

                            $invalidTransfers = $records->filter(function ($transfer) {
                                return $transfer->transfer_status !== 'PENDING';
                            });

                            if ($invalidTransfers->isNotEmpty()) {
                                $transferIds = $invalidTransfers->pluck('id')->join(', ');
                                throw new \Exception("Transfers #{$transferIds} cannot be cancelled as they are not in PENDING state.");
                            }

                            foreach ($records as $transfer) {
                                $device = Device::find($transfer->device_id);
                                
                                if (!$device) {
                                    continue;
                                }

                                // Handle based on transfer type
                                if ($transfer->transfer_type === 'DISTRIBUTION') {
                                    $device->update([
                                        'status' => $transfer->original_status ?? 'ONLINE',
                                        'distribution_point_id' => $transfer->from_location,
                                        'allocation_point_id' => $transfer->original_allocation_point_id,
                                        'cancellation_reason' => $data['cancellation_reason'],
                                        'cancelled_at' => now()
                                    ]);
                                } else if ($transfer->transfer_type === 'ALLOCATION') {
                                    $device->update([
                                        'status' => $transfer->original_status ?? 'ONLINE',
                                        'distribution_point_id' => null,
                                        'allocation_point_id' => $transfer->from_location
                                    ]);
                                }

                                // Mark transfer as cancelled
                                $transfer->update([
                                    'transfer_status' => 'CANCELLED',
                                        'cancellation_reason' => $data['cancellation_reason'],
                                        'cancelled_at' => now(),
                                    'status' => 'CANCELLED'
                                    ,
                                    'status' => 'CANCELLED' 
                                                        ]);


                            // Delete the transfer record after cancellation
                                $transfer->delete();
                            }

                            DB::commit();

                            Notification::make()
                                ->success()
                                ->title('Transfers cancelled successfully')
                                ->body('Selected transfers have been cancelled, devices returned to their original locations, and transfer records deleted.')
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->danger()
                                ->title('Error cancelling transfers')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                BulkAction::make('approveTransfers')
                    ->label('Approve Selected Transfers')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            $device = Device::find($record->device_id);
                            if (!$device) continue;

                            if ($record->transfer_type === 'ALLOCATION' && $record->status === 'PENDING') {
                                // Handle allocation point transfer
                                $device->update([
                                    'status' => 'RECEIVED',
                                    'distribution_point_id' => null,
                                    'allocation_point_id' => $record->to_allocation_point_id
                                ]);
                                
                                // Update transfer record with device serial and delete it
                                $record->update([
                                    'status' => 'COMPLETED',
                                    'received' => true,
                                    'device_serial' => $device->device_id
                                ]);
                                $record->delete(); // Delete the transfer record
                                
                            } elseif ($record->transfer_type === 'DISTRIBUTION' && $record->status === 'PENDING') {
                                // Handle distribution point transfer
                                $device->update([
                                    'status' => 'RECEIVED',
                                    'distribution_point_id' => $record->to_location,
                                    'allocation_point_id' => null
                                ]);
                                // Update transfer record with device serial and delete it
                                $record->update([
                                    'status' => 'COMPLETED',
                                    'received' => true,
                                    'device_serial' => $device->device_id
                                ]);
                                $record->delete(); // Delete the transfer record
                            }
                        }

                        Notification::make()
                            ->title('Selected transfers approved and deleted successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Approve Transfers')
                    ->modalDescription('Are you sure you want to approve the selected transfers? This will update device locations and delete the transfer records.')
                    ->modalSubmitActionLabel('Yes, approve transfers'),
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each(function ($record) {
                            if ($record->status === 'PENDING') {
                                $device = Device::find($record->device_id);
                                if ($device) {
                                    // Ensure we have a valid status
                                    $status = $record->original_status ?? 'CONFIGURED';
                                    $device->update([
                                        'status' => $status,
                                        'distribution_point_id' => $record->from_location
                                    ]);
                                }
                            }
                            $record->delete();
                        });

                        Notification::make()
                            ->title('Selected transfers deleted successfully')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->color('danger')
                    ->icon('heroicon-o-trash'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransfers::route('/'),
            'create' => Pages\CreateTransfer::route('/create'),
            'edit' => Pages\EditTransfer::route('/{record}/edit'),
        ];
    }
}
