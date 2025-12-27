<?php

namespace App\Observers;

use App\Models\DispatchLog;
use App\Models\DispatchFinanceRecord;
use Illuminate\Support\Facades\Log;

class DispatchLogObserver
{
    /**
     * Handle DispatchLog creation - create finance record
     * 
     * PHASE 5: When a dispatch is logged, create a corresponding finance record
     */
    public function created(DispatchLog $log): void
    {
        try {
            // Get assignment with receipt (use the new assign_to_agent_id relationship)
            $assignment = $log->assignToAgent;
            
            if (!$assignment || !$assignment->receipt_id) {
                Log::info('No receipt associated with dispatch', [
                    'dispatch_log_id' => $log->id,
                    'assignment_id' => $assignment?->id,
                ]);
                return;
            }

            $receipt = $assignment->receipt;
            if (!$receipt) {
                Log::warning('Receipt not found for assignment', [
                    'assignment_id' => $assignment->id,
                    'receipt_id' => $assignment->receipt_id,
                ]);
                return;
            }

            // Create DispatchFinanceRecord
            $financeRecord = DispatchFinanceRecord::create([
                'receipt_id' => $receipt->id,
                'assigned_to_agent_id' => $assignment->id,
                'device_id' => $log->device_id,
                'dispatch_date' => $log->dispatched_at,
                'total_amount_gmd' => $receipt->total_charge_gmd,
                'status' => 'PENDING',
                'finance_notes' => "Dispatch for receipt {$receipt->receipt_number}",
                'created_by' => $log->dispatched_by,
            ]);

            Log::info('DispatchFinanceRecord created', [
                'finance_record_id' => $financeRecord->id,
                'receipt_id' => $receipt->id,
                'dispatch_log_id' => $log->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create DispatchFinanceRecord', [
                'error' => $e->getMessage(),
                'dispatch_log_id' => $log->id,
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw - allow dispatch to succeed even if finance record creation fails
        }
    }
}
