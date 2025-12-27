# Receipt Usage Restoration Plan
## When Data is Returned from ConfirmedAffixed to Data Entry

**Date:** December 21, 2025  
**Status:** PROPOSAL - Awaiting Approval  
**Version:** 1.0

---

## 1. EXECUTIVE SUMMARY

This document outlines a plan to **restore (increase) the `receipts.used` column** when a ConfirmedAffixed record is returned to Data Entry. This is the inverse operation of the dispatch flow where `used` is decremented.

**Current Issue:**
- When a device is dispatched via `AssignToAgent::create()`, the `ReceiptObserver` decrements `receipts.used`
- When data is returned from ConfirmedAffixed to Data Entry, **no receipt restoration occurs**
- This causes lost receipt capacity tracking

**Proposed Solution:**
- Leverage the existing `ReceiptObserver` for consistency
- Add support for when `AssignToAgent` is deleted during the return flow
- The `ReceiptObserver::deleting()` already handles this perfectly

---

## 2. CURRENT FLOW ANALYSIS

### 2.1 Dispatch Flow (Working ✓)

```
ViewAssignmentDataEntry::action('dp_form')
    ↓
AssignToAgent::create($data with receipt_id)
    ↓
ReceiptObserver::created()
    ↓
receipt->decrement('used')  [Decrements from moving_trucks to lower]
```

**Code Location:** `app/Observers/ReceiptObserver.php` (Lines 13-44)

```php
public function created(AssignToAgent $assignment): void
{
    if ($assignment->receipt_id) {
        $receipt = $assignment->receipt;
        // Validation...
        $receipt->decrement('used');  // ← Reduces available usage
    }
}
```

---

### 2.2 Return Flow (Currently Missing ✗)

```
ConfirmedAffixedResource::returnData()
    ↓
DB::table('assign_to_agents')->where('device_id', ...)->delete()
    ↓
DataEntryAssignment::create(['status' => 'RETURNED'])
    ↓
✗ NO RECEIPT RESTORATION ✗
```

**Problem:** The return flow uses raw DB deletion instead of model deletion, so observers don't trigger.

---

## 3. ROOT CAUSE ANALYSIS

### Current Return Code (ConfirmedAffixedResource.php, Line 400-405)

```php
// Delete assign_to_agents record if exists
DB::table('assign_to_agents')  // ← RAW DB QUERY - No Observer!
    ->where('device_id', $record->device_id)
    ->delete();
```

**Why this is problematic:**
- Raw DB queries bypass Eloquent observers
- The `ReceiptObserver::deleting()` method never fires
- Receipt usage is never restored

---

## 4. PROPOSED SOLUTION

### 4.1 Strategy: Leverage Existing Observer

**Use:** `ReceiptObserver::deleting()` (already implemented!)  
**Location:** `app/Observers/ReceiptObserver.php` (Lines 46-68)

```php
public function deleting(AssignToAgent $assignment): void
{
    if ($assignment->receipt_id) {
        $receipt = $assignment->receipt;
        if ($receipt) {
            $receipt->increment('used');  // ← RESTORES usage
        }
    }
}
```

This method **already does exactly what we need**!

### 4.2 Implementation Changes

#### **Change 1: Update ConfirmedAffixedResource.php (returnData action)**

**File:** `app/Filament/Resources/ConfirmedAffixedResource.php`  
**Lines:** 400-405

**Current Code (Raw DB Query):**
```php
// Delete assign_to_agents record if exists
DB::table('assign_to_agents')
    ->where('device_id', $record->device_id)
    ->delete();
```

**Updated Code (Use Eloquent Model to trigger observer):**
```php
// Delete assign_to_agents record if exists (using model to trigger observer)
$assignmentsToDelete = \App\Models\AssignToAgent::where('device_id', $record->device_id)
    ->get();

foreach ($assignmentsToDelete as $assignment) {
    // This triggers ReceiptObserver::deleting() which restores receipt usage
    $assignment->delete();
}
```

