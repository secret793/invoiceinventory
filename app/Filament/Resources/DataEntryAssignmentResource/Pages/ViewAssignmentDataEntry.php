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
use App\Models\Receipt;
use App\Models\AllocationPoint;
use App\Services\ExchangeRateService;
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
use App\Services\ReceiptFilterService;

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

    // Receipt filter properties
    public array $receiptFilters = [
        'receipt_number' => null,
        'destination_id' => null,
        'start_date' => null,
        'start_time' => null,
        'end_date' => null,
        'end_time' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ];

    public string $destinationSearch = '';

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

    /**
     * Get filtered receipts with pagination
     */
    public function getFilteredReceiptsProperty()
    {
        $filterService = app(ReceiptFilterService::class);
        
        $filters = array_merge($this->receiptFilters, [
            'allocation_point_id' => $this->dataEntryAssignment->allocation_point_id,
        ]);

        return $filterService->applyFilters($filters)->paginate(10);
    }

    /**
     * Get receipt statistics (only when filters are active)
     */
    public function getReceiptStatisticsProperty()
    {
        if (!$this->hasActiveReceiptFilters()) {
            return null;
        }

        $filterService = app(ReceiptFilterService::class);
        
        $filters = array_merge($this->receiptFilters, [
            'allocation_point_id' => $this->dataEntryAssignment->allocation_point_id,
        ]);

        return $filterService->getStatistics($filters);
    }

    /**
     * Check if receipt filters are active
     */
    public function hasActiveReceiptFilters(): bool
    {
        $filterService = app(ReceiptFilterService::class);
        return $filterService->hasActiveFilters($this->receiptFilters);
    }

    /**
     * Get available destinations for receipts
     */
    public function getReceiptDestinationsProperty()
    {
        $filterService = app(ReceiptFilterService::class);
        return $filterService->getAvailableDestinations($this->dataEntryAssignment->allocation_point_id);
    }

    /**
     * Apply receipt filters
     */
    public function applyReceiptFilters(): void
    {
        // Trigger reactive refresh
        $this->dispatch('refreshReceiptTable');
    }

    /**
     * Reset receipt filters
     */
    public function resetReceiptFilters(): void
    {
        $this->receiptFilters = [
            'receipt_number' => null,
            'destination_id' => null,
            'start_date' => null,
            'start_time' => null,
            'end_date' => null,
            'end_time' => null,
            'sort_by' => 'date',
            'sort_direction' => 'desc',
        ];
        $this->dispatch('refreshReceiptTable');
    }

    /**
     * Sort receipts by column
     */
    public function sortReceiptsBy($column): void
    {
        if ($this->receiptFilters['sort_by'] === $column) {
            // Toggle direction if same column
            $this->receiptFilters['sort_direction'] = 
                $this->receiptFilters['sort_direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            // New column, default to asc
            $this->receiptFilters['sort_by'] = $column;
            $this->receiptFilters['sort_direction'] = 'asc';
        }
        $this->dispatch('refreshReceiptTable');
    }

    /**
     * Export receipts to Excel
     */
    public function exportReceipts()
    {
        $params = array_merge($this->receiptFilters, [
            'allocation_point_id' => $this->dataEntryAssignment->allocation_point_id,
        ]);

        // Remove null values
        $params = array_filter($params, fn($value) => $value !== null);

        $this->dispatch('open-export-url', url: route('export.receipts', $params));
    }

    /**
     * Export receipts to CSV
     */
    public function exportReceiptsCsv()
    {
        $params = array_merge($this->receiptFilters, [
            'allocation_point_id' => $this->dataEntryAssignment->allocation_point_id,
            'format' => 'csv',
        ]);
        $params = array_filter($params, fn($value) => $value !== null);
        $this->dispatch('open-export-url', url: route('export.receipts', $params));
    }

    /**
     * Get filtered destinations based on search query
     */
    public function getFilteredDestinationsProperty()
    {
        $destinations = $this->receiptDestinations;
        
        if (!$this->destinationSearch) {
            return collect();
        }
        
        return collect($destinations)
            ->filter(fn($name) => str_contains(strtolower($name), strtolower($this->destinationSearch)))
            ->take(10); // Limit to 10 results
    }

    /**
     * Select a destination from search results
     */
    public function selectDestination(string $destinationId): void
    {
        $this->receiptFilters['destination_id'] = $destinationId;
        $this->destinationSearch = '';
    }

    /**
     * Clear selected destination
     */
    public function clearDestination(): void
    {
        $this->receiptFilters['destination_id'] = null;
        $this->destinationSearch = '';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('generate_receipt')
                ->label('Generate Receipt')
                ->color('info')
                ->icon('heroicon-o-ticket')
                ->modalWidth('4xl')
                ->before(function () {
                    \Illuminate\Support\Facades\Log::info('🔵 MODAL OPENED: Generate Receipt', [
                        'action_name' => 'generate_receipt',
                        'timestamp' => now()->toDateTimeString(),
                        'user_id' => auth()->id(),
                        'assignment_id' => $this->dataEntryAssignment?->id,
                    ]);
                })
                ->form($this->getReceiptFormSchema())
                ->action(function (array $data): void {
                    \Illuminate\Support\Facades\Log::info('🔵 FORM SUBMITTED: Generate Receipt', ['data' => $data]);
                    // Validate that either route_id or long_route_id is selected
                    if (empty($data['route_id']) && empty($data['long_route_id'])) {
                        \Filament\Notifications\Notification::make()
                            ->title('Validation Error')
                            ->body('Please select either Route or Long Route before submitting the form.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $receipt = $this->createReceiptFromModalAndReturn($data);
                })
                ->visible(fn () => $this->dataEntryAssignment && auth()->user()->can('create', Receipt::class)),

            \Filament\Actions\Action::make('view_dispatch_report')
                ->label('View Dispatch Report')
                ->color('success')
                ->icon('heroicon-o-document-chart-bar')
                ->before(function () {
                    \Illuminate\Support\Facades\Log::info('🟢 MODAL OPENED: View Dispatch Report', [
                        'action_name' => 'view_dispatch_report',
                        'timestamp' => now()->toDateTimeString(),
                        'user_id' => auth()->id(),
                        'assignment_id' => $this->dataEntryAssignment?->id,
                    ]);
                })
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
                ->before(function () {
                    \Illuminate\Support\Facades\Log::info('🟡 MODAL OPENED: Dispatch Device(s)', [
                        'action_name' => 'dp_form',
                        'timestamp' => now()->toDateTimeString(),
                        'user_id' => auth()->id(),
                        'selected_devices' => $this->selectedDevices ?? [],
                        'assignment_id' => $this->dataEntryAssignment?->id,
                    ]);
                })
                ->form([
                    Forms\Components\DatePicker::make('date')
                        ->label('Dispatch Date')
                        ->default(now())
                        ->disabled(),

                    // PHASE 5: Add Receipt Selection with search-only autocomplete using getSearchResultsUsing
                    Forms\Components\Select::make('receipt_id')
                        ->label('Receipt Number')
                        ->searchable()
                        ->preload(false)
                        ->getSearchResultsUsing(fn (string $search) => 
                            Receipt::query()
                                ->where('used', '>', 0)
                                ->where(function ($query) use ($search) {
                                    $query->where('receipt_number', 'like', "%{$search}%")
                                          ->orWhere('sad_number', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->pluck('receipt_number', 'id')
                                ->toArray()
                        )
                        ->getOptionLabelUsing(fn ($value) => 
                            Receipt::find($value)?->receipt_number ?? 'Unknown'
                        )
                        ->optionsLimit(50)
                        ->searchPrompt('Type to search receipts...')
                        ->noSearchResultsMessage('No available receipts found.')
                        ->loadingMessage('Searching receipts...')
                        ->searchDebounce(300)
                        ->placeholder('Search by receipt number or SAD...')
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            $this->validateAndPopulateReceipt($set, $state);
                        })
                        ->required(),

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
                        ->searchable()
                        ->preload(false)
                        ->getSearchResultsUsing(function (string $search, callable $get) {
                            $regimeId = $get('regime');
                            if (!$regimeId) {
                                return [];
                            }
                            return Destination::where('regime_id', $regimeId)
                                ->where('status', 'active')
                                ->where('name', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value) => 
                            Destination::find($value)?->name ?? 'Unknown'
                        )
                        ->reactive()
                        ->required(),

                    Forms\Components\Select::make('route_id')
                        ->label('Route')
                        ->options(fn () => Route::pluck('name', 'id'))
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            if (!$state) {
                                return;
                            }

                            try {
                                $route = Route::find($state);
                                if (!$route) {
                                    return;
                                }

                                // Use 'amount' column for unit charge calculation
                                // The 'amount' is already in GMD, so use it directly as unit charge
                                $unitChargGMD = $route->amount ?? 0;
                                $set('unit_charge_gmd', $unitChargGMD);

                                // Get current trucks value and recalculate total
                                $trucks = $get('moving_trucks') ?? 1;
                                if ($unitChargGMD > 0) {
                                    $totalChargGMD = $unitChargGMD * $trucks;
                                    $set('total_charge_gmd', $totalChargGMD);
                                    $set('used', $trucks);
                                }
                            } catch (\Exception $e) {
                                \Log::error('Error in route_id afterStateUpdated: ' . $e->getMessage());
                            }
                        }),

                    Forms\Components\Select::make('long_route_id')
                        ->label('Long Route')
                        ->options(fn () => LongRoute::pluck('name', 'id')),

                    Forms\Components\DatePicker::make('manifest_date')
                        ->label('Manifest Date'),

                    Forms\Components\TextInput::make('agency')
                        ->label('Agency'),

                    Forms\Components\TextInput::make('agent_contact')
                        ->label('Agent Contact')
                        ->tel(),

                    Forms\Components\TextInput::make('truck_number')
                        ->label('Truck Number'),

                    Forms\Components\TextInput::make('driver_name')
                        ->label('Driver Name')
                        ->required(),
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

                        // PHASE 5: Check receipt availability
                        $receipt = Receipt::find($data['receipt_id'] ?? null);
                        if (!$receipt) {
                            throw new \Exception('Receipt not found');
                        }

                        if (!$receipt->canBeUsed()) {
                            throw new \Exception("Receipt {$receipt->receipt_number} is not available (fully used)");
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
                        collect($this->selectedDevices)->each(function ($deviceId) use ($data, $receipt) {
                            $device = Device::find($deviceId);

                            Log::info('Processing device', [
                                'device_id' => $deviceId,
                                'device_serial' => $device->device_id
                            ]);

                            // Create AssignToAgent record with receipt_id
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
                                'allocation_point_id' => $device->allocation_point_id,
                                'receipt_id' => $data['receipt_id'] ?? null, // PHASE 5: Add receipt_id
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

            \Filament\Actions\Action::make('view_generated_receipts')
                ->label('View Generated Receipts')
                ->color('primary')
                ->icon('heroicon-o-list-bullet')
                ->modalHeading('Generated Receipts')
                ->modalWidth('7xl')
                ->before(function () {
                    \Illuminate\Support\Facades\Log::info('🟣 MODAL OPENED: View Generated Receipts', [
                        'action_name' => 'view_generated_receipts',
                        'timestamp' => now()->toDateTimeString(),
                        'user_id' => auth()->id(),
                        'assignment_id' => $this->dataEntryAssignment?->id,
                    ]);
                })
                ->modalContent(fn () => view('filament.resources.data-entry-assignment.pages.receipts-table-modal', [
                    'receiptFilters' => $this->receiptFilters,
                    'filteredReceipts' => $this->filteredReceipts,
                    'receiptStatistics' => $this->receiptStatistics,
                    'receiptDestinations' => $this->receiptDestinations,
                    'destinationSearch' => $this->destinationSearch,
                    'filteredDestinations' => $this->filteredDestinations,
                ]))
                ->visible(fn () => auth()->user()->can('viewAny', Receipt::class)),
        ];
    }

    /**
     * Get all receipts for modal display
     */
    private function getReceiptsForModal(): array
    {
        $allocationPointId = $this->dataEntryAssignment->allocation_point_id;
        
        return Receipt::where('allocation_point_id', $allocationPointId)
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn ($receipt) => [
                'receipt_number' => $receipt->receipt_number,
                'date' => $receipt->created_at ? $receipt->created_at->format('Y-m-d H:i') : 'N/A',
                'moving_trucks' => $receipt->moving_trucks,
                'used' => $receipt->used,
                'total_charge_gmd' => 'D ' . number_format($receipt->total_charge_gmd, 2),
            ])
            ->toArray();
    }

    /**
     * Get receipts for table view
     */
    private function getReceiptsForDisplay()
    {
        $allocationPointId = $this->dataEntryAssignment->allocation_point_id;
        
        return Receipt::where('allocation_point_id', $allocationPointId)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Show PDF preview for receipt
     */
    private function showReceiptPdfPreview(Receipt $receipt): void
    {
        // This will be handled by the modal content view
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
                            ->options(Destination::where('status', 'Active')->pluck('name', 'id'))
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
                            ->relationship('destination', 'name', fn ($query) => $query->where('status', 'Active'))
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
                ->with(['dataEntryAssignment', 'user'])
                ->addSelect([
                    'return_notes_data' => \App\Models\DataEntryAssignment::select('notes')
                        ->whereColumn('allocation_point_id', 'devices.allocation_point_id')
                        ->where('status', 'RETURNED')
                        ->latest()
                        ->limit(1),
                    'return_date' => \App\Models\DataEntryAssignment::select('updated_at')
                        ->whereColumn('allocation_point_id', 'devices.allocation_point_id')
                        ->where('status', 'RETURNED')
                        ->latest()
                        ->limit(1)
                ])
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
                    ->searchable()
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
                Tables\Columns\TextColumn::make('return_notes_data')
                    ->label('Return Notes')
                    ->getStateUsing(function ($record) {
                        return $record->return_notes_data ?? '';
                    })
                    ->description(function ($record) {
                        return $record->return_date
                            ? 'Returned on: ' . \Carbon\Carbon::parse($record->return_date)->format('Y-m-d H:i')
                            : '';
                    })
                    ->wrap()
                    ->words(30)
                    ->toggleable()
                    ->tooltip(function ($record) {
                        return $record->return_notes_data ? 'Full note: ' . $record->return_notes_data : null;
                    })
                    ->placeholder('No return notes'),
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
                    ->label('Device Status'),
                SelectFilter::make('has_return_notes')
                    ->label('Return Status')
                    ->options([
                        'with_notes' => 'Has Return Notes',
                        'without_notes' => 'No Return Notes',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        if (in_array('with_notes', $data['values'])) {
                            $query->whereNotNull('returned_assignments.notes')
                                  ->where('returned_assignments.notes', '!=', '');
                        }

                        if (in_array('without_notes', $data['values'])) {
                            $query->where(function($q) {
                                $q->whereNull('returned_assignments.notes')
                                  ->orWhere('returned_assignments.notes', '=', '');
                            });
                        }

                        return $query;
                    }),
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
                ->relationship('destination', 'name', fn ($query) => $query->where('status', 'Active'))
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

    /**
     * Get form schema for receipt generation modal
     */
    private function getReceiptFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\DateTimePicker::make('date')
                        ->label('Receipt Date')
                        ->default(now())
                        ->disabled()
                        ->dehydrated()
                        ->required(),

                    Forms\Components\Select::make('consignment_nature')
                        ->label('Consignment Nature')
                        ->options([
                            'CN' => 'CN – Containers',
                            'FT' => 'FT – Fuel Tanker',
                            'GC' => 'GC – General Cargo',
                            'OV' => 'OV – Overland Vehicles',
                        ])
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('sad_number')
                        ->label('SAD Number')
                        ->required()
                        ->maxLength(50)
                        ->unique('receipts', 'sad_number'),
                ])
                ->columns(3),

            Forms\Components\Section::make('Route Selection')
                ->description('Select either Route OR Long Route (at least one is required)')
                ->schema([
                    Forms\Components\Select::make('route_id')
                        ->label('Route')
                        ->options(Route::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            if (!$state) return;
                            
                            $route = Route::find($state);
                            if (!$route) return;
                            
                            // Get exchange rate from API
                            $exchangeRateService = app(\App\Services\ExchangeRateService::class);
                            $exchangeRate = $exchangeRateService->getGMDPerUSD();
                            
                            // Calculate unit charge: base_usd × exchange_rate
                            $baseUSD = $route->base_usd_amount ?? 0;
                            $unitChargGMD = $baseUSD * $exchangeRate;
                            
                            // Set pricing fields
                            $set('base_unit_charge_usd', $baseUSD);
                            $set('exchange_rate_used', $exchangeRate);
                            $set('unit_charge_gmd', $unitChargGMD);
                            
                            // Set billing unit to "Short Route" (static text)
                            $set('billing_unit', 'Short Route');
                            
                            // Clear long route if short route is selected
                            $set('long_route_id', null);
                            
                            // Calculate total based on trucks
                            $trucks = $get('moving_trucks') ?? 1;
                            if ($unitChargGMD > 0) {
                                $totalChargGMD = $unitChargGMD * $trucks;
                                $set('total_charge_gmd', $totalChargGMD);
                                $set('used', $trucks);
                            }
                        }),

                    Forms\Components\Select::make('long_route_id')
                        ->label('Long Route')
                        ->options(LongRoute::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            if (!$state) return;
                            
                            $longRoute = LongRoute::find($state);
                            if (!$longRoute) return;
                            
                            // Get exchange rate from API
                            $exchangeRateService = app(\App\Services\ExchangeRateService::class);
                            $exchangeRate = $exchangeRateService->getGMDPerUSD();
                            
                            // Calculate unit charge: base_usd × exchange_rate
                            $baseUSD = $longRoute->base_usd_amount ?? 0;
                            $unitChargGMD = $baseUSD * $exchangeRate;
                            
                            // Set pricing fields
                            $set('base_unit_charge_usd', $baseUSD);
                            $set('exchange_rate_used', $exchangeRate);
                            $set('unit_charge_gmd', $unitChargGMD);
                            
                            // Set billing unit to "Long Route" (static text)
                            $set('billing_unit', 'Long Route');
                            
                            // Clear short route if long route is selected
                            $set('route_id', null);
                            
                            // Calculate total based on trucks
                            $trucks = $get('moving_trucks') ?? 1;
                            if ($unitChargGMD > 0) {
                                $totalChargGMD = $unitChargGMD * $trucks;
                                $set('total_charge_gmd', $totalChargGMD);
                                $set('used', $trucks);
                            }
                        }),

                    Forms\Components\TextInput::make('billing_unit')
                        ->label('Billing Unit')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('moving_trucks')
                        ->label('Moving Trucks')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(1000)
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            $unitCharge = $get('unit_charge_gmd');
                            if ($unitCharge && $state) {
                                $set('total_charge_gmd', $unitCharge * $state);
                                $set('used', $state);
                            }
                        }),
                ])
                ->columns(2),

            Forms\Components\Section::make('Location')
                ->schema([
                    Forms\Components\Select::make('allocation_point_id')
                        ->label('Allocation Point')
                        ->options(AllocationPoint::where('status', 'active')->pluck('name', 'id'))
                        ->default(fn () => $this->dataEntryAssignment?->allocation_point_id)
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('destination_id')
                        ->label('Destination')
                        ->options(Destination::where('status', 'Active')->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Pricing Calculation')
                ->schema([
                    Forms\Components\TextInput::make('base_unit_charge_usd')
                        ->label('Base Unit Charge (USD)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->prefix('$'),

                    Forms\Components\TextInput::make('exchange_rate_used')
                        ->label('Exchange Rate (GMD/USD)')
                        ->numeric()
                        ->disabled()
                        ->helperText('Controlled by Admin in System Settings')
                        ->dehydrated(),

                    Forms\Components\TextInput::make('unit_charge_gmd')
                        ->label('Unit Charge (GMD)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->prefix('D'),

                    Forms\Components\TextInput::make('total_charge_gmd')
                        ->label('Total Charge (GMD)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->prefix('D'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Agent & Consignment Details')
                ->schema([
                    Forms\Components\TextInput::make('agent_name')
                        ->label('Agent Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('agent_phone')
                        ->label('Agent Phone')
                        ->tel()
                        ->required()
                        ->maxLength(20),

                    Forms\Components\Textarea::make('consignee_details')
                        ->label('Consignee Details')
                        ->required()
                        ->maxLength(500),

                    Forms\Components\Textarea::make('shipper_details')
                        ->label('Shipper Details')
                        ->maxLength(500),

                    Forms\Components\Textarea::make('description_of_goods')
                        ->label('Description of Goods')
                        ->required()
                        ->maxLength(1000),
                ])
                ->columns(2),

            Forms\Components\Section::make('System Generated')
                ->schema([
                    Forms\Components\TextInput::make('used')
                        ->label('Available Usage Count')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->default(0),

                    Forms\Components\Hidden::make('created_by')
                        ->default(fn () => auth()->id())
                        ->dehydrated(),
                ])
                ->columns(2),
        ];
    }

    /**
     * Create receipt from modal form data
     */
    /**
     * Create receipt and return the instance for PDF preview
     */
    private function createReceiptFromModalAndReturn(array $data): ?Receipt
    {
        try {
            DB::beginTransaction();

            // Use the unit_charge_gmd that was calculated from route.amount in the form
            $unitGMD = $data['unit_charge_gmd'] ?? 0;
            $totalGMD = $data['total_charge_gmd'] ?? 0;
            $exchangeRate = $data['exchange_rate_used'] ?? 0;
            $baseCharge = $data['base_unit_charge_usd'] ?? 0;
            $trucks = $data['moving_trucks'] ?? 1;

            // If values are missing, try to calculate from route
            if ($unitGMD == 0 && isset($data['route_id'])) {
                $route = Route::find($data['route_id']);
                if ($route) {
                    $unitGMD = $route->amount ?? 0;
                    $totalGMD = $unitGMD * $trucks;
                }
            }

            // Determine billing unit based on route type
            $billingUnit = $data['billing_unit'] ?? null;
            if (!$billingUnit) {
                if (isset($data['long_route_id']) && $data['long_route_id']) {
                    $billingUnit = 'Long Route';
                } elseif (isset($data['route_id']) && $data['route_id']) {
                    $billingUnit = 'Short Route';
                }
            }

            // Create receipt record
            $receipt = Receipt::create([
                'receipt_number' => $this->generateReceiptNumber(),
                'date' => $data['date'] ?? now(),
                'consignment_nature' => $data['consignment_nature'],
                'sad_number' => $data['sad_number'],
                'route_id' => $data['route_id'] ?? null,
                'long_route_id' => $data['long_route_id'] ?? null,
                'allocation_point_id' => $data['allocation_point_id'] ?? $this->dataEntryAssignment?->allocation_point_id,
                'destination_id' => $data['destination_id'] ?? null,
                'billing_unit' => $billingUnit,
                'moving_trucks' => $trucks,
                'base_unit_charge_usd' => $baseCharge,
                'exchange_rate_used' => $exchangeRate,
                'unit_charge_gmd' => $unitGMD,
                'total_charge_gmd' => $totalGMD,
                'agent_name' => $data['agent_name'],
                'agent_phone' => $data['agent_phone'],
                'consignee_details' => $data['consignee_details'],
                'shipper_details' => $data['shipper_details'] ?? null,
                'description_of_goods' => $data['description_of_goods'],
                'used' => $trucks,
                'created_by' => auth()->id(),
                'generated_by_user' => auth()->id(),
            ]);

            DB::commit();

            $pdfUrl = route('receipts.pdf', $receipt);

            Notification::make()
                ->title('Receipt Created Successfully!')
                ->body("Receipt {$receipt->receipt_number} has been generated. Click 'View Generated Receipts' to download the PDF.")
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Download PDF')
                        ->url($pdfUrl, shouldOpenInNewTab: true),
                ])
                ->send();

            return $receipt;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Receipt generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Error')
                ->body('Failed to generate receipt: ' . $e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * Populate dispatch form fields from selected receipt
     * PHASE 5: Auto-fill dispatch form fields when receipt is selected
     */
    private function populateReceiptFields(Forms\Set $set, $receiptId): void
    {
        if (!$receiptId) {
            return;
        }

        $receipt = Receipt::with(['route', 'longRoute', 'destination', 'destination.regime'])->find($receiptId);
        if (!$receipt) {
            Log::warning('Receipt not found during form population', ['receipt_id' => $receiptId]);
            return;
        }

        // Auto-fill form fields from receipt
        $set('boe', $receipt->sad_number);
        $set('agent_contact', $receipt->agent_phone);

        // Populate destination - must set regime first so destination options load
        if ($receipt->destination && $receipt->destination->regime) {
            // Set regime first to enable destination options
            $set('regime', $receipt->destination->regime_id);
            // Then set destination
            $set('destination', $receipt->destination_id);
            
            Log::info('Destination and regime populated from receipt', [
                'receipt_id' => $receipt->id,
                'destination_id' => $receipt->destination_id,
                'regime_id' => $receipt->destination->regime_id,
            ]);
        }

        if ($receipt->route) {
            $set('route_id', $receipt->route_id);
        }

        if ($receipt->longRoute) {
            $set('long_route_id', $receipt->long_route_id);
        }

        Log::info('Receipt fields populated in dispatch form', [
            'receipt_id' => $receipt->id,
            'receipt_number' => $receipt->receipt_number,
        ]);
    }

    /**
     * Validate receipt and populate fields with real-time validation
     */
    private function validateAndPopulateReceipt(Forms\Set $set, $receiptId): void
    {
        if (!$receiptId) {
            return;
        }

        $receipt = Receipt::with([
            'route', 
            'longRoute', 
            'destination', 
            'destination.regime'
        ])->find($receiptId);

        if (!$receipt) {
            Log::warning('Receipt not found during form population', [
                'receipt_id' => $receiptId
            ]);
            return;
        }

        // VALIDATION: Check if receipt has been fully used
        if (!$receipt->canBeUsed()) {
            \Filament\Notifications\Notification::make()
                ->title('Receipt Exhausted')
                ->body("Receipt {$receipt->receipt_number} has been fully used. " .
                       "Please select another receipt.")
                ->danger()
                ->send();
            
            // Clear the selection
            $set('receipt_id', null);
            return;
        }

        // Show usage status
        \Filament\Notifications\Notification::make()
            ->title('Receipt Selected')
            ->body("Receipt {$receipt->receipt_number} - " .
                   "Usage: {$receipt->getUsageDisplay()} trucks available")
            ->success()
            ->send();

        // Populate form fields
        $set('boe', $receipt->sad_number);
        $set('agent_contact', $receipt->agent_phone);

        // IMPORTANT: Set regime FIRST so destination options can load based on regime_id
        // The destination field is dependent on regime, so regime must be set before destination
        if ($receipt->destination && $receipt->destination->regime) {
            $set('regime', $receipt->destination->regime_id);
            Log::info('Regime populated from receipt', [
                'receipt_id' => $receipt->id,
                'regime_id' => $receipt->destination->regime_id,
            ]);
            
            // Now set destination - this will work because regime is already set
            $set('destination', $receipt->destination_id);
            Log::info('Destination populated from receipt', [
                'receipt_id' => $receipt->id,
                'destination_id' => $receipt->destination_id,
            ]);
        }

        // Populate route and long route
        if ($receipt->route) {
            $set('route_id', $receipt->route_id);
            Log::info('Route populated from receipt', [
                'receipt_id' => $receipt->id,
                'route_id' => $receipt->route_id,
            ]);
        }

        if ($receipt->longRoute) {
            $set('long_route_id', $receipt->long_route_id);
            Log::info('Long route populated from receipt', [
                'receipt_id' => $receipt->id,
                'long_route_id' => $receipt->long_route_id,
            ]);
        }

        Log::info('Receipt validated and fields populated', [
            'receipt_id' => $receipt->id,
            'receipt_number' => $receipt->receipt_number,
            'usage_status' => $receipt->getUsageDisplay(),
            'regime_id' => $receipt->destination?->regime_id,
            'destination_id' => $receipt->destination_id,
        ]);
    }

    /**
     * Generate unique receipt number
     */
    private function generateReceiptNumber(): string
    {
        $date = now()->format('Ymd');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return 'R-' . $date . '-' . $random;
    }
}
