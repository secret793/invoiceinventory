<?php

namespace App\Filament\Resources\DataEntryAssignmentResource\Pages;

use App\Filament\Resources\DataEntryAssignmentResource;
use App\Models\DispatchLog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\DB;

class DispatchReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = DataEntryAssignmentResource::class;
    protected static string $view = 'filament.resources.data-entry-assignment.pages.dispatch-report';
    public ?array $filters = [];

    public function mount(): void
    {
        // Check if user is authorized to view the report
        $this->authorize('viewDispatchReport', \App\Models\DataEntryAssignment::class);

        // Initialize filters with default values
        $this->filters = [
            'date_from' => now()->subDays(7)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'time_from' => '00:00:00',
            'time_to' => '23:59:59',
            'device_id' => '',
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('device.device_id')
                    ->label('Device ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dispatched_at')
                    ->label('Dispatch Time')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('dispatcher.name')
                    ->label('Dispatched By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assignment.title')
                    ->label('Assignment')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('details.boe')
                    ->label('SAD/T1')
                    ->searchable(),
                TextColumn::make('details.vehicle_number')
                    ->label('Vehicle #')
                    ->searchable(),
                TextColumn::make('details.destination')
                    ->label('Destination')
                    ->searchable(),
            ])
            ->filters([
                Filter::make('filters')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('From Date')
                            ->default(now()->subDays(7))
                            ->reactive(),
                        DatePicker::make('date_to')
                            ->label('To Date')
                            ->default(now())
                            ->reactive(),
                        TimePicker::make('time_from')
                            ->label('From Time')
                            ->default('00:00:00')
                            ->withoutSeconds(),
                        TimePicker::make('time_to')
                            ->label('To Time')
                            ->default('23:59:59')
                            ->withoutSeconds(),
                        TextInput::make('device_id')
                            ->label('Device ID')
                            ->placeholder('Enter device ID')
                            ->reactive(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('dispatched_at', '>=', $date),
                            )
                            ->when(
                                $data['date_to'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('dispatched_at', '<=', $date),
                            )
                            ->when(
                                $data['time_from'] ?? null,
                                fn (Builder $query, $time): Builder => $query->whereTime('dispatched_at', '>=', $time),
                            )
                            ->when(
                                $data['time_to'] ?? null,
                                fn (Builder $query, $time): Builder => $query->whereTime('dispatched_at', '<=', $time),
                            )
                            ->when(
                                $data['device_id'] ?? null,
                                fn (Builder $query, $deviceId): Builder => $query->whereHas('device', 
                                    fn($q) => $q->where('device_id', 'like', "%{$deviceId}%")
                                ),
                            );
                    })
            ])
            ->actions([
                Action::make('view_details')
                    ->label('View Details')
                    ->url(fn (DispatchLog $record): string => route('filament.resources.devices.view', $record->device_id))
                    ->icon('heroicon-o-eye')
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                ExportBulkAction::make('export')
                    ->label('Export Selected')
                    ->icon('heroicon-o-document-download')
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return DispatchLog::query()
            ->with(['device', 'dispatcher', 'assignment'])
            ->latest('dispatched_at');
    }

    public function getTitle(): string
    {
        return 'Dispatch Report';
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.resources.data-entry-assignments.index') => 'Data Entry Assignments',
            'Dispatch Report',
        ];
    }
}
