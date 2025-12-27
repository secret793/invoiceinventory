<?php

namespace App\Observers;

use App\Models\DataEntryAssignment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DataEntryAssignmentObserver
{
    /**
     * Handle the DataEntryAssignment "creating" event.
     * Set default values and prepare for duplicate handling
     */
    public function creating(DataEntryAssignment $dataEntryAssignment): void
    {
        Log::info('DataEntryAssignmentObserver: creating event triggered', [
            'allocation_point_id' => $dataEntryAssignment->allocation_point_id,
            'status' => $dataEntryAssignment->status,
            'title' => $dataEntryAssignment->title
        ]);

        // Set show_in_menu to true for new assignments
        $dataEntryAssignment->show_in_menu = true;
    }

    /**
     * Handle the DataEntryAssignment "created" event.
     * Handle duplicate prevention and merge with existing assignments
     */
    public function created(DataEntryAssignment $dataEntryAssignment): void
    {
        Log::info('DataEntryAssignmentObserver: created event triggered', [
            'id' => $dataEntryAssignment->id,
            'allocation_point_id' => $dataEntryAssignment->allocation_point_id,
            'status' => $dataEntryAssignment->status
        ]);

        // Check for existing assignments for this allocation point (excluding the current one)
        $existingAssignment = DataEntryAssignment::where('allocation_point_id', $dataEntryAssignment->allocation_point_id)
            ->where('id', '!=', $dataEntryAssignment->id)
            ->first();

        if ($existingAssignment) {
            Log::info('DataEntryAssignmentObserver: Found existing assignment, merging data', [
                'existing_id' => $existingAssignment->id,
                'new_id' => $dataEntryAssignment->id,
                'existing_status' => $existingAssignment->status,
                'new_status' => $dataEntryAssignment->status
            ]);

            // Merge the data into the existing assignment
            $existingAssignment->update([
                'status' => $dataEntryAssignment->status,
                // Notes should be unique per new transfer/return. Do NOT carry over old notes.
                // If the new record has a note (e.g., return reason), use it; otherwise clear to null.
                'notes' => $dataEntryAssignment->notes ?: null,
                'description' => $this->mergeDescriptions($existingAssignment->description, $dataEntryAssignment->description),
                'title' => $dataEntryAssignment->title ?: $existingAssignment->title,
                'user_id' => $dataEntryAssignment->user_id ?: $existingAssignment->user_id,
                'show_in_menu' => true, // Ensure it shows in menu
                'updated_at' => now()
            ]);

            // Delete the duplicate record that was just created
            $dataEntryAssignment->delete();

            Log::info('DataEntryAssignmentObserver: Merged and deleted duplicate assignment', [
                'deleted_id' => $dataEntryAssignment->id,
                'kept_id' => $existingAssignment->id
            ]);

            return;
        }

        // If no existing assignment, manage duplicates normally
        $this->manageDuplicatesForAllocationPoint($dataEntryAssignment->allocation_point_id);
    }

    /**
     * Handle the DataEntryAssignment "updated" event.
     * Manage menu visibility when assignments are updated
     */
    public function updated(DataEntryAssignment $dataEntryAssignment): void
    {
        Log::debug('DataEntryAssignmentObserver: updated event triggered', [
            'id' => $dataEntryAssignment->id,
            'allocation_point_id' => $dataEntryAssignment->allocation_point_id,
            'status' => $dataEntryAssignment->status,
            'changes' => $dataEntryAssignment->getChanges()
        ]);

        // If status changed, re-evaluate menu visibility
        if ($dataEntryAssignment->wasChanged('status')) {
            $this->manageDuplicatesForAllocationPoint($dataEntryAssignment->allocation_point_id);
        }
    }

    /**
     * Handle the DataEntryAssignment "deleted" event.
     * Re-evaluate menu visibility for the allocation point
     */
    public function deleted(DataEntryAssignment $dataEntryAssignment): void
    {
        Log::info('DataEntryAssignmentObserver: deleted event triggered', [
            'id' => $dataEntryAssignment->id,
            'allocation_point_id' => $dataEntryAssignment->allocation_point_id
        ]);

        $this->manageDuplicatesForAllocationPoint($dataEntryAssignment->allocation_point_id);
    }

    /**
     * Manage duplicates for a specific allocation point
     * Ensures only one assignment shows in menu per allocation point
     */
    private function manageDuplicatesForAllocationPoint(int $allocationPointId): void
    {
        try {
            // Get all assignments for this allocation point
            $assignments = DataEntryAssignment::where('allocation_point_id', $allocationPointId)
                ->orderBy('status') // Non-RETURNED status first
                ->orderBy('created_at', 'desc') // Most recent first
                ->get();

            if ($assignments->count() <= 1) {
                // No duplicates, ensure the single assignment shows in menu
                if ($assignments->count() === 1) {
                    $assignments->first()->update(['show_in_menu' => true]);
                }
                return;
            }

            Log::info('DataEntryAssignmentObserver: Managing duplicates', [
                'allocation_point_id' => $allocationPointId,
                'total_assignments' => $assignments->count()
            ]);

            // Determine which assignment should show in menu
            $preferredAssignment = $assignments->sortBy([
                fn ($a) => $a->status === 'RETURNED' ? 1 : 0, // Non-RETURNED first
                fn ($a) => -$a->created_at->timestamp // Most recent first
            ])->first();

            // Update menu visibility
            foreach ($assignments as $assignment) {
                $showInMenu = $assignment->id === $preferredAssignment->id;
                
                if ($assignment->show_in_menu !== $showInMenu) {
                    $assignment->update(['show_in_menu' => $showInMenu]);
                    
                    Log::info('DataEntryAssignmentObserver: Updated menu visibility', [
                        'assignment_id' => $assignment->id,
                        'show_in_menu' => $showInMenu,
                        'status' => $assignment->status
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('DataEntryAssignmentObserver: Error managing duplicates', [
                'allocation_point_id' => $allocationPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Merge notes from existing and new assignments
     */
    private function mergeNotes(?string $existingNotes, ?string $newNotes): ?string
    {
        if (empty($newNotes)) {
            return $existingNotes;
        }

        if (empty($existingNotes)) {
            return $newNotes;
        }

        return $existingNotes . "\n\n--- Updated " . now()->format('Y-m-d H:i:s') . " ---\n" . $newNotes;
    }

    /**
     * Merge descriptions from existing and new assignments
     */
    private function mergeDescriptions(?string $existingDescription, ?string $newDescription): ?string
    {
        if (empty($newDescription)) {
            return $existingDescription;
        }

        if (empty($existingDescription)) {
            return $newDescription;
        }

        // If new description is different, append it
        if ($existingDescription !== $newDescription) {
            return $existingDescription . "\n" . $newDescription;
        }

        return $existingDescription;
    }
}
