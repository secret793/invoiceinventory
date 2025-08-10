# Manual Overstay Testing Commands

## Quick Test Commands

### 1. Create Test Data
```bash
php artisan tinker --execute="require 'test_overstay_data.php';"
```

### 2. Debug All Devices
```bash
php artisan debug:overstay-days
```

### 3. Update Overstay Days
```bash
php artisan app:update-overdue-hours
```

### 4. Update Overstay Amounts
```bash
php artisan app:update-overstay-amounts
```

### 5. Test Specific Devices
```bash
# Test Case 1: 3 days overstay (normal route) - Expected: D2000
php artisan debug:overstay-days TEST-OVERSTAY-001

# Test Case 2: 5 days overstay (long route) - Expected: D4000  
php artisan debug:overstay-days TEST-OVERSTAY-002

# Test Case 3: No overstay - Expected: D0
php artisan debug:overstay-days TEST-OVERSTAY-003

# Test Case 4: Affixing date from monitoring - Expected: D4000
php artisan debug:overstay-days TEST-OVERSTAY-004
```

## Manual Database Queries

### Check DeviceRetrieval Records
```sql
SELECT 
    id,
    device_id,
    affixing_date,
    long_route_id,
    overstay_days,
    overstay_amount,
    payment_status
FROM device_retrievals 
WHERE device_id IN (
    SELECT id FROM devices WHERE device_id LIKE 'TEST-OVERSTAY-%'
);
```

### Check Monitoring Records
```sql
SELECT 
    id,
    device_id,
    affixing_date,
    overstay_days,
    current_date
FROM monitorings 
WHERE device_id IN (
    SELECT id FROM devices WHERE device_id LIKE 'TEST-OVERSTAY-%'
);
```

### Manual Overstay Calculation
```sql
SELECT 
    dr.id as device_retrieval_id,
    d.device_id,
    dr.affixing_date as dr_affixing_date,
    m.affixing_date as monitoring_affixing_date,
    COALESCE(dr.affixing_date, m.affixing_date) as effective_affixing_date,
    dr.long_route_id,
    CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END as grace_period,
    DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, m.affixing_date))) as days_diff,
    GREATEST(0, DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, m.affixing_date))) - CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END) as calculated_overstay_days,
    dr.overstay_days as current_overstay_days,
    dr.overstay_amount as current_overstay_amount
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
LEFT JOIN monitorings m ON m.device_id = d.id
WHERE d.device_id LIKE 'TEST-OVERSTAY-%'
ORDER BY d.device_id;
```

## Expected Results

| Device ID | Affixing Date | Route Type | Grace Period | Days Diff | Expected Overstay Days | Expected Amount |
|-----------|---------------|------------|--------------|-----------|----------------------|-----------------|
| TEST-OVERSTAY-001 | 4 days ago | Normal | 1 day | 4 | 3 days | D2000 |
| TEST-OVERSTAY-002 | 7 days ago | Long | 2 days | 7 | 5 days | D4000 |
| TEST-OVERSTAY-003 | 12 hours ago | Normal | 1 day | 0 | 0 days | D0 |
| TEST-OVERSTAY-004 | 6 days ago (monitoring) | Normal | 1 day | 6 | 5 days | D4000 |

## Overstay Amount Calculation Logic

- **No charge**: 0-1 days overstay = D0
- **D1000 per day**: Starting from day 2
- **Formula**: `(overstay_days - 1) * 1000`

Examples:
- 0 days overstay = D0
- 1 day overstay = D0  
- 2 days overstay = D1000
- 3 days overstay = D2000
- 4 days overstay = D3000
- 5 days overstay = D4000

## Troubleshooting

### If overstay days are not calculating:
1. Check if affixing_date is set
2. Verify grace period logic (normal route = 1 day, long route = 2 days)
3. Check if observers are firing
4. Run manual update commands

### If amounts are wrong:
1. Verify overstay_days are correct first
2. Check CalculatesOverstayAmount trait
3. Run: `php artisan app:update-overstay-amounts`

### Check logs:
```bash
tail -f storage/logs/laravel.log | grep -i overstay
```
