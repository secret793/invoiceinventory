<?php

namespace App\Filament\Resources\ConfirmedAffixedResource\Pages;

use App\Filament\Resources\ConfirmedAffixedResource;
use App\Models\Destination;
use App\Models\DeviceRetrieval;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class ListConfirmedAffixeds extends ListRecords
{
    protected static string $resource = ConfirmedAffixedResource::class;

    // Filter properties for the modal
    public $filters = [
        'search' => null,
        'device_id' => null,
        'boe' => null,
        'vehicle_number' => null,
        'start_date' => null,
        'end_date' => null,
        'start_time' => null,
        'end_time' => null,
        'destination' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ];

    public function getConfirmedAffixLogsProperty()
    {
        $query = \App\Models\ConfirmedAffixLog::query()
            ->with(['device', 'route', 'longRoute', 'affixedBy']);

        // Apply allocation point permission filtering (same logic as ConfirmedAffixed model)
        $user = auth()->user();
        if (!$user->hasRole(['Super Admin', 'Warehouse Manager'])) {
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
                        \Log::error('ListConfirmedAffixeds: Error filtering by allocation points', [
                            'error' => $e->getMessage(),
                            'user_id' => $user->id
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

        // Apply general search filter (searches device_id, boe, vehicle_number)
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereHas('device', function($deviceQuery) use ($search) {
                    $deviceQuery->where('device_id', 'LIKE', "%{$search}%");
                })
                ->orWhere('boe', 'LIKE', "%{$search}%")
                ->orWhere('vehicle_number', 'LIKE', "%{$search}%");
            });
        }

        // Apply device ID filter
        if (!empty($this->filters['device_id'])) {
            $deviceId = $this->filters['device_id'];
            $query->whereHas('device', function($q) use ($deviceId) {
                $q->where('device_id', 'LIKE', "%{$deviceId}%");
            });
        }

        // Apply BOE filter
        if (!empty($this->filters['boe'])) {
            $query->where('boe', 'LIKE', "%{$this->filters['boe']}%");
        }

        // Apply vehicle number filter
        if (!empty($this->filters['vehicle_number'])) {
            $query->where('vehicle_number', 'LIKE', "%{$this->filters['vehicle_number']}%");
        }

        // Apply destination filter
        if (!empty($this->filters['destination'])) {
            $query->where('destination', 'LIKE', "%{$this->filters['destination']}%");
        }

        // Apply date range filter
        if (!empty($this->filters['start_date'])) {
            $startDate = $this->filters['start_date'];
            if (!empty($this->filters['start_time'])) {
                $startDate = $startDate . ' ' . $this->filters['start_time'];
            }
            $query->where('created_at', '>=', $startDate);
        } elseif (!empty($this->filters['start_time'])) {
            $query->whereTime('created_at', '>=', $this->filters['start_time']);
        }

        if (!empty($this->filters['end_date'])) {
            $endDate = $this->filters['end_date'];
            if (!empty($this->filters['end_time'])) {
                $endDate = $endDate . ' ' . $this->filters['end_time'];
            } else {
                $endDate = $endDate . ' 23:59:59';
            }
            $query->where('created_at', '<=', $endDate);
        } elseif (!empty($this->filters['end_time'])) {
            $query->whereTime('created_at', '<=', $this->filters['end_time']);
        }

        // Apply sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate(10);
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
            \Filament\Actions\Action::make('viewReport')
                ->label('View Report')
                ->color('success')
                ->icon('heroicon-o-document-chart-bar')
                ->modalContent(fn () => view('filament.resources.confirmed-affixed-resource.pages.confirmed-affix-report'))
                ->modalWidth('7xl')
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
                        'destination' => $this->filters['destination'] ?? null,
                        'sort_by' => $this->filters['sort_by'] ?? null,
                        'sort_direction' => $this->filters['sort_direction'] ?? null,
                    ];

                    // Remove null values from the params array
                    $filteredParams = array_filter($params, function($value) {
                        return $value !== null;
                    });

                    return $action->url(route('export.confirmed-affix-report', $filteredParams));
                }),
        ];
    }

    protected function getActions(): array
    {
        return [
            // Create button removed as records should only be created through data entry
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('destination')
                ->options(fn () => Destination::pluck('name', 'name')->toArray())
                ->label('Destination'),
            SelectFilter::make('destination_id')
                ->relationship('destination', 'name')
                ->label('Destination (By ID)'),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('bulkPickForAffixing')
                ->label('Pick Selected for Affixing')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\DateTimePicker::make('affixing_date')
                        ->label('Affixing Date')
                        ->required()
                        ->default(now())
                        ->readOnly()
                ])
                ->action(function (Collection $records, array $data): void {
                    try {
                        DB::beginTransaction();

                        foreach ($records as $record) {
                            // Skip if already affixed
                            if ($record->status === 'AFFIXED') {
                                continue;
                            }

                            // Create device retrieval record
                            DeviceRetrieval::create([
                                'date' => now(),
                                'device_id' => $record->device_id,
                                'boe' => $record->boe,
                                'vehicle_number' => $record->vehicle_number,
                                'regime' => $record->regime,
                                'destination' => $record->destination,
                                'route_id' => $record->route_id,
                                'long_route_id' => $record->long_route_id,
                                'manifest_date' => $record->manifest_date,
                                'agency' => $record->agency,
                                'agent_contact' => $record->agent_contact,
                                'truck_number' => $record->truck_number,
                                'driver_name' => $record->driver_name,
                                'affixing_date' => $data['affixing_date'],
                                'retrieval_status' => 'NOT_RETRIEVED',
                                'transfer_status' => 'pending'
                            ]);

                            // Delete from assign_to_agents
                            DB::table('assign_to_agents')
                                ->where('device_id', $record->device_id)
                                ->delete();

                            // Delete the confirmed affixed record
                            $record->delete();

                            // In bulkPickForAffixing bulk action, after DeviceRetrieval::create([...]) and before deleting the record:
                            \App\Models\ConfirmedAffixLog::create([
                                'device_id' => $record->device_id,
                                'boe' => $record->boe,
                                'sad_number' => $record->sad_number ?? null,
                                'vehicle_number' => $record->vehicle_number,
                                'regime' => $record->regime,
                                'destination' => $record->destination,
                                'destination_id' => $record->destination_id ?? null,
                                'route_id' => $record->route_id,
                                'long_route_id' => $record->long_route_id,
                                'manifest_date' => $record->manifest_date,
                                'agency' => $record->agency,
                                'agent_contact' => $record->agent_contact,
                                'truck_number' => $record->truck_number,
                                'driver_name' => $record->driver_name,
                                'affixing_date' => $data['affixing_date'],
                                'status' => 'AFFIXED',
                                'allocation_point_id' => $record->allocation_point_id ?? null,
                                'affixed_by' => auth()->id(),
                            ]);
                        }

                        DB::commit();

                        Notification::make()
                            ->success()
                            ->title('Selected devices picked for affixing successfully')
                            ->send();

                    } catch (\Exception $e) {
                        DB::rollBack();

                        Notification::make()
                            ->danger()
                            ->title('Error processing bulk affixing')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
        ];
    }

    // Add a method for single pick for affixing (if not present)
    public function pickForAffixing($record, $affixingDate)
    {
        try {
            $log = \App\Models\ConfirmedAffixLog::create([
                'device_id' => $record->device_id,
                'boe' => $record->boe,
                'sad_number' => $record->sad_number ?? null,
                'vehicle_number' => $record->vehicle_number,
                'regime' => $record->regime,
                'destination' => $record->destination,
                'destination_id' => $record->destination_id ?? null,
                'route_id' => $record->route_id,
                'long_route_id' => $record->long_route_id,
                'manifest_date' => $record->manifest_date,
                'agency' => $record->agency,
                'agent_contact' => $record->agent_contact,
                'truck_number' => $record->truck_number,
                'driver_name' => $record->driver_name,
                'affixing_date' => $affixingDate,
                'status' => 'AFFIXED',
                'allocation_point_id' => $record->allocation_point_id ?? null,
                'affixed_by' => auth()->id(),
            ]);
            if (!$log) {
                \Log::error('Single pickForAffixing: Failed to create ConfirmedAffixLog for device_id: ' . $record->device_id);
            } else {
                \Log::info('Single pickForAffixing: Created ConfirmedAffixLog', [
                    'device_id' => $record->device_id,
                    'log_id' => $log->id
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Single pickForAffixing: Exception creating ConfirmedAffixLog: ' . $e->getMessage(), [
                'device_id' => $record->device_id
            ]);
        }
    }
}
