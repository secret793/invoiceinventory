-- ========================================
-- MANUAL UPDATE TESTS FOR OVERSTAY OBSERVER
-- ========================================
-- These commands test if the observer detects manual database changes

-- ========================================
-- BEFORE UPDATES - CHECK CURRENT STATE
-- ========================================

SELECT 
    '=== BEFORE MANUAL UPDATES ===' as info;

SELECT 
    d.device_id,
    dr.id as retrieval_id,
    dr.date,
    dr.affixing_date,
    dr.long_route_id,
    dr.overstay_days,
    CONCAT('D', dr.overstay_amount) as overstay_amount,
    dr.updated_at
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id LIKE 'SQL-TEST-%'
ORDER BY d.device_id;

-- ========================================
-- TEST 1: UPDATE AFFIXING_DATE
-- ========================================
-- Change SQL-TEST-001 from 2 days ago to 8 days ago
-- Expected: 7 days overstay = D7000

SELECT 
    '=== TEST 1: Updating affixing_date for SQL-TEST-001 ===' as info;

UPDATE device_retrievals dr
JOIN devices d ON dr.device_id = d.id
SET dr.affixing_date = DATE_SUB(NOW(), INTERVAL 8 DAY)
WHERE d.device_id = 'SQL-TEST-001';

-- Check result immediately
SELECT 
    d.device_id,
    dr.affixing_date,
    dr.overstay_days,
    CONCAT('D', dr.overstay_amount) as overstay_amount,
    'Expected: 7 days, D7000' as expected,
    dr.updated_at
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id = 'SQL-TEST-001';

-- ========================================
-- TEST 2: UPDATE DATE FIELD
-- ========================================
-- Change SQL-TEST-002 date from 4 days ago to 10 days ago
-- Expected: 9 days overstay = D9000

SELECT 
    '=== TEST 2: Updating date for SQL-TEST-002 ===' as info;

UPDATE device_retrievals dr
JOIN devices d ON dr.device_id = d.id
SET dr.date = DATE_SUB(NOW(), INTERVAL 10 DAY)
WHERE d.device_id = 'SQL-TEST-002';

-- Check result
SELECT 
    d.device_id,
    dr.date,
    dr.affixing_date,
    dr.overstay_days,
    CONCAT('D', dr.overstay_amount) as overstay_amount,
    'Expected: 9 days, D9000' as expected,
    dr.updated_at
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id = 'SQL-TEST-002';

-- ========================================
-- TEST 3: CHANGE ROUTE TYPE (LONG_ROUTE_ID)
-- ========================================
-- Change SQL-TEST-003 from long route to normal route
-- 4 days ago with normal route (1 day grace) = 3 days overstay = D3000

SELECT 
    '=== TEST 3: Changing route type for SQL-TEST-003 ===' as info;

UPDATE device_retrievals dr
JOIN devices d ON dr.device_id = d.id
SET dr.long_route_id = NULL  -- Change from long route to normal route
WHERE d.device_id = 'SQL-TEST-003';

-- Check result
SELECT 
    d.device_id,
    dr.affixing_date,
    dr.long_route_id,
    CASE WHEN dr.long_route_id IS NOT NULL THEN 'Long Route' ELSE 'Normal Route' END as route_type,
    dr.overstay_days,
    CONCAT('D', dr.overstay_amount) as overstay_amount,
    'Expected: 3 days, D3000 (changed to normal route)' as expected,
    dr.updated_at
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id = 'SQL-TEST-003';

-- ========================================
-- TEST 4: UPDATE MANIFEST_DATE
-- ========================================
-- Change SQL-TEST-004 manifest_date to 5 days ago (remove affixing_date so it uses manifest_date)
-- Expected: 4 days overstay = D4000

SELECT 
    '=== TEST 4: Updating manifest_date for SQL-TEST-004 ===' as info;

