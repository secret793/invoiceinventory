<?php

namespace App\Filament\Resources\MonitoringResource\Pages;

use App\Filament\Resources\MonitoringResource;
use App\Models\Monitoring;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Database\Eloquent\Collection;
use Filament\Support\Colors\Color;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ListMonitorings extends ListRecords
{
    protected static string $resource = MonitoringResource::class;
    protected static string $view = 'filament.resources.monitoring-resource.pages.list-monitorings';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('overdue')
                ->label('Overdue Devices')
                ->color(Color::Red)
                ->icon('heroicon-o-exclamation-circle')
                ->action(function () {
                    $this->resetTableFiltersForm();
                    $this->tableFilters = array_merge($this->tableFilters, ['overdue' => ['value' => true]]);
                })
                ->button(),
        ];
    }

    public function applyFilter(string $type): void
    {
        $this->tableFilters[$type] = true;
        $this->resetPage();
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('addNote')
                ->label('Add Note')
                ->icon('heroicon-o-pencil')
                ->form([
                    Forms\Components\Textarea::make('note')
                        ->label('Note')
                        ->required(),
                    Forms\Components\DateTimePicker::make('manifest_date')
                        ->label('Manifest Date')
                ])
                ->action(function (Monitoring $record, array $data): void {
                    try {
                        // Use our new magic method!
                        $success = $record->addNewNote(
                            $data['note'],
                            $data['manifest_date'] ?? null
                        );

                        if ($success) {
                            Notification::make()
                                ->success()
                                ->title('Note Added')
                                ->body('Your note has been saved successfully!')
                                ->send();
                        } else {
                            throw new \Exception('Failed to save note');
                        }

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('Could not add the note: ' . $e->getMessage())
                            ->send();
                    }
                })
                ->modalWidth('md')
        ];
    }

    public function table(Table $table): Table
    {
        $query = static::getResource()::getEloquentQuery()
            ->with(['device', 'route', 'longRoute']);

        return $table
            ->query($query)
            ->columns([
                // Removed custom checkbox column to avoid double checkboxes
                Tables\Columns\TextColumn::make('date')
                    ->label('Dispatch Date')
                    ->dateTime()
                    ->sortable(),
                // Hide current_date as it's for internal use only
                // Tables\Columns\TextColumn::make('current_date')
                //     ->label('Current Date')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('device.device_id')
                    ->label('Device ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('boe')
                    ->label('BOE')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('vehicle_number')
                    ->label('Vehicle No.')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('regime')
                    ->label('Regime')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('route.name')
                    ->label('Route')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('longRoute.name')
                    ->label('Long Route')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manifest_date')
                    ->label('Manifest Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination')
                    ->label('Destination')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('agency')
                    ->label('Agency')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('agent_contact')
                    ->label('Agent Contact')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('truck_number')
                    ->label('Truck No.')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('driver_name')
                    ->label('Driver Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('affixing_date')
                    ->label('Affixing Date ')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? $state->diffForHumans() : 'N/A'),
                    
                Tables\Columns\TextColumn::make('overdue_hours')
                    ->label('Overdue Hours')
                    ->sortable()
                    ->color(fn ($record) => $record->overdue_hours > 0 ? 'danger' : 'success')
                    ->weight(fn ($record) => $record->overdue_hours > 0 ? 'bold' : '')
                    ->formatStateUsing(fn ($state, $record) => $state > 0 ? "{$state} hour" . ($state > 1 ? 's' : '') : 'On time')
                    ->description(fn ($record) => $record->affixing_date ? 'Since ' . $record->affixing_date->diffForHumans() : ''),
                    
                Tables\Columns\TextColumn::make('overstay_days')
                    ->label('Overstay Days')
                    ->sortable()
                    ->color(fn ($record) => $record->overstay_days > 0 ? 'danger' : 'success')
                    ->weight(fn ($record) => $record->overstay_days > 0 ? 'bold' : '')
                    ->formatStateUsing(fn ($state, $record) => $state > 0 ? "{$state} day" . ($state > 1 ? 's' : '') : 'On time')
                    ->description(fn ($record) => $record->overstay_amount > 0 ? 'Amount: GHS ' . number_format($record->overstay_amount, 2) : ''),
                Tables\Columns\TextColumn::make('retrieval_status')
                    ->label('Retrieval Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'NOT_RETRIEVED' => 'danger',
                        'RETRIEVED' => 'success',
                        'RETURNED' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'NOT_RETRIEVED' => 'Not Retrieved',
                        'RETRIEVED' => 'Retrieved',
                        'RETURNED' => 'Returned',
                        default => $state,
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('pending')
                    ->label('Pending Devices')
                    ->placeholder('All Devices')
                    ->trueLabel('Show Only Pending')
                    ->falseLabel('Show Only Non-Pending')
                    ->queries(
                        true: fn ($query) => $query->whereNull('manifest_date'),
                        false: fn ($query) => $query->whereNotNull('manifest_date'),
                    ),
                Tables\Filters\TernaryFilter::make('overdue')
                    ->label('Overdue Devices')
                    ->placeholder('All Devices')
                    ->trueLabel('Show Only Overdue')
                    ->falseLabel('Show Only Non-Overdue')
                    ->queries(
                        true: fn ($query) => $query->where('overdue_hours', '>', 0),
                        false: fn ($query) => $query->where('overdue_hours', '=', 0),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('addNote')
                    ->label('Add Note')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->required()
                            ->maxLength(1000),
                        Forms\Components\DateTimePicker::make('manifest_date')
                            ->label('Manifest Date')
                    ])
                    ->action(function (Monitoring $record, array $data): void {
                        try {
                            // Use our new magic method!
                            $success = $record->addNewNote(
                                $data['note'],
                                $data['manifest_date'] ?? null
                            );

                            if ($success) {
                                Notification::make()
                                    ->success()
                                    ->title('Note Added')
                                    ->body('Your note has been saved successfully!')
                                    ->send();
                            } else {
                                throw new \Exception('Failed to save note');
                            }

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Could not add the note: ' . $e->getMessage())
                                ->send();
                        }
                    })
                    ->modalWidth('md'),
                // Delete actions have been removed
            ])
            ->bulkActions([
                // Bulk actions have been removed
                Tables\Actions\BulkAction::make('process')
                    ->label('Process Selected')
                    ->action(function (Collection $records) {
                        // Add your processing logic here
                    })
                    ->deselectRecordsAfterCompletion()
            ])
            ->defaultSort('date', 'desc')
            //->paginationPageOptions([Monitoring::count()])
            ->poll('10s');
    }
}
