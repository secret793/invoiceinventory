<?php
//Not using this file
namespace App\Filament\Resources\ConfirmedAffixedResource\Pages;

use App\Filament\Resources\ConfirmedAffixedResource;
use App\Models\ConfirmedAffixed;
use App\Models\LongRoute;
use App\Models\Route;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class ViewConfirmedAffixed extends ViewRecord
{
    protected static string $resource = ConfirmedAffixedResource::class;

    protected function getViewData(): array
    {
        $data = parent::getViewData();
        $user = auth()->user();

        Log::info('ViewConfirmedAffixed: getViewData called', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_roles' => $user?->roles->pluck('name')->toArray() ?? [],
            'record_id' => $this->record->id,
            'record_destination' => $this->record->destination,
            'record_destination_id' => $this->record->destination_id
        ]);

        // Check if user is a Retrieval Officer or Affixing Officer
        if ($user?->hasRole(['Retrieval Officer', 'Affixing Officer'])) {
            Log::info('ViewConfirmedAffixed: Processing Retrieval Officer/Affixing Officer access', [
                'user_id' => $user->id,
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'record_id' => $this->record->id
            ]);

            // Get all permissions that start with 'view_destination_'
            $destinationPermissions = $user->permissions
                ->filter(fn ($permission) => str_starts_with($permission->name, 'view_destination_'))
                ->map(fn ($permission) => Str::after($permission->name, 'view_destination_'))
                ->toArray();

            Log::info('ViewConfirmedAffixed: Destination permissions extracted', [
                'user_id' => $user->id,
                'record_id' => $this->record->id,
                'destination_permissions' => $destinationPermissions,
                'all_permissions' => $user->permissions->pluck('name')->toArray()
            ]);

            // If user has destination permissions, check if they can view this record
            if (!empty($destinationPermissions)) {
                // Convert permission slugs to possible destination names
                $possibleDestinations = [];

                foreach ($destinationPermissions as $slug) {
                    $possibleDestinations[] = $slug;
                    $possibleDestinations[] = ucfirst($slug);
                    $possibleDestinations[] = strtoupper($slug);
                    $possibleDestinations[] = Str::title($slug);
                    $possibleDestinations[] = Str::title(str_replace('-', ' ', $slug));
                }

                // Remove duplicates
                $possibleDestinations = array_unique($possibleDestinations);

                Log::info('ViewConfirmedAffixed: Possible destination variations generated', [
                    'user_id' => $user->id,
                    'record_id' => $this->record->id,
                    'original_slugs' => $destinationPermissions,
                    'possible_destinations' => $possibleDestinations
                ]);

                // Check if the record's destination matches any of the possible destinations
                $recordDestination = $this->record->destination;
                $destinationName = $this->record->destination?->name ?? $recordDestination;

                Log::info('ViewConfirmedAffixed: Checking record destination access', [
                    'user_id' => $user->id,
                    'record_id' => $this->record->id,
                    'record_destination_string' => $recordDestination,
                    'record_destination_relation' => $this->record->destination?->name,
                    'final_destination_name' => $destinationName,
                    'allowed_destinations' => $possibleDestinations,
                    'access_granted' => in_array($destinationName, $possibleDestinations)
                ]);

                if (!in_array($destinationName, $possibleDestinations)) {
                    Log::warning('ViewConfirmedAffixed: Access denied - destination not in allowed list', [
                        'user_id' => $user->id,
                        'record_id' => $this->record->id,
                        'destination_name' => $destinationName,
                        'allowed_destinations' => $possibleDestinations
                    ]);

                    // If not authorized, redirect to the list page
                    $this->redirect(ConfirmedAffixedResource::getUrl());
                }

                Log::info('ViewConfirmedAffixed: Access granted for record', [
                    'user_id' => $user->id,
                    'record_id' => $this->record->id,
                    'destination_name' => $destinationName
                ]);
            } else {
                Log::warning('ViewConfirmedAffixed: User has no destination permissions, redirecting', [
                    'user_id' => $user->id,
                    'user_roles' => $user->roles->pluck('name')->toArray(),
                    'record_id' => $this->record->id,
                    'all_permissions' => $user->permissions->pluck('name')->toArray()
                ]);

                // If no destination permissions, redirect to the list page
                $this->redirect(ConfirmedAffixedResource::getUrl());
            }
        } else {
            Log::info('ViewConfirmedAffixed: User is not Retrieval Officer/Affixing Officer, allowing access', [
                'user_id' => $user?->id,
                'record_id' => $this->record->id,
                'user_roles' => $user?->roles->pluck('name')->toArray() ?? []
            ]);
        }

        return $data;
    }

    public function table(Table $table): Table
    {
        $table = parent::table($table);
        $user = auth()->user();

        Log::info('ViewConfirmedAffixed: table method called', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_roles' => $user?->roles->pluck('name')->toArray() ?? []
        ]);

        // For Retrieval Officer and Affixing Officer, filter by destination permissions
        if ($user?->hasRole(['Retrieval Officer', 'Affixing Officer'])) {
            Log::info('ViewConfirmedAffixed: table processing Retrieval Officer/Affixing Officer access', [
                'user_id' => $user->id,
                'user_roles' => $user->roles->pluck('name')->toArray()
            ]);

            // Get all permissions that start with 'view_destination_'
            $destinationPermissions = $user->permissions
                ->filter(fn ($permission) => str_starts_with($permission->name, 'view_destination_'))
                ->map(fn ($permission) => Str::after($permission->name, 'view_destination_'))
                ->toArray();

            Log::info('ViewConfirmedAffixed: table destination permissions extracted', [
                'user_id' => $user->id,
                'destination_permissions' => $destinationPermissions,
                'all_permissions' => $user->permissions->pluck('name')->toArray()
            ]);

            // If user has destination permissions, filter by those
            if (!empty($destinationPermissions)) {
                // Convert permission slugs to possible destination names
                $possibleDestinations = [];

                foreach ($destinationPermissions as $slug) {
                    $possibleDestinations[] = $slug;
                    $possibleDestinations[] = ucfirst($slug);
                    $possibleDestinations[] = strtoupper($slug);
                    $possibleDestinations[] = Str::title($slug);
                    $possibleDestinations[] = Str::title(str_replace('-', ' ', $slug));
                }

                // Remove duplicates
                $possibleDestinations = array_unique($possibleDestinations);

                Log::info('ViewConfirmedAffixed: table possible destination variations generated', [
                    'user_id' => $user->id,
                    'original_slugs' => $destinationPermissions,
                    'possible_destinations' => $possibleDestinations
                ]);

                // Filter table query to only include records with matching destinations
                $table->modifyQueryUsing(function (Builder $query) use ($possibleDestinations, $user) {
                    $query->where(function ($query) use ($possibleDestinations) {
                        $query->whereIn('destination', $possibleDestinations)
                            ->orWhereHas('destination', function ($subQuery) use ($possibleDestinations) {
                                $subQuery->whereIn('name', $possibleDestinations);
                            });
                    });

                    Log::info('ViewConfirmedAffixed: table applied destination filtering', [
                        'user_id' => $user->id,
                        'filter_destinations' => $possibleDestinations
                    ]);
                });
            } else {
                Log::warning('ViewConfirmedAffixed: table User has no destination permissions', [
                    'user_id' => $user->id,
                    'user_roles' => $user->roles->pluck('name')->toArray(),
                    'all_permissions' => $user->permissions->pluck('name')->toArray()
                ]);
            }
        } else {
            Log::info('ViewConfirmedAffixed: table user is not Retrieval Officer/Affixing Officer, no filtering applied', [
                'user_id' => $user?->id,
                'user_roles' => $user?->roles->pluck('name')->toArray() ?? []
            ]);
        }

        return $table;
    }
}


