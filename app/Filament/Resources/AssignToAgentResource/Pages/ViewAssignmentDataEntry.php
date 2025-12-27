<?php

namespace App\Filament\Resources\AssignToAgentResource\Pages;

use App\Filament\Resources\AssignToAgentResource;
use App\Models\Device;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Actions\Action as PageAction;
use Filament\Forms;
use Carbon\Carbon;

class ViewAssignmentDataEntry extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = AssignToAgentResource::class;

    protected static string $view = 'filament.resources.assign-to-agent.pages.view-assignment-data-entry';

    public $dataEntryAssignment;
    public $allocationPoint;
    public $sortField = 'device_id';
    public $sortDirection = 'asc';
    public $selectedDevices = [];

    public function mount(string|int $record): void
    {
        $this->dataEntryAssignment = AssignToAgentResource::getModel()::find($record);
        $this->allocationPoint = $this->dataEntryAssignment->allocationPoint;
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('dispatch_selected')
                ->label('Dispatch')
                ->color('warning')
                ->form([
                    Forms\Components\DatePicker::make('date')
                        ->label('Date')
                        ->default(now())
                        ->disabled(),
                    Forms\Components\TextInput::make('device_serial')
                        ->label('Device Serial')
                        ->default(fn () => collect($this->selectedDevices)->join(', '))
                        ->disabled(),
                    Forms\Components\TextInput::make('boe')
                        ->label('SAD')
                        ->required(),
                    Forms\Components\TextInput::make('vehicle_number')
                        ->label('Vehicle Number')
                        ->required(),
                    Forms\Components\Select::make('regime')
                        ->label('Regime')
                        ->options([
                            'transit' => 'Transit',
                            'import' => 'Import',
                            'export' => 'Export',
                        ])
                        ->required(),
                    Forms\Components\Select::make('destination')
                        ->label('Destination')
                        ->options([
                            'port' => 'Port',
                            'border' => 'Border',
                            'warehouse' => 'Warehouse',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('boe_number')
                        ->label('BOE Number'),
                    Forms\Components\TextInput::make('agency')
                        ->label('Agency'),
                    Forms\Components\TextInput::make('agent_contact')
                        ->label('Agent Contact')
                        ->tel(),
                    Forms\Components\TextInput::make('truck_number')
                        ->label('Truck Number'),
                    Forms\Components\TextInput::make('driver_name')
                        ->label('Driver Name'),
                ])
                ->action(function (array $data): void {
                    // Handle form submission
                    Notification::make()
                        ->title('Devices dispatched successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Device::query()
                    ->where('allocation_point_id', $this->allocationPoint->id)
                    ->with(['dataEntryAssignment' => function($query) {
                        $query->select('id', 'device_id', 'notes', 'updated_at', 'status')
                            ->where('status', 'RETURNED')
                            ->latest();
                    }])
            )
            ->columns([
                Tables\Columns\TextColumn::make('device_id')
                    ->label('Device ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('device_type')
                    ->label('Device Type')
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
                Tables\Columns\TextColumn::make('dataEntryAssignment.notes')
                    ->label('Return Note')
                    ->description(fn ($record) => 
                        $record->dataEntryAssignment?->updated_at 
                            ? 'Returned on: ' . $record->dataEntryAssignment->updated_at->format('Y-m-d H:i') 
                            : ''
                    )
                    ->wrap()
                    ->words(30)
                    ->tooltip(function ($record) {
                        return $record->dataEntryAssignment?->notes ?? null;
                    })
                    ->searchable(),
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
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('select_devices')
                    ->label('Select')
                    ->action(function (array $records): void {
                        $this->selectedDevices = collect($records)->pluck('id')->toArray();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->color('primary')
                    ->button(),
            ]);
    }

    public function getTitle(): string
    {
        return "Devices at {$this->allocationPoint->name}";
    }
}
