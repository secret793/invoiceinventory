# Receipt Usage Restoration - Visual Flow Diagrams

## 1. DISPATCH vs RETURN - SYMMETRIC OPERATIONS

```
╔═══════════════════════════════════════════════════════════════════════════╗
║                    DISPATCH FLOW (Currently Working)                      ║
╚═══════════════════════════════════════════════════════════════════════════╝

Data Entry Officer fills dispatch form
        ↓
Selects Device(s), Receipt, Destination, etc.
        ↓
Clicks "Dispatch Device(s)" button
        ↓
AssignToAgent::create([
    'device_id' => $deviceId,
    'receipt_id' => $receiptId,  ← RECEIPT LINKED
    'date' => now(),
    ...
])
        ↓
┌─────────────────────────────────────────────────────────────────┐
│ ReceiptObserver::created(AssignToAgent $assignment)             │
├─────────────────────────────────────────────────────────────────┤
│ 1. Check if $assignment->receipt_id exists                      │
│ 2. Fetch receipt: $receipt = $assignment->receipt               │
│ 3. Validate: if ($receipt->used <= 0) throw Exception          │
│ 4. DECREMENT: $receipt->decrement('used')                      │
│    Example: 5 → 4 (one truck slot consumed)                    │
│ 5. LOG: Receipt usage decremented from 5 to 4                  │
└─────────────────────────────────────────────────────────────────┘
        ↓
Device removed from allocation point
        ↓
ConfirmedAffixed record created
        ↓
Device awaits affixing
        ↓
Receipt now has 4 slots available (was 5)


╔═══════════════════════════════════════════════════════════════════════════╗
║           RETURN FLOW (Currently Missing Receipt Restoration)             ║
╚═══════════════════════════════════════════════════════════════════════════╝

Affixing Officer reviews data quality
        ↓
Finds issue or rejects for correction
        ↓
Clicks "Return Data" button
        ↓
Fills "Reason for Return" form
        ↓
Submits confirmation
        ↓
DB::beginTransaction()
        ↓
DataEntryAssignment::create(['status' => 'RETURNED'])
        ↓
[CURRENT - RAW DB QUERY - NO OBSERVER FIRING] ✗
DB::table('assign_to_agents')
    ->where('device_id', $device->device_id)
    ->delete();  ← LOSES OPPORTUNITY FOR RESTORATION
        ↓
✗ ReceiptObserver::deleting() NEVER FIRES ✗
✗ Receipt.used NEVER INCREMENTED BACK ✗
        ↓
Device restored to allocation point
        ↓
Status = 'ONLINE' again
        ↓
PROBLEM: Receipt still shows 4 available (should be 5)
         Lost slot capacity permanently!


╔═══════════════════════════════════════════════════════════════════════════╗
║         RETURN FLOW - WITH FIX (PROPOSED - Using Eloquent Model)         ║
╚═══════════════════════════════════════════════════════════════════════════╝

Affixing Officer reviews data quality
        ↓
Finds issue or rejects for correction
        ↓
Clicks "Return Data" button
        ↓
Fills "Reason for Return" form
        ↓
Submits confirmation
        ↓
DB::beginTransaction()
        ↓
DataEntryAssignment::create(['status' => 'RETURNED'])
        ↓
[NEW - ELOQUENT MODEL DELETION - OBSERVER FIRES!] ✓
$assignments = AssignToAgent::where('device_id', $deviceId)->get()
        ↓
foreach ($assignments as $assignment) {
    $assignment->delete();  ← TRIGGERS OBSERVER!
}
        ↓
┌─────────────────────────────────────────────────────────────────┐
│ ReceiptObserver::deleting(AssignToAgent $assignment)            │
├─────────────────────────────────────────────────────────────────┤
│ 1. Check if $assignment->receipt_id exists                      │
│ 2. Fetch receipt: $receipt = $assignment->receipt               │
│ 3. INCREMENT: $receipt->increment('used')                       │
│    Example: 4 → 5 (one truck slot returned)                    │
│ 4. LOG: Receipt usage incremented from 4 to 5                   │
└─────────────────────────────────────────────────────────────────┘
        ↓
Device restored to allocation point
        ↓
Status = 'ONLINE' again
        ↓
✓ Receipt restored to 5 available slots ✓
✓ Capacity tracking accurate ✓
```

