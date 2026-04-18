<?php

namespace App\Filament\Resources\DeviceRetrievalResource\Pages;

use App\Filament\Resources\DeviceRetrievalResource;
use App\Filament\Actions\OverdueBillAction;
use App\Filament\Actions\FinanceApprovalAction;
use App\Filament\Actions\OverdueBillsAction;
use App\Filament\Actions\AdminWaiverAction;
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
use Illuminate\Support\Facades\Cache;

class ListDeviceRetrievals extends ListRecords
{
    protected static string $resource = DeviceRetrievalResource::class;

    // Filter properties for the device retrieval report modal
    public $filters = [];

    // Legacy property to prevent Livewire errors
    public $reportFilters = [];

    // Report #2 specific filters
    public $report2Filters = [];
    
    // Temporary filter properties for user input (before applying)
    public $tempReport2Filters = [];

    // New properties for date filtering
    public $useDateFiltering = false;
    public $dateColumn = 'retrieval_date'; // Only retrieval_date or returned_at allowed
    
    // Overstay devices filter properties
    public array $overstayFilters = [
        'device_id' => null,
        'boe' => null,
        'invoice_number' => null,
        'destination_id' => null,
        'allocation_point_id' => null,
        'payment_status' => null,
        'overstay_amount_min' => null,
        'overstay_amount_max' => null,
        'overstay_days_min' => null,
        'overstay_days_max' => null,
        'start_date' => null,
        'end_date' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ];

    // Temporary filter properties for overstay (for input binding before applying)
    public array $tempOverstayFilters = [
        'device_id' => null,
        'boe' => null,
        'invoice_number' => null,
        'destination_id' => null,
        'allocation_point_id' => null,
        'payment_status' => null,
        'overstay_amount_min' => null,
        'overstay_amount_max' => null,
        'overstay_days_min' => null,
        'overstay_days_max' => null,
        'start_date' => null,
        'end_date' => null,
    ];

    // Search properties for overstay devices
    public string $destinationSearch = '';
    public string $allocationPointSearch = '';
    public int $overstayPage = 1;
    
    // Manual overstay days update - track selected device
    public ?int $selectedDeviceRetrievalId = null;
    public ?DeviceRetrievalModel $selectedDeviceRetrieval = null;
    
    // Cache duration in seconds
    protected $cacheDuration = 300; // 5 minutes

    protected $queryString = [
        'report2Filters' => ['except' => '', 'as' => 'filters'],
    ];

    protected function isReadOnlyTrackerOfficer(): bool
    {
        return auth()->user()?->hasRole('Read Only Tracker Officer') ?? false;
    }

    // Add cache key generator
    protected function getCacheKey(): string
    {
        return 'device_retrieval_report_2_' . md5(serialize($this->report2Filters));
    }

    // Cache key generator for overstay devices
    protected function getCacheKeyOverstay(): string
    {
        return 'overstay_devices_' . md5(serialize($this->overstayFilters));
    }

    protected function getDateFieldForAction($actionType = null): string 
    {
        return match($actionType) {
            'RETRIEVED' => 'retrieval_date',
            'RETURNED' => 'returned_at',
            default => 'created_at'
        };
    }

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

        // Initialize Report #2 filters with default status
        $this->report2Filters = [
            'search' => null,
            'device_id' => null,
            'boe' => null,
            'vehicle_number' => null,
            'start_date' => null,
            'end_date' => null,
            'start_time' => null,
            'end_time' => null,
            'retrieval_status' => 'ALL_STATUS', // Set default status
            'action_type' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
        
        // Initialize temporary filters (for input binding)
        $this->tempReport2Filters = [
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
        ];

        // Initialize new date filtering properties
        $this->useDateFiltering = false;
        $this->dateColumn = 'both'; // Default to both dates for ALL_STATUS

        // Initialize overstay device filters
        $this->overstayFilters = [
            'device_id' => null,
            'boe' => null,
            'invoice_number' => null,
            'destination_id' => null,
            'allocation_point_id' => null,
            'payment_status' => null,
            'overstay_amount_min' => null,
            'overstay_amount_max' => null,
            'overstay_days_min' => null,
            'overstay_days_max' => null,
            'start_date' => null,
            'end_date' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];

        // Initialize temporary overstay filters
        $this->tempOverstayFilters = [
            'device_id' => null,
            'boe' => null,
            'invoice_number' => null,
            'destination_id' => null,
            'allocation_point_id' => null,
            'payment_status' => null,
            'overstay_amount_min' => null,
            'overstay_amount_max' => null,
            'overstay_days_min' => null,
            'overstay_days_max' => null,
            'start_date' => null,
            'end_date' => null,
        ];

        // Initialize overstay search properties
        $this->destinationSearch = '';
        $this->allocationPointSearch = '';
    }

