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
    public $reportFilters = [
        'search' => null,
        'device_id' => null,
        'boe' => null,
        'vehicle_number' => null,
        'start_date' => null,
        'end_date' => null,
        'start_time' => null,
        'end_time' => null,
        'destination' => null,
        'retrieval_status' => null,
        'action_type' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ];

    public function getDeviceRetrievalLogsProperty()
    {
        $query = \App\Models\DeviceRetrievalLog::query()
            ->with(['device', 'route', 'longRoute', 'retrievedBy', 'distributionPoint', 'allocationPoint']);

        // Apply allocation point permission filtering
        $user = auth()->user();
        if (!$user->hasRole(['Super Admin', 'Warehouse Manager'])) {
            $userAllocationPoints = $user->allocationPoints->pluck('id')->toArray();
            if (!empty($userAllocationPoints)) {
                $query->whereIn('allocation_point_id', $userAllocationPoints);
            } else {
                // If user has no allocation points assigned, show no records
                $query->whereRaw('1 = 0');
            }
        }

        // Apply filters
        if (!empty($this->reportFilters['search'])) {
            $search = $this->reportFilters['search'];
            $query->where(function($q) use ($search) {
                $q->whereHas('device', function($deviceQuery) use ($search) {
                    $deviceQuery->where('device_id', 'LIKE', "%{$search}%");
                })
                ->orWhere('boe', 'LIKE', "%{$search}%")
                ->orWhere('vehicle_number', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($this->reportFilters['device_id'])) {
            $query->whereHas('device', function($deviceQuery) {
                $deviceQuery->where('device_id', 'LIKE', "%{$this->reportFilters['device_id']}%");
            });
        }

        if (!empty($this->reportFilters['boe'])) {
            $query->where('boe', 'LIKE', "%{$this->reportFilters['boe']}%");
        }

        if (!empty($this->reportFilters['vehicle_number'])) {
            $query->where('vehicle_number', 'LIKE', "%{$this->reportFilters['vehicle_number']}%");
        }

        if (!empty($this->reportFilters['destination'])) {
            $query->where('destination', 'LIKE', "%{$this->reportFilters['destination']}%");
        }

        if (!empty($this->reportFilters['retrieval_status'])) {
            $query->where('retrieval_status', $this->reportFilters['retrieval_status']);
        }

        if (!empty($this->reportFilters['action_type'])) {
            $query->where('action_type', $this->reportFilters['action_type']);
        }

        // Apply date and time filters
        if (!empty($this->reportFilters['start_date']) && !empty($this->reportFilters['end_date'])) {
            $startDate = $this->reportFilters['start_date'];
            $endDate = $this->reportFilters['end_date'];

            if (!empty($this->reportFilters['start_time']) && !empty($this->reportFilters['end_time'])) {
                $startDateTime = $startDate . ' ' . $this->reportFilters['start_time'];
                $endDateTime = $endDate . ' ' . $this->reportFilters['end_time'];
                $query->whereBetween('created_at', [$startDateTime, $endDateTime]);
            } else {
                $query->whereDate('created_at', '>=', $startDate)
                      ->whereDate('created_at', '<=', $endDate);
            }
        } elseif (!empty($this->reportFilters['start_date'])) {
            if (!empty($this->reportFilters['start_time'])) {
                $startDateTime = $this->reportFilters['start_date'] . ' ' . $this->reportFilters['start_time'];
                $query->where('created_at', '>=', $startDateTime);
            } else {
                $query->whereDate('created_at', '>=', $this->reportFilters['start_date']);
            }
        } elseif (!empty($this->reportFilters['end_date'])) {
            if (!empty($this->reportFilters['end_time'])) {
                $endDateTime = $this->reportFilters['end_date'] . ' ' . $this->reportFilters['end_time'];
                $query->where('created_at', '<=', $endDateTime);
            } else {
                $query->whereDate('created_at', '<=', $this->reportFilters['end_date']);
            }
        }

        // Apply sorting
        $sortBy = $this->reportFilters['sort_by'] ?? 'created_at';
        $sortDirection = $this->reportFilters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate(50);
    }

    /**
     * Reset all report filters
     */
    public function resetReportFilters()
    {
        $this->reportFilters = [
            'search' => null,
            'device_id' => null,
            'boe' => null,
            'vehicle_number' => null,
            'start_date' => null,
            'end_date' => null,
            'start_time' => null,
            'end_time' => null,
            'destination' => null,
            'retrieval_status' => null,
            'action_type' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    /**
     * Apply report filters and refresh modal content
     */
    public function applyReportFilters()
    {
        // This method is called when Apply Filters is clicked
        // The reactive properties will automatically update
    }

    /**
     * Handle column sorting
     */
    public function sortBy($column)
    {
        $currentSortBy = $this->reportFilters['sort_by'] ?? 'created_at';
        $currentDirection = $this->reportFilters['sort_direction'] ?? 'desc';

        if ($currentSortBy === $column) {
            // Toggle direction if same column
            $this->reportFilters['sort_direction'] = $currentDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // New column, default to asc
            $this->reportFilters['sort_by'] = $column;
            $this->reportFilters['sort_direction'] = 'asc';
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
                ->modalSubmitActionLabel('Export to Excel')
                ->modalWidth('6xl')
                ->modalContent(fn () => view('filament.resources.device-retrieval-resource.pages.device-retrieval-report'))
                ->action(function (array $data) {
                    // Filter out empty values and create query parameters
                    $filteredParams = array_filter($data, function($value) {
                        return $value !== null && $value !== '';
                    });

                    // Build query string
                    $queryString = http_build_query($filteredParams);

                    // Redirect to export route with parameters
                    return redirect()->away(route('export.device-retrieval-report') . ($queryString ? '?' . $queryString : ''));
                })
                ->form([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('search')
                                ->label('Search (Device ID, BOE, Vehicle)')
                                ->placeholder('Search...'),
                            Forms\Components\TextInput::make('device_id')
                                ->label('Device ID'),
                            Forms\Components\TextInput::make('boe')
                                ->label('BOE'),
                        ]),
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('vehicle_number')
                                ->label('Vehicle Number'),
                            Forms\Components\TextInput::make('destination')
                                ->label('Destination'),
                            Forms\Components\Select::make('retrieval_status')
                                ->label('Retrieval Status')
                                ->options([
                                    'NOT_RETRIEVED' => 'Not Retrieved',
                                    'RETRIEVED' => 'Retrieved',
                                    'RETURNED' => 'Returned',
                                ]),
                        ]),
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Select::make('action_type')
                                ->label('Action Type')
                                ->options([
                                    'RETRIEVED' => 'Retrieved',
                                    'RETURNED' => 'Returned',
                                ]),
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Start Date'),
                            Forms\Components\DatePicker::make('end_date')
                                ->label('End Date'),
                        ]),
                    Forms\Components\Grid::make(4)
                        ->schema([
                            Forms\Components\TimePicker::make('start_time')
                                ->label('Start Time'),
                            Forms\Components\TimePicker::make('end_time')
                                ->label('End Time'),
                            Forms\Components\Select::make('sort_by')
                                ->label('Sort By')
                                ->options([
                                    'created_at' => 'Date Created',
                                    'retrieval_date' => 'Retrieval Date',
                                    'device_id' => 'Device ID',
                                    'boe' => 'BOE',
                                    'vehicle_number' => 'Vehicle Number',
                                    'destination' => 'Destination',
                                ])
                                ->default('created_at'),
                            Forms\Components\Select::make('sort_direction')
                                ->label('Sort Direction')
                                ->options([
                                    'asc' => 'Ascending',
                                    'desc' => 'Descending',
                                ])
                                ->default('desc'),
                        ]),
                ]),
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





