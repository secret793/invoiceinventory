<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\DeviceRetrieval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Finance Invoices';

    // Only allow access to finance roles
    public static function canAccess(): bool
    {
        $user = auth()->user();
        
        // Check if user has finance-related roles
        return $user->hasRole([
            'Finance Manager', 
            'Finance Officer', 
            'Super Admin'
        ]);
    }

    // Modify query based on user roles
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // If user is a finance role, they can see all invoices
        if (auth()->user()->hasRole(['Finance Manager', 'Finance Officer', 'Super Admin'])) {
            return $query;
        }

        // Otherwise, return empty query
        return $query->whereRaw('1 = 0');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Invoice Details')
                            ->schema([
                                Forms\Components\TextInput::make('reference_number')
                                    ->label('Reference Number')
                                    ->disabled(),

                                Forms\Components\DatePicker::make('reference_date')
                                    ->label('Reference Date')
                                    ->disabled(),

                                Forms\Components\TextInput::make('status')
                                    ->label('Status')
                                    ->disabled(),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Shipment Information')
                            ->schema([
                                Forms\Components\TextInput::make('sad_boe')
                                    ->label('SAD No')
                                    ->disabled(),

                                Forms\Components\TextInput::make('regime')
                                    ->label('Regime')
                                    ->disabled(),

                                Forms\Components\TextInput::make('agent')
                                    ->label('Agent')
                                    ->disabled(),

                                Forms\Components\TextInput::make('route')
                                    ->label('Route')
                                    ->disabled(),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Penalty Information')
                            ->schema([
                                Forms\Components\TextInput::make('overstay_days')
                                    ->label('Number of Days Overstayed')
                                    ->disabled(),

                                Forms\Components\TextInput::make('penalty_amount')
                                    ->label('Penalty Amount Per Day')
                                    ->disabled()
                                    ->prefix('D'),

                                Forms\Components\TextInput::make('device_number')
                                    ->label('Device Number')
                                    ->disabled(),

                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total Amount')
                                    ->disabled()
                                    ->prefix('D')
                                    ->default(0),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Payment Information')
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->disabled(),

                                Forms\Components\TextInput::make('paid_by')
                                    ->label('Paid By')
                                    ->disabled(),

                                Forms\Components\TextInput::make('received_by')
                                    ->label('Received By')
                                    ->disabled(),

                                Forms\Components\Textarea::make('finance_notes')
                                    ->label('Finance Notes')
                                    ->rows(3),

                                Forms\Components\FileUpload::make('logo_path')
                                    ->label('Logo')
                                    ->image()
                                    ->disabled()
                                    ->hidden(),
                            ])
                            ->columns(2),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference Number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sad_boe')
                    ->label('SAD No')
                    ->searchable(),

                Tables\Columns\TextColumn::make('regime')
                    ->label('Regime')
                    ->searchable(),

                Tables\Columns\TextColumn::make('agent')
                    ->label('Agent')
                    ->searchable(),

                Tables\Columns\TextColumn::make('route')
                    ->label('Route')
                    ->searchable(),

                Tables\Columns\TextColumn::make('overstay_days')
                    ->label('Overstay Days')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('penalty_amount')
                    ->label('Penalty Amount Per Day')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('device_number')
                    ->label('Device Number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->wrap(),

                Tables\Columns\TextColumn::make('paid_by')
                    ->label('Paid By')
                    ->searchable(),

                Tables\Columns\TextColumn::make('received_by')
                    ->label('Received By')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PP' => 'warning',
                        'PD' => 'success',
                        'RJ' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PP' => 'Pending Payment',
                        'PD' => 'Paid',
                        'RJ' => 'Rejected',
                    ])
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(function (Invoice $record) {
                        // Update invoice status
                        $record->update([
                            'status' => 'PD',
                            'finance_approved_by' => auth()->id(),
                            'finance_approved_at' => now(),
                        ]);

                        // Update device retrieval status
                        $deviceRetrieval = $record->deviceRetrieval;
                        if ($deviceRetrieval) {
                            $deviceRetrieval->update([
                                'finance_status' => 'PD',
                                'finance_approved_by' => auth()->id(),
                                'finance_approved_at' => now(),
                            ]);
                        }

                        // Notification
                        \Filament\Notifications\Notification::make()
                            ->title('Invoice Approved')
                            ->body("Invoice {$record->reference_number} has been approved.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Invoice $record) => $record->status === 'PP'),
                
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->action(function (Invoice $record) {
                        // Delete the invoice
                        $record->delete();

                        // Notification
                        \Filament\Notifications\Notification::make()
                            ->title('Invoice Rejected')
                            ->body("Invoice {$record->reference_number} has been rejected.")
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (Invoice $record) => $record->status === 'PP')
                    ->requiresConfirmation(),
                
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->color('primary')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (Invoice $record) {
                        try {
                            // Generate PDF
                            $pdf = Pdf::loadView('pdfs.invoice', [
                                'invoice' => $record,
                                'deviceRetrieval' => $record->deviceRetrieval,
                            ]);

                            // Generate unique filename
                            $filename = "invoice_{$record->reference_number}_" . now()->format('YmdHis') . '.pdf';

                            // Ensure storage directory exists
                            $storagePath = storage_path('app/public/invoices');
                            if (!file_exists($storagePath)) {
                                mkdir($storagePath, 0755, true);
                            }

                            // Save to storage
                            $path = "public/invoices/{$filename}";
                            Storage::put($path, $pdf->output());

                            // Get the full path for download
                            $fullPath = storage_path("app/public/invoices/{$filename}");

                            // Download the file
                            return response()->download($fullPath, $filename)
                                ->deleteFileAfterSend(true);
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('PDF Export Failed')
                                ->body("Error: {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Invoice $record) => $record->status === 'PD')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_selected_pdfs')
                    ->label('Export Selected PDFs')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function ($records) {
                        // Filter out records without a status of 'PD'
                        $paidRecords = $records->filter(fn($record) => $record->status === 'PD');

                        if ($paidRecords->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title('No Paid Invoices')
                                ->body('There are no paid invoices to export.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Create a zip file of PDFs
                        $zipFileName = 'invoices_' . now()->format('YmdHis') . '.zip';
                        $zip = new \ZipArchive();
                        $zipPath = storage_path("app/invoices/{$zipFileName}");
                        
                        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                            foreach ($paidRecords as $record) {
                                // Safely get deviceRetrieval
                                $deviceRetrieval = $record->deviceRetrieval ?? null;

                                $pdf = Pdf::loadView('pdfs.invoice', [
                                    'invoice' => $record,
                                    'deviceRetrieval' => $deviceRetrieval,
                                ]);
                                
                                $filename = "invoice_{$record->reference_number}.pdf";
                                $zip->addFromString($filename, $pdf->output());
                            }
                            $zip->close();
                        }

                        // Download zip
                        return response()->download($zipPath, $zipFileName);
                    })
                    ->visible(fn ($records) => 
                        $records instanceof \Illuminate\Support\Collection && 
                        $records->filter(fn($record) => $record->status === 'PD')->isNotEmpty()
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvoices::route('/'),
        ];
    }
}
