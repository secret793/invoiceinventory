<?php

namespace App\Observers;

use App\Models\AssignToAgent;
use App\Models\Receipt;
use Illuminate\Support\Facades\Log;

class ReceiptObserver
{
    /**
     * Handle AssignToAgent creation - decrement receipt usage
     * 
     * When a device is dispatched using a receipt, decrement the receipt's used count
     */
    public function created(AssignToAgent $assignment): void
    {
        if ($assignment->receipt_id) {
            $receipt = $assignment->receipt;

            if (!$receipt) {
                Log::warning('Receipt not found for assignment', [
                    'assignment_id' => $assignment->id,
                    'receipt_id' => $assignment->receipt_id,
                ]);
                return;
            }

            if ($receipt->used <= 0) {
                Log::error('Receipt fully used, cannot dispatch', [
                    'receipt_id' => $receipt->id,
                    'receipt_number' => $receipt->receipt_number,
                ]);
                throw new \Exception("Receipt {$receipt->receipt_number} is fully used");
            }

            // Decrement receipt usage
            $receipt->decrement('used');

            Log::info('Receipt usage decremented', [
                'receipt_id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'used_remaining' => $receipt->fresh()->used,
                'assignment_id' => $assignment->id,
            ]);
        }
    }

    /**
     * Handle AssignToAgent deletion - increment receipt usage (if needed)
     * 
     * If a dispatch is cancelled, increment the receipt's used count back
     */
    public function deleting(AssignToAgent $assignment): void
    {
        if ($assignment->receipt_id) {
            $receipt = $assignment->receipt;

            if ($receipt) {
                $receipt->increment('used');

                Log::info('Receipt usage incremented (dispatch cancelled)', [
                    'receipt_id' => $receipt->id,
                    'receipt_number' => $receipt->receipt_number,
                    'used_remaining' => $receipt->fresh()->used,
                    'assignment_id' => $assignment->id,
                ]);
            }
        }
    }
}
