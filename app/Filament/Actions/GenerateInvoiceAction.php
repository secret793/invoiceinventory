<?php

namespace App\Filament\Actions;

use App\Models\DeviceRetrieval;
use App\Models\Invoice;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateInvoiceAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Generate Invoice')
            ->icon('heroicon-o-document-text')
            ->color('success')
            ->form(function ($record) {
                return [
                    Forms\Components\Section::make('Payment Information')
                        ->schema([
                            Forms\Components\TextInput::make('reference_number')
                                ->label('Reference Number')
                                ->default('INV-' . now()->format('YmdHis'))
                                ->required()
                                ->maxLength(255),
                            
                            Forms\Components\TextInput::make('total_amount')
                                ->label('Total Amount')
                                ->numeric()
                                ->required(),
                            
                            Forms\Components\TextInput::make('paid_by')
                                ->label('Paid By')
                                ->default(auth()->user()->name)
                                ->maxLength(255),
                            
                            Forms\Components\TextInput::make('received_by')
                                ->label('Received By')
                                ->default('Finance Department')
                                ->maxLength(255),
                        ]),

                    Forms\Components\Section::make('Device Details')
                        ->schema([
                            Forms\Components\TextInput::make('device_number')
                                ->label('Device Number')
                                ->default($record->device?->device_id)
                                ->required(),
                            
                            Forms\Components\TextInput::make('route')
                                ->label('Route')
                                ->default($record->route?->name ?? 'Unknown')
                                ->required(),
                            
                            Forms\Components\TextInput::make('overstay_days')
                                ->label('Overstay Days')
                                ->default($record->overstay_days)
                                ->numeric()
                                ->required(),
                            
                            Forms\Components\TextInput::make('penalty_amount')
                                ->label('Penalty Amount')
                                ->numeric()
                                ->required(),
                        ]),

                    Forms\Components\Section::make('Additional Information')
                        ->schema([
                            Forms\Components\TextInput::make('sad_boe')
                                ->label('SAD/BOE Number')
                                ->default($record->sad_number)
                                ->required(),
                            
                            Forms\Components\TextInput::make('regime')
                                ->label('Regime')
                                ->default($record->regime)
                                ->required(),
                            
                            Forms\Components\TextInput::make('agent')
                                ->label('Agent')
                                        ->default($record->agency),

                                    Forms\Components\TextInput::make('departure')
                                        ->label('Departure')
                                        ->default('BANJUL'),

                                    Forms\Components\TextInput::make('destination')
                                        ->label('Destination')
                                        ->default($record->destination),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('Penalty Information')
                                ->schema([
                                    Forms\Components\TextInput::make('overstay_days')
                                        ->label('Number days overstayed')
                                        ->default($record->overstay_days)
                                        ->numeric()
                                        ->disabled(),

                                    Forms\Components\TextInput::make('penalty_amount')
                                        ->label('Penalty amount Per')
                                        ->default(1000)
                                        ->numeric()
                                        ->prefix('GMD')
                                        ->required(),

                                    Forms\Components\TextInput::make('device_number')
                                        ->label('Device No')
                                        ->default(function () use ($record) {
                                            $device = $record->device;
                                            return $device ? $device->device_id : '';
                                        })
                                ->required(),
                            
                                    Forms\Components\TextInput::make('asset_number')
                                        ->label('Asset Number')
                                        ->default(function () use ($record) {
                                            $device = $record->device;
                                            return $device ? $device->serial_number : '';
                                        }),

                                    Forms\Components\TextInput::make('total_amount')
                                        ->label('Total Amount')
                                        ->default(function () use ($record) {
                                            return $record->overstay_days * 1000;
                                        })
                                        ->numeric()
                                        ->prefix('GMD')
                                        ->disabled(),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('Payment Information')
                                ->schema([
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                        ->default('Penalty payment for overstayed trip')
                                        ->required(),

                                    Forms\Components\TextInput::make('paid_by')
                                        ->label('Paid by')
                                        ->required(),

                                    Forms\Components\TextInput::make('received_by')
                                        ->label('Received by')
                                        ->default(auth()->user()->name)
                                        ->required(),
                            
                            Forms\Components\FileUpload::make('logo')
                                ->label('Invoice Logo')
                                ->image()
                                ->directory('invoice_logos')
                                ->visibility('private'),
                        ])
                ];
            })
            ->action(function (array $data, $record) {
                try {
                    DB::beginTransaction();

                    // Handle signature upload
                    $signaturePath = null;
                    if (isset($data['signature']) && !empty($data['signature'])) {
                        $signaturePath = $data['signature'];
                    }

                    // Calculate total amount
                    $totalAmount = $data['overstay_days'] * $data['penalty_amount'];

                    // Create invoice
                    $invoice = Invoice::create([
                        'device_retrieval_id' => $record->id,
                        'reference_number' => $data['reference_number'],
                        'reference_date' => now(),
                        'sad_boe' => $data['sad_boe'],
                        'regime' => $data['regime'],
                        'agent' => $data['agent'],
                        'route' => $data['route'],
                        'overstay_days' => $data['overstay_days'],
                        'penalty_amount' => $data['penalty_amount'],
                        'device_number' => $data['device_number'],
                        'total_amount' => $data['total_amount'],
                        'description' => $data['description'] ?? null,
                        'paid_by' => $data['paid_by'] ?? auth()->user()->name,
                        'received_by' => $data['received_by'] ?? 'Finance Department',
                        'logo_path' => $data['logo'] ? $data['logo'][0] : null,
                        'status' => 'PP', // Pending Payment
                    ]);

                    // Update device retrieval payment status if not already paid
                    if ($record->payment_status !== 'PD') {
                        $record->update([
                            'payment_status' => 'PD',
                            'finance_approval_date' => now(),
                            'finance_approved_by' => auth()->id(),
                        ]);
                    }

                    DB::commit();

                    // Send notification to finance officers
                    $invoice->sendPendingApprovalNotification();

                    // Generate PDF and provide download link
                    $url = route('invoices.download', $invoice->id);

                    Notification::make()
                        ->title('Invoice generated successfully')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Invoice generation failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'device_retrieval_id' => $record->id
                    ]);

                    Notification::make()
                        ->danger()
                        ->title('Error')
                        ->body('Failed to generate invoice: ' . $e->getMessage())
                        ->send();
                }
            })
            ->modalWidth('4xl')
            ->modalHeading('Generate Overstay Penalty Invoice')
            ->visible(function (DeviceRetrieval $record): bool {
                return $record->overstay_days >= 2;
            });
    }
}
