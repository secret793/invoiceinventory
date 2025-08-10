<?php

namespace App\Filament\Actions;

use App\Models\DeviceRetrieval;
use App\Models\Invoice;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceApprovalAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Approve Payment')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->form(function (DeviceRetrieval $record) {
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
                                ->default($record->agent_contact)
                                ->required(),
                            
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->placeholder('Optional additional notes')
                                ->maxLength(500),
                            
                            Forms\Components\FileUpload::make('logo')
                                ->label('Invoice Logo')
                                ->image()
                                ->directory('invoice_logos')
                                ->visibility('private'),
                        ])
                ];
            })
            ->action(function (array $data, DeviceRetrieval $record): void {
                try {
                    DB::beginTransaction();

                    // Update device retrieval with finance approval
                    $record->update([
                        'payment_status' => 'PD', // Now changing to Paid
                        'finance_approval_date' => now(),
                        'finance_approved_by' => auth()->id(),
                    ]);
                    
                    // Update invoice status
                    $invoice = Invoice::where('device_retrieval_id', $record->id)
                        ->where('status', 'pending_approval')
                        ->first();

                    if ($invoice) {
                        $invoice->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        // Update related device retrieval
                        $record->update([
                            'payment_status' => 'PD', // Paid
                            'finance_approval_date' => now(),
                            'finance_approved_by' => auth()->id(),
                        ]);
                    }

                    DB::commit();

                    Notification::make()
                        ->title('Payment Approved Successfully')
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Finance approval failed', [
                        'error' => $e->getMessage(),
                        'record_id' => $record->id,
                    ]);

                    Notification::make()
                        ->title('Finance Approval Failed')
                        ->danger()
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading('Approve Payment')
            ->modalDescription('Are you sure you want to approve this payment?')
            ->visible(function (DeviceRetrieval $record): bool {
                return $record->payment_status === 'PP' &&
                    $record->overstay_amount > 0 &&
                    (auth()->user()->hasRole('Finance Officer') || auth()->user()->hasRole('Super Admin')) &&
                    !(auth()->user()->hasRole('Retrieval Officer') && 
                      !auth()->user()->hasRole('Finance Officer') && 
                      !auth()->user()->hasRole('Super Admin'));
            });
    }
}