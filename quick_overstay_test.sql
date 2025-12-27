-- ========================================
-- QUICK OVERSTAY TEST - SINGLE COMMANDS
-- ========================================

-- 1. CREATE A QUICK TEST DEVICE
INSERT INTO devices (device_id, status, created_at, updated_at) 
VALUES ('QUICK-OVERSTAY-001', 'ACTIVE', NOW(), NOW());

-- 2. CREATE DEVICE RETRIEVAL WITH 1 DAY OVERSTAY (Expected: D1000)
INSERT INTO device_retrievals (
    date, device_id, boe, sad_number, vehicle_number, regime, destination,
    affixing_date, long_route_id, agency, agent_contact, truck_number, 
    driver_name, retrieval_status, transfer_status, payment_status,
    overstay_days, overstay_amount, created_at, updated_at
) VALUES (
    DATE_SUB(NOW(), INTERVAL 2 DAY),  -- 2 days ago
    (SELECT id FROM devices WHERE device_id = 'QUICK-OVERSTAY-001'),
    'BOE-QUICK-001', 'SAD-QUICK-001', 'VEH-QUICK-001', 'TRANSIT', 'Soma',
    DATE_SUB(NOW(), INTERVAL 2 DAY),  -- affixing_date: 2 days ago (1 day overstay)
    NULL,  -- normal route (1 day grace)
    'Quick Test Agency', '1234567890', 'TRUCK-QUICK-001',
    'Quick Driver', 'NOT_RETRIEVED', 'pending', 'PP',
    0, 0.00, NOW(), NOW()
);

-- 3. CHECK RESULT (Should show 1 day overstay, D1000)
SELECT 
    d.device_id,
    dr.affixing_date,
    dr.overstay_days,
    CONCAT('D', dr.overstay_amount) as overstay_amount,
    'Expected: 1 day, D1000' as expected
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id = 'QUICK-OVERSTAY-001';

-- 4. UPDATE TO 3 DAYS OVERSTAY (Expected: D3000)
UPDATE device_retrievals dr
JOIN devices d ON dr.device_id = d.id
SET dr.affixing_date = DATE_SUB(NOW(), INTERVAL 4 DAY)  -- 4 days ago = 3 days overstay
WHERE d.device_id = 'QUICK-OVERSTAY-001';

-- 5. CHECK UPDATED RESULT (Should show 3 days overstay, D3000)
SELECT 
    d.device_id,
    dr.affixing_date,
    dr.overstay_days,
    CONCAT('D', dr.overstay_amount) as overstay_amount,
    'Expected: 3 days, D3000' as expected,
    'Observer should have updated automatically' as note
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id = 'QUICK-OVERSTAY-001';

-- 6. CLEANUP
-- DELETE FROM device_retrievals WHERE boe = 'BOE-QUICK-001';
-- DELETE FROM devices WHERE device_id = 'QUICK-OVERSTAY-001';
