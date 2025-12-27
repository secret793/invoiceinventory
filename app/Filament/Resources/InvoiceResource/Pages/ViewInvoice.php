<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-o-document-download')
                ->color('primary')
                ->url(fn () => route('invoices.download', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () =>
                    $this->record->status === 'pending' &&
                    auth()->user()->hasRole(['Finance Officer', 'Super Admin'])
                )
                ->action(function () {
                    $this->record->approve(auth()->user());

                    // Update the related device retrieval if it exists
                    if ($this->record->device_retrieval_id) {
                        $deviceRetrieval = \App\Models\DeviceRetrieval::find($this->record->device_retrieval_id);
                        if ($deviceRetrieval) {
                            $deviceRetrieval->update([
                                'payment_status' => 'PD',
                                'finance_approval_date' => now(),
                                'finance_approved_by' => auth()->id(),
                            ]);
                        }
                    }

                    $this->notify('success', 'Invoice approved successfully');

                    $this->redirect(InvoiceResource::getUrl('index'));
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required(),
                ])
                ->visible(fn () =>
                    $this->record->status === 'pending' &&
                    auth()->user()->hasRole(['Finance Officer', 'Super Admin'])
                )
                ->action(function (array $data) {
                    $this->record->reject($data['rejection_reason']);

                    $this->notify('danger', 'Invoice rejected');

                    $this->redirect(InvoiceResource::getUrl('index'));
                }),
        ];
    }
}
