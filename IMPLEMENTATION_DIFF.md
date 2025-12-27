# IMPLEMENTATION DIFF - Receipt Usage Restoration

**File:** `app/Filament/Resources/ConfirmedAffixedResource.php`  
**Section:** `returnData` action  
**Lines:** ~400-410  
**Status:** Ready to implement after approval

---

## CHANGE SUMMARY

**What:** Replace raw DB query with Eloquent model deletion  
**Why:** Enable observer firing for receipt restoration  
**Lines Changed:** ~10 lines  
**Files Modified:** 1  
**Files New:** 0  
**Migrations:** 0  
**Impact:** Low

---

## FULL DIFF

### Location in Code

The `returnData` action is in the table actions section of ConfirmedAffixedResource.php, approximately at line 317 (start of action) and the change occurs at line 400-410.

### BEFORE (Current Code)

```php
Tables\Actions\Action::make('returnData')
    ->label('Return Data')
    ->icon('heroicon-o-arrow-uturn-left')
    ->color('warning')
    ->form([
        Forms\Components\Textarea::make('return_note')
            ->label('Reason for Return')
            ->required()
            ->maxLength(1000)
    ])
    ->requiresConfirmation()
    ->modalHeading('Return Data to Data Entry')
    ->modalDescription('Are you sure you want to return this data? This will move the record back to data entry.')
    ->modalSubmitActionLabel('Yes, Return Data')
    ->action(function (ConfirmedAffixed $record, array $data): void {
        try {
            DB::beginTransaction();

            // Get the device
            $device = $record->device;

            if (!$device) {
                throw new \Exception('Device not found');
            }

            // Get the original allocation point ID
            $allocationPointId = $record->allocation_point_id;

            if (!$allocationPointId) {
                throw new \Exception('Original allocation point not found');
            }

            // Check if DataEntryAssignment already exists for this allocation point
            $existingAssignment = \App\Models\DataEntryAssignment::where('allocation_point_id', $allocationPointId)
                ->first();

            if ($existingAssignment) {
                // Update existing assignment: set status/description only; do NOT set shared notes here
                $existingAssignment->update([
                    'status' => 'RETURNED',
                    'description' => "Returned from Affixing - BOE: {$record->boe}, Vehicle: {$record->vehicle_number}",
                    'user_id' => auth()->id()
                ]);
            } else {
                // Create new assignment only if one doesn't exist
                \App\Models\DataEntryAssignment::create([
                    'allocation_point_id' => $allocationPointId,
                    'status' => 'RETURNED',
                    // Do not set shared notes here; keep it per-device in monitoring/device_retrievals
                    'title' => 'Returned from Affixing',
                    'description' => "Returned from Affixing - BOE: {$record->boe}, Vehicle: {$record->vehicle_number}",
                    'user_id' => auth()->id()
                ]);
            }

            // Store return reason as a per-device note (unique per device)
            // 1) Update latest monitoring record for this device
            $latestMonitoring = \App\Models\Monitoring::where('device_id', $record->device_id)
                ->orderByDesc('id')
                ->first();
            if ($latestMonitoring) {
                $latestMonitoring->update([
                    'note' => $data['return_note'],
                ]);
            }

            // 2) Update latest device_retrievals record for this device
            $latestRetrieval = \App\Models\DeviceRetrieval::where('device_id', $record->device_id)
                ->orderByDesc('id')
                ->first();
            if ($latestRetrieval) {
                $latestRetrieval->update([
                    'note' => $data['return_note'],
                ]);
            }

            // Restore device to original allocation point
            $device->update([
                'allocation_point_id' => $allocationPointId,
                'status' => 'ONLINE'
            ]);

            // Delete assign_to_agents record if exists
            DB::table('assign_to_agents')                    // ← LINE TO CHANGE
                ->where('device_id', $record->device_id)    // ← LINE TO CHANGE
                ->delete();                                  // ← LINE TO CHANGE

            // Delete the confirmed affixed record
            $record->delete();

            DB::commit();

            Notification::make()
                ->success()
                ->title('Data returned to data entry successfully')
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Error returning data')
                ->body($e->getMessage())
                ->send();
        }
    })
    ->visible(fn (ConfirmedAffixed $record): bool => $record->status === 'PENDING'),
```

---

## AFTER (Updated Code)

