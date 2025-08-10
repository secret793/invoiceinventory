<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\DeviceRetrieval;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice)
    {
        Log::info('Invoice updated event triggered', [
            'invoice_id' => $invoice->id,
            'original_status' => $invoice->getOriginal('status'),
            'new_status' => $invoice->status,
            'is_dirty_status' => $invoice->isDirty('status')
        ]);

        // Only act if status changed from PP to PD
        if ($invoice->isDirty('status') && $invoice->getOriginal('status') === 'PP' && $invoice->status === 'PD') {
            try {
                Log::info('Attempting to update DeviceRetrieval payment status', [
                    'invoice_id' => $invoice->id,
                    'device_retrieval_id' => $invoice->device_retrieval_id
                ]);

                // Eager load the relationship to ensure it's available
                $invoice->load('deviceRetrieval');
                
                if ($invoice->deviceRetrieval) {
                    // Update using direct DB query to avoid potential model events loop
                    $updated = DeviceRetrieval::where('id', $invoice->device_retrieval_id)
                        ->update([
                            'payment_status' => 'PD',
                            'updated_at' => now()
                        ]);

                    if ($updated) {
                        Log::info('Successfully updated DeviceRetrieval payment_status', [
                            'invoice_id' => $invoice->id,
                            'device_retrieval_id' => $invoice->device_retrieval_id,
                            'rows_affected' => $updated
                        ]);
                    } else {
                        Log::error('Failed to update DeviceRetrieval payment_status - no rows affected', [
                            'invoice_id' => $invoice->id,
                            'device_retrieval_id' => $invoice->device_retrieval_id
                        ]);
                    }
                } else {
                    Log::warning('No related DeviceRetrieval found for invoice', [
                        'invoice_id' => $invoice->id,
                        'device_retrieval_id' => $invoice->device_retrieval_id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error updating DeviceRetrieval payment_status', [
                    'invoice_id' => $invoice->id,
                    'device_retrieval_id' => $invoice->device_retrieval_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}