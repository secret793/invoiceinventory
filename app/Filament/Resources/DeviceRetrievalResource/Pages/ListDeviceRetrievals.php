<?php

namespace App\Filament\Resources\DeviceRetrievalResource\Pages;

use App\Filament\Resources\DeviceRetrievalResource;
use App\Filament\Actions\OverdueBillAction;
use App\Filament\Actions\FinanceApprovalAction;
use App\Filament\Actions\OverdueBillsAction;
use App\Models\DeviceRetrieval as DeviceRetrievalModel;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Device;
use App\Models\DistributionPoint;
use Filament\Forms;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class ListDeviceRetrievals extends ListRecords
{
    protected static string $resource = DeviceRetrievalResource::class;

    // Filter properties for the device retrieval report modal
    public $filters = [];

    // Legacy property to prevent Livewire errors
    public $reportFilters = [];

    public function mount(): void
    {
        parent::mount();

        // Initialize filters (no default dates)
        $this->filters = [
            'search' => null,
            'device_id' => null,
            'boe' => null,
            'vehicle_number' => null,
            'start_date' => null,
            'end_date' => null,
            'start_time' => null,
            'end_time' => null,

            'retrieval_status' => null,
            'action_type' => null,
            'allocation_point_id' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];

        // Initialize legacy property to prevent errors
        $this->reportFilters = [];
    }

    /**
     * Handle legacy property access
     */
    public function getReportFiltersProperty()
    {
        return $this->filters;
    }

    /**
     * Handle legacy property updates
     */
    public function updatedReportFilters($value, $key)
    {
        $this->filters[$key] = $value;
    }



    public function getDeviceRetrievalLogsProperty()
    {
        // Build query using the same logic as DeviceRetrievalReport
        $startDateTime = null;
        $endDateTime = null;

        // Handle start date/time
        if (!empty($this->filters['start_date'])) {
            $startDateTime = $this->filters['start_date'];
            if (!empty($this->filters['start_time'])) {
                $startDateTime .= ' ' . $this->filters['start_time'];
            } else {
                $startDateTime .= ' 00:00:00';
            }
        }

        // Handle end date/time
        if (!empty($this->filters['end_date'])) {
            $endDateTime = $this->filters['end_date'];
            if (!empty($this->filters['end_time'])) {
                $endDateTime .= ' ' . $this->filters['end_time'];
            } else {
                $endDateTime .= ' 23:59:59';
            }
        }

        $query = \App\Models\DeviceRetrievalLog::query()
            ->with([
                'device',
                'allocationPoint' => function($query) {
                    $query->withoutGlobalScopes();
                },
                'retrievedBy',
                'route',
                'longRoute',
                'distributionPoint'
            ])
            ->when($startDateTime, fn ($query) => $query->where('created_at', '>=', $startDateTime))
            ->when($endDateTime, fn ($query) => $query->where('created_at', '<=', $endDateTime))
            ->when($this->filters['allocation_point_id'] ?? null, fn ($query, $id) => $query->where('allocation_point_id', $id))
            ->when($this->filters['retrieval_status'] ?? null, fn ($query, $status) => $query->where('retrieval_status', $status))
            ->when($this->filters['action_type'] ?? null, fn ($query, $actionType) => $query->where('action_type', $actionType))
            ->when($this->filters['device_id'] ?? null, fn ($query, $deviceId) => $query->whereHas('device', function($q) use ($deviceId) {
                $q->where('device_id', 'like', "%{$deviceId}%");
            }))
            ->when($this->filters['boe'] ?? null, fn ($query, $boe) => $query->where('boe', 'like', "%{$boe}%"))
            ->when($this->filters['vehicle_number'] ?? null, fn ($query, $vehicleNumber) => $query->where('vehicle_number', 'like', "%{$vehicleNumber}%"))

            ->when($this->filters['search'] ?? null, fn ($query, $search) => $query->where(function($q) use ($search) {
                $q->whereHas('device', function($q) use ($search) {
                    $q->where('device_id', 'like', "%{$search}%");
                })
                ->orWhere('boe', 'like', "%{$search}%")
                ->orWhere('vehicle_number', 'like', "%{$search}%");
            }));

        // Note: Permission filtering is now handled by the DeviceRetrievalLog global scope
        // which filters by destination permissions for Retrieval Officers

        // Apply sorting
        $query->orderBy($this->filters['sort_by'] ?? 'created_at', $this->filters['sort_direction'] ?? 'desc');

        return $query->paginate(25);
    }

    /**
     * Reset filters
     */
    public function resetFilters()
    {
        $this->reset('filters');
        // Force refresh of computed properties
        unset($this->cachedMountedActions);
    }

    /**
     * Apply filters
     */
    public function applyFilters()
    {
        // Trigger a refresh of the data to apply current filters
        $this->dispatch('$refresh');
    }

    /**
     * Handle column sorting (delegate to DeviceRetrievalReport controller)
     */
    public function sortBy($column)
    {
        $currentSortBy = $this->filters['sort_by'] ?? 'created_at';
        $currentDirection = $this->filters['sort_direction'] ?? 'desc';

        if ($currentSortBy === $column) {
            // Toggle direction if same column
            $this->filters['sort_direction'] = $currentDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // New column, default to asc
            $this->filters['sort_by'] = $column;
            $this->filters['sort_direction'] = 'asc';
        }
    }



    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            // Device Retrieval Report Action
            Actions\Action::make('deviceRetrievalReport')
                ->label('Device Retrieval Report')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->modalHeading('Device Retrieval Report')
                ->modalWidth('7xl')
                ->modalContent(fn () => view('filament.resources.device-retrieval-resource.pages.device-retrieval-report'))
                ->modalSubmitActionLabel('Export to Excel')
                ->modalSubmitAction(function ($action) {
                    $params = [
                        'search' => $this->filters['search'] ?? null,
                        'device_id' => $this->filters['device_id'] ?? null,
                        'boe' => $this->filters['boe'] ?? null,
                        'vehicle_number' => $this->filters['vehicle_number'] ?? null,
                        'start_date' => $this->filters['start_date'] ?? null,
                        'end_date' => $this->filters['end_date'] ?? null,
                        'start_time' => $this->filters['start_time'] ?? null,
                        'end_time' => $this->filters['end_time'] ?? null,

                        'retrieval_status' => $this->filters['retrieval_status'] ?? null,
                        'action_type' => $this->filters['action_type'] ?? null,
                        'allocation_point_id' => $this->filters['allocation_point_id'] ?? null,
                        'sort_by' => $this->filters['sort_by'] ?? null,
                        'sort_direction' => $this->filters['sort_direction'] ?? null,
                    ];

                    // Remove null values from the params array
                    $filteredParams = array_filter($params, function($value) {
                        return $value !== null && $value !== '';
                    });

                    return $action->url(route('export.device-retrieval-report', $filteredParams));
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('device.device_id')
                    ->label('Device ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('boe')
                    ->label('BOE')
                    ->searchable(),
                // Hidden as per requirements - current_time is now managed internally
                // Tables\Columns\TextColumn::make('current_time')
                //     ->label('Current Time')
                //     ->dateTime()
                //     ->sortable()
                //     ->searchable()
                //     ->formatStateUsing(fn ($record) => $record->current_time?->diffForHumans() ?? 'N/A')
                //     ->description(fn ($record): string => $record->current_time?->toDateTimeString() ?? 'N/A')
                //     ->tooltip(fn ($record): string => $record->current_time?->toDateTimeString() ?? 'N/A')
                //     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vehicle_number')
                    ->label('Vehicle Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('regime')
                    ->label('Regime')
                    ->searchable(),
                Tables\Columns\TextColumn::make('allocationPoint.name')
                    ->label('Allocation Point')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('destination')
                    ->label('Destination')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('retrieval_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'NOT_RETRIEVED' => 'warning',
                        'RETRIEVED' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('overstay_days')
                    ->label('Overstay Days')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('overstay_amount')
                    ->label('Overstay Amount')
                    ->money('GMD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transfer_status')
                    ->options([
                        'pending' => 'Transfer Pending',
                        'completed' => 'Transfer Completed',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'PP' => 'Pending Payment',
                        'PD' => 'Paid',
                    ]),
                Tables\Filters\Filter::make('overstay_days')
                    ->form([
                        Forms\Components\TextInput::make('min')
                            ->label('Minimum Overstay Days')
                            ->numeric(),
                        Forms\Components\TextInput::make('max')
                            ->label('Maximum Overstay Days')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min'],
                                fn (Builder $query, $min): Builder => $query->where('overstay_days', '>=', $min)
                            )
                            ->when(
                                $data['max'],
                                fn (Builder $query, $max): Builder => $query->where('overstay_days', '<=', $max)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Return to Outstation action
                    Tables\Actions\Action::make('returnToOutstation')
                        ->label('Return to Outstation')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('distribution_point_id')
                                ->label('Select Distribution Point')
                                ->options(function () {
                                    return DistributionPoint::select('id', 'name')
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->required()
                                ->searchable()
                        ])
                        ->action(function ($record, array $data): void {
                            try {
                                DB::beginTransaction();

                                DB::transaction(function () use ($record, $data) {
                                    // 1. First update the device status
                                    DB::table('devices')
                                        ->where('id', $record->device_id)
                                        ->update([
                                            'status' => 'PENDING',
                                            'distribution_point_id' => $data['distribution_point_id'],
                                            'updated_at' => now()
                                        ]);

                                    // 2. Update retrieval status to RETURNED using model to trigger observers
                                    $record->update([
                                        'retrieval_status' => 'RETURNED',
                                        'transfer_status' => 'pending',
                                        'distribution_point_id' => $data['distribution_point_id'],
                                    ]);

                                    // Wait a moment for observers to complete their work
                                    usleep(100000); // 0.1 seconds

                                    // 3. Get monitoring record before deletion for logging
                                    $monitoringRecord = DB::table('monitorings')
                                        ->where('device_id', $record->device_id)
                                        ->first();

                                    // 4. Delete monitoring record
                                    $monitoringDeleted = DB::table('monitorings')
                                        ->where('device_id', $record->device_id)
                                        ->delete();

                                    // Log monitoring deletion
                                    Log::info('Monitoring record deleted', [
                                        'device_retrieval_id' => $record->id,
                                        'device_id' => $record->device_id,
                                        'monitoring_id' => $monitoringRecord->id ?? null,
                                        'rows_affected' => $monitoringDeleted,
                                        'timestamp' => now()->toDateTimeString()
                                    ]);

                                    // 5. Delete device retrieval record
                                    $retrievalDeleted = DB::table('device_retrievals')
                                        ->where('id', $record->id)
                                        ->delete();

                                    // Log retrieval deletion
                                    Log::info('Device retrieval record deleted', [
                                        'device_retrieval_id' => $record->id,
                                        'device_id' => $record->device_id,
                                        'rows_affected' => $retrievalDeleted,
                                        'timestamp' => now()->toDateTimeString()
                                    ]);

                                    // 6. Verify deletions
                                    if ($monitoringDeleted === 0 || $retrievalDeleted === 0) {
                                        throw new \Exception('Failed to delete one or more records');
                                    }
                                });

                                DB::commit();

                                Notification::make()
                                    ->success()
                                    ->title('Device Return and Cleanup Complete')
                                    ->body('The device has been returned to outstation and all related records have been cleaned up successfully.')
                                    ->send();

                            } catch (\Exception $e) {
                                DB::rollBack();
                                Log::error('Error in returnToOutstation', [
                                    'error' => $e->getMessage(),
                                    'device_retrieval_id' => $record->id,
                                    'trace' => $e->getTraceAsString()
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to complete device return: ' . $e->getMessage())
                                    ->send();

                                // Re-throw to ensure the transaction is marked as failed
                                throw $e;
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Return Device to Outstation')
                        ->visible(fn ($record): bool =>
                            $record->retrieval_status === 'RETRIEVED' &&
                            $record->transfer_status !== 'completed' &&
                            auth()->user()?->hasAnyRole([
                                'Super Admin',
                                'Warehouse Manager',
                                'Retrieval Officer'
                            ])
                        ),

                    // Overdue Bills action
                    OverdueBillsAction::make()
                        ->visible(fn ($record) =>
                            $record->overstay_days >= 2 &&
                            $record->payment_status !== 'PD'
                        ),

                    // Retrieve Device action
                    Tables\Actions\Action::make('retrieveDevice')
                        ->label('Retrieve Device')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record): bool =>
                            $record->retrieval_status === 'NOT_RETRIEVED' &&
                            auth()->user()?->hasAnyRole([
                                'Super Admin',
                                'Warehouse Manager',
                                'Retrieval Officer'
                            ])
                        )
                        ->form(function ($record) {
                            // Check if user is Super Admin or Warehouse Manager
                            $isPrivilegedUser = auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager']);

                            // Check if device is overdue
                            $isOverdue = $record->overdue_days > 0;
                            $isLongRoute = $record->long_route_id !== null;
                            $minDays = $isLongRoute ? 2 : 1;
                            $requiresReceipt = $isOverdue && $record->overdue_days >= $minDays;

                            // If user is privileged, or device isn't overdue, return empty form
                            if ($isPrivilegedUser || !$requiresReceipt) {
                                return [];
                            }

                            // For other users with overdue devices, show receipt input
                            return [
                                TextInput::make('receipt_number')
                                    ->label('Receipt Number')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText("Device is overdue by {$record->overdue_days} days. Receipt number is required.")
                            ];
                        })
                        ->action(function ($record, array $data): void {
                            try {
                                DB::beginTransaction();

                                $isPrivilegedUser = auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager']);
                                $isOverdue = $record->overdue_days > 0;
                                $isLongRoute = $record->long_route_id !== null;
                                $minDays = $isLongRoute ? 2 : 1;
                                $requiresReceipt = $isOverdue && $record->overdue_days >= $minDays;

                                // Check if receipt is required but not provided
                                if (!$isPrivilegedUser && $requiresReceipt && empty($data['receipt_number'])) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Receipt Required')
                                        ->body('This device is overdue. Please provide a receipt number.')
                                        ->send();
                                    return;
                                }

                                // Check if device can be retrieved based on overdue status and payment
                                if (!$record->canBeRetrieved()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Payment Required')
                                        ->body('This device has overdue fees. Payment must be completed before retrieval.')
                                        ->send();
                                    return;
                                }

                                // ✅ USE MODEL-BASED UPDATES TO TRIGGER OBSERVERS
                                $updateData = [
                                    'retrieval_status' => 'RETRIEVED',
                                ];

                                // Add receipt number if provided
                                if (!empty($data['receipt_number'])) {
                                    $updateData['receipt_number'] = $data['receipt_number'];
                                }

                                // Update device retrieval using Eloquent model to trigger observers
                                $record->update($updateData);

                                // Update device status using Eloquent model
                                $device = \App\Models\Device::find($record->device_id);
                                if ($device) {
                                    $device->update(['status' => 'RETRIEVED']);
                                }

                                // No need for manual monitoring updates - observers will handle this
                                Log::info('Device retrieval status updated via model - observers will handle sync', [
                                    'device_retrieval_id' => $record->id,
                                    'device_id' => $record->device_id,
                                    'new_status' => 'RETRIEVED',
                                    'timestamp' => now()->toDateTimeString()
                                ]);

                                DB::commit();

                                Notification::make()
                                    ->success()
                                    ->title('Device Retrieved')
                                    ->body('Device has been successfully retrieved.')
                                    ->send();

                            } catch (\Exception $e) {
                                DB::rollBack();
                                Log::error('Device retrieval failed', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                    'device_retrieval_id' => $record->id
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to retrieve device: ' . $e->getMessage())
                                    ->send();
                            }
                        })
                        ->modalHeading(function ($record) {
                            $isPrivilegedUser = auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager']);
                            $isOverdue = $record->overdue_days > 0;
                            $isLongRoute = $record->long_route_id !== null;
                            $minDays = $isLongRoute ? 2 : 1;

                            if (!$isPrivilegedUser && $isOverdue && $record->overdue_days >= $minDays) {
                                return 'Retrieve Overdue Device';
                            }
                            return 'Retrieve Device';
                        })
                        ->modalDescription(function ($record) {
                            $isPrivilegedUser = auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager']);
                            $isOverdue = $record->overdue_days > 0;
                            $isLongRoute = $record->long_route_id !== null;
                            $minDays = $isLongRoute ? 2 : 1;

                            if (!$isPrivilegedUser && $isOverdue && $record->overdue_days >= $minDays) {
                                return "This device is overdue by {$record->overdue_days} days. Please provide a receipt number.";
                            }
                            return 'Are you sure you want to retrieve this device?';
                        })
                        ->requiresConfirmation(),



                    // Finance Approval action
                    Tables\Actions\Action::make('finance_approval')
                        ->label('Approve Payment')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) =>
                            // Only show to Finance Officers and Super Admins
                            (auth()->user()->hasRole('Finance Officer') || auth()->user()->hasRole('Super Admin')) &&
                            // Only show for pending payment records
                            $record->payment_status === 'PP' &&
                            // Only show if there's an amount to approve
                            $record->overstay_amount > 0 &&
                            // IMPORTANT: Don't show to Retrieval Officers who aren't also Finance Officers or Super Admins
                            !(auth()->user()->hasRole('Retrieval Officer') &&
                              !auth()->user()->hasRole('Finance Officer') &&
                              !auth()->user()->hasRole('Super Admin'))
                        )
                        ->form([
                            Forms\Components\TextInput::make('receipt_number')
                                ->required()
                                ->label('Receipt Number')
                                ->default(fn ($record) => $record->receipt_number),
                            Forms\Components\Textarea::make('finance_notes')
                                ->label('Finance Notes')
                                ->default(fn ($record) => $record->finance_notes),
                        ])
                        ->action(function ($record, array $data): void {
                            try {
                                DB::beginTransaction();

                                // Update device retrieval with finance approval
                                $record->update([
                                    'receipt_number' => $data['receipt_number'],
                                    'finance_notes' => $data['finance_notes'] ?? null,
                                    'finance_approval_date' => now(),
                                    'finance_approved_by' => auth()->id(),
                                    'payment_status' => 'PD', // Now changing to Paid
                                ]);

                                DB::commit();

                                Notification::make()
                                    ->success()
                                    ->title('Payment Approved')
                                    ->body('The payment has been approved.')
                                    ->send();

                            } catch (\Exception $e) {
                                DB::rollBack();
                                Log::error('Finance approval failed', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                    'device_retrieval_id' => $record->id
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to process payment approval: ' . $e->getMessage())
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Approve Payment')
                        ->modalDescription('Are you sure you want to approve this payment?'),

                    // Download Invoice action
                    Tables\Actions\Action::make('download_invoice')
                        ->label('Download Invoice')
                        ->icon('heroicon-o-document-download')
                        ->color('primary')
                        ->url(fn ($record) => route('invoices.download.retrieval', $record->id))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) =>
                            $record->payment_status === 'PD' &&
                            !empty($record->finance_approval_date)
                        ),
                ])
            ])
            ->defaultSort('date', 'desc')
            ->poll('10s');
    }
}





