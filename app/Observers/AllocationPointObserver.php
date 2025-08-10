<?php

namespace App\Observers;

use App\Models\AllocationPoint;
use App\Models\DataEntryAssignment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class AllocationPointObserver
{
    /**
     * Handle the AllocationPoint "created" event.
     */
    public function created(AllocationPoint $allocationPoint): void
    {
        // Prevent duplicate DataEntryAssignment for the same allocation point
        if (DataEntryAssignment::where('allocation_point_id', $allocationPoint->id)->exists()) {
            Log::warning("Duplicate DataEntryAssignment prevented for allocation point: {$allocationPoint->name}");
            return;
        }
        // Create DataEntryAssignment
        try {
            DataEntryAssignment::create([
                'title' => $allocationPoint->name . ' - Data Entry',
                'description' => 'Data entry assignment for ' . $allocationPoint->name,
                'allocation_point_id' => $allocationPoint->id,
                'status' => 'PENDING',
                'user_id' => auth()->id()
            ]);

            // Create permissions for the Data Entry Officer
            $this->createPermissions($allocationPoint->name);

            Log::info("Created DataEntryAssignment for allocation point: {$allocationPoint->name}");
        } catch (\Exception $e) {
            Log::error("Failed to create DataEntryAssignment: " . $e->getMessage());
        }
    }

    /**
     * Handle the AllocationPoint "updated" event.
     */
    public function updated(AllocationPoint $allocationPoint): void
    {
        // Update corresponding DataEntryAssignment if allocation point name changes
        if ($allocationPoint->isDirty('name')) {
            DataEntryAssignment::where('allocation_point_id', $allocationPoint->id)
                ->update([
                    'title' => $allocationPoint->name . ' - Data Entry',
                    'description' => 'Data entry assignment for ' . $allocationPoint->name
                ]);

            Log::info("Updated DataEntryAssignment for allocation point: {$allocationPoint->name}");
        }
    }

    /**
     * Handle the AllocationPoint "deleted" event.
     */
    public function deleted(AllocationPoint $allocationPoint): void
    {
        // Delete corresponding DataEntryAssignment
        DataEntryAssignment::where('allocation_point_id', $allocationPoint->id)->delete();

        Log::info("Deleted DataEntryAssignment for allocation point: {$allocationPoint->name}");
    }

    private function createPermissions(string $name): void
    {
        $permissions = [
            'view_allocationpoint_' . Str::slug($name),
            'edit_allocationpoint_' . Str::slug($name),
            'delete_allocationpoint_' . Str::slug($name),
            'view_data_entry_' . Str::slug($name),
            'edit_data_entry_' . Str::slug($name),
            'delete_data_entry_' . Str::slug($name),
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