    /**
     * Check if exactly one device is selected and get it
     */
    public function getSelectedDeviceForOverstayUpdate(): ?DeviceRetrievalModel
    {
        // Get selected records from the table
        $selectedRecords = $this->selectedTableRecords;
        
        // If no records selected, return null
        if (empty($selectedRecords)) {
            return null;
        }
        
        // If more than one record selected, return null
        if (count($selectedRecords) > 1) {
            return null;
        }
        
        // Get the single selected record ID
        $selectedId = array_key_first($selectedRecords);
        
        // Load and return the device retrieval
        return DeviceRetrievalModel::find($selectedId);
    }

    /**
     * Handle manual overstay days update button click
     */
    public function openManualOverstayModal(): void
    {
        $device = $this->getSelectedDeviceForOverstayUpdate();
        
        if ($device === null) {
            $selectedCount = count($this->selectedTableRecords ?? []);
            
            if ($selectedCount === 0) {
                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title('No Device Selected')
                    ->body('Please select a device retrieval record first.')
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title('Multiple Devices Selected')
                    ->body('Please select only ONE device retrieval record.')
                    ->send();
            }
            return;
        }
        
        // Store the selected device for the modal
        $this->selectedDeviceRetrievalId = $device->id;
        $this->selectedDeviceRetrieval = $device;
        
        // Dispatch event to open the modal
        $this->dispatchBrowserEvent('open-manual-overstay-modal');
    }