---

## 2. STATE DIAGRAM - Receipt Lifecycle

```
┌─────────────────┐
│  Receipt Created│
│  used = 5       │
│  (5 slots avail)│
└────────┬────────┘
         │
         │ Device dispatched
         │ AssignToAgent::create()
         │ ReceiptObserver::created()
         ↓
┌─────────────────┐
│  After Dispatch │
│  used = 4       │
│  (4 slots avail)│
└────────┬────────┘
         │
         ├─────────────────────┬─────────────────────┐
         │                     │                     │
    Device OK            Data Rejected         Device Lost
    Continue             Affixing Issue        (No return)
         │                     │                     │
         ↓                     ↓                     ↓
   ┌──────────┐      ┌──────────────────┐   ┌─────────┐
   │ Continue │      │ Return to Data   │   │ Lost    │
   │ Processing│      │ Entry (Return Data)   │ Device  │
   └──────────┘      └────────┬─────────┘   └─────────┘
                               │
                               │ AssignToAgent::delete()
                               │ ReceiptObserver::deleting()
                               ↓
                      ┌─────────────────┐
                      │  After Return   │
                      │  used = 5       │
                      │ (5 slots restored)
                      └─────────────────┘
```

---

## 3. DETAILED RETURN ACTION SEQUENCE

```
Step 1: User Action
┌────────────────────────────────────────────┐
│ Affixing Officer clicks "Return Data"      │
│ on ConfirmedAffixed record                 │
└─────────┬──────────────────────────────────┘
          ↓

Step 2: Form Submission
┌────────────────────────────────────────────┐
│ Modal Form:                                │
│ - "Reason for Return" (required)           │
│ - Submit with confirmation                 │
└─────────┬──────────────────────────────────┘
          ↓

Step 3: Transaction Start
┌────────────────────────────────────────────┐
│ DB::beginTransaction()                     │
│ All-or-nothing operation                   │
└─────────┬──────────────────────────────────┘
          ↓

Step 4: DataEntryAssignment Status Update
┌────────────────────────────────────────────┐
│ Check existing DataEntryAssignment         │
│   - If exists: Update status = 'RETURNED'  │
│   - If not: Create new with RETURNED       │
└─────────┬──────────────────────────────────┘
          ↓

Step 5: AssignToAgent Restoration ⭐ KEY STEP
┌─────────────────────────────────────────────────────┐
│ [BEFORE - Raw Query]                                │
│ DB::table('assign_to_agents')                       │
│    ->where('device_id', $id)                        │
│    ->delete();  ← NO OBSERVER FIRE                  │
│                                                     │
│ [AFTER - Eloquent Model]                            │
│ $assignments = AssignToAgent                        │
│    ::where('device_id', $id)                        │
│    ->get();                                         │
│                                                     │
│ foreach ($assignments as $a) {                      │
│    $a->delete();  ← OBSERVER FIRES! ✓               │
│        │                                            │
│        └──→ ReceiptObserver::deleting()             │
│            └──→ receipt->increment('used')          │
│                └──→ used: 4 → 5                     │
│                └──→ Log restoration                 │
│                                                     │
│ Result: Receipt capacity restored! ✓                │
└─────────┬──────────────────────────────────────────┘
          ↓

Step 6: Monitoring Record Update
┌────────────────────────────────────────────┐
│ Update monitoring record:                  │
│ - Set note = return_reason                 │
└─────────┬──────────────────────────────────┘
          ↓

Step 7: DeviceRetrieval Update
┌────────────────────────────────────────────┐
│ Update device_retrieval record:            │
│ - Set note = return_reason                 │
└─────────┬──────────────────────────────────┘
          ↓

Step 8: Device Restoration
┌────────────────────────────────────────────┐
│ Update device:                             │
│ - allocation_point_id = original_point     │
│ - status = 'ONLINE'                        │
└─────────┬──────────────────────────────────┘
          ↓

Step 9: Cleanup ConfirmedAffixed
┌────────────────────────────────────────────┐
│ Delete ConfirmedAffixed record             │
│ (Device no longer in affixing state)       │
└─────────┬──────────────────────────────────┘
          ↓

Step 10: Transaction Commit
┌────────────────────────────────────────────┐
│ DB::commit()                               │
│ All changes persisted or rollback if error │
└─────────┬──────────────────────────────────┘
          ↓

Step 11: User Notification
┌────────────────────────────────────────────┐
│ Success notification sent:                 │
│ "Data returned to data entry successfully" │
└────────────────────────────────────────────┘
```