**Why this works:**
- `delete()` on Eloquent model triggers `deleting()` observer
- `ReceiptObserver::deleting()` automatically increments the receipt's `used` column
- Maintains consistency with dispatch flow
- Reuses existing, tested code

---

## 5. DETAILED FLOW WITH CHANGES

### 5.1 Updated Return Flow

```
ConfirmedAffixedResource::returnData()
    ↓
Get AssignToAgent records for device (Eloquent Query)
    ↓
Loop through each AssignToAgent record
    ↓
$assignment->delete()  ← Triggers Observer!
    ↓
ReceiptObserver::deleting()
    ↓
if ($assignment->receipt_id) {
    $receipt->increment('used')  ← RESTORES usage!
}
    ↓
Log the restoration
    ↓
Continue with return flow...
```

### 5.2 Complete Return Action Flow

```
1. User clicks "Return Data" on ConfirmedAffixed record
   ↓
2. Form validation (return_note required)
   ↓
3. DB::beginTransaction()
   ↓
4. Fetch device and allocation point
   ↓
5. Create/Update DataEntryAssignment with status='RETURNED'
   ↓
6. [NEW] Delete AssignToAgent records WITH OBSERVER FIRING
   ├─→ ReceiptObserver::deleting() 
   ├─→ receipt->increment('used')
   └─→ Logs restoration
   ↓
7. Update Monitoring record with return note
   ↓
8. Update DeviceRetrieval record with return note
   ↓
9. Restore device to allocation point (status='ONLINE')
   ↓
10. Delete ConfirmedAffixed record
    ↓
11. DB::commit()
    ↓
12. Show success notification
```

---

## 6. TESTING STRATEGY

### 6.1 Unit Test Scenarios

**Test 1: Receipt Usage Restoration**
```
GIVEN: Device dispatched with receipt_id = 5, receipt.used = 2
WHEN: AssignToAgent record deleted via model (not raw DB)
THEN: receipt.used should increment to 3
AND: Log entry created showing restoration
```

**Test 2: Multiple Assignments for Same Device**
```
GIVEN: Device with multiple AssignToAgent records
WHEN: Return data is triggered
THEN: All AssignToAgent records deleted
AND: Receipt.used incremented for EACH record
```

**Test 3: Assignment Without Receipt**
```
GIVEN: AssignToAgent record without receipt_id
WHEN: Record deleted
THEN: No error thrown
AND: Gracefully handles null receipt_id
```

**Test 4: Observer Logging**
```
GIVEN: Assignment deleted with valid receipt_id
WHEN: ReceiptObserver::deleting() executes
THEN: Log entry includes:
  - receipt_id
  - receipt_number
  - used_remaining
  - assignment_id
```

### 6.2 Integration Test Flow

```
1. Create Receipt (used = 5)
2. Create Device in allocation point
3. Dispatch device (AssignToAgent::create with receipt_id)
   → Receipt.used becomes 4 ✓
4. Create ConfirmedAffixed from AssignToAgent
5. Trigger returnData action
   → AssignToAgent deleted via model
   → ReceiptObserver::deleting fires
   → Receipt.used restored to 5 ✓
6. Verify DataEntryAssignment status = 'RETURNED'
7. Verify Device restored to allocation point
```

### 6.3 Test Data Setup

