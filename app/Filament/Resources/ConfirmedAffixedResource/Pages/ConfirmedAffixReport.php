<?php

namespace App\Filament\Resources\ConfirmedAffixedResource\Pages;

use App\Filament\Resources\ConfirmedAffixedResource;
use App\Models\AllocationPoint;
use App\Models\ConfirmedAffixed;
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

class ConfirmedAffixReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = ConfirmedAffixedResource::class;
    protected static string $view = 'filament.resources.confirmed-affixed-resource.pages.confirmed-affix-report';

    protected static ?string $title = 'Confirmed Affix Report';
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
            'status' => request('status', ''),
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
                TextColumn::make('device.device_id')->label('Device ID')->searchable()->sortable(),
                TextColumn::make('boe')->label('BOE/SAD')->searchable()->sortable(),
                TextColumn::make('vehicle_number')->label('Vehicle Number')->searchable()->sortable(),
                TextColumn::make('allocationPoint.name')->label('Allocation Point')->searchable()->sortable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match (strtolower($state)) {
                    'confirmed' => 'success',
                    'pending' => 'warning',
                    'rejected' => 'danger',
                    default => 'gray',
                })->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                // Add any additional filters here
            ])
            ->headerActions([
                Action::make('filter')
                    ->label('Filter')
                    ->color('primary')
                    ->modalHeading('Filter Report')
                    ->form([
                        TextInput::make('search')->label('Search'),
                        DatePicker::make('start_date')->label('Start Date'),
                        TextInput::make('start_time')->label('Start Time')->type('time'),
                        DatePicker::make('end_date')->label('End Date'),
                        TextInput::make('end_time')->label('End Time')->type('time'),
                        Select::make('allocation_point_id')
                            ->label('Allocation Point')
                            ->options(\App\Models\AllocationPoint::pluck('name', 'id')->toArray()),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                '' => 'All',
                                'confirmed' => 'Confirmed',
                                'pending' => 'Pending',
                                'rejected' => 'Rejected',
                            ]),
                    ])
                    ->action(function (array $data) {
                        $this->filters = array_merge($this->filters, $data);
                        \Filament\Notifications\Notification::make()->title('Filters applied')->success()->send();
                    }),
                Action::make('export')
                    ->label('Export to Excel')
                    ->color('success')
                    ->action(function () {
                        $filters = $this->filters;
                        $filters['sort_by'] = $this->sort_by;
                        $filters['sort_direction'] = $this->sort_direction;
                        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ConfirmedAffixReportExport($filters, \App\Models\ConfirmedAffixLog::class), 'confirmed-affix-report-' . now()->format('Y-m-d') . '.xlsx');
                    }),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        $startDateTime = null;
        $endDateTime = null;
        if (!empty($this->filters['start_date'])) {
            $startDateTime = $this->filters['start_date'];
            if (!empty($this->filters['start_time'])) {
                $startDateTime .= ' ' . $this->filters['start_time'];
            } else {
                $startDateTime .= ' 00:00:00';
            }
        }
        if (!empty($this->filters['end_date'])) {
            $endDateTime = $this->filters['end_date'];
            if (!empty($this->filters['end_time'])) {
                $endDateTime .= ' ' . $this->filters['end_time'];
            } else {
                $endDateTime .= ' 23:59:59';
            }
        }
        $query = \App\Models\ConfirmedAffixLog::query()
            ->with(['device', 'allocationPoint'])
            ->when($startDateTime, fn (Builder $query) => $query->where('created_at', '>=', $startDateTime))
            ->when($endDateTime, fn (Builder $query) => $query->where('created_at', '<=', $endDateTime))
            ->when($this->filters['allocation_point_id'] ?? null, fn (Builder $query, $id) => $query->where('allocation_point_id', $id))
            ->when($this->filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))
            ->when($this->filters['device_id'] ?? null, fn (Builder $query, $deviceId) => $query->whereHas('device', function($q) use ($deviceId) {
                $q->where('device_id', 'like', "%{$deviceId}%");
            }))
            ->when($this->filters['boe'] ?? null, fn (Builder $query, $boe) => $query->where('boe', 'like', "%{$boe}%"))
            ->when($this->filters['vehicle_number'] ?? null, fn (Builder $query, $vehicleNumber) => $query->where('vehicle_number', 'like', "%{$vehicleNumber}%"))
            ->when($this->filters['destination'] ?? null, fn (Builder $query, $destination) => $query->where('destination', 'like', "%{$destination}%"))
            ->when($this->filters['search'] ?? null, fn (Builder $query, $search) => $query->where(function($q) use ($search) {
                $q->whereHas('device', function($q) use ($search) {
                    $q->where('device_id', 'like', "%{$search}%");
                })
                ->orWhere('boe', 'like', "%{$search}%")
                ->orWhere('vehicle_number', 'like', "%{$search}%");
            }));
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
            'status' => '',
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
        return route('export.confirmed-affix-report') . ($query ? ('?' . $query) : '');
    }

    public function getAllocationPointsProperty(): array
    {
        return AllocationPoint::pluck('name', 'id')->toArray();
    }


}
