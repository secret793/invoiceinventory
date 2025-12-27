<?php

namespace App\Filament\Resources\TransferResource\Pages;

use App\Filament\Resources\TransferResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Select;
use App\Models\DistributionPoint;
use Filament\Forms\Components\TextInput;
use App\Models\AllocationPoint;
use Filament\Notifications\Notification;
use App\Models\Device;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\BulkAction;

class ListTransfers extends ListRecords
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            // Add any relevant widgets here
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('cancelTransfers')
                ->label('Cancel Transfer')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Reason for Cancellation')
                        ->required()
                        ->maxLength(1000)
                ])
                ->modalHeading('Cancel Transfer')
                ->modalDescription('Are you sure you want to cancel the selected transfers? This will return devices to their original locations.')
                ->modalSubmitActionLabel('Yes, cancel transfers')
                ->action(function (Collection $records, array $data): void {
                    try {
                        DB::beginTransaction();

                        $invalidTransfers = $records->filter(function ($transfer) {
                            return $transfer->transfer_status !== 'PENDING';
                        });

                        if ($invalidTransfers->isNotEmpty()) {
                            throw new \Exception("Some transfers cannot be cancelled as they are not in PENDING state.");
                        }

                        foreach ($records as $transfer) {
                            $device = Device::find($transfer->device_id);
                            
                            if (!$device) continue;

                            // Handle based on transfer type
                            if ($transfer->transfer_type === 'DISTRIBUTION') {
                                $device->update([
                                    'status' => $transfer->original_status ?? 'ONLINE',
                                    'distribution_point_id' => $transfer->from_location,
                                    'allocation_point_id' => $transfer->original_allocation_point_id
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
                            ]);
                        }

                        DB::commit();

                        Notification::make()
                            ->success()
                            ->title('Transfers cancelled successfully')
                            ->send();

                        $this->deselectAll();

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
                ->label('Approve Transfer')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Transfer')
                ->modalDescription('Are you sure you want to approve the selected transfers?')
                ->action(function (Collection $records): void {
                    try {
                        foreach ($records as $record) {
                            $device = Device::find($record->device_id);
                            if (!$device) continue;

                            if ($record->transfer_type === 'ALLOCATION' && $record->status === 'PENDING') {
                                $device->update([
                                    'status' => 'RECEIVED',
                                    'distribution_point_id' => null,
                                    'allocation_point_id' => $record->to_allocation_point_id
                                ]);
                                
                                $record->update([
                                    'status' => 'COMPLETED',
                                    'received' => true,
                                    'device_serial' => $device->device_id
                                ]);
                                $record->delete();
                                
                            } elseif ($record->transfer_type === 'DISTRIBUTION' && $record->status === 'PENDING') {
                                $device->update([
                                    'status' => 'RECEIVED',
                                    'distribution_point_id' => $record->to_location,
                                    'allocation_point_id' => null
                                ]);

                                $record->update([
                                    'status' => 'COMPLETED',
                                    'received' => true,
                                    'device_serial' => $device->device_id
                                ]);
                                $record->delete();
                            }
                        }

                        Notification::make()
                            ->title('Selected transfers approved successfully')
                            ->success()
                            ->send();

                        $this->deselectAll();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error approving transfers')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
        ];
    }

    protected function getTableActions(): array
    {
        return [];
    }
}