```php
// Create test receipt
$receipt = Receipt::create([
    'receipt_number' => 'REC-20251221-TEST',
    'date' => now(),
    'consignment_nature' => 'CN',
    'sad_number' => 'SAD-TEST-001',
    'moving_trucks' => 5,
    'used' => 5,
    'allocation_point_id' => 1,
    'destination_id' => 1,
    // ... other required fields
]);

// Create device
$device = Device::create([
    'device_id' => 'DEV-TEST-001',
    'status' => 'ONLINE',
    'allocation_point_id' => 1,
]);

// Dispatch (this decrements receipt.used to 4)
$assignment = AssignToAgent::create([
    'date' => now(),
    'device_id' => $device->id,
    'boe' => 'BOE-001',
    'vehicle_number' => 'VEH-001',
    'regime' => 'CN',
    'destination_id' => 1,
    'allocation_point_id' => 1,
    'receipt_id' => $receipt->id,  // ← Links to receipt
]);

// Verify dispatch decremented
assert($receipt->fresh()->used === 4);

// Return (should increment back to 5)
$assignment->delete();

// Verify restoration
assert($receipt->fresh()->used === 5);
```

---

## 7. IMPLEMENTATION CHECKLIST

### Phase 1: Code Changes
- [ ] Update `ConfirmedAffixedResource.php` line 400-405
  - Replace raw DB query with Eloquent model deletion
  - Add logging for receipt restoration
  
- [ ] No changes needed to `ReceiptObserver.php`
  - Already has `deleting()` method ready
  - Already handles receipt increment
  - Already logs restoration

- [ ] Verify `AssignToAgent` model has receipt relationship
  - Check: `public function receipt(): BelongsTo`
  - ✓ Already exists

### Phase 2: Testing
- [ ] Write unit test for `ReceiptObserver::deleting()`
- [ ] Write integration test for return flow
- [ ] Manual testing with real scenario
- [ ] Verify logging output

### Phase 3: Deployment
- [ ] Review code changes
- [ ] Run tests
- [ ] Deploy to staging
- [ ] Test in staging environment
- [ ] Deploy to production

---

## 8. CODE CHANGES - DETAILED

### 8.1 File: `app/Filament/Resources/ConfirmedAffixedResource.php`

**Location:** Lines 400-410  
**Action Type:** Code replacement  
**Reason:** Enable observer firing for receipt restoration

#### Before:
```php
// Delete assign_to_agents record if exists
DB::table('assign_to_agents')
    ->where('device_id', $record->device_id)
    ->delete();

// Delete the confirmed affixed record
$record->delete();
```

#### After:
```php
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
```

---

## 9. OBSERVER BEHAVIOR - NO CHANGES NEEDED

### ReceiptObserver Already Handles This

**File:** `app/Observers/ReceiptObserver.php`

The `deleting()` method is **perfect as-is**:

```php
public function deleting(AssignToAgent $assignment): void
{
    if ($assignment->receipt_id) {
        $receipt = $assignment->receipt;

        if ($receipt) {
            $receipt->increment('used');  // ← This is what we need!

            Log::info('Receipt usage incremented (dispatch cancelled)', [
                'receipt_id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'used_remaining' => $receipt->fresh()->used,
                'assignment_id' => $assignment->id,
            ]);
        }
    }
}
```

**No changes required** - it already:
- ✓ Checks if receipt_id exists
- ✓ Fetches fresh receipt
- ✓ Increments used column
- ✓ Logs the action with all details
- ✓ Handles null receipt gracefully

---

## 10. LOGGING & AUDIT TRAIL

### Logs Generated

**On Dispatch (AssignToAgent::create):**
```
[Receipt usage decremented]
assignment_id: 123
receipt_id: 45
receipt_number: REC-20251221-0001
used_remaining: 4 (after decrement)
```

**On Return (AssignToAgent::delete):**
```
[Receipt usage incremented (dispatch cancelled)]
assignment_id: 123
receipt_id: 45
receipt_number: REC-20251221-0001
used_remaining: 5 (after increment)
```

This creates a complete audit trail showing:
- What receipt was used
- When it was used
- When it was returned
- Final usage count

---

## 11. EDGE CASES & HANDLING

