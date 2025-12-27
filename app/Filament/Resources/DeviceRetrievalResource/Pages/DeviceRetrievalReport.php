<?php

namespace App\Filament\Resources\DeviceRetrievalResource\Pages;

use App\Filament\Resources\DeviceRetrievalResource;
use App\Models\AllocationPoint;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Filament\Tables\Actions\Action;

class DeviceRetrievalReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = DeviceRetrievalResource::class;
    protected static string $view = 'filament.resources.device-retrieval-resource.pages.device-retrieval-report';

    protected static ?string $title = 'Device Retrieval Report';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $filters = [];
    public string $sort_by = 'created_at';
    public string $sort_direction = 'desc';

    public function mount(): void
    {
        $this->filters = [
            'device_id' => request('device_id'),
            'boe' => request('boe'),
            'vehicle_number' => request('vehicle_number'),
            'start_date' => request('start_date', now()->subDays(30)->format('Y-m-d')),
            'start_time' => request('start_time', '00:00'),
            'end_date' => request('end_date', now()->format('Y-m-d')),
            'end_time' => request('end_time', '23:59'),
            'allocation_point_id' => request('allocation_point_id'),
            'retrieval_status' => request('retrieval_status', ''),
            'action_type' => request('action_type', ''),
            'destination' => request('destination'),
            'sort_by' => request('sort_by', 'created_at'),
            'sort_direction' => request('sort_direction', 'desc'),
            'search' => request('search', ''),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('retrieval_status')->label('Retrieval Status')->badge()->color(fn (string $state): string => match (strtolower($state)) {
                    'retrieved' => 'success',
                    'not_retrieved' => 'warning',
                    'returned' => 'info',
                    default => 'gray',
                })->sortable(),
                TextColumn::make('device.device_id')->label('Device ID')->searchable()->sortable(),
                TextColumn::make('boe')->label('BOE/SAD')->searchable()->sortable(),
                TextColumn::make('vehicle_number')->label('Vehicle Number')->searchable()->sortable(),
                TextColumn::make('destination')->label('Destination')->searchable()->sortable()->limit(30),
                TextColumn::make('allocationPoint.name')->label('Allocation Point')->searchable()->sortable(),
                TextColumn::make('action_type')->badge()->color(fn (string $state): string => match (strtolower($state)) {
                    'retrieved' => 'success',
                    'returned' => 'info',
                    default => 'gray',
                })->sortable(),
                TextColumn::make('retrievedBy.name')->label('Retrieved By')->searchable()->sortable(),
                TextColumn::make('retrieval_date')->label('Retrieval Date')->dateTime()->sortable(),
                TextColumn::make('returned_at')->label('Returned At')->dateTime()->sortable(),
                TextColumn::make('overstay_days')->label('Overstay Days')->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('overstay_amount')->label('Overstay Amount')->money('GMD')->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                // Add any additional filters here
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export to Excel')
                    ->color('success')
                    ->action(function () {
                        $filters = $this->filters;
                        $filters['sort_by'] = $this->sort_by;
                        $filters['sort_direction'] = $this->sort_direction;
                        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\DeviceRetrievalReportExport($filters, \App\Models\DeviceRetrievalLog::class), 'device-retrieval-report-' . now()->format('Y-m-d') . '.xlsx');
                    }),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        // Initialize query first
        $query = \App\Models\DeviceRetrievalLog::query()
            ->withoutGlobalScopes()
            ->with(['device', 'allocationPoint', 'retrievedBy', 'route', 'longRoute', 'distributionPoint']);

        // Determine which date field to use based on retrieval status
        $dateField = 'retrieval_date'; // default to retrieval_date
        if (!empty($this->filters['retrieval_status']) && strtoupper($this->filters['retrieval_status']) === 'RETURNED') {
            $dateField = 'returned_at';
        }

        // Log which date field is being used
        \Log::info('DeviceRetrievalReportExport: Date field selection', [
            'selected_date_field' => $dateField,
            'retrieval_status' => $this->filters['retrieval_status'] ?? 'not_set'
        ]);

        // Apply date and time filters using the correct date field
        if (!empty($this->filters['start_date'])) {
            $startDateTime = $this->filters['start_date'];
            if (!empty($this->filters['start_time'])) {
                $startDateTime .= ' ' . $this->filters['start_time'];
            } else {
                $startDateTime .= ' 00:00:00';
            }
            $query->where($dateField, '>=', $startDateTime);
        }
        
        if (!empty($this->filters['end_date'])) {
            $endDateTime = $this->filters['end_date'];
            if (!empty($this->filters['end_time'])) {
                $endDateTime .= ' ' . $this->filters['end_time'];
            } else {
                $endDateTime .= ' 23:59:59';
            }
            $query->where($dateField, '<=', $endDateTime);
        }

        $query = $query->when($this->filters['allocation_point_id'] ?? null, fn (Builder $query, $id) => $query->where('allocation_point_id', $id))
            ->when($this->filters['retrieval_status'] ?? null, fn (Builder $query, $status) => $query->where('retrieval_status', $status))
            ->when($this->filters['action_type'] ?? null, fn (Builder $query, $actionType) => $query->where('action_type', $actionType))
            ->when($this->filters['device_id'] ?? null, fn (Builder $query, $deviceId) => $query->whereHas('device', function($q) use ($deviceId) {
                $q->where('device_id', 'like', "%{$deviceId}%");
            }))
            ->when($this->filters['boe'] ?? null, fn (Builder $query, $boe) => $query->where('boe', 'like', "%{$boe}%"))
            ->when($this->filters['vehicle_number'] ?? null, fn (Builder $query, $vehicleNumber) => $query->where('vehicle_number', 'like', "%{$vehicleNumber}%"))
            ->when(isset($this->filters['destination']) && trim($this->filters['destination']) !== '', 
                function (Builder $query) {
                    $destination = trim($this->filters['destination']);
                    $query->where('destination', 'LIKE', "%{$destination}%");
                    
                    // Log destination filtering
                    \Log::info('DeviceRetrievalReport: Destination filter applied', [
                        'user_id' => auth()->id(),
                        'destination_filter' => $destination,
                        'sql' => $query->toSql(),
                        'bindings' => $query->getBindings()
                    ]);
                })
            ->when($this->filters['search'] ?? null, fn (Builder $query, $search) => $query->where(function($q) use ($search) {
                $q->whereHas('device', function($q) use ($search) {
                    $q->where('device_id', 'like', "%{$search}%");
                })
                ->orWhere('boe', 'like', "%{$search}%")
                ->orWhere('vehicle_number', 'like', "%{$search}%");
            }));

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

        // Apply sorting
        $query->orderBy($this->filters['sort_by'] ?? 'created_at', $this->filters['sort_direction'] ?? 'desc');
        return $query;
    }

    public function resetFilters(): void
    {
        $this->filters = [
            'device_id' => null,
            'boe' => null,
            'vehicle_number' => null,
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'start_time' => '00:00',
            'end_date' => now()->format('Y-m-d'),
            'end_time' => '23:59',
            'retrieval_status' => '',
            'action_type' => '',
            'allocation_point_id' => null,
            'destination' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
            'search' => '',
        ];
        $this->dispatch('refreshTable');
    }

    public function getExportUrlProperty(): string
    {
        $params = array_filter($this->filters);
        $query = http_build_query($params);
        return route('export.device-retrieval-report') . ($query ? ('?' . $query) : '');
    }

    public function getAllocationPointsProperty(): array
    {
        return AllocationPoint::pluck('name', 'id')->toArray();
    }

    public function sortBy(string $column): void
    {
        if ($this->filters['sort_by'] === $column) {
            $this->filters['sort_direction'] = $this->filters['sort_direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            $this->filters['sort_by'] = $column;
            $this->filters['sort_direction'] = 'asc';
        }
        $this->dispatch('refreshTable');
    }

    public function applyFilters(): void
    {
        $this->dispatch('refreshTable');
    }

    public function getDeviceRetrievalLogsProperty()
    {
        return $this->getTableQuery()->paginate(25);
    }
}
