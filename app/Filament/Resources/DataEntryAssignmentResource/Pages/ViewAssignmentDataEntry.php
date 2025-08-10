<?php

namespace App\Filament\Resources\DataEntryAssignmentResource\Pages;

use App\Filament\Resources\DataEntryAssignmentResource;
use App\Models\Device;
use App\Models\Regime;
use App\Models\Destination;
use App\Models\AssignToAgent;
use App\Models\ConfirmedAffixed;
use App\Models\Route;
use App\Models\LongRoute;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Forms;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DeviceRetrieval;
use App\Models\DispatchLog;
use Filament\Forms\Components\View;
use App\Filament\Resources\DispatchLogResource;

class ViewAssignmentDataEntry extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = DataEntryAssignmentResource::class;

    protected static string $view = 'filament.resources.data-entry-assignment.pages.view-assignment-data-entry';

    public $dataEntryAssignment;
    public $allocationPoint;
    public $selectedDevices = [];
    public $selectedRegime = null;
    public $destinations = [];
    public $showAssignedToAgent = false;
    public $showDispatchReportModal = false;

    // Filter properties
    public $filters = [
        'device_id' => null,
        'start_date' => null,
        'end_date' => null,
        'start_time' => null,
        'end_time' => null,
        'allocation_point_id' => null,
        'sort_by' => 'dispatched_at',
        'sort_direction' => 'desc',
    ];

    public function getDispatchLogsProperty()
    {
        if (!$this->dataEntryAssignment) {
            return collect();
        }

        $query = DispatchLog::query()
            ->where('data_entry_assignment_id', $this->dataEntryAssignment->id)
            ->with(['device', 'dispatcher', 'device.confirmedAffixed', 'device.confirmedAffixed.route', 'device.confirmedAffixed.longRoute']);

        // Apply device ID filter
        if (!empty($this->filters['device_id'])) {
            $deviceId = $this->filters['device_id'];
            $query->whereHas('device', function($q) use ($deviceId) {
                $q->where('device_id', 'LIKE', "%{$deviceId}%");
            });
        }

        // Apply date range filter
        if (!empty($this->filters['start_date'])) {
            $startDate = $this->filters['start_date'];
            if (!empty($this->filters['start_time'])) {
                $startDate = $startDate . ' ' . $this->filters['start_time'];
            }
            $query->where('dispatched_at', '>=', $startDate);
        } elseif (!empty($this->filters['start_time'])) {
            $query->whereTime('dispatched_at', '>=', $this->filters['start_time']);
        }
        if (!empty($this->filters['end_date'])) {
            $endDate = $this->filters['end_date'];
            if (!empty($this->filters['end_time'])) {
                $endDate = $endDate . ' ' . $this->filters['end_time'];
            } else {
                $endDate = $endDate . ' 23:59:59';
            }
            $query->where('dispatched_at', '<=', $endDate);
        } elseif (!empty($this->filters['end_time'])) {
            $query->whereTime('dispatched_at', '<=', $this->filters['end_time']);
        }
        // Apply allocation point filter
        if (!empty($this->filters['allocation_point_id'])) {
            $query->whereHas('device', function($q) {
                $q->whereHas('confirmedAffixed', function($q) {
                    $q->where('allocation_point_id', $this->filters['allocation_point_id']);
                });
            });
        }
        // Apply sorting
        $sortBy = $this->filters['sort_by'] ?? 'dispatched_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);
        return $query->paginate(10);
    }

    /**
     * Get allocation points for the filter dropdown
     */
    public function getAllocationPointsProperty()
    {
        return \App\Models\AllocationPoint::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Reset all filters
     */
    public function resetFilters()
    {
        $this->reset('filters');
        // Force refresh of computed properties
        unset($this->cachedMountedActions);
    }

    /**
     * Apply filters and refresh modal content
     */
    public function applyFilters()
    {
        // This method is called when Apply Filters is clicked
        // The reactive properties will automatically update
    }

    /**
     * Handle column sorting
     */
    public function sortBy($column)
    {
        $currentSortBy = $this->filters['sort_by'] ?? 'dispatched_at';
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

    public function mount(string|int $record): void
    {
        // Get the model class without global scopes
        $modelClass = DataEntryAssignmentResource::getModel();

        // Find the record without global scopes to ensure we can access it
        $this->dataEntryAssignment = $modelClass::withoutGlobalScopes()->find($record);

        if (!$this->dataEntryAssignment) {
            // Check if the record exists at all
            $exists = $modelClass::withoutGlobalScopes()->where('id', $record)->exists();

            if (!$exists) {
                throw new \Exception('Data entry assignment not found (ID: ' . $record . ')');
            }

            // If the record exists but is filtered by scopes, check permissions
            $user = auth()->user();
            if (!$user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
                throw new \Exception('You do not have permission to access this assignment.');
            } else {
                throw new \Exception('Data entry assignment not found or you do not have permission to access it.');
            }
        }

        // Load the allocation point relationship
        $this->allocationPoint = $this->dataEntryAssignment->allocationPoint;

        if (!$this->allocationPoint) {
            throw new \Exception('Allocation point not found for this assignment.');
        }

        // Check user permissions
        if (!auth()->user()->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
            $userAllocationPoints = auth()->user()->allocationPoints->pluck('id')->toArray();
            if (!in_array($this->allocationPoint->id, $userAllocationPoints)) {
                abort(403, 'You do not have permission to access this assignment.');
            }
        }

        if (!auth()->user()->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
            abort(403, 'Unauthorized access.');
        }

        $this->form->fill([
            'date' => now(),
            'device_ids' => [],
            'boe' => null,
            'vehicle_number' => null,
            'regime' => null,
            'destination' => null,
            'destination_id' => null,
            'route_id' => null,
            'long_route_id' => null,
            'manifest_date' => null,
            'agency' => null,
            'agent_contact' => null,
            'truck_number' => null,
            'driver_name' => null,
        ]);
    }

    public function filterByStatus($status): void
    {
        $filters = $this->tableFilters;

        if (!isset($filters['status'])) {
            $filters['status'] = ['values' => []];
        }

        $currentValues = $filters['status']['values'] ?? [];

        if (in_array($status, $currentValues)) {
            $filters['status']['values'] = array_diff($currentValues, [$status]);
        } else {
            $filters['status']['values'] = array_merge($currentValues, [$status]);
        }

        if ($status === 'ASSIGNED TO AGENT') {
            $this->showAssignedToAgent = !$this->showAssignedToAgent;
        }

        $this->tableFilters = $filters;
    }

    public function updatedSelectedRegime($value)
    {
        $this->reset('destinations');
        if ($value) {
            $this->destinations = Destination::where('regime_id', $value)
                ->where('status', 'active')
                ->pluck('name', 'id');
        }
    }

    public function updatedTableFilters($filters): void
    {
        $this->showAssignedToAgent = collect($filters['status'] ?? [])->contains('ASSIGNED TO AGENT');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('view_dispatch_report')
                ->label('View Dispatch Report')
                ->color('success')
                ->icon('heroicon-o-document-chart-bar')
                ->modalContent(fn () => view('filament.resources.data-entry-assignment-resource.pages.dispatch-report-modal', [
                    'dispatchLogs' => $this->dispatchLogs,
                    'assignment' => $this->dataEntryAssignment,
                    'allocationPoints' => $this->allocationPoints,
                    'filters' => $this->filters,
                ]))
                ->modalWidth('7xl')
                ->modalSubmitActionLabel('Export to Excel')
                ->modalSubmitAction(function ($action) {
                    $params = [
                        'assignment' => $this->dataEntryAssignment->id,
                        'device_id' => $this->filters['device_id'] ?? null,
                        'start_date' => $this->filters['start_date'] ?? null,
                        'end_date' => $this->filters['end_date'] ?? null,
                        'start_time' => $this->filters['start_time'] ?? null,
                        'end_time' => $this->filters['end_time'] ?? null,
                        'allocation_point_id' => $this->filters['allocation_point_id'] ?? null,
                        'sort_by' => $this->filters['sort_by'] ?? null,
                        'sort_direction' => $this->filters['sort_direction'] ?? null
                    ];

                    // Remove null values from the params array
                    $filteredParams = array_filter($params, function($value) {
                        return $value !== null;
                    });

                    return $action->url(route('export.dispatch-report', $filteredParams));
                })
                ->visible(fn () => $this->dataEntryAssignment && auth()->user()->can('viewAny', \App\Models\DispatchLog::class)),

            \Filament\Actions\Action::make('dp_form')
                ->label('Dispatch Device(s)')
                ->color('warning')
                ->icon('heroicon-o-truck')
                ->modalWidth('2xl')
                ->form([
                    Forms\Components\DatePicker::make('date')
                        ->label('Dispatch Date')
                        ->default(now())
                        ->disabled(),
                    Forms\Components\Textarea::make('device_serial')
                        ->label('Device ID')
                        ->default(function () {
                            Log::info('Getting device serials', ['selectedDevices' => $this->selectedDevices]);

                            if (empty($this->selectedDevices)) {
                                return '';
                            }

                            $devices = Device::whereIn('id', $this->selectedDevices)
                                ->get(['id', 'device_id'])
                                ->map(function ($device) {
                                    return $device->device_id;
                                });

                            Log::info('Found devices', ['devices' => $devices]);

                            return $devices->join(', ');
                        })
                        ->disabled()
                        ->rows(2),
                    Forms\Components\TextInput::make('boe')
                        ->label('SAD/T1')
                        ->required(),
                    Forms\Components\TextInput::make('vehicle_number')
                        ->label('Vehicle Number')
                        ->required(),
                    Forms\Components\Select::make('regime')
                        ->label('Regime')
                        ->options(fn () => Regime::where('is_active', true)->pluck('name', 'id'))
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->selectedRegime = $state)
                        ->required(),
                    Forms\Components\Select::make('destination')
                        ->label('Destination')
                        ->options(function (callable $get) {
                            $regimeId = $get('regime');
                            if (!$regimeId) return [];
                            return Destination::where('regime_id', $regimeId)
                                ->where('status', 'active')
                                ->pluck('name', 'id');
                        })
                        ->required(),
                    Forms\Components\Select::make('route_id')
                        ->label('Route')
                        ->options(fn () => Route::pluck('name', 'id')),
                       // ->required(),
                    Forms\Components\Select::make('long_route_id')
                        ->label('Long Route')
                        ->options(fn () => LongRoute::pluck('name', 'id')),
                       // ->required(),
                    Forms\Components\DatePicker::make('manifest_date')
                        ->label('Manifest Date'),
                        //->required(),
                    Forms\Components\TextInput::make('agency')
                        ->label('Agency'),
                    Forms\Components\TextInput::make('agent_contact')
                        ->label('Agent Contact')
                        ->tel(),
                    Forms\Components\TextInput::make('truck_number')
                        ->label('Truck Number'),
                    Forms\Components\TextInput::make('driver_name')
                        ->label('Driver Name'),
                ])
                ->action(function (array $data): void {
                    try {
                        Log::info('Starting form submission', [
                            'selectedDevices' => $this->selectedDevices,
                            'formData' => $data
                        ]);

                        DB::beginTransaction();

                        // Validate selected devices
                        if (empty($this->selectedDevices)) {
                            Log::warning('No devices selected during form submission');
                            throw new \Exception('Please select at least one device before submitting');
                        }

                        // Check for already dispatched devices
                        $alreadyDispatchedDevices = AssignToAgent::whereIn('device_id', $this->selectedDevices)
                            ->get()
                            ->pluck('device.device_id');

                        if ($alreadyDispatchedDevices->isNotEmpty()) {
                            throw new \Exception('The following devices are already dispatched: ' . $alreadyDispatchedDevices->join(', '));
                        }

                        // Check for devices with RECEIVED status
                        $receivedDevices = Device::whereIn('id', $this->selectedDevices)
                            ->where('status', 'RECEIVED')
                            ->pluck('device_id');

                        if ($receivedDevices->isNotEmpty()) {
                            throw new \Exception('The following devices cannot be dispatched because they have RECEIVED status. They must be collected first: ' . $receivedDevices->join(', '));
                        }

                        // Create ConfirmedAffixed records for each device
                        collect($this->selectedDevices)->each(function ($deviceId) use ($data) {
                            $device = Device::find($deviceId);

                            Log::info('Processing device', [
                                'device_id' => $deviceId,
                                'device_serial' => $device->device_id
                            ]);

                            // Create AssignToAgent record
                            $assignmentData = [
                                'date' => now(),
                                'device_id' => $device->id,
                                'boe' => $data['boe'],
                                'vehicle_number' => $data['vehicle_number'],
                                'regime' => Regime::find($data['regime'])->name,
                                'destination_id' => $data['destination'],
                                'destination' => Destination::find($data['destination'])->name,
                                'route_id' => $data['route_id'],
                                'long_route_id' => $data['long_route_id'],
                                'manifest_date' => $data['manifest_date'],
                                'agency' => $data['agency'] ?? null,
                                'agent_contact' => $data['agent_contact'] ?? null,
                                'truck_number' => $data['truck_number'] ?? null,
                                'driver_name' => $data['driver_name'] ?? null,
                                'allocation_point_id' => $device->allocation_point_id // Store original allocation point
                            ];

                            Log::info('Creating AssignToAgent record', $assignmentData);
                            $assignment = AssignToAgent::create($assignmentData);

                            if (!$assignment) {
                                Log::error('Failed to create AssignToAgent', $assignmentData);
                                throw new \Exception('Failed to create assignment for device: ' . $device->device_id);
                            }

                            // Create ConfirmedAffixed record with the same data
                            $confirmedAffixedData = array_merge($assignmentData, [
                                'status' => 'PENDING'
                            ]);

                            Log::info('Creating ConfirmedAffixed record', $confirmedAffixedData);
                            $confirmedAffixed = ConfirmedAffixed::create($confirmedAffixedData);

                            if (!$confirmedAffixed) {
                                Log::error('Failed to create ConfirmedAffixed', $confirmedAffixedData);
                                throw new \Exception('Failed to create confirmed affixed record for device: ' . $device->device_id);
                            }

                            // Log the dispatch
                            try {
                                $dispatchLog = \App\Models\DispatchLog::create([
                                    'device_id' => $device->id,
                                    'data_entry_assignment_id' => $this->dataEntryAssignment->id,
                                    'dispatched_by' => auth()->id(),
                                    'dispatched_at' => now(),
                                    'details' => [
                                        'status' => 'DISPATCHED',
                                        'boe' => $data['boe'],
                                        'vehicle_number' => $data['vehicle_number'],
                                        'destination' => $confirmedAffixedData['destination'] ?? null,
                                        'route_id' => $data['route_id'] ?? null,
                                        'long_route_id' => $data['long_route_id'] ?? null,
                                        'manifest_date' => $data['manifest_date'] ?? null,
                                        'action' => 'dispatched_to_confirmed_affixed'
                                    ]
                                ]);

                                Log::info('Dispatch logged successfully', [
                                    'dispatch_log_id' => $dispatchLog->id,
                                    'device_id' => $device->id,
                                    'assignment_id' => $this->dataEntryAssignment->id
                                ]);
                            } catch (\Exception $e) {
                                Log::error('Failed to log dispatch', [
                                    'error' => $e->getMessage(),
                                    'device_id' => $device->id,
                                    'assignment_id' => $this->dataEntryAssignment->id
                                ]);
                                // Don't fail the whole operation if logging fails
                            }

                            // Remove device from allocation point
                            $device->update(['allocation_point_id' => null]);

                            Log::info('Successfully created assignment and confirmed affixed records', [
                                'assignment_id' => $assignment->id,
                                'confirmed_affixed_id' => $confirmedAffixed->id,
                                'device_id' => $device->id,
                                'device_serial' => $device->device_id
                            ]);
                        });

                        DB::commit();

                        $this->selectedDevices = [];

                        Notification::make()
                            ->title('Devices dispatched successfully')
                            ->success()
                            ->send();

                        Log::info('Form submission completed successfully');
                    } catch (\Exception $e) {
                        DB::rollBack();

                        Log::error('Error dispatching devices: ' . $e->getMessage(), [
                            'exception' => $e,
                            'data' => $data ?? [],
                            'selectedDevices' => $this->selectedDevices
                        ]);

                        Notification::make()
                            ->title('Error dispatching devices')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function table(Table $table): Table
    {
        if ($this->showAssignedToAgent) {
            return $this->assignedToAgentTable($table);
        }

        return $this->devicesTable($table);
    }

    protected function assignedToAgentTable(Table $table): Table
    {
        return $table
            ->query(AssignToAgent::query())
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('device.device_id')
                    ->label('Device ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('boe')
                    ->label('SAD/T1')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle_number')
                    ->label('Vehicle No.')
                    ->searchable(),
                Tables\Columns\TextColumn::make('regime')
                    ->label('Regime')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination.name')
                    ->label('Destination')
                    ->description(fn ($record) => $record->destination_id ? '' : 'Legacy Data')
                    ->searchable(),
                Tables\Columns\TextColumn::make('route.name')
                    ->label('Route')
                    ->searchable(),
                    Tables\Columns\TextColumn::make('longRoute.name')
                    ->label('Long Route')
                    ->searchable(),

                   // Tables\Columns\TextColumn::make('affixing_date')
                   // ->dateTime()
                   // ->sortable(),

                Tables\Columns\TextColumn::make('manifest_date')
                    ->label('Manifest Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agency')
                    ->label('Agency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agent_contact')
                    ->label('Agent Contact'),
                Tables\Columns\TextColumn::make('truck_number')
                    ->label('Truck No.'),
                Tables\Columns\TextColumn::make('driver_name')
                    ->label('Driver Name'),
                    ])
            ->defaultSort('date', 'desc')
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->modalWidth('7xl')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->required(),
                        Forms\Components\TextInput::make('boe')
                            ->label('SAD/T1')
                            ->required(),
                        Forms\Components\TextInput::make('vehicle_number')
                            ->required(),
                        Forms\Components\Select::make('regime')
                            ->options(Regime::where('is_active', true)->pluck('name', 'name'))
                            ->required(),
                        Forms\Components\Select::make('route_id')
                            ->label('Route')
                            ->options(Route::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                            Forms\Components\Select::make('destination_id')
                            ->label('Destination')
                            ->options(Destination::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('long_route_id')
                            ->label('Long Route')
                            ->options(LongRoute::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\DatePicker::make('manifest_date')
                            ->label('Manifest Date')
                            ->required(),
                        Forms\Components\TextInput::make('agency'),
                        Forms\Components\TextInput::make('agent_contact')
                            ->tel(),
                        Forms\Components\TextInput::make('truck_number'),
                        Forms\Components\TextInput::make('driver_name'),
                    ])
                    ->action(function (AssignToAgent $record, array $data): void {
                        DB::beginTransaction();
                        try {
                            $destination = Destination::findOrFail($data['destination_id']);
                            $record->update([
                                'date' => $data['date'],
                                'boe' => $data['boe'],
                                'vehicle_number' => $data['vehicle_number'],
                                'regime' => $data['regime'],
                                'destination_id' => $destination->id,
                                // Remove direct destination name assignment, let the relationship handle it
                                'route_id' => $data['route_id'],
                                'long_route_id' => $data['long_route_id'],
                                'manifest_date' => $data['manifest_date'],
                                'agency' => $data['agency'],
                                'agent_contact' => $data['agent_contact'],
                                'truck_number' => $data['truck_number'],
                                'driver_name' => $data['driver_name'],
                            ]);
                            DB::commit();

                            Notification::make()
                                ->title('Record updated successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()
                                ->title('Error updating record')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (AssignToAgent $record): void {
                        if ($record->device) {
                            $record->device->update(['allocation_point_id' => $this->allocationPoint->id]);
                        }
                        $record->delete();

                        Notification::make()
                            ->title('Record deleted successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('pickForAffixing')
                    ->label('Pick for Affixing')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Pick for Affixing')
                    ->modalDescription('Are you sure you want to pick these devices for affixing? Please select the affixing date below.')
                    ->modalSubmitActionLabel('Yes, Pick for Affixing')
                    ->modalCancelActionLabel('No, Cancel')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->required(),
                        Forms\Components\TextInput::make('boe')
                            ->label('SAD/T1')
                            ->required(),
                        Forms\Components\DateTimePicker::make('affixing_date')
                            ->label('Affixing Date')
                            ->required(),
                        Forms\Components\TextInput::make('vehicle_number')
                            ->required(),
                        Forms\Components\Select::make('regime')
                            ->options(Regime::where('is_active', true)->pluck('name', 'name'))
                            ->required(),

                        Forms\Components\Select::make('destination_id')
                            ->label('Destination')
                            ->relationship('destination', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('route_id')
                            ->label('Route')
                            ->options(Route::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('long_route_id')
                            ->label('Long Route')
                            ->options(LongRoute::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\DatePicker::make('manifest_date')
                            ->label('Manifest Date')
                            ->required(),
                        Forms\Components\TextInput::make('agency'),
                        Forms\Components\TextInput::make('agent_contact')
                            ->tel(),
                        Forms\Components\TextInput::make('truck_number'),
                        Forms\Components\TextInput::make('driver_name'),
                    ])
                    ->action(function (array $data, Collection $records): void {
                        DB::beginTransaction();
                        try {
                            foreach ($records as $record) {
                                $destination = Destination::findOrFail($data['destination_id']);
                                $record->update([
                                    'date' => $data['date'],
                                    'boe' => $data['boe'],
                                    'vehicle_number' => $data['vehicle_number'],
                                    'regime' => $data['regime'],
                                    'destination_id' => $destination->id,
                                    'route_id' => $data['route_id'],
                                    'long_route_id' => $data['long_route_id'],
                                    'manifest_date' => $data['manifest_date'],
                                    'agency' => $data['agency'],
                                    'agent_contact' => $data['agent_contact'],
                                    'truck_number' => $data['truck_number'],
                                    'driver_name' => $data['driver_name'],
                                ]);
                            }
                            DB::commit();

                            Notification::make()
                                ->title('Records updated successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()
                                ->title('Error updating records')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\BulkAction::make('delete')
                    ->label('Delete Selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $records->each(function ($record) {
                            // Restore allocation point before deleting
                            if ($record->device) {
                                $record->device->update(['allocation_point_id' => $this->allocationPoint->id]);
                            }
                            $record->delete();
                        });

                        Notification::make()
                            ->title('Records deleted successfully')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function devicesTable(Table $table): Table
    {
        return $table
            ->query(Device::query()
                ->where('allocation_point_id', $this->allocationPoint->id)
                ->with('dataEntryAssignment') // Eager load the relationship
            )
            ->columns([
                Tables\Columns\TextColumn::make('device_id')
                    ->label('Device ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('device_type')
                    ->label('Device Type')
                    ->searchable()
                    ->sortable(),
Tables\Columns\TextColumn::make('date_received')
->label('Receipt Date')
->date('Y-m-d')
->sortable(),
Tables\Columns\TextColumn::make('date_received')
->label('Receipt Date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_received')
                    ->label('Receipt Date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_received')
                    ->label('Receipt Date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_received')
                    ->label('Receipt Date')
                    ->date('Y-m-d')
->searchable()
                    ->sortable(),
Tables\Columns\TextColumn::make('date_received')
->label('Receipt Date')
->date('Y-m-d')
->sortable(),
Tables\Columns\TextColumn::make('date_received')
->label('Receipt Date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_received')
                    ->label('Receipt Date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_received')
                    ->label('Receipt Date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_received')
                    ->label('Receipt Date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_received')
                    ->label('Receipt Date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ONLINE' => 'success',
                        'OFFLINE' => 'danger',
                        'DAMAGED' => 'warning',
                        'FIXED' => 'info',
                        'LOST' => 'gray',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('dataEntryAssignment.notes')
                    ->label('Return Note')
                    ->description(fn ($record) => $record->dataEntryAssignment && $record->dataEntryAssignment->updated_at
                        ? 'On: ' . $record->dataEntryAssignment->updated_at->format('Y-m-d H:i')
                        : '')
                    ->wrap()
                    ->toggleable()
                    ->tooltip(function ($record) {
                        if ($record->dataEntryAssignment && $record->dataEntryAssignment->notes) {
                            return 'Full note: ' . $record->dataEntryAssignment->notes;
                        }
                        return null;
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Assigned By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Assigned On')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'ONLINE' => 'Online',
                        'OFFLINE' => 'Offline',
                        'DAMAGED' => 'Damaged',
                        'FIXED' => 'Fixed',
                        'LOST' => 'Lost',
                        'ASSIGNED TO AGENT' => 'Assigned to Agent',
                    ])
                    ->multiple()
                    ->label('Status'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('select_devices')
                    ->label('Select')
                    ->action(function (Collection $records): void {
                        Log::info('Bulk action triggered with records', [
                            'record_count' => $records->count(),
                            'record_ids' => $records->pluck('id')->toArray()
                        ]);
                        $this->selectedDevices = $records->pluck('id')->toArray();
                        Log::info('Selected devices updated', [
                            'selectedDevices' => $this->selectedDevices
                        ]);
                    })
                    ->deselectRecordsAfterCompletion()
                    ->color('primary')
                    ->button(),
            ]);
    }

    public function getTitle(): string
    {
        return "Devices for {$this->allocationPoint->name}";
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('device_id')
                ->relationship('device', 'device_id')
                ->required()
                ->searchable(),

            Forms\Components\Select::make('destination_id')
                ->label('Destination')
                ->relationship('destination', 'name')
                ->required()
                ->searchable()
                ->reactive()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if ($state) {
                        $destination = Destination::find($state);
                        if ($destination) {
                            $set('destination', $destination->name);
                            $set('destination_id', $destination->id);
                        }
                    }
                }),

            Forms\Components\Hidden::make('destination'),

            // ...existing code...
        ];
    }

    public function submit()
    {
        try {
            DB::beginTransaction();

            $data = $this->form->getState();

            // Ensure destination data is properly set
            if (isset($data['destination']) && $data['destination']) {
                $destination = Destination::find($data['destination']);
                if ($destination) {
                    $data['destination_id'] = $destination->id;
                    $data['destination'] = $destination->name;
                }
            }

            // Validate devices
            if (empty($data['device_ids'])) {
                throw new \Exception('No devices selected for assignment.');
            }

            // Process device assignments...
            foreach ($data['device_ids'] as $deviceId) {
                $device = Device::find($deviceId);
                if (!$device) {
                    Log::warning("Device not found", ['device_id' => $deviceId]);
                    continue;
                }

                $assignmentData = array_merge(
                    Arr::except($data, ['device_ids']),
                    ['device_id' => $device->id]
                );

                // Create records...
                $assignment = AssignToAgent::create($assignmentData);
                $confirmedAffixed = ConfirmedAffixed::create(array_merge($assignmentData, ['status' => 'PENDING']));

                if (!$assignment || !$confirmedAffixed) {
                    throw new \Exception('Failed to create records for device: ' . $device->device_id);
                }

                // Update device allocation
                $device->update(['allocation_point_id' => null]);
            }

            DB::commit();

            $this->reset(['selectedDevices']);
            $this->mount($this->dataEntryAssignment->id);

            Notification::make()
                ->title('Success')
                ->body('Devices have been assigned successfully.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Assignment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Error')
                ->body('Failed to assign devices: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