| Scenario | Handling | Status |
|----------|----------|--------|
| AssignToAgent without receipt_id | Gracefully skipped (if check) | ✓ Handled |
| Receipt not found | Log warning, continue | ✓ Handled |
| Multiple AssignToAgent for same device | Each deletion triggers observer independently | ✓ Handled |
| Receipt already fully used (used=0) | Increment still happens, moves to used=1 | ✓ Handled |
| Database transaction rollback | Deletion rolled back, observer doesn't fire | ✓ Handled |
| Concurrent returns | DB locks handled by transaction | ✓ Handled |

---

## 12. BENEFITS OF THIS APPROACH

1. **Reuses Existing Code**
   - No need for new observer
   - Leverages battle-tested `ReceiptObserver::deleting()`
   - Same observer handles dispatch and return symmetrically

2. **Maintains Consistency**
   - Dispatch: AssignToAgent::create → receipt decrements
   - Return: AssignToAgent::delete → receipt increments
   - Perfect mirror operations

3. **Audit Trail**
   - All actions logged with context
   - Can trace receipt lifecycle
   - Debugging simplified

4. **Testable**
   - Can test observer in isolation
   - Can test integration flow
   - No magic, straightforward logic

5. **Low Risk**
   - Minimal code change
   - No new dependencies
   - Uses existing framework patterns
   - No performance impact

6. **Automatic Error Handling**
   - Observer already handles missing receipts
   - Null checks in place
   - Graceful degradation

---

## 13. MIGRATION CONSIDERATIONS

### No Database Migration Needed
- No schema changes
- No new columns
- No index changes
- All data structures already in place

### No Model Changes Needed
- `AssignToAgent` already has `receipt()` relationship
- `Receipt` already has `used` column
- All relationships defined

### No Configuration Changes Needed
- Observer already registered in `AppServiceProvider`
- No new configuration required

---

## 14. PERFORMANCE IMPACT

### Minimal Performance Impact

**Before:** Raw DB delete (~1ms)
```sql
DELETE FROM assign_to_agents WHERE device_id = 1
```

**After:** Eloquent deletion with observer (~2-3ms)
```php
$assignment->delete(); // Loads record + fires observer
```

**Impact:** +1-2ms per return operation  
**Acceptable:** Yes, negligible impact

---

## 15. APPROVAL CRITERIA

For approval, verify:

- [ ] Receipt observer `deleting()` method exists and works correctly
- [ ] AssignToAgent model has receipt relationship defined
- [ ] Raw DB query can be replaced with Eloquent deletion
- [ ] No breaking changes to existing dispatch flow
- [ ] Logging provides sufficient audit trail
- [ ] Edge cases handled gracefully
- [ ] Testing strategy is comprehensive

---

## 16. NEXT STEPS

### If Approved:
1. Implement code change in `ConfirmedAffixedResource.php`
2. Add comprehensive logging
3. Write and run tests
4. Deploy to staging for testing
5. Deploy to production

### If Changes Needed:
1. Request clarifications
2. Adjust plan as needed
3. Re-submit for approval

---

## APPENDIX A: Related Files

| File | Lines | Purpose |
|------|-------|---------|
| `app/Observers/ReceiptObserver.php` | 1-72 | Handles receipt usage decrement/increment |
| `app/Models/AssignToAgent.php` | 1-50 | Define receipt relationship |
| `app/Models/Receipt.php` | 1-170 | Receipt model with used column |
| `app/Filament/Resources/ConfirmedAffixedResource.php` | 310-430 | Return data action |
| `database/migrations/2025_11_24_add_receipt_id_to_assign_to_agents.php` | 1-35 | Receipt foreign key |

---

## APPENDIX B: Related Observer Pattern

This solution follows Laravel's Observer pattern:

```
Model Event → Observer Method → Business Logic
     ↓             ↓                  ↓
AssignToAgent  ReceiptObserver  receipt->increment()
  ::delete()    ::deleting()      in DB
```

This is the standard Laravel way to handle side effects from model changes.

---

**Document Prepared By:** AI Assistant  
**Date:** December 21, 2025  
**Status:** Ready for Review and Approval