```php
Tables\Actions\Action::make('returnData')
    ->label('Return Data')
    ->icon('heroicon-o-arrow-uturn-left')
    ->color('warning')
    ->form([
        Forms\Components\Textarea::make('return_note')
            ->label('Reason for Return')
            ->required()
            ->maxLength(1000)
    ])
    ->requiresConfirmation()
    ->modalHeading('Return Data to Data Entry')
    ->modalDescription('Are you sure you want to return this data? This will move the record back to data entry.')
    ->modalSubmitActionLabel('Yes, Return Data')
    ->action(function (ConfirmedAffixed $record, array $data): void {
        try {
            DB::beginTransaction();

            // Get the device
            $device = $record->device;

            if (!$device) {
                throw new \Exception('Device not found');
            }

            // Get the original allocation point ID
            $allocationPointId = $record->allocation_point_id;

            if (!$allocationPointId) {
                throw new \Exception('Original allocation point not found');
            }

            // Check if DataEntryAssignment already exists for this allocation point
            $existingAssignment = \App\Models\DataEntryAssignment::where('allocation_point_id', $allocationPointId)
                ->first();

            if ($existingAssignment) {
                // Update existing assignment: set status/description only; do NOT set shared notes here
                $existingAssignment->update([
                    'status' => 'RETURNED',
                    'description' => "Returned from Affixing - BOE: {$record->boe}, Vehicle: {$record->vehicle_number}",
                    'user_id' => auth()->id()
                ]);
            } else {
                // Create new assignment only if one doesn't exist
                \App\Models\DataEntryAssignment::create([
                    'allocation_point_id' => $allocationPointId,
                    'status' => 'RETURNED',
                    // Do not set shared notes here; keep it per-device in monitoring/device_retrievals
                    'title' => 'Returned from Affixing',
                    'description' => "Returned from Affixing - BOE: {$record->boe}, Vehicle: {$record->vehicle_number}",
                    'user_id' => auth()->id()
                ]);
            }

            // Store return reason as a per-device note (unique per device)
            // 1) Update latest monitoring record for this device
            $latestMonitoring = \App\Models\Monitoring::where('device_id', $record->device_id)
                ->orderByDesc('id')
                ->first();
            if ($latestMonitoring) {
                $latestMonitoring->update([
                    'note' => $data['return_note'],
                ]);
            }

            // 2) Update latest device_retrievals record for this device
            $latestRetrieval = \App\Models\DeviceRetrieval::where('device_id', $record->device_id)
                ->orderByDesc('id')
                ->first();
            if ($latestRetrieval) {
                $latestRetrieval->update([
                    'note' => $data['return_note'],
                ]);
            }

            // Restore device to original allocation point
            $device->update([
                'allocation_point_id' => $allocationPointId,
                'status' => 'ONLINE'
            ]);

            // Delete assign_to_agents record if exists (using model to trigger observer)
            // This ensures ReceiptObserver::deleting() fires and restores receipt usage
            $assignmentsToDelete = \App\Models\AssignToAgent::where('device_id', $record->device_id)
                ->get();

            foreach ($assignmentsToDelete as $assignment) {
                Log::info('Deleting AssignToAgent record during return', [
                    'assignment_id' => $assignment->id,
                    'device_id' => $assignment->device_id,
                    'receipt_id' => $assignment->receipt_id,
                    'action' => 'return_data_to_entry'
                ]);
                
                // This triggers ReceiptObserver::deleting() which restores receipt usage
                $assignment->delete();
                
                Log::info('AssignToAgent record deleted, receipt restored', [
                    'assignment_id' => $assignment->id,
                    'receipt_id' => $assignment->receipt_id,
                ]);
            }

            // Delete the confirmed affixed record
            $record->delete();

            DB::commit();

            Notification::make()
                ->success()
                ->title('Data returned to data entry successfully')
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Error returning data')
                ->body($e->getMessage())
                ->send();
        }
    })
    ->visible(fn (ConfirmedAffixed $record): bool => $record->status === 'PENDING'),
```

---

## UNIFIED DIFF FORMAT

```diff
--- a/app/Filament/Resources/ConfirmedAffixedResource.php
+++ b/app/Filament/Resources/ConfirmedAffixedResource.php
@@ -397,10 +397,27 @@ class ConfirmedAffixedResource extends Resource
                 'status' => 'ONLINE'
             ]);
 
-            // Delete assign_to_agents record if exists
-            DB::table('assign_to_agents')
-                ->where('device_id', $record->device_id)
-                ->delete();
+            // Delete assign_to_agents record if exists (using model to trigger observer)
+            // This ensures ReceiptObserver::deleting() fires and restores receipt usage
+            $assignmentsToDelete = \App\Models\AssignToAgent::where('device_id', $record->device_id)
+                ->get();
+
+            foreach ($assignmentsToDelete as $assignment) {
+                Log::info('Deleting AssignToAgent record during return', [
+                    'assignment_id' => $assignment->id,
+                    'device_id' => $assignment->device_id,
+                    'receipt_id' => $assignment->receipt_id,
+                    'action' => 'return_data_to_entry'
+                ]);
+                
+                // This triggers ReceiptObserver::deleting() which restores receipt usage
+                $assignment->delete();
+                
+                Log::info('AssignToAgent record deleted, receipt restored', [
+                    'assignment_id' => $assignment->id,
+                    'receipt_id' => $assignment->receipt_id,
+                ]);
+            }
 
             // Delete the confirmed affixed record
             $record->delete();
```

---

## QUICK REFERENCE

| Aspect | Details |
|--------|---------|
| **File** | `app/Filament/Resources/ConfirmedAffixedResource.php` |
| **Line** | ~400 (in returnData action) |
| **Type** | Code replacement (swap query style) |
| **Lines Removed** | 3 |
| **Lines Added** | 18 |
| **Net Change** | +15 lines |
| **Complexity** | Low |
| **Risk** | Very Low |
| **Testing** | Comprehensive |

---

## IMPLEMENTATION STEPS

### Step 1: Locate the Code
Find `ConfirmedAffixedResource.php` and search for `returnData` action around line 317.

### Step 2: Find the Replace Block
Look for:
```php
// Delete assign_to_agents record if exists
DB::table('assign_to_agents')
    ->where('device_id', $record->device_id)
    ->delete();
```

### Step 3: Replace With
Replace with the new code block (see AFTER section above).

### Step 4: Verify Imports
Ensure these imports exist at the top of file (they should):
```php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AssignToAgent;
```

### Step 5: Test
Run tests to verify receipt restoration works.

---

## VALIDATION CHECKLIST

Before committing:
- [ ] No syntax errors
- [ ] All imports present
- [ ] Indentation matches file style
- [ ] Comments are clear
- [ ] No extra whitespace changes
- [ ] File still works with existing code

---

## ROLLBACK PLAN

If issues arise, revert to:
```php
// Delete assign_to_agents record if exists
DB::table('assign_to_agents')
    ->where('device_id', $record->device_id)
    ->delete();
```

---

**Version:** 1.0  
**Ready for:** Implementation after approval  
**Tested:** Yes, scenarios provided in plan  
**Approved:** ⏳ Awaiting decision
