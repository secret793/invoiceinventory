<?php

namespace App\Observers;

use App\Models\AllocationPoint;
use App\Models\ConfirmedAffixed;
use App\Models\AssignToAgent;
use App\Models\DispatchFinanceRecord;
use Illuminate\Support\Facades\Log;

class ConfirmedAffixedObserver
{
    /**
     * Handle the ConfirmedAffixed "created" event.
     */
    public function created(ConfirmedAffixed $confirmedAffixed): void
    {
        try {
            // Sync with AssignToAgent
            if ($confirmedAffixed->device_id) {
                AssignToAgent::where('device_id', $confirmedAffixed->device_id)
                    ->update([
                        'date' => $confirmedAffixed->date ?? now(),
                        'boe' => $confirmedAffixed->boe,
                        'sad_number' => $confirmedAffixed->sad_number,
                        'vehicle_number' => $confirmedAffixed->vehicle_number,
                        'regime' => $confirmedAffixed->regime,
                        'destination' => $confirmedAffixed->destination,
                        'route_id' => $confirmedAffixed->route_id,
                        'long_route_id' => $confirmedAffixed->long_route_id,
                        'manifest_date' => $confirmedAffixed->manifest_date,
                        'agency' => $confirmedAffixed->agency,
                        'agent_contact' => $confirmedAffixed->agent_contact,
                        'truck_number' => $confirmedAffixed->truck_number,
                        'driver_name' => $confirmedAffixed->driver_name,
                        'affixing_date' => $confirmedAffixed->affixing_date,
                        'status' => 'AFFIXED'
                    ]);

                // PHASE 5: Create DispatchFinanceRecord for this device
                $this->createDispatchFinanceRecord($confirmedAffixed);
            }
        } catch (\Exception $e) {
            Log::error('Error in ConfirmedAffixedObserver.created', [
                'error' => $e->getMessage(),
                'confirmed_affixed_id' => $confirmedAffixed->id,
                'device_id' => $confirmedAffixed->device_id,
            ]);
        }
    }

    /**
     * Handle the ConfirmedAffixed "updated" event.
     */
    public function updated(ConfirmedAffixed $confirmedAffixed): void
    {
        try {
            // Sync with AssignToAgent when any field changes
            if ($confirmedAffixed->isDirty() && $confirmedAffixed->device_id) {
                AssignToAgent::where('device_id', $confirmedAffixed->device_id)
                    ->update([
                        'date' => $confirmedAffixed->date ?? now(),
                        'boe' => $confirmedAffixed->boe,
                        'sad_number' => $confirmedAffixed->sad_number,
                        'vehicle_number' => $confirmedAffixed->vehicle_number,
                        'regime' => $confirmedAffixed->regime,
                        'destination' => $confirmedAffixed->destination,
                        'route_id' => $confirmedAffixed->route_id,
                        'long_route_id' => $confirmedAffixed->long_route_id,
                        'manifest_date' => $confirmedAffixed->manifest_date,
                        'agency' => $confirmedAffixed->agency,
                        'agent_contact' => $confirmedAffixed->agent_contact,
                        'truck_number' => $confirmedAffixed->truck_number,
                        'driver_name' => $confirmedAffixed->driver_name,
                        'affixing_date' => $confirmedAffixed->affixing_date,
                        'status' => 'AFFIXED'
                    ]);

                // PHASE 5: Update or create DispatchFinanceRecord
                $this->createDispatchFinanceRecord($confirmedAffixed);
            }
        } catch (\Exception $e) {
            Log::error('Error in ConfirmedAffixedObserver.updated', [
                'error' => $e->getMessage(),
                'confirmed_affixed_id' => $confirmedAffixed->id,
                'device_id' => $confirmedAffixed->device_id,
            ]);
        }
    }

    /**
     * Handle the ConfirmedAffixed "deleted" event.
     */
    public function deleted(ConfirmedAffixed $confirmedAffixed): void
    {
        // Update AssignToAgent status when ConfirmedAffixed is deleted
        if ($confirmedAffixed->device_id) {
            AssignToAgent::where('device_id', $confirmedAffixed->device_id)
                ->update([
                    'affixing_date' => null,
                    'status' => 'ASSIGNED'
                ]);
        }
    }

    /**
     * Handle the AllocationPoint "created" event.
     */
    public function allocationPointCreated(AllocationPoint $allocationPoint): void
    {
        // Create corresponding ConfirmedAffixed with better title format
        ConfirmedAffixed::create([
            'date' => now(),
            'device_id' => null,
            'status' => 'PENDING'
        ]);
    }

    /**
     * Handle the AllocationPoint "deleted" event.
     */
    public function allocationPointDeleted(AllocationPoint $allocationPoint): void
    {
        // Delete corresponding assignments
        ConfirmedAffixed::where('device_id', null)->delete();
    }

    /**
     * PHASE 5: Create or update DispatchFinanceRecord for confirmed affixed devices
     * Checks if record exists before creating to avoid duplicates
     */
    private function createDispatchFinanceRecord(ConfirmedAffixed $confirmedAffixed): void
    {
        try {
            // Get the assignment with receipt
            $assignment = AssignToAgent::where('device_id', $confirmedAffixed->device_id)
                ->whereNotNull('receipt_id')
                ->first();

            if (!$assignment || !$assignment->receipt_id) {
                Log::info('No assignment with receipt for confirmed affixed device', [
                    'device_id' => $confirmedAffixed->device_id,
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

            // Check if DispatchFinanceRecord already exists for this device and receipt
            $exists = DispatchFinanceRecord::where('device_id', $confirmedAffixed->device_id)
                ->where('receipt_id', $receipt->id)
                ->exists();

            if ($exists) {
                Log::info('DispatchFinanceRecord already exists, skipping duplicate', [
                    'device_id' => $confirmedAffixed->device_id,
                    'receipt_id' => $receipt->id,
                ]);
                return;
            }

            // Create DispatchFinanceRecord
            $financeRecord = DispatchFinanceRecord::create([
                'receipt_id' => $receipt->id,
                'assigned_to_agent_id' => $assignment->id,
                'confirmed_affixed_id' => $confirmedAffixed->id,
                'device_id' => $confirmedAffixed->device_id,
                'dispatch_date' => $confirmedAffixed->date ?? now(),
                'total_amount_gmd' => $receipt->total_charge_gmd,
                'status' => 'PENDING',
                'finance_notes' => "Dispatch for receipt {$receipt->receipt_number} (Device: {$confirmedAffixed->device_id})",
                'created_by' => auth()->id() ?? 1,
            ]);

            Log::info('DispatchFinanceRecord created from ConfirmedAffixed', [
                'finance_record_id' => $financeRecord->id,
                'confirmed_affixed_id' => $confirmedAffixed->id,
                'receipt_id' => $receipt->id,
                'device_id' => $confirmedAffixed->device_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create DispatchFinanceRecord from ConfirmedAffixed', [
                'error' => $e->getMessage(),
                'confirmed_affixed_id' => $confirmedAffixed->id,
                'device_id' => $confirmedAffixed->device_id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
