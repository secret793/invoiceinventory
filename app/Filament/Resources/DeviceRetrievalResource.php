<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceRetrievalResource\Pages;
use App\Models\DeviceRetrieval;
use App\Models\Device;
use App\Models\DistributionPoint;
use App\Models\AllocationPoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use App\Filament\Actions\OverdueBillsAction;
use App\Filament\Actions\GenerateInvoiceAction;

class DeviceRetrievalResource extends Resource
{
    protected static ?string $model = DeviceRetrieval::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Device Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Existing form fields...

                Forms\Components\Section::make('Overstay Information')
                    ->schema([
                        Forms\Components\TextInput::make('overstay_days')
                            ->label('Overstay Days')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('overstay_amount')
                            ->label('Overstay Amount (D)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->prefix('D'),

                        Forms\Components\Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'PP' => 'Pending Payment',
                                'PD' => 'Paid',
                            ])
                            ->default('PP')
                            ->required(),
                    ])
                    ->columns(2),

                // Finance approval section (if needed)
                Forms\Components\Section::make('Finance Approval')
                    ->schema([
                        Forms\Components\DateTimePicker::make('finance_approval_date')
                            ->label('Approval Date'),

                        Forms\Components\TextInput::make('finance_approved_by')
                            ->label('Approved By')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->numeric()
                            ->prefix('D')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn () => auth()->user()->hasRole('Finance Officer') ||
                                       auth()->user()->hasRole('Super Admin')),
            ]);
    }

    public static function table(Table $table): Table
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
                Tables\Columns\TextColumn::make('vehicle_number')
                    ->label('Vehicle Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('regime')
                    ->label('Regime')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination')
                    ->label('Destination')
                    ->searchable(),
                Tables\Columns\TextColumn::make('overstay_days')
                    ->label('Overstay Days')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('transfer_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('retrieval_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'NOT_RETRIEVED' => 'warning',
                        'RETRIEVED' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('destination.name')
                    ->label('Destination')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('allocationPoint.name')
                    ->label('Allocation Point')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('overstay_amount')
                    ->label('Overstay Amount')
                    ->money('GMD')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Payment Status')
                    ->enum([
                        'PP' => 'Pending Payment',
                        'PD' => 'Paid',
                    ])
                    ->colors([
                        'danger' => 'PP',
                        'success' => 'PD',
                    ]),
                Tables\Columns\TextColumn::make('finance_approval_date')
                    ->label('Finance Approval Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'COMPLETED' => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('transfer_status')
                    ->options([
                        'pending' => 'Transfer Pending',
                        'completed' => 'Transfer Completed',
                    ]),
                Tables\Filters\SelectFilter::make('retrieval_status')
                    ->options([
                        'NOT_RETRIEVED' => 'Not Retrieved',
                        'RETRIEVED' => 'Retrieved',
                    ])
                    ->label('Retrieval Status'),

                Tables\Filters\Filter::make('overstay_days')
                    ->form([
                        Forms\Components\TextInput::make('overstay_days_min')
                            ->label('Minimum Overstay Days')
                            ->numeric(),
                        Forms\Components\TextInput::make('overstay_days_max')
                            ->label('Maximum Overstay Days')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['overstay_days_min'],
                                fn (Builder $query, $min): Builder => $query->where('overstay_days', '>=', $min)
                            )
                            ->when(
                                $data['overstay_days_max'],
                                fn (Builder $query, $max): Builder => $query->where('overstay_days', '<=', $max)
                            );
                    }),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Devices')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('overdue_days', '>', 0)
                    ),

                Tables\Filters\Filter::make('route_type')
                    ->form([
                        Forms\Components\Select::make('route_type')
                            ->options([
                                'normal' => 'Normal Route',
                                'long' => 'Long Route',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['route_type'],
                            fn (Builder $query, $routeType): Builder =>
                                $routeType === 'long'
                                    ? $query->whereNotNull('long_route_id')
                                    : $query->whereNull('long_route_id')
                        );
                    }),
                Tables\Filters\SelectFilter::make('destination')
                    ->relationship('destination', 'name')
                    ->label('Filter by Destination')
                    ->multiple(),
                Tables\Filters\SelectFilter::make('allocation_point')
                    ->relationship('allocationPoint', 'name')
                    ->label('Filter by Allocation Point')
                    ->multiple(),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'PP' => 'Pending Payment',
                        'PD' => 'Paid',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make(fn ($record) => static::getTableActions($record)),
            ])
            ->bulkActions([
                // your bulk actions here if any
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
            'index' => Pages\ListDeviceRetrievals::route('/'),
            'create' => Pages\CreateDeviceRetrieval::route('/create'),
            'edit' => Pages\EditDeviceRetrieval::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Super Admin, Warehouse Manager, Data Entry Officer, and Affixing Officer can see all device retrievals
        if ($user?->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer', 'Affixing Officer'])) {
            return $query;
        }

        // For Finance Officer, only show devices with overstay_days >= 2
        if ($user?->hasRole('Finance Officer')) {
            return $query->where('overstay_days', '>=', 2);
        }

        // For Retrieval Officer, filter by destination permissions
        if ($user?->hasRole('Retrieval Officer')) {
            // Get all permissions that start with 'view_destination_'
            $destinationPermissions = $user->permissions
                ->filter(fn ($permission) => str_starts_with($permission->name, 'view_destination_'))
                ->map(fn ($permission) => Str::after($permission->name, 'view_destination_'))
                ->toArray();

            // If user has destination permissions, filter by those
            if (!empty($destinationPermissions)) {
                // Convert permission slugs to possible destination names
                $possibleDestinations = [];

                foreach ($destinationPermissions as $slug) {
                    // Add variations of the destination name to check against the database
                    // 1. Original slug
                    $possibleDestinations[] = $slug;

                    // 2. Capitalized first letter (e.g., "kamkam" -> "Kamkam")
                    $possibleDestinations[] = ucfirst($slug);

                    // 3. All uppercase (e.g., "kamkam" -> "KAMKAM")
                    $possibleDestinations[] = strtoupper($slug);

                    // 4. Title case (e.g., "kamkam" -> "Kamkam")
                    $possibleDestinations[] = Str::title($slug);

                    // 5. Replace dashes with spaces and title case (e.g., "kam-kam" -> "Kam Kam")
                    $possibleDestinations[] = Str::title(str_replace('-', ' ', $slug));
                }

                // Remove duplicates
                $possibleDestinations = array_unique($possibleDestinations);

                // Filter query to only include device retrievals with matching destinations
                return $query->where(function ($query) use ($possibleDestinations) {
                    // Check against the destination column (string)
                    $query->whereIn('destination', $possibleDestinations)
                        // Also check against the destination relationship if it exists
                        ->orWhereHas('destination', function ($subQuery) use ($possibleDestinations) {
                            $subQuery->whereIn('name', $possibleDestinations);
                        });
                });
            }

            // If no destination permissions, show nothing
            return $query->where('id', 0);
        }

        // Default: show nothing for other roles
        return $query->where('id', 0);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole([
            'Super Admin',
            'Warehouse Manager',
            'Retrieval Officer',
            'Affixing Officer',
            'Finance Officer'
        ]);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        if ($user?->hasRole(['Super Admin', 'Warehouse Manager', 'Retrieval Officer', 'Affixing Officer'])) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole([
            'Super Admin',
            'Warehouse Manager',
            'Retrieval Officer',
            'Affixing Officer'
        ]);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole([
            'Super Admin',
            'Warehouse Manager',
            'Retrieval Officer',
            'Affixing Officer'
        ]);
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $user = auth()->user();

        // For Finance Officer, only show devices with overstay_days >= 2
        if ($user?->hasRole('Finance Officer')) {
            $query->where('overstay_days', '>=', 2);
        }

        // For Retrieval Officer, filter by destination permissions
        if ($user?->hasRole('Retrieval Officer')) {
            // Get all permissions that start with 'view_destination_'
            $destinationPermissions = $user->permissions
                ->filter(fn ($permission) => str_starts_with($permission->name, 'view_destination_'))
                ->map(fn ($permission) => Str::after($permission->name, 'view_destination_'))
                ->toArray();

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

                // Filter query to only include device retrievals with matching destinations
                $query->where(function ($query) use ($possibleDestinations) {
                    // Check against the destination column (string)
                    $query->whereIn('destination', $possibleDestinations)
                        // Also check against the destination relationship if it exists
                        ->orWhereHas('destination', function ($subQuery) use ($possibleDestinations) {
                            $subQuery->whereIn('name', $possibleDestinations);
                        });
                });
            } else {
                // If no destination permissions, show nothing
                $query->where('id', 0);
            }
        }

        return $query;
    }

    /**
     * Get dynamic table actions based on record state and user role
     */
    public static function getTableActions($record): array
    {
        $actions = [];

        // Retrieve Device action - for NOT_RETRIEVED devices
        if ($record->retrieval_status === 'NOT_RETRIEVED' &&
            auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Retrieval Officer', 'Affixing Officer']) &&
            // Use the canBeRetrieved method to check if device can be retrieved
            $record->canBeRetrieved()) {
            $actions[] = Tables\Actions\Action::make('retrieveDevice')
                ->label('Retrieve Device')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Retrieve Device')
                ->modalDescription('Are you sure you want to mark this device as retrieved?')
                ->action(function (DeviceRetrieval $record) {
                    $record->update([
                        'retrieval_status' => 'RETRIEVED'
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Device Retrieved Successfully')
                        ->success()
                        ->send();
                });
        }

        // Return to Outstation action - for RETRIEVED devices not yet transferred
        if ($record->retrieval_status === 'RETRIEVED' &&
            $record->transfer_status !== 'completed' &&
            auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Retrieval Officer'])) {
            $actions[] = Tables\Actions\Action::make('returnToOutstation')
                ->label('Return to Outstation')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Return Device to Outstation')
                ->modalDescription('Are you sure you want to return this device to the outstation?')
                ->action(function (DeviceRetrieval $record) {
                    $record->update([
                        'retrieval_status' => 'RETURNED'
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Device Returned to Outstation Successfully')
                        ->success()
                        ->send();
                });
        }

        // Overdue Bills action - for devices with overstay_days >= 2 that aren't paid
        if ($record->overstay_days >= 2 &&
            $record->payment_status !== 'PD' &&
            auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Retrieval Officer'])) {
            $actions[] = OverdueBillsAction::make('overdueBills');
        }

        // Finance Approval action - for pending payment records
        if ($record->payment_status === 'PP' &&
            $record->overstay_amount > 0 &&
            auth()->user()?->hasAnyRole(['Finance Officer', 'Super Admin'])) {
            $actions[] = Tables\Actions\Action::make('finance_approval')
                ->label('Approve Payment')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url(route('filament.admin.resources.device-retrievals.approve-payment', $record))
                ->openUrlInNewTab();
        }

        // Download Invoice action - for paid records (visible to all users)
        if ($record->payment_status === 'PD' && !empty($record->finance_approval_date)) {
            $actions[] = Tables\Actions\Action::make('download_invoice')
                ->label('Download Invoice')
                ->icon('heroicon-o-document-download')
                ->color('primary')
                ->url(route('invoices.download.retrieval', $record->id))
                ->openUrlInNewTab();
        }

        // Always add view action
        $actions[] = Tables\Actions\ViewAction::make();

        // Add edit action for authorized users
        if (auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Retrieval Officer'])) {
            $actions[] = Tables\Actions\EditAction::make();
        }

        return $actions;
    }
}