    /**
     * Handle manual overstay days update submission
     */
    public function submitManualOverstayUpdate(array $data): void
    {
        try {
            $record = DeviceRetrievalModel::find($data['device_retrieval_id'] ?? $this->selectedDeviceRetrievalId);
            
            if (!$record) {
                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body('Device retrieval record not found.')
                    ->send();
                return;
            }

            // Store old value for notification
            $oldValue = $record->overstay_days;
            $newValue = (int) $data['new_overstay_days'];

            // Update the overstay_days field - will trigger the observer
            // The observer will sync this to the Monitoring table automatically
            $record->update([
                'overstay_days' => $newValue
            ]);

            $deviceId = $record->device?->device_id ?? 'Unknown';

            \Filament\Notifications\Notification::make()
                ->title('Overstay Days Updated Successfully')
                ->body("Device $deviceId: Updated from $oldValue to $newValue days. Observer will continue monitoring.")
                ->success()
                ->send();
            
            // Clear selected device
            $this->selectedDeviceRetrievalId = null;
            $this->selectedDeviceRetrieval = null;
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error Updating Overstay Days')
                ->body('An error occurred: ' . $e->getMessage())
                ->send();
        }
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

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\LiveTimerWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        $deviceRetrievalReportAction = Actions\Action::make('deviceRetrievalReport')
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

                $filteredParams = array_filter($params, function ($value) {
                    return $value !== null && $value !== '';
                });

                return $action->url(route('export.device-retrieval-report', $filteredParams));
            });

        $viewOverstayDevicesAction = Actions\Action::make('viewOverstayDevices')
            ->label('View Overstay Devices')
            ->icon('heroicon-o-exclamation-circle')
            ->color('danger')
            ->modalHeading('Overstay Devices')
            ->modalWidth('7xl')
            ->modalContent(fn () => view('filament.resources.device-retrieval-resource.pages.overstay-devices-table-modal', [
                'overstayFilters' => $this->overstayFilters,
                'tempOverstayFilters' => $this->tempOverstayFilters,
                'filteredOverstayDevices' => $this->filteredOverstayDevices,
                'overstayStatistics' => $this->overstayStatistics,
                'availableDestinations' => $this->getAvailableDestinationsProperty(),
                'availableAllocationPoints' => $this->getAvailableAllocationPointsProperty(),
                'destinationSearch' => $this->destinationSearch,
                'allocationPointSearch' => $this->allocationPointSearch,
                'filteredDestinations' => $this->filteredDestinations,
                'filteredAllocationPoints' => $this->filteredAllocationPoints,
                'hasActiveOverstayFilters' => $this->hasActiveOverstayFilters(),
            ]))
            ->visible(fn () => auth()->check());

        if ($this->isReadOnlyTrackerOfficer()) {
            return [
                $deviceRetrievalReportAction,
                $viewOverstayDevicesAction,
            ];
        }

        return [
            Actions\CreateAction::make(),
            $deviceRetrievalReportAction,
            
            // Device Retrieval Report #2 Action
            Actions\Action::make('deviceRetrievalReport2')
                ->label('Device Retrieval Report #2')
                ->icon('heroicon-o-chart-bar-square')
                ->color('warning')
                ->modalHeading('Device Retrieval Report #2')
                ->modalWidth('7xl')
                ->modalContent(fn () => view('filament.resources.device-retrieval-resource.pages.device-retrieval-report-2'))
                ->modalSubmitActionLabel('Export Report #2')
                ->modalSubmitAction(function ($action) {
                    // Check if retrieval status is selected
                    if (empty($this->report2Filters['retrieval_status'])) {
                        \Filament\Notifications\Notification::make()
                            ->title('Please select a retrieval status')
                            ->danger()
                            ->send();
                        return;
                    }

                    $params = [
                        'search' => $this->report2Filters['search'] ?? null,
                        'device_id' => $this->report2Filters['device_id'] ?? null,
                        'boe' => $this->report2Filters['boe'] ?? null,
                        'vehicle_number' => $this->report2Filters['vehicle_number'] ?? null,
                        'start_date' => $this->report2Filters['start_date'] ?? null,
                        'end_date' => $this->report2Filters['end_date'] ?? null,
                        'start_time' => $this->report2Filters['start_time'] ?? null,
                        'end_time' => $this->report2Filters['end_time'] ?? null,
                        'retrieval_status' => $this->report2Filters['retrieval_status'] ?? null,
                        'action_type' => $this->report2Filters['action_type'] ?? null,
                        'sort_by' => $this->report2Filters['sort_by'] ?? null,
                        'sort_direction' => $this->report2Filters['sort_direction'] ?? null,
                    ];

                    // Remove null values from the params array
                    $filteredParams = array_filter($params, function($value) {
                        return $value !== null && $value !== '';
                    });

                    return $action->url(route('export.device-retrieval-report-2', $filteredParams));
                }),

            $viewOverstayDevicesAction,
            
            // Manual Overstay Days Update action - for Super Admin only
            Actions\Action::make('manualOverstayDaysUpdate')
                ->label('Manual Overstay Days')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->modalHeading('Update Device Overstay Days')
                ->modalWidth('lg')
                ->form(function () {
                    // Check if exactly one device is selected BEFORE showing form
                    $device = $this->getSelectedDeviceForOverstayUpdate();
                    
                    if ($device === null) {
                        $selectedCount = count($this->selectedTableRecords ?? []);
                        
                        if ($selectedCount === 0) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('No Device Selected')
                                ->body('Please select a device retrieval record first by checking the checkbox.')
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Multiple Devices Selected')
                                ->body('Please select only ONE device retrieval record.')
                                ->send();
                        }
                        
                        return [];
                    }
                    
                    // Store the selected device for use in the action
                    $this->selectedDeviceRetrievalId = $device->id;
                    $this->selectedDeviceRetrieval = $device;
                    
                    return [
                        Forms\Components\TextInput::make('new_overstay_days')
                            ->label('Enter New Overstay Days Number')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->helperText('Observer will continue to monitor and update automatically.'),
                    ];
                })
                ->action(function (array $data): void {
                    // Only execute if we have a selected device
                    if ($this->selectedDeviceRetrievalId) {
                        $this->submitManualOverstayUpdate($data);
                    }
                })
                ->visible(fn () => auth()->user()?->hasRole('Super Admin')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('affixing_date')
                    ->label('Date of Affixing')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Dispatch Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('retrieval_status')
                    ->label('Status')
                    ->options([
                        'RETRIEVED' => 'Retrieved',
                        'NOT_RETRIEVED' => 'Not Retrieved',
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
            ->actions($this->isReadOnlyTrackerOfficer() ? [] : [
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

                                    // 3. Archive device retrieval record (instead of deleting)
                                    // This preserves invoices and maintains relationships
                                    $retrievalArchived = DB::table('device_retrievals')
                                        ->where('id', $record->id)
                                        ->update([
                                            'is_archived' => true,
                                            'archived_at' => now(),
                                            'archive_reason' => 'Returned to Outstation on ' . now()->format('Y-m-d H:i:s'),
                                            'updated_at' => now(),
                                        ]);

                                    // Log retrieval archiving
                                    Log::info('Device retrieval record archived (not deleted)', [
                                        'device_retrieval_id' => $record->id,
                                        'device_id' => $record->device_id,
                                        'rows_affected' => $retrievalArchived,
                                        'reason' => 'Returned to Outstation',
                                        'invoice_preserved' => true,
                                        'timestamp' => now()->toDateTimeString()
                                    ]);

                                    // 4. Delete monitoring record (tracking data no longer needed)
                                    $monitoringRecord = DB::table('monitorings')
                                        ->where('device_id', $record->device_id)
                                        ->first();

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

                                    // 5. Verify archiving succeeded
                                    if ($retrievalArchived === 0) {
                                        throw new \Exception('Failed to archive device retrieval record');
                                    }
                                });

                                DB::commit();

                                Notification::make()
                                    ->success()
                                    ->title('Device Returned Successfully')
                                    ->body('The device has been returned to outstation. Invoices and financial records have been preserved for audit and approval.')
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
                            $record->overstay_days >= 1 &&
                            $record->payment_status !== 'PD' &&
                            !$record->isWaived()
                        ),

                    // Admin Waiver action
                    AdminWaiverAction::make()
                        ->visible(fn (DeviceRetrievalModel $record): bool =>
                            auth()->user()?->hasRole(['Super Admin', 'Admin']) &&
                            $record->overstay_days > 0 &&
                            $record->payment_status === 'PP' &&
                            !$record->isWaived()
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
                            $isOverdue = $record->overstay_days > 0;
                            $isLongRoute = $record->long_route_id !== null;
                            $minDays = $isLongRoute ? 2 : 1;
                            $requiresReceipt = $isOverdue && $record->overstay_days >= $minDays;

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
                                    ->helperText("Device is overdue by {$record->overstay_days} days. Receipt number is required.")
                            ];
                        })
                        ->action(function ($record, array $data): void {
                            try {
                                DB::beginTransaction();

                                // Reload record from database to get fresh data (in case it was waived)
                                $record = DeviceRetrievalModel::findOrFail($record->id);

                                $isPrivilegedUser = auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager']);
                                $isOverdue = $record->overstay_days > 0;
                                $isLongRoute = $record->long_route_id !== null;
                                $minDays = $isLongRoute ? 2 : 1;
                                $requiresReceipt = $isOverdue && $record->overstay_days >= $minDays;

                                // Check if receipt is required but not provided
                                if (!$isPrivilegedUser && $requiresReceipt && empty($data['receipt_number'])) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Receipt Required')
                                        ->body('This device is overdue. Please provide a receipt number.')
                                        ->send();
                                    return;
                                }

                                // Check if device has overstay without payment - MUST generate bill first
                                // BUT: Allow retrieval if device is waived
                                if ($record->overstay_days >= 1 && $record->payment_status !== 'PD' && !$record->isWaived()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Bill Required Before Retrieval')
                                        ->body('Device has ' . $record->overstay_days . ' day(s) overstay. Overstay bill MUST be generated and marked as Paid before device retrieval.')
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
                            $isOverdue = $record->overstay_days > 0;
                            $isLongRoute = $record->long_route_id !== null;
                            $minDays = $isLongRoute ? 2 : 1;

                            if (!$isPrivilegedUser && $isOverdue && $record->overstay_days >= $minDays) {
                                return 'Retrieve Overdue Device';
                            }
                            return 'Retrieve Device';
                        })
                        ->modalDescription(function ($record) {
                            $isPrivilegedUser = auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager']);
                            $isOverdue = $record->overstay_days > 0;
                            $isLongRoute = $record->long_route_id !== null;
                            $minDays = $isLongRoute ? 2 : 1;

                            if (!$isPrivilegedUser && $isOverdue && $record->overstay_days >= $minDays) {
                                return "This device is overdue by {$record->overstay_days} days. Please provide a receipt number.";
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
            ->defaultSort('affixing_date', 'desc')
            ->poll('10s')
            ->selectable(!$this->isReadOnlyTrackerOfficer())
            ->bulkActions($this->isReadOnlyTrackerOfficer() ? [] : [
                \App\Filament\Actions\BulkUpdateOverstayAction::make(),
            ]);
    }

    // Helper to pick the correct date for display based on currently selected retrieval_status
    protected function getReport2DateForRecord($record)
    {
        $status = $this->report2Filters['retrieval_status'] ?? null;
        if ($status === 'RETRIEVED') {
            return $record->retrieval_date;
        }
        if ($status === 'RETURNED') {
            return $record->returned_at;
        }
        // default fallback
        return $record->retrieval_date ?? $record->returned_at ?? $record->created_at ?? null;
    }

    /**
     * Get data for Report #2 (dedicated DeviceRetrievalReport2 model with destination permissions)
     */
    public function getReport2DataProperty()
    {
        // Clear cache when date filtering is enabled
        if ($this->useDateFiltering) {
            Cache::forget($this->getCacheKey());
        }
        
        return cache()->remember($this->getCacheKey(), $this->cacheDuration, function () {
            // Base query for DeviceRetrievalReport2 (with global scope for destination permissions)
            $query = \App\Models\DeviceRetrievalReport2::query()
                ->with([
                    'device',
                    'allocationPoint',
                    'retrievedBy',
                    'returnedBy',
                    'route',
                ])
                ->select([
                    'id', 'device_id', 'device_full_id', 'boe', 'vehicle_number', 'regime', 
                    'destination', 'retrieval_status', 'action_type', 'retrieved_by', 
                    'returned_by', 'retrieval_date', 'returned_at', 'overstay_days', 
                    'overstay_amount', 'route_id', 'allocation_point_id', 'created_at', 'updated_at'
                ]);

            // Apply retrieval status filter (required)
            if (!empty($this->report2Filters['retrieval_status'])) {
                // Handle date filtering first if enabled
                if ($this->useDateFiltering) {
                    $startDateTime = !empty($this->report2Filters['start_date']) 
                        ? $this->report2Filters['start_date'] . ' ' . 
                          (!empty($this->report2Filters['start_time']) ? $this->report2Filters['start_time'] : '00:00:00')
                        : null;

                    $endDateTime = !empty($this->report2Filters['end_date'])
                        ? $this->report2Filters['end_date'] . ' ' .
                          (!empty($this->report2Filters['end_time']) ? $this->report2Filters['end_time'] : '23:59:59')
                        : null;

                    // If date filtering is enabled but no dates are selected, return empty results
                    if (empty($startDateTime) && empty($endDateTime)) {
                        return \App\Models\DeviceRetrievalReport2::whereRaw('1 = 0')->paginate(25);
                    }

                    // Apply date filtering based on selected column
                    if ($this->dateColumn === 'both' && $this->report2Filters['retrieval_status'] === 'ALL_STATUS') {
                        $query->where(function($q) use ($startDateTime, $endDateTime) {
                            // For both dates, check if either retrieval_date or returned_at matches
                            if ($startDateTime && $endDateTime) {
                                $q->where(function($subQ) use ($startDateTime, $endDateTime) {
                                    $subQ->whereBetween('retrieval_date', [$startDateTime, $endDateTime])
                                         ->orWhereBetween('returned_at', [$startDateTime, $endDateTime]);
                                });
                            } elseif ($startDateTime) {
                                $q->where(function($subQ) use ($startDateTime) {
                                    $subQ->where('retrieval_date', '>=', $startDateTime)
                                         ->orWhere('returned_at', '>=', $startDateTime);
                                });
                            } elseif ($endDateTime) {
                                $q->where(function($subQ) use ($endDateTime) {
                                    $subQ->where('retrieval_date', '<=', $endDateTime)
                                         ->orWhere('returned_at', '<=', $endDateTime);
                                });
                            }
                        });
                    } elseif ($this->dateColumn === 'retrieval_date' || $this->report2Filters['retrieval_status'] === 'RETRIEVED') {
                        // Apply date filtering for retrieval date
                        if ($startDateTime && $endDateTime) {
                            $query->whereBetween('retrieval_date', [$startDateTime, $endDateTime]);
                        } elseif ($startDateTime) {
                            $query->where('retrieval_date', '>=', $startDateTime);
                        } elseif ($endDateTime) {
                            $query->where('retrieval_date', '<=', $endDateTime);
                        }
                    } elseif ($this->dateColumn === 'returned_at' || $this->report2Filters['retrieval_status'] === 'RETURNED') {
                        // Apply date filtering for return date
                        if ($startDateTime && $endDateTime) {
                            $query->whereBetween('returned_at', [$startDateTime, $endDateTime]);
                        } elseif ($startDateTime) {
                            $query->where('returned_at', '>=', $startDateTime);
                        } elseif ($endDateTime) {
                            $query->where('returned_at', '<=', $endDateTime);
                        }
                    }
                }

                // Then apply retrieval status filter
                $query = $query->when($this->report2Filters['retrieval_status'] === 'RETRIEVED', function ($q) {
                    return $q->where('retrieval_status', 'RETRIEVED');
                })->when($this->report2Filters['retrieval_status'] === 'RETURNED', function ($q) {
                    return $q->where('retrieval_status', 'RETURNED');
                });

                // Apply date filtering if enabled and dates are provided
                if ($this->useDateFiltering && 
                    (!empty($this->report2Filters['start_date']) || !empty($this->report2Filters['end_date']))) {
                    
                    // Build date/time strings
                    $startDateTime = !empty($this->report2Filters['start_date']) 
                        ? $this->report2Filters['start_date'] . ' ' . 
                          (!empty($this->report2Filters['start_time']) 
                            ? $this->report2Filters['start_time'] . ':00' 
                            : '00:00:00')
                        : null;

                    $endDateTime = !empty($this->report2Filters['end_date'])
                        ? $this->report2Filters['end_date'] . ' ' .
                          (!empty($this->report2Filters['end_time'])
                            ? $this->report2Filters['end_time'] . ':59'
                            : '23:59:59')
                        : null;

                    // Apply date filtering based on status and filter selection
                    if ($this->report2Filters['retrieval_status'] === 'ALL_STATUS') {
                        // For ALL_STATUS, search based on selected date field
                        $query->where(function ($q) use ($startDateTime, $endDateTime) {
                            $q->where(function ($subQ) use ($startDateTime, $endDateTime) {
                                if ($startDateTime && $endDateTime) {
                                    $subQ->whereBetween('retrieval_date', [$startDateTime, $endDateTime])
                                         ->orWhereBetween('returned_at', [$startDateTime, $endDateTime]);
                                } else {
                                    $subQ->when($startDateTime, fn ($q) => $q->where('retrieval_date', '>=', $startDateTime)
                                                                          ->orWhere('returned_at', '>=', $startDateTime))
                                         ->when($endDateTime, fn ($q) => $q->where('retrieval_date', '<=', $endDateTime)
                                                                          ->orWhere('returned_at', '<=', $endDateTime));
                                }
                            });
                        });
                    } elseif ($this->report2Filters['retrieval_status'] === 'RETRIEVED') {
                        // For RETRIEVED status, always use retrieval_date
                        if ($startDateTime && $endDateTime) {
                            $query->whereBetween('retrieval_date', [$startDateTime, $endDateTime]);
                        } else {
                            $query->when($startDateTime, fn ($q) => $q->where('retrieval_date', '>=', $startDateTime))
                                  ->when($endDateTime, fn ($q) => $q->where('retrieval_date', '<=', $endDateTime));
                        }
                    } elseif ($this->report2Filters['retrieval_status'] === 'RETURNED') {
                        // For RETURNED status, always use returned_at
                        if ($startDateTime && $endDateTime) {
                            $query->whereBetween('returned_at', [$startDateTime, $endDateTime]);
                        } else {
                            $query->when($startDateTime, fn ($q) => $q->where('returned_at', '>=', $startDateTime))
                                  ->when($endDateTime, fn ($q) => $q->where('returned_at', '<=', $endDateTime));
                        }
                    }
                }
            } else {
                // If no retrieval status selected, return empty result
                $query->where('id', 0);
            }

            // Apply other filters
            $query->when($this->report2Filters['device_id'] ?? null, 
                fn ($q, $deviceId) => $q->where('device_full_id', 'like', "%{$deviceId}%"))
                  ->when($this->report2Filters['boe'] ?? null, 
                fn ($q, $boe) => $q->where('boe', 'like', "%{$boe}%"))
                  ->when($this->report2Filters['vehicle_number'] ?? null, 
                fn ($q, $vehicleNumber) => $q->where('vehicle_number', 'like', "%{$vehicleNumber}%"))
                  ->when($this->report2Filters['search'] ?? null, 
                fn ($q, $search) => $q->where(function($sq) use ($search) {
                    $sq->where('device_full_id', 'like', "%{$search}%")
                      ->orWhere('boe', 'like', "%{$search}%")
                      ->orWhere('vehicle_number', 'like', "%{$search}%");
                }));

            // Apply sorting
            $query->orderBy(
                $this->report2Filters['sort_by'] ?? 'created_at', 
                $this->report2Filters['sort_direction'] ?? 'desc'
            );

            return $query->paginate(25);
        });
    }

    /**
     * Apply Report #2 filters manually (triggered by Filter button)
     */
    public function applyReport2Filters()
    {
        // Copy temporary filters to active filters (retrieval_status and action_type are already live)
        $this->report2Filters = array_merge($this->report2Filters, [
            'search' => $this->tempReport2Filters['search'] ?? null,
            'device_id' => $this->tempReport2Filters['device_id'] ?? null,
            'boe' => $this->tempReport2Filters['boe'] ?? null,
            'vehicle_number' => $this->tempReport2Filters['vehicle_number'] ?? null,
            'start_date' => $this->tempReport2Filters['start_date'] ?? null,
            'end_date' => $this->tempReport2Filters['end_date'] ?? null,
            'start_time' => $this->tempReport2Filters['start_time'] ?? null,
            'end_time' => $this->tempReport2Filters['end_time'] ?? null,
            // retrieval_status and action_type are already updated via wire:model.live
        ]);
        
        // Trigger data refresh
        $this->resetPage();
    }

    /**
     * Reset Report #2 filters
     */
    /**
     * Handle retrieval status changes and clear cache
     */
    public function updatedReport2FiltersRetrievalStatus($value)
    {
        // Clear cache to reflect changes
        Cache::forget($this->getCacheKey());
    }

    public function resetReport2Filters()
    {
        $this->reset(['report2Filters', 'tempReport2Filters', 'useDateFiltering', 'dateColumn']);
        
        // Reinitialize filters
        $this->report2Filters = [
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
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
        
        $this->tempReport2Filters = [
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
        ];

        // Reset date filtering settings to defaults
        $this->useDateFiltering = false;
        $this->dateColumn = 'retrieval_date';
    }

    /**
     * Handle column sorting for Report #2
     */
    public function sortReport2By($column)
    {
        $currentSortBy = $this->report2Filters['sort_by'] ?? 'created_at';
        $currentDirection = $this->report2Filters['sort_direction'] ?? 'desc';

        if ($currentSortBy === $column) {
            // Toggle direction if same column
            $this->report2Filters['sort_direction'] = $currentDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // New column, default to asc
            $this->report2Filters['sort_by'] = $column;
            $this->report2Filters['sort_direction'] = 'asc';
        }

        // Clear cache when sorting changes
        Cache::forget($this->getCacheKey());
    }

    /**
     * ============================================
     * OVERSTAY DEVICES FEATURE METHODS
     * ============================================
     */

    /**
     * Get filtered overstay devices with pagination
     */
    public function getFilteredOverstayDevicesProperty()
    {
        $filterService = app(\App\Services\OverstayDeviceFilterService::class);
        return $filterService->applyFilters($this->overstayFilters)->paginate(10, ['*'], 'page', $this->overstayPage);
    }

    /**
     * Get statistics for filtered overstay devices
     */
    public function getOverstayStatisticsProperty()
    {
        if (!$this->hasActiveOverstayFilters()) {
            return null;
        }

        $filterService = app(\App\Services\OverstayDeviceFilterService::class);
        return $filterService->getStatistics($this->overstayFilters);
    }

    /**
     * Check if overstay filters are active
     */
    public function hasActiveOverstayFilters(): bool
    {
        $filterService = app(\App\Services\OverstayDeviceFilterService::class);
        return $filterService->hasActiveFilters($this->overstayFilters);
    }

    /**
     * Get available destinations for overstay devices
     */
    public function getAvailableDestinationsProperty()
    {
        $filterService = app(\App\Services\OverstayDeviceFilterService::class);
        return $filterService->getAvailableDestinations();
    }

    /**
     * Get available allocation points for overstay devices
     */
    public function getAvailableAllocationPointsProperty()
    {
        $filterService = app(\App\Services\OverstayDeviceFilterService::class);
        return $filterService->getAvailableAllocationPoints();
    }

    /**
     * Apply overstay filters
     */
    public function applyOverstayFilters(): void
    {
        // Copy temporary filters to active filters
        $this->overstayFilters = array_merge($this->overstayFilters, $this->tempOverstayFilters);
        $this->overstayPage = 1;
        Cache::forget($this->getCacheKeyOverstay());
        $this->resetPage();
    }

    /**
     * Reset overstay filters
     */
    public function resetOverstayFilters(): void
    {
        $this->overstayFilters = [
            'device_id' => null,
            'boe' => null,
            'invoice_number' => null,
            'destination_id' => null,
            'allocation_point_id' => null,
            'payment_status' => null,
            'overstay_amount_min' => null,
            'overstay_amount_max' => null,
            'overstay_days_min' => null,
            'overstay_days_max' => null,
            'start_date' => null,
            'end_date' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];

        $this->tempOverstayFilters = [
            'device_id' => null,
            'boe' => null,
            'invoice_number' => null,
            'destination_id' => null,
            'allocation_point_id' => null,
            'payment_status' => null,
            'overstay_amount_min' => null,
            'overstay_amount_max' => null,
            'overstay_days_min' => null,
            'overstay_days_max' => null,
            'start_date' => null,
            'end_date' => null,
        ];

        $this->destinationSearch = '';
        $this->allocationPointSearch = '';
        $this->overstayPage = 1;

        Cache::forget($this->getCacheKeyOverstay());
        $this->resetPage();
    }

    /**
     * Sort overstay devices by column
     */
    public function sortOverstayDevicesBy($column): void
    {
        $currentSortBy = $this->overstayFilters['sort_by'] ?? 'created_at';
        $currentDirection = $this->overstayFilters['sort_direction'] ?? 'desc';

        if ($currentSortBy === $column) {
            // Toggle direction if same column
            $this->overstayFilters['sort_direction'] = $currentDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // New column, default to asc
            $this->overstayFilters['sort_by'] = $column;
            $this->overstayFilters['sort_direction'] = 'asc';
        }

        $this->overstayPage = 1;
        Cache::forget($this->getCacheKeyOverstay());
    }

    /**
     * Set the current page for overstay devices pagination
     */
    public function setOverstayPage(int $page): void
    {
        $this->overstayPage = max(1, $page);
    }

    /**
     * Export overstay devices to Excel
     */
    public function exportOverstayDevices()
    {
        $params = $this->overstayFilters;
        $params = array_filter($params, fn($value) => $value !== null);
        return redirect(route('export.overstay-devices', $params));
    }

    /**
     * Get filtered destinations based on search query
     */
    public function getFilteredDestinationsProperty()
    {
        $destinations = $this->getAvailableDestinationsProperty();

        if (!$this->destinationSearch) {
            return collect();
        }

        return $destinations
            ->filter(fn($d) => str_contains(strtolower($d->name), strtolower($this->destinationSearch)))
            ->take(10);
    }

    /**
     * Get filtered allocation points based on search query
     */
    public function getFilteredAllocationPointsProperty()
    {
        $points = $this->getAvailableAllocationPointsProperty();

        if (!$this->allocationPointSearch) {
            return collect();
        }

        return $points
            ->filter(fn($p) => str_contains(strtolower($p->name), strtolower($this->allocationPointSearch)))
            ->take(10);
    }

    /**
     * Select a destination from search results
     */
    public function selectDestination(string $destinationId): void
    {
        $this->tempOverstayFilters['destination_id'] = $destinationId;
        $this->destinationSearch = '';
    }

    /**
     * Select an allocation point from search results
     */
    public function selectAllocationPoint(string $pointId): void
    {
        $this->tempOverstayFilters['allocation_point_id'] = $pointId;
        $this->allocationPointSearch = '';
    }

    /**
     * Clear selected destination
     */
    public function clearDestination(): void
    {
        $this->tempOverstayFilters['destination_id'] = null;
        $this->destinationSearch = '';
    }

    /**
     * Clear selected allocation point
     */
    public function clearAllocationPoint(): void
    {
        $this->tempOverstayFilters['allocation_point_id'] = null;
        $this->allocationPointSearch = '';
    }
}





