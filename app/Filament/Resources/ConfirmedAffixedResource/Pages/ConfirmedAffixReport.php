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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                TextColumn::make('affixedBy.name')->label('Affixed By')->searchable()->sortable(),
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
                            ->options($this->getAllocationPointsForFilter()),
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
            ->with(['device', 'allocationPoint', 'affixedBy']);

        // Apply allocation point permission filtering (same logic as ConfirmedAffixed model)
        $user = auth()->user();
        if ($user && !$user->hasRole(['Super Admin', 'Warehouse Manager'])) {
            // For Retrieval Officer and Affixing Officer, filter by allocation point permissions
            if ($user->hasRole(['Retrieval Officer', 'Affixing Officer'])) {
                // Get all permissions starting with 'view_allocationpoint_'
                $permissions = $user->permissions->pluck('name')->toArray();
                $allocationPointPermissions = array_filter($permissions, function ($permission) {
                    return \Illuminate\Support\Str::startsWith($permission, 'view_allocationpoint_');
                });

                // Extract allocation point names from permissions
                $allocationPointNames = array_map(function ($permission) {
                    return \Illuminate\Support\Str::after($permission, 'view_allocationpoint_');
                }, $allocationPointPermissions);

                if (!empty($allocationPointNames)) {
                    try {
                        // Get allocation points directly with raw query for reliability
                        $allocationPoints = collect(\DB::table('allocation_points')->get())
                            ->map(function($item) {
                                return (object)[
                                    'id' => $item->id,
                                    'name' => $item->name,
                                    'location' => $item->location,
                                    'status' => $item->status
                                ];
                            });

                        // Find matching allocation points by name (case insensitive)
                        $matchingPoints = $allocationPoints->filter(function($point) use ($allocationPointNames) {
                            $pointName = strtolower($point->name);
                            foreach ($allocationPointNames as $searchName) {
                                if (str_contains($pointName, strtolower($searchName))) {
                                    return true;
                                }
                            }
                            return false;
                        });

                        $allocationPointIds = $matchingPoints->pluck('id')->toArray();
                        $allocationPointIds = array_unique($allocationPointIds);

                        if (!empty($allocationPointIds)) {
                            $query->whereIn('allocation_point_id', $allocationPointIds);
                        } else {
                            // Show nothing if no matching allocation points
                            $query->whereRaw('1 = 0');
                        }
                    } catch (\Exception $e) {
                        Log::error('ConfirmedAffixReport: Error filtering by allocation points', [
                            'error' => $e->getMessage(),
                            'user_id' => $user->id,
                            'trace' => $e->getTraceAsString()
                        ]);
                        $query->whereRaw('1 = 0');
                    }
                } else {
                    // Show nothing if no permissions
                    $query->whereRaw('1 = 0');
                }
            } else {
                // Default: show nothing for other roles
                $query->whereRaw('1 = 0');
            }
        }

        // Apply other filters
        $query->when($startDateTime, fn (Builder $query) => $query->where('created_at', '>=', $startDateTime))
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

        // Debug: Log the final query and results
        $finalQuery = $query->toSql();
        $bindings = $query->getBindings();
        $results = $query->get();

        Log::info('ConfirmedAffixReport: Final query debug', [
            'user_id' => $user?->id,
            'sql' => $finalQuery,
            'bindings' => $bindings,
            'result_count' => $results->count(),
            'filters' => $this->filters,
            'start_date_time' => $startDateTime,
            'end_date_time' => $endDateTime
        ]);

        if ($results->count() > 0) {
            Log::info('ConfirmedAffixReport: Sample results', [
                'user_id' => $user?->id,
                'first_3_results' => $results->take(3)->map(function($r) {
                    return [
                        'id' => $r->id,
                        'device_id' => $r->device_id,
                        'boe' => $r->boe,
                        'allocation_point_id' => $r->allocation_point_id,
                        'created_at' => $r->created_at
                    ];
                })->toArray()
            ]);
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
        return $this->getAllocationPointsForFilter();
    }

    /**
     * Get allocation points that the user has permission to view
     */
    public function getAllocationPointsForFilter(): array
    {
        $user = auth()->user();

        // Super Admin and Warehouse Manager can see all allocation points
        if ($user && $user->hasRole(['Super Admin', 'Warehouse Manager'])) {
            return AllocationPoint::pluck('name', 'id')->toArray();
        }

        // For other roles, filter by permissions
        if ($user && $user->hasRole(['Retrieval Officer', 'Affixing Officer'])) {
            // Get all permissions starting with 'view_allocationpoint_'
            $permissions = $user->permissions->pluck('name')->toArray();
            $allocationPointPermissions = array_filter($permissions, function ($permission) {
                return \Illuminate\Support\Str::startsWith($permission, 'view_allocationpoint_');
            });

            // Extract allocation point names from permissions
            $allocationPointNames = array_map(function ($permission) {
                return \Illuminate\Support\Str::after($permission, 'view_allocationpoint_');
            }, $allocationPointPermissions);

            if (!empty($allocationPointNames)) {
                try {
                    // Get allocation points directly with raw query for reliability
                    $allocationPoints = collect(\DB::table('allocation_points')->get())
                        ->map(function($item) {
                            return (object)[
                                'id' => $item->id,
                                'name' => $item->name,
                                'location' => $item->location,
                                'status' => $item->status
                            ];
                        });

                    // Find matching allocation points by name (case insensitive)
                    $matchingPoints = $allocationPoints->filter(function($point) use ($allocationPointNames) {
                        $pointName = strtolower($point->name);
                        foreach ($allocationPointNames as $searchName) {
                            if (str_contains($pointName, strtolower($searchName))) {
                                return true;
                            }
                        }
                        return false;
                    });

                    return $matchingPoints->pluck('name', 'id')->toArray();
                } catch (\Exception $e) {
                    Log::error('ConfirmedAffixReport: Error getting allocation points for filter', [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id
                    ]);
                    return [];
                }
            }
        }

        // Default: return empty array for users without permissions
        return [];
    }


}