---

## 4. OBSERVER FIRING PATTERN

```
EVENT CHAIN: Dispatch

┌──────────────────────────────┐
│ AssignToAgent::create()       │ ← User action
└────────────┬─────────────────┘
             │
             ↓
        ┌────────────────────────────────┐
        │ Eloquent Model Event           │
        │ Fires "creating" then "created"│
        └────────────┬───────────────────┘
                     │
                     ↓
    ┌─────────────────────────────────────┐
    │ ReceiptObserver::created()           │ ← FIRES!
    ├─────────────────────────────────────┤
    │ Check receipt_id exists             │
    │ Fetch receipt                       │
    │ receipt->decrement('used')          │
    │ Log: "Receipt usage decremented"    │
    └─────────────────────────────────────┘
                     ↓
             used: 5 → 4 ✓


EVENT CHAIN: Return (After Fix)

┌──────────────────────────────┐
│ $assignment->delete()         │ ← Code change
└────────────┬─────────────────┘
             │
             ↓
        ┌────────────────────────────────┐
        │ Eloquent Model Event           │
        │ Fires "deleting" then "deleted"│
        └────────────┬───────────────────┘
                     │
                     ↓
    ┌─────────────────────────────────────┐
    │ ReceiptObserver::deleting()          │ ← FIRES!
    ├─────────────────────────────────────┤
    │ Check receipt_id exists             │
    │ Fetch receipt                       │
    │ receipt->increment('used')          │
    │ Log: "Receipt usage incremented"    │
    └─────────────────────────────────────┘
                     ↓
             used: 4 → 5 ✓
```

---

## 5. TESTING SCENARIO - VISUAL

```
TEST: Single Device Return with Receipt Restoration

T0: Setup
    Receipt: REC-001, used = 5
    Device: DEV-001, status = ONLINE, allocation_point = AP1
    
    ├─── receipt ─────────────────────────┐
    │     id: 1                           │
    │     receipt_number: REC-001         │
    │     used: 5  ← Initial               │
    └─────────────────────────────────────┘


T1: Dispatch
    Dispatch DEV-001 using REC-001
    
    AssignToAgent::create([
        device_id => 1,
        receipt_id => 1,
        ...
    ])
    
    ↓ ReceiptObserver::created() fires ↓
    receipt->decrement('used')
    
    Result:
    ├─── receipt ─────────────────────────┐
    │     id: 1                           │
    │     receipt_number: REC-001         │
    │     used: 4  ← Decremented ✓        │
    └─────────────────────────────────────┘
    
    ├─── assign_to_agents ───────────────┐
    │     id: 1                           │
    │     device_id: 1                    │
    │     receipt_id: 1  ← Linked ✓       │
    └─────────────────────────────────────┘
    
    ├─── devices ────────────────────────┐
    │     id: 1                           │
    │     device_id: DEV-001              │
    │     allocation_point_id: null ← Out │
    └─────────────────────────────────────┘


T2: Create ConfirmedAffixed
    ConfirmedAffixed::create([
        device_id => 1,
        status => PENDING,
        ...
    ])


T3: Return to Data Entry
    Click "Return Data"
    Provide reason: "Data quality issue"
    
    ↓ Code Change Executes ↓
    $assignment = AssignToAgent::find(1)
    $assignment->delete()
    
    ↓ ReceiptObserver::deleting() fires ↓
    receipt->increment('used')
    
    Result:
    ├─── receipt ─────────────────────────┐
    │     id: 1                           │
    │     receipt_number: REC-001         │
    │     used: 5  ← RESTORED! ✓          │
    └─────────────────────────────────────┘
    
    ├─── assign_to_agents ───────────────┐
    │     DELETED ✓                       │
    └─────────────────────────────────────┘
    
    ├─── data_entry_assignments ─────────┐
    │     id: 1                           │
    │     status: RETURNED  ← Created ✓   │
    │     notes: Data quality issue ✓     │
    └─────────────────────────────────────┘
    
    ├─── devices ────────────────────────┐
    │     id: 1                           │
    │     device_id: DEV-001              │
    │     allocation_point_id: 1 ← Restored │
    │     status: ONLINE                  │
    └─────────────────────────────────────┘

OUTCOME: ✓ Receipt capacity fully restored!
         ✓ Device back in allocation point!
         ✓ Data entry officer can correct and re-dispatch!
```

