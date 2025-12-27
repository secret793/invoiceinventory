<?php

namespace App\Filament\Actions;

use App\Models\DeviceRetrieval;
use App\Models\WaiverHistory;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminWaiverAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('waive')
            ->label('Waive')
            ->icon('heroicon-o-no-symbol')
            ->color('info')
            ->form([
                Forms\Components\Section::make('Waiver Information')
                    ->schema([
                        Forms\Components\TextInput::make('device_info')
                            ->label('Device')
                            ->disabled()
                            ->default(fn (DeviceRetrieval $record) => "{$record->device?->device_id}"),

                        Forms\Components\TextInput::make('overstay_info')
                            ->label('Overstay Charges')
                            ->disabled()
                            ->default(fn (DeviceRetrieval $record) => "{$record->overstay_days} days (D{$record->overstay_amount})"),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Waiving Overstay')
                            ->required()
                            ->minLength(10)
                            ->maxLength(500)
                            ->placeholder('Please provide detailed reason for the waiver (minimum 10 characters)')
                            ->helperText('Reason will be stored for audit trail'),
                    ])
                    ->columns(1)
            ])
            ->action(function (array $data, DeviceRetrieval $record): void {
                DB::beginTransaction();
                try {
                    // Get the invoice if it exists
                    $invoice = $record->invoice;

                    // Create waiver history record
                    WaiverHistory::create([
                        'device_retrieval_id' => $record->id,
                        'invoice_id' => $invoice?->id,
                        'admin_user_id' => auth()->id(),
                        'reason' => $data['reason'],
                        'original_overstay_days' => $record->overstay_days,
                        'original_amount' => $record->overstay_amount,
                    ]);

                    // Update device retrieval
                    $record->update([
                        'payment_status' => 'WAIVED',
                        'overstay_days' => 0,
                        'overstay_amount' => 0,
                    ]);

                    // Update invoice if exists
                    if ($invoice) {
                        $invoice->update([
                            'status' => 'WAIVED',
                            'waived_by' => auth()->id(),
                            'waived_at' => now(),
                        ]);
                    }

                    DB::commit();

                    Log::info('Overstay waived', [
                        'device_retrieval_id' => $record->id,
                        'invoice_id' => $invoice?->id,
                        'admin_id' => auth()->id(),
                        'admin_name' => auth()->user()->name,
                        'original_amount' => $record->overstay_amount,
                        'reason' => $data['reason'],
                    ]);

                    Notification::make()
                        ->title('Overstay Waived Successfully')
                        ->body("Waived by: " . auth()->user()->name . " for reason: " . $data['reason'])
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    DB::rollBack();

                    Log::error('Waiver failed', [
                        'error' => $e->getMessage(),
                        'device_retrieval_id' => $record->id,
                        'admin_id' => auth()->id(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    Notification::make()
                        ->title('Waiver Failed')
                        ->body('Error: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->visible(fn (DeviceRetrieval $record): bool =>
                auth()->user()?->hasRole(['Super Admin', 'Admin']) &&
                $record->overstay_days > 0 &&
                $record->payment_status === 'PP' &&
                !$record->isWaived()
            )
            ->requiresConfirmation()
            ->modalHeading('Waive Overstay Charges')
            ->modalDescription(fn (DeviceRetrieval $record): string =>
                "This will waive {$record->overstay_days} days of overstay charges (D{$record->overstay_amount}) for device {$record->device?->device_id}. This action is permanent and will be logged for audit purposes."
            );
    }
}
