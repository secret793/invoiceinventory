# Route Validation Implementation

## Summary
Added validation to receipt generation forms to ensure that **either Route OR Long Route must be selected** before the form can be submitted.

## Problem Identified
Previously, both `route_id` and `long_route_id` fields were optional in the receipt generation forms, allowing users to submit receipts without selecting any route, which would result in invalid receipt records.

## Solution Implemented

### Files Modified
1. **`app/Filament/Resources/DataEntryAssignmentResource/Pages/ViewAssignmentDataEntry.php`**
   - Lines 1370-1451 (Route Selection section)
   
2. **`app/Filament/Resources/ReceiptResource.php`**
   - Lines 76-117 (Route Selection section)

### Changes Made

#### 1. Updated Section Description
```php
Section::make('Route Selection')
    ->description('Select either Route OR Long Route (at least one is required)')
```

#### 2. Added Custom Validation Rules
Both `route_id` and `long_route_id` fields now have custom validation:

```php
->rules([
    fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
        if (empty($value) && empty($get('long_route_id'))) {  // or 'route_id' for long_route_id
            $fail('Either Route or Long Route must be selected.');
        }
    },
])
->validationMessages([
    'required' => 'Either Route or Long Route must be selected.',
])
```

#### 3. Maintained Reactive Behavior
- When a route is selected, it auto-populates pricing and clears the other route field
- All existing functionality (exchange rate calculation, billing unit setting, etc.) remains intact

## How It Works

### Validation Logic
- If **Route** is empty AND **Long Route** is empty → ❌ Form submission blocked
- If **Route** is selected OR **Long Route** is selected → ✅ Form can be submitted
- If both are selected → The last one selected takes precedence (auto-clears the other)

### User Experience
1. User opens receipt generation form
2. User must select either:
   - **Route** (Short Route) - triggers short route pricing
   - **Long Route** - triggers long route pricing
3. If user tries to submit without selecting either:
   - Validation error appears: "Either Route or Long Route must be selected."
   - Form submission is blocked
4. User selects a route and can proceed

## Testing Checklist
- [ ] Try submitting receipt form without selecting any route
- [ ] Verify error message appears: "Either Route or Long Route must be selected."
- [ ] Select a Route and verify form submits successfully
- [ ] Select a Long Route and verify form submits successfully
- [ ] Verify that selecting one route clears the other
- [ ] Verify pricing calculations still work correctly

## Benefits
✅ **Data Integrity** - Prevents invalid receipts without route information  
✅ **User Guidance** - Clear error messages guide users to correct the issue  
✅ **Business Logic** - Ensures billing can be calculated properly  
✅ **Database Constraints** - Aligns with business rules requiring route selection

## Related Files
- `app/Models/Receipt.php` - Receipt model with route relationships
- `database/migrations/2025_11_23_create_receipts_table.php` - Receipts table structure
- Both routes are nullable in database but now required by form validation

## Date Implemented
January 11, 2026