UPDATE device_retrievals dr
JOIN devices d ON dr.device_id = d.id
SET 
    dr.affixing_date = NULL,  -- Remove affixing_date
    dr.manifest_date = DATE_SUB(NOW(), INTERVAL 5 DAY)  -- Set manifest_date to 5 days ago
WHERE d.device_id = 'SQL-TEST-004';

-- Check result
SELECT 
    d.device_id,
    dr.date,
    dr.affixing_date,
    dr.manifest_date,
    dr.overstay_days,
    CONCAT('D', dr.overstay_amount) as overstay_amount,
    'Expected: 4 days, D4000 (using manifest_date)' as expected,
    dr.updated_at
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id = 'SQL-TEST-004';

-- ========================================
-- TEST 5: MULTIPLE FIELD UPDATE
-- ========================================
-- Update multiple fields for SQL-TEST-005
-- Set affixing_date to 12 days ago and change to long route
-- Expected: 10 days overstay = D10000

SELECT 
    '=== TEST 5: Multiple field update for SQL-TEST-005 ===' as info;

UPDATE device_retrievals dr
JOIN devices d ON dr.device_id = d.id
SET 
    dr.affixing_date = DATE_SUB(NOW(), INTERVAL 12 DAY),  -- 12 days ago
    dr.long_route_id = 1  -- Change to long route (2 days grace)
WHERE d.device_id = 'SQL-TEST-005';

-- Check result
SELECT 
    d.device_id,
    dr.date,
    dr.affixing_date,
    dr.long_route_id,
    CASE WHEN dr.long_route_id IS NOT NULL THEN 'Long Route' ELSE 'Normal Route' END as route_type,
    dr.overstay_days,
    CONCAT('D', dr.overstay_amount) as overstay_amount,
    'Expected: 10 days, D10000 (12 days ago, long route)' as expected,
    dr.updated_at
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id = 'SQL-TEST-005';

-- ========================================
-- FINAL SUMMARY - ALL RESULTS
-- ========================================

SELECT 
    '=== FINAL SUMMARY AFTER ALL UPDATES ===' as info;

SELECT 
    d.device_id,
    dr.id as retrieval_id,
    dr.boe,
    COALESCE(dr.affixing_date, dr.manifest_date, dr.date) as reference_date,
    CASE WHEN dr.long_route_id IS NOT NULL THEN 'Long (2 days)' ELSE 'Normal (1 day)' END as grace_type,
    DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, dr.manifest_date, dr.date))) as days_since_ref,
    CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END as grace_period,
    GREATEST(0, DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, dr.manifest_date, dr.date))) - CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END) as expected_days,
    dr.overstay_days as actual_days,
    CONCAT('D', GREATEST(0, DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, dr.manifest_date, dr.date))) - CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END) * 1000) as expected_amount,
    CONCAT('D', dr.overstay_amount) as actual_amount,
    CASE 
        WHEN dr.overstay_days = GREATEST(0, DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, dr.manifest_date, dr.date))) - CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END)
        AND dr.overstay_amount = GREATEST(0, DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, dr.manifest_date, dr.date))) - CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END) * 1000
        THEN '✅ PASS' 
        ELSE '❌ FAIL' 
    END as test_result,
    dr.updated_at
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id LIKE 'SQL-TEST-%'
ORDER BY d.device_id;

-- ========================================
-- EXPECTED FINAL RESULTS:
-- ========================================
-- SQL-TEST-001: 7 days overstay, D7000 (updated affixing_date)
-- SQL-TEST-002: 9 days overstay, D9000 (updated date)
-- SQL-TEST-003: 3 days overstay, D3000 (changed to normal route)
-- SQL-TEST-004: 4 days overstay, D4000 (using manifest_date)
-- SQL-TEST-005: 10 days overstay, D10000 (multiple updates)

-- ========================================
-- CLEANUP (OPTIONAL)
-- ========================================
-- Uncomment to clean up test data
-- DELETE FROM device_retrievals WHERE boe LIKE 'BOE-SQL-TEST-%';
-- DELETE FROM devices WHERE device_id LIKE 'SQL-TEST-%';