---

## 6. IMPACT MAP

```
┌─────────────────────────────────────────────────────────────────┐
│                    CODE CHANGE IMPACT                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  File: ConfirmedAffixedResource.php (Lines 400-405)            │
│  ├─ Change: Raw DB query → Eloquent model deletion            │
│  ├─ Risk: LOW (established pattern)                           │
│  └─ Tests: Can test observer in isolation                     │
│                                                                 │
│  Impacted Files:                                              │
│  ├─ ReceiptObserver.php (no changes, already works)          │
│  ├─ AssignToAgent model (no changes, relationships exist)     │
│  ├─ Receipt model (no changes, increment works as-is)         │
│  └─ No database migrations needed                             │
│                                                                 │
│  Observable Behaviors:                                         │
│  ├─ Receipt.used increases when AssignToAgent deleted         │
│  ├─ Logs show restoration entry                               │
│  ├─ Device returns to allocation point                        │
│  └─ DataEntryAssignment status becomes 'RETURNED'             │
│                                                                 │
│  Performance Impact:                                           │
│  ├─ Before: Raw delete ~1ms                                   │
│  ├─ After: Eloquent delete ~2-3ms                             │
│  └─ Impact: Negligible, <2ms additional per return             │
│                                                                 │
│  Database Impact:                                              │
│  ├─ No schema changes                                          │
│  ├─ No new indexes                                             │
│  ├─ No migration required                                      │
│  └─ All existing tables leveraged                             │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. VALIDATION CHECKLIST

```
BEFORE IMPLEMENTATION - VERIFY:

□ ReceiptObserver::deleting() exists
  Location: app/Observers/ReceiptObserver.php (Line 46-68)
  Check: Has receipt->increment('used') ✓

□ AssignToAgent has receipt relationship
  Location: app/Models/AssignToAgent.php
  Check: public function receipt(): BelongsTo ✓

□ Receipt model has used column
  Location: app/Models/Receipt.php
  Check: Column definition in schema ✓

□ Observer is registered
  Location: app/Providers/AppServiceProvider.php
  Check: AssignToAgent::observe(ReceiptObserver::class) ✓

□ No migrations needed
  Check: All relationships already defined ✓

AFTER IMPLEMENTATION - VERIFY:

□ Logs show receipt restoration
  Check: "Receipt usage incremented" in logs

□ Receipt.used value increases
  Check: Database query shows updated value

□ Device back in allocation point
  Check: allocation_point_id is set

□ DataEntryAssignment created
  Check: status = 'RETURNED', notes populated

□ No database errors
  Check: Transaction commits successfully

□ No observer errors
  Check: No exceptions thrown
```

---

**Diagram Version:** 1.0  
**Created:** December 21, 2025  
**Status:** Ready for Review
