<?php

namespace App\Filament\Resources\DeviceResource\Pages;

use App\Filament\Resources\DeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use App\Imports\DevicesImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Exception;
use App\Filament\Widgets\DeviceStatisticsWidget;
use App\Filament\Widgets\StoreStatisticsWidget;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use App\Filament\Widgets\DeviceStatusFilterWidget;
use Livewire\Attributes\On;

class ListDevices extends ListRecords
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            DeviceStatisticsWidget::class,
            DeviceStatusFilterWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('importProducts')
                ->label('Import Products')
                ->color('danger')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    FileUpload::make('attachment')
                        ->label('Upload Excel File')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel'
                        ]),
                ])
                ->action(function (array $data) {
                    try {
                        if (!isset($data['attachment'])) {
                            throw new \Exception("No file uploaded.");
                        }

                        $filePath = Storage::disk('public')->path($data['attachment']);

                        if (!file_exists($filePath)) {
                            throw new \Exception("File does not exist: $filePath");
                        }

                        // Import with validation
                        Excel::import(new DevicesImport, $filePath);

                        Notification::make()
                            ->title('Import Successful')
                            ->success()
                            ->send();

                    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                        $failures = $e->failures();
                        $errors = collect($failures)->map(function ($failure) {
                            return "Row {$failure->row()}: {$failure->errors()[0]}";
                        })->join('<br>');

                        Notification::make()
                            ->title('Import Failed')
                            ->danger()
                            ->body($errors)
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Actions\Action::make('downloadExcel')
                ->label('Download Excel File')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    return DeviceResource::downloadExcel(); // Call the download method
                }),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('device_id')->label('Device ID')->sortable()->searchable(),
            TextColumn::make('device_type')->label('Device Type')->sortable()->searchable(),
            TextColumn::make('batch_number')->label('Batch Number')->sortable()->searchable(),
            TextColumn::make('status')->sortable()->searchable(),
            TextColumn::make('date_received')->label('Receipt Date')->date()->sortable(),
            TextColumn::make('distributionPoint.name')
                ->label('Distribution Point')
                ->sortable()
                ->searchable(),
            TextColumn::make('sim_number')
                ->label('SIM Number')
                ->sortable()
                ->searchable()
                ->toggleable(),
            TextColumn::make('sim_operator')
                ->label('SIM Operator')
                ->sortable()
                ->searchable()
                ->toggleable(),
            TextColumn::make('user.name')
                ->label('Added By')
                ->sortable()
                ->searchable(),
            TextColumn::make('cancellation_reason')
                ->label('Cancel Note')
                ->description(fn ($record) => $record->cancelled_at ? 'Cancelled on: ' . $record->cancelled_at->format('Y-m-d H:i') : '')
                ->wrap()
                ->tooltip(function ($record) {
                    if ($record->cancellation_reason) {
                        return 'Full reason: ' . $record->cancellation_reason;
                    }
                    return null;
                })
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('cancellation_status')
                ->label('Cancellation Status')
                ->options([
                    'cancelled' => 'Cancelled',
                    'not_cancelled' => 'Not Cancelled',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['value'] === 'cancelled',
                            fn (Builder $query): Builder => $query->whereNotNull('cancelled_at'),
                        )
                        ->when(
                            $data['value'] === 'not_cancelled',
                            fn (Builder $query): Builder => $query->whereNull('cancelled_at'),
                        );
                })
                ->indicateUsing(function (array $data): ?string {
                    if ($data['value'] === 'cancelled') {
                        return 'Showing only cancelled devices';
                    }
                    if ($data['value'] === 'not_cancelled') {
                        return 'Showing only non-cancelled devices';
                    }
                    return null;
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            EditAction::make(),
            Tables\Actions\Action::make('changeStatus')
                ->label('Change Status')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->form([
                    Select::make('status')
                        ->label('New Status')
                        ->options([
                            'ONLINE' => 'ONLINE',
                            'OFFLINE' => 'OFFLINE',
                            'DAMAGED' => 'DAMAGED',
                            'FIXED' => 'FIXED',
                            'LOST' => 'LOST',
                        ])
                        ->required(),
                ])
                ->action(function ($record, array $data) {
                    $record->update(['status' => $data['status']]);

                    Notification::make()
                        ->title('Device status updated successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    #[On('filter-devices-by-status')] 
    public function filterDevicesByStatus($statuses)
    {
        $this->tableFilters['status']['values'] = $statuses;
        $this->dispatch('device-status-filter-widget::updateStatusCounts');
    }
}