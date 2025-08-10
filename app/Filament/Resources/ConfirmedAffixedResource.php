<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfirmedAffixedResource\Pages;
use App\Models\ConfirmedAffixed;
use App\Models\DeviceRetrieval;
use App\Models\LongRoute;
use App\Models\Route;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Http\Livewire\ConfirmedAffixReportModal;

class ConfirmedAffixedResource extends Resource
{
    protected static ?string $model = ConfirmedAffixed::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationLabel = 'Confirmed Affixed';
    protected static ?string $navigationGroup = 'Device Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('device_id')
                    ->relationship('device', 'device_id')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('route_id')
                    ->label('Route')
                    ->options(Route::pluck('name', 'id'))
                    ->searchable(),
                   // ->required(),
                Forms\Components\Select::make('long_route_id')
                    ->label('Long Route')
                    ->options(LongRoute::pluck('name', 'id'))
                    ->searchable(),
                Forms\Components\DateTimePicker::make('affixing_date'),
                Forms\Components\TextInput::make('boe')
                    ->label('SAD/T1')
                    ->required(),
                Forms\Components\TextInput::make('vehicle_number')
                    ->required(),
                Forms\Components\TextInput::make('regime')
                    ->required(),
                Forms\Components\DatePicker::make('manifest_date'),
                Forms\Components\Select::make('destination')
                    ->label('Destination')
                    ->options(function () {
                        return \App\Models\Destination::pluck('name', 'name')->toArray();
                    })
                    ->required()
                    ->searchable(),
                //Forms\Components\Select::make('destination_id')
                   // ->relationship('destination', 'name')
                  //  ->label('Destination ID')
                   // ->searchable(),
                Forms\Components\TextInput::make('agency'),
                Forms\Components\TextInput::make('agent_contact'),
                Forms\Components\TextInput::make('truck_number'),
                Forms\Components\TextInput::make('driver_name'),
                Forms\Components\Select::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'AFFIXED' => 'Affixed',
                        'COMPLETED' => 'Completed',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('device.device_id')
                    ->label('Device ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('boe')
                    ->label('SAD/T1')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination')
                    ->label('Destination')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('regime')
                    ->searchable(),
                Tables\Columns\TextColumn::make('route.name')
                    ->label('Route')
                    ->searchable(),
                Tables\Columns\TextColumn::make('longRoute.name')
                    ->label('Long Route')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manifest_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agent_contact'),
                Tables\Columns\TextColumn::make('truck_number'),
                Tables\Columns\TextColumn::make('driver_name')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'AFFIXED' => 'Affixed',
                        'COMPLETED' => 'Completed',
                    ])
            ])
            ->actions([
                Tables\Actions\Action::make('pickForAffixing')
                    ->label('Pick for Affixing')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\DateTimePicker::make('affixing_date')
                            ->label('Affixing Date')
                            ->required()
                            ->default(now())
                            ->readOnly()
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Pick Device for Affixing')
                    ->modalDescription('Are you sure you want to pick this device for affixing?')
                    ->modalSubmitActionLabel('Yes, Pick for Affixing')
                    ->action(function (ConfirmedAffixed $record, array $data): void {
                        try {
                            DB::beginTransaction();

                            // Step 1: Create common data for both tables
                            $commonData = [
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
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            // Step 2: Create device retrieval record
                            $deviceRetrieval = array_merge($commonData, [
                                'allocation_point_id' => $record->allocation_point_id,
                                'retrieval_status' => 'NOT_RETRIEVED',
                                'transfer_status' => 'pending',
                            ]);

                            // Insert device retrieval record
                            $deviceRetrievalId = DB::table('device_retrievals')->insertGetId($deviceRetrieval);
                            
                            if (!$deviceRetrievalId) {
                                throw new \Exception('Failed to create device retrieval record');
                            }
                            
                            Log::info('Device retrieval record created', [
                                'device_retrieval_id' => $deviceRetrievalId,
                                'device_id' => $record->device_id,
                                'boe' => $record->boe
                            ]);
                            
                            // Step 3: Create monitoring record with current_date
                            $monitoringData = array_merge($commonData, [
                                'current_date' => now(),
                                'status' => 'ACTIVE',
                                'note' => 'Device affixed on ' . now()->format('Y-m-d H:i:s'),
                            ]);
                            
                            // Insert monitoring record
                            $monitoringId = DB::table('monitorings')->insertGetId($monitoringData);
                            
                            if (!$monitoringId) {
                                throw new \Exception('Failed to create monitoring record');
                            }
                            
                            Log::info('Monitoring record created', [
                                'monitoring_id' => $monitoringId,
                                'device_id' => $record->device_id,
                                'device_retrieval_id' => $deviceRetrievalId
                            ]);

                            // Step 3.5: Create ConfirmedAffixLog record (same style as monitoring)
                            $logData = [
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
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $logId = DB::table('confirmed_affix_logs')->insertGetId($logData);
                            if (!$logId) {
                                throw new \Exception('Failed to create ConfirmedAffixLog record');
                            }
                            Log::info('ConfirmedAffixLog record created', [
                                'log_id' => $logId,
                                'device_id' => $record->device_id
                            ]);

                            // Step 4: Delete from assign_to_agents table
                            $deletedAssignments = DB::table('assign_to_agents')
                                ->where('device_id', $record->device_id)
                                ->delete();
                                
                            Log::info('Assign to agents records deleted', [
                                'device_id' => $record->device_id,
                                'deleted_count' => $deletedAssignments
                            ]);

                            // Step 5: Update and then delete confirmed_affixeds record
                            $updated = DB::table('confirmed_affixeds')
                                ->where('id', $record->id)
                                ->update([
                                    'status' => 'AFFIXED',
                                    'affixing_date' => $data['affixing_date'],
                                    'updated_at' => now()
                                ]);
                                
                            if ($updated === 0) {
                                Log::warning('No records updated in confirmed_affixeds', ['id' => $record->id]);
                            }
                            
                            // Step 6: Delete confirmed_affixeds record
                            $deleted = DB::table('confirmed_affixeds')
                                ->where('id', $record->id)
                                ->delete();
                                
                            if ($deleted === 0) {
                                Log::warning('No records deleted from confirmed_affixeds', ['id' => $record->id]);
                            }

                            DB::commit();

                            Notification::make()
                                ->success()
                                ->title('Device picked for affixing')
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            $errorContext = [
                                'error' => [
                                    'message' => $e->getMessage(),
                                    'file' => $e->getFile(),
                                    'line' => $e->getLine(),
                                    'trace' => $e->getTraceAsString()
                                ],
                                'record' => [
                                    'id' => $record->id,
                                    'device_id' => $record->device_id,
                                    'boe' => $record->boe
                                ]
                            ];
                            
                            Log::error('Error in pickForAffixing', $errorContext);

                            // Send detailed error in development, generic in production
                            $errorMessage = app()->environment('production') 
                                ? 'An error occurred while processing your request. Please try again.'
                                : $e->getMessage();

                            Notification::make()
                                ->danger()
                                ->title('Error picking device for affixing')
                                ->body($errorMessage)
                                ->send();
                                
                            // Re-throw the exception for further handling if needed
                            throw $e;
                        }
                    })
                    ->visible(fn (ConfirmedAffixed $record): bool => $record->status !== 'AFFIXED'),
                Tables\Actions\Action::make('returnData')
                    ->label('Return Data')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('return_note')
                            ->label('Reason for Return')
                            ->required()
                            ->maxLength(1000)
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Return Data to Data Entry')
                    ->modalDescription('Are you sure you want to return this data? This will move the record back to data entry.')
                    ->modalSubmitActionLabel('Yes, Return Data')
                    ->action(function (ConfirmedAffixed $record, array $data): void {
                        try {
                            DB::beginTransaction();

                            // Get the device
                            $device = $record->device;

                            if (!$device) {
                                throw new \Exception('Device not found');
                            }

                            // Get the original allocation point ID
                            $allocationPointId = $record->allocation_point_id;

                            if (!$allocationPointId) {
                                throw new \Exception('Original allocation point not found');
                            }

                            // Check if DataEntryAssignment already exists for this allocation point
                            $existingAssignment = \App\Models\DataEntryAssignment::where('allocation_point_id', $allocationPointId)
                                ->first();

                            if ($existingAssignment) {
                                // Update existing assignment instead of creating new one
                                $existingAssignment->update([
                                    'status' => 'RETURNED',
                                    'notes' => $data['return_note'] . "\n(Previous notes: " . $existingAssignment->notes . ")",
                                    'description' => $existingAssignment->description . "\nReturned from Affixing - BOE: {$record->boe}, Vehicle: {$record->vehicle_number}",
                                    'user_id' => auth()->id()
                                ]);
                            } else {
                                // Create new assignment only if one doesn't exist
                                \App\Models\DataEntryAssignment::create([
                                    'allocation_point_id' => $allocationPointId,
                                    'status' => 'RETURNED',
                                    'notes' => $data['return_note'],
                                    'title' => 'Returned from Affixing',
                                    'description' => "Returned from Affixing - BOE: {$record->boe}, Vehicle: {$record->vehicle_number}",
                                    'user_id' => auth()->id()
                                ]);
                            }

                            // Restore device to original allocation point
                            $device->update([
                                'allocation_point_id' => $allocationPointId,
                                'status' => 'ONLINE'
                            ]);

                            // Delete assign_to_agents record if exists
                            DB::table('assign_to_agents')
                                ->where('device_id', $record->device_id)
                                ->delete();

                            // Delete the confirmed affixed record
                            $record->delete();

                            DB::commit();

                            Notification::make()
                                ->success()
                                ->title('Data returned to data entry successfully')
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->danger()
                                ->title('Error returning data')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(fn (ConfirmedAffixed $record): bool => $record->status === 'PENDING'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConfirmedAffixeds::route('/'),
            'report' => Pages\ConfirmedAffixReport::route('/report'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole([
            'Super Admin',
            'Warehouse Manager',
            'Affixing Officer',
            'Retrieval Officer'
        ]);
    }

    public static function canCreate(): bool
    {
        // Disable creation of records through the UI
        // Records should only be created through data entry
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        // Disable editing of records through the UI
        // Records should be managed through the data entry process
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole([
            'Super Admin',
            'Warehouse Manager',
            'Affixing Officer',
            'Retrieval Officer'
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        Log::info('ConfirmedAffixedResource: getEloquentQuery called', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_roles' => $user?->roles->pluck('name')->toArray() ?? []
        ]);

        // Only Super Admin and Warehouse Manager can see all records
        if ($user?->hasRole(['Super Admin', 'Warehouse Manager'])) {
            Log::info('ConfirmedAffixedResource: User has admin access, no filtering applied', [
                'user_id' => $user->id,
                'roles' => $user->roles->pluck('name')->toArray()
            ]);
            return $query;
        }

        // For Retrieval Officer and Affixing Officer, filter by allocation point permissions
        if ($user?->hasRole(['Retrieval Officer', 'Affixing Officer'])) {
            Log::info('ConfirmedAffixedResource: Processing Retrieval Officer/Affixing Officer access', [
                'user_id' => $user->id,
                'user_roles' => $user->roles->pluck('name')->toArray()
            ]);

            try {
                // Get all permissions that start with 'view_allocationpoint_'
                $allocationPointPermissions = $user->permissions
                    ->filter(fn ($permission) => str_starts_with($permission->name, 'view_allocationpoint_'))
                    ->map(fn ($permission) => str_replace('view_allocationpoint_', '', $permission->name))
                    ->unique()
                    ->values()
                    ->toArray();

                Log::info('ConfirmedAffixedResource: Allocation point permissions found', [
                    'user_id' => $user->id,
                    'allocation_point_permissions' => $allocationPointPermissions
                ]);

                if (empty($allocationPointPermissions)) {
                    Log::warning('ConfirmedAffixedResource: User has no allocation point permissions', [
                        'user_id' => $user->id,
                        'all_permissions' => $user->permissions->pluck('name')->toArray()
                    ]);
                    return $query->where('id', 0);
                }

                // Get allocation points directly with raw query
                $allocationPoints = collect(\DB::table('allocation_points')
                    ->select('id', 'name', 'location', 'status')
                    ->get())
                    ->map(function($item) {
                        return (object)[
                            'id' => $item->id,
                            'name' => $item->name,
                            'location' => $item->location,
                            'status' => $item->status
                        ];
                    });

                Log::debug('Allocation points loaded:', [
                    'count' => $allocationPoints->count(),
                    'points' => $allocationPoints->pluck('name', 'id')
                ]);

                // Find matching allocation points by name (case insensitive)
                $matchingPoints = $allocationPoints->filter(function($point) use ($allocationPointPermissions) {
                    $pointName = strtolower($point->name);
                    foreach ($allocationPointPermissions as $searchName) {
                        if (str_contains($pointName, strtolower($searchName))) {
                            return true;
                        }
                    }
                    return false;
                });

                $matchingPointIds = $matchingPoints->pluck('id')->toArray();
                $matchingNames = $matchingPoints->pluck('name');

                Log::info('ConfirmedAffixedResource: Matching allocation points found', [
                    'user_id' => $user->id,
                    'matching_point_ids' => $matchingPointIds,
                    'matching_names' => $matchingNames,
                    'search_terms' => $allocationPointPermissions
                ]);

                if (empty($matchingPointIds)) {
                    Log::warning('ConfirmedAffixedResource: No matching allocation points found', [
                        'user_id' => $user->id,
                        'search_terms' => $allocationPointPermissions
                    ]);
                    return $query->where('id', 0);
                }

                // Filter by matching allocation point IDs
                return $query->whereIn('allocation_point_id', $matchingPointIds);

            } catch (\Exception $e) {
                Log::error('Error in ConfirmedAffixedResource getEloquentQuery:', [
                    'user_id' => $user?->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return $query->where('id', 0);
            }
        }

        Log::info('ConfirmedAffixedResource: User has no recognized role, showing no records', [
            'user_id' => $user?->id,
            'roles' => $user?->roles->pluck('name')->toArray() ?? []
        ]);

        // Default: show nothing for other roles
        return $query->where('id', 0);
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $user = auth()->user();

        Log::info('ConfirmedAffixedResource: getTableQuery called', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_roles' => $user?->roles->pluck('name')->toArray() ?? []
        ]);

        // For Retrieval Officer and Affixing Officer, filter by destination permissions
        if ($user?->hasRole(['Retrieval Officer', 'Affixing Officer'])) {
            Log::info('ConfirmedAffixedResource: getTableQuery processing Retrieval Officer/Affixing Officer access', [
                'user_id' => $user->id,
                'user_roles' => $user->roles->pluck('name')->toArray()
            ]);

            // Get all permissions that start with 'view_destination_'
            $destinationPermissions = $user->permissions
                ->filter(fn ($permission) => str_starts_with($permission->name, 'view_destination_'))
                ->map(fn ($permission) => Str::after($permission->name, 'view_destination_'))
                ->toArray();

            Log::info('ConfirmedAffixedResource: getTableQuery destination permissions extracted', [
                'user_id' => $user->id,
                'destination_permissions' => $destinationPermissions,
                'all_permissions' => $user->permissions->pluck('name')->toArray()
            ]);

            // If user has destination permissions, filter by those
            if (!empty($destinationPermissions)) {
                // Convert permission slugs to possible destination names
                $possibleDestinations = [];

                foreach ($destinationPermissions as $slug) {
                    // Add variations of the destination name to check against the database
                    $possibleDestinations[] = $slug;
                    $possibleDestinations[] = ucfirst($slug);
                    $possibleDestinations[] = strtoupper($slug);
                    $possibleDestinations[] = Str::title($slug);
                    $possibleDestinations[] = Str::title(str_replace('-', ' ', $slug));
                }

                // Remove duplicates
                $possibleDestinations = array_unique($possibleDestinations);

                Log::info('ConfirmedAffixedResource: getTableQuery possible destination variations generated', [
                    'user_id' => $user->id,
                    'original_slugs' => $destinationPermissions,
                    'possible_destinations' => $possibleDestinations
                ]);

                // Filter query to only include confirmed affixed records with matching destinations
                $query->where(function ($query) use ($possibleDestinations, $user) {
                    // Check against the destination column (string)
                    $query->whereIn('destination', $possibleDestinations)
                        // Also check against the destination relationship if it exists
                        ->orWhereHas('destination', function ($subQuery) use ($possibleDestinations) {
                            $subQuery->whereIn('name', $possibleDestinations);
                        });

                    Log::info('ConfirmedAffixedResource: getTableQuery applied destination filtering', [
                        'user_id' => $user->id,
                        'filter_destinations' => $possibleDestinations
                    ]);
                });
            } else {
                Log::warning('ConfirmedAffixedResource: getTableQuery User has no destination permissions, showing no records', [
                    'user_id' => $user->id,
                    'user_roles' => $user->roles->pluck('name')->toArray(),
                    'all_permissions' => $user->permissions->pluck('name')->toArray()
                ]);

                // If no destination permissions, show nothing
                $query->where('id', 0);
            }
        }

        return $query;
    }
}

