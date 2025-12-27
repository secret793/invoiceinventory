-- Test Manual Database Updates to Verify Observer Triggers
-- Run these SQL commands to test if the observer detects manual database changes

-- 1. First, check current test data
SELECT 
    d.device_id,
    dr.id as device_retrieval_id,
    dr.date,
    dr.affixing_date,
    dr.long_route_id,
    dr.overstay_days,
    dr.overstay_amount,
    DATEDIFF(CURDATE(), DATE(dr.affixing_date)) as days_since_affixing,
    CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END as grace_period,
    GREATEST(0, DATEDIFF(CURDATE(), DATE(dr.affixing_date)) - CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END) as expected_overstay_days
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id LIKE 'OVERSTAY-TEST-%'
ORDER BY d.device_id;

-- 2. Test manual update of affixing_date (should trigger observer)
-- Change OVERSTAY-TEST-001 to have 7 days overstay (8 days ago with 1 day grace = 7 days overstay = D7000)
UPDATE device_retrievals dr
JOIN devices d ON dr.device_id = d.id
SET dr.affixing_date = DATE_SUB(NOW(), INTERVAL 8 DAY)
WHERE d.device_id = 'OVERSTAY-TEST-001';

-- 3. Test manual update of date field (should trigger observer)
-- Change OVERSTAY-TEST-002 date to 10 days ago
UPDATE device_retrievals dr
JOIN devices d ON dr.device_id = d.id
SET dr.date = DATE_SUB(NOW(), INTERVAL 10 DAY)
WHERE d.device_id = 'OVERSTAY-TEST-002';

-- 4. Test manual update of long_route_id (should trigger observer)
-- Change OVERSTAY-TEST-003 from long route to normal route
UPDATE device_retrievals dr
JOIN devices d ON dr.device_id = d.id
SET dr.long_route_id = NULL
WHERE d.device_id = 'OVERSTAY-TEST-003';

-- 5. Check results after manual updates
SELECT 
    d.device_id,
    dr.id as device_retrieval_id,
    dr.date,
    dr.affixing_date,
    dr.long_route_id,
    dr.overstay_days,
    dr.overstay_amount,
    DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, dr.date))) as days_since_reference,
    CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END as grace_period,
    GREATEST(0, DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, dr.date))) - CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END) as expected_overstay_days,
    GREATEST(0, DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, dr.date))) - CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END) * 1000 as expected_amount
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id LIKE 'OVERSTAY-TEST-%'
ORDER BY d.device_id;

-- Expected results after manual updates:
-- OVERSTAY-TEST-001: 7 days overstay, D7000 (changed affixing_date to 8 days ago)
-- OVERSTAY-TEST-002: Should recalculate based on new date
-- OVERSTAY-TEST-003: Should recalculate with normal route grace period (1 day instead of 2)

-- 6. Test edge cases
-- Create a record with no dates (should result in 0 overstay)
INSERT INTO devices (device_id, status, created_at, updated_at) 
VALUES ('OVERSTAY-TEST-EDGE', 'ACTIVE', NOW(), NOW());

INSERT INTO device_retrievals (
    date, device_id, boe, sad_number, vehicle_number, regime, destination,
    agency, agent_contact, truck_number, driver_name, retrieval_status, 
    transfer_status, payment_status, created_at, updated_at
) VALUES (
    NULL, 
    (SELECT id FROM devices WHERE device_id = 'OVERSTAY-TEST-EDGE'),
    'BOE-EDGE', 'SAD-EDGE', 'VEH-EDGE', 'TRANSIT', 'Test',
    'Edge Agency', '0000000000', 'TRUCK-EDGE', 'Edge Driver', 
    'NOT_RETRIEVED', 'pending', 'PP', NOW(), NOW()
);

-- Check edge case result
SELECT 
    d.device_id,
    dr.overstay_days,
    dr.overstay_amount
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id = 'OVERSTAY-TEST-EDGE';
