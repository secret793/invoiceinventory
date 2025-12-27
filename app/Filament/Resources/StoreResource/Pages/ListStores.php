<?php

namespace App\Filament\Resources\StoreResource\Pages;

use App\Filament\Resources\StoreResource;
use App\Models\Store;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use App\Imports\StoresImport; // Ensure you have this import
use App\Filament\Widgets\StoreStatisticsWidget; // Ensure this is correct
use Maatwebsite\Excel\Facades\Excel; // Import Excel facade
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\DeviceResource;
use App\Imports\DevicesImport;
use Illuminate\Http\UploadedFile;
use Exception;
use App\Filament\Widgets\DeviceStatisticsWidget;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Columns\TextColumn;

class ListStores extends ListRecords
{
    protected static string $resource = StoreResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            StoreStatisticsWidget::class, // Include the Store Statistics Widget
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            // Actions\Action::make('importProducts')
            //     ->label('Import Products')
            //     ->color('danger')
            //     ->icon('heroicon-o-document-arrow-down')
            //     ->form([
            //         FileUpload::make('attachment')
            //             ->label('Upload Excel File')
            //             ->required()
            //             ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']),
            //     ])
            //     ->action(function (array $data) {
            //         if (isset($data['attachment'])) {
            //             $filePath = Storage::disk('public')->path($data['attachment']);
            //             if (!file_exists($filePath)) {
            //                 throw new \Exception("File does not exist: $filePath");
            //             }
            //             Excel::import(new StoresImport, $filePath);
            //         } else {
            //             throw new \Exception("No file uploaded.");
            //         }
            //     }),
            // Actions\Action::make('downloadExcel')
            //     ->label('Download Excel File')
            //     ->icon('heroicon-o-document-arrow-down')
            //     ->action(function () {
            //         return StoreResource::downloadExcel();
            //     }),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('serial_number')->label('Device ID')->sortable()->searchable(),
            TextColumn::make('device_type')->label('Device Type')->sortable()->searchable(),
            TextColumn::make('batch_number')->label('Batch Number')->sortable()->searchable(),
            TextColumn::make('status')
                ->sortable()
                ->searchable()
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'UNCONFIGURED' => 'warning',
                    'ONLINE' => 'success',
                    'OFFLINE' => 'danger',
                    'DAMAGED' => 'danger',
                    'FIXED' => 'success',
                    'LOST' => 'danger',
                    default => 'secondary',
                }),
            TextColumn::make('date_received')
                ->label('Date Received')
                ->date()
                ->sortable(),
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
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Actions\Action::make('changeStatus')
                ->label('Change Store Status')
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
                ->action(function (Collection $records, array $data) {
                    if ($records->isEmpty()) {
                        Notification::make()
                            ->title('No stores selected')
                            ->danger()
                            ->send();
                        return;
                    }

                    $records->each(function ($record) use ($data) {
                        $record->update(['status' => $data['status']]);
                    });

                    Notification::make()
                        ->title('Store status updated successfully')
                        ->success()
                        ->send();
                })
                ->deselectRecordsAfterCompletion(),
        ];
    }
}
