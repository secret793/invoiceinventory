-- ========================================
-- SAMPLE SQL DATA FOR OVERSTAY TESTING
-- ========================================

-- Clean up existing test data first
DELETE FROM device_retrievals WHERE boe LIKE 'BOE-SQL-TEST-%';
DELETE FROM devices WHERE device_id LIKE 'SQL-TEST-%';

-- ========================================
-- CREATE TEST DEVICES
-- ========================================

INSERT INTO devices (device_id, status, created_at, updated_at) VALUES
('SQL-TEST-001', 'ACTIVE', NOW(), NOW()),
('SQL-TEST-002', 'ACTIVE', NOW(), NOW()),
('SQL-TEST-003', 'ACTIVE', NOW(), NOW()),
('SQL-TEST-004', 'ACTIVE', NOW(), NOW()),
('SQL-TEST-005', 'ACTIVE', NOW(), NOW());

-- ========================================
-- TEST CASE 1: 1 Day Overstay = D1000
-- ========================================
-- Affixing date 2 days ago, normal route (1 day grace) = 1 day overstay

INSERT INTO device_retrievals (
    date, device_id, boe, sad_number, vehicle_number, regime, destination,
    affixing_date, long_route_id, manifest_date, agency, agent_contact,
    truck_number, driver_name, retrieval_status, transfer_status, payment_status,
    overstay_days, overstay_amount, created_at, updated_at
) VALUES (
    DATE_SUB(NOW(), INTERVAL 2 DAY),  -- date: 2 days ago
    (SELECT id FROM devices WHERE device_id = 'SQL-TEST-001'),
    'BOE-SQL-TEST-001',
    'SAD-SQL-TEST-001', 
    'VEH-SQL-001',
    'TRANSIT',
    'Soma',
    DATE_SUB(NOW(), INTERVAL 2 DAY),  -- affixing_date: 2 days ago
    NULL,  -- long_route_id: NULL (normal route, 1 day grace)
    DATE_SUB(NOW(), INTERVAL 2 DAY),  -- manifest_date: 2 days ago
    'Test Agency 1',
    '1234567890',
    'TRUCK-SQL-001',
    'Driver SQL 001',
    'NOT_RETRIEVED',
    'pending',
    'PP',
    0,  -- overstay_days: will be calculated by observer
    0.00,  -- overstay_amount: will be calculated by observer
    NOW(),
    NOW()
);

-- ========================================
-- TEST CASE 2: 3 Days Overstay = D3000
-- ========================================
-- Affixing date 4 days ago, normal route (1 day grace) = 3 days overstay

INSERT INTO device_retrievals (
    date, device_id, boe, sad_number, vehicle_number, regime, destination,
    affixing_date, long_route_id, manifest_date, agency, agent_contact,
    truck_number, driver_name, retrieval_status, transfer_status, payment_status,
    overstay_days, overstay_amount, created_at, updated_at
) VALUES (
    DATE_SUB(NOW(), INTERVAL 4 DAY),  -- date: 4 days ago
    (SELECT id FROM devices WHERE device_id = 'SQL-TEST-002'),
    'BOE-SQL-TEST-002',
    'SAD-SQL-TEST-002',
    'VEH-SQL-002',
    'TRANSIT',
    'Farefeni',
    DATE_SUB(NOW(), INTERVAL 4 DAY),  -- affixing_date: 4 days ago
    NULL,  -- long_route_id: NULL (normal route, 1 day grace)
    DATE_SUB(NOW(), INTERVAL 4 DAY),  -- manifest_date: 4 days ago
    'Test Agency 2',
    '0987654321',
    'TRUCK-SQL-002',
    'Driver SQL 002',
    'NOT_RETRIEVED',
    'pending',
    'PP',
    0,  -- overstay_days: will be calculated by observer
    0.00,  -- overstay_amount: will be calculated by observer
    NOW(),
    NOW()
);

-- ========================================
-- TEST CASE 3: Long Route - 2 Days Overstay = D2000
-- ========================================
-- Affixing date 4 days ago, long route (2 days grace) = 2 days overstay

INSERT INTO device_retrievals (
    date, device_id, boe, sad_number, vehicle_number, regime, destination,
    affixing_date, long_route_id, manifest_date, agency, agent_contact,
    truck_number, driver_name, retrieval_status, transfer_status, payment_status,
    overstay_days, overstay_amount, created_at, updated_at
) VALUES (
    DATE_SUB(NOW(), INTERVAL 4 DAY),  -- date: 4 days ago
    (SELECT id FROM devices WHERE device_id = 'SQL-TEST-003'),
    'BOE-SQL-TEST-003',
    'SAD-SQL-TEST-003',
    'VEH-SQL-003',
    'TRANSIT',
    'Banjul',
    DATE_SUB(NOW(), INTERVAL 4 DAY),  -- affixing_date: 4 days ago
    1,  -- long_route_id: 1 (long route, 2 days grace)
    DATE_SUB(NOW(), INTERVAL 4 DAY),  -- manifest_date: 4 days ago
    'Test Agency 3',
    '1122334455',
    'TRUCK-SQL-003',
    'Driver SQL 003',
    'NOT_RETRIEVED',
    'pending',
    'PP',
    0,  -- overstay_days: will be calculated by observer
    0.00,  -- overstay_amount: will be calculated by observer
    NOW(),
    NOW()
);

-- ========================================
-- TEST CASE 4: No Overstay (Within Grace Period) = D0
-- ========================================
-- Affixing date 12 hours ago, normal route (1 day grace) = 0 days overstay

INSERT INTO device_retrievals (
    date, device_id, boe, sad_number, vehicle_number, regime, destination,
    affixing_date, long_route_id, manifest_date, agency, agent_contact,
    truck_number, driver_name, retrieval_status, transfer_status, payment_status,
    overstay_days, overstay_amount, created_at, updated_at
) VALUES (
    DATE_SUB(NOW(), INTERVAL 12 HOUR),  -- date: 12 hours ago
    (SELECT id FROM devices WHERE device_id = 'SQL-TEST-004'),
    'BOE-SQL-TEST-004',
    'SAD-SQL-TEST-004',
    'VEH-SQL-004',
    'WAREHOUSE',
    'Ghana',
    DATE_SUB(NOW(), INTERVAL 12 HOUR),  -- affixing_date: 12 hours ago
    NULL,  -- long_route_id: NULL (normal route, 1 day grace)
    DATE_SUB(NOW(), INTERVAL 12 HOUR),  -- manifest_date: 12 hours ago
    'Test Agency 4',
    '5566778899',
    'TRUCK-SQL-004',
    'Driver SQL 004',
    'NOT_RETRIEVED',
    'pending',
    'PP',
    0,  -- overstay_days: will be calculated by observer
    0.00,  -- overstay_amount: will be calculated by observer
    NOW(),
    NOW()
);

-- ========================================
-- TEST CASE 5: Using Only Date Field (No Affixing Date)
-- ========================================
-- Date 6 days ago, no affixing_date, normal route = 5 days overstay = D5000

INSERT INTO device_retrievals (
    date, device_id, boe, sad_number, vehicle_number, regime, destination,
    affixing_date, long_route_id, manifest_date, agency, agent_contact,
    truck_number, driver_name, retrieval_status, transfer_status, payment_status,
    overstay_days, overstay_amount, created_at, updated_at
) VALUES (
    DATE_SUB(NOW(), INTERVAL 6 DAY),  -- date: 6 days ago
    (SELECT id FROM devices WHERE device_id = 'SQL-TEST-005'),
    'BOE-SQL-TEST-005',
    'SAD-SQL-TEST-005',
    'VEH-SQL-005',
    'TRANSIT',
    'Kerewan',
    NULL,  -- affixing_date: NULL (will use date field)
    NULL,  -- long_route_id: NULL (normal route, 1 day grace)
    NULL,  -- manifest_date: NULL
    'Test Agency 5',
    '9988776655',
    'TRUCK-SQL-005',
    'Driver SQL 005',
    'NOT_RETRIEVED',
    'pending',
    'PP',
    0,  -- overstay_days: will be calculated by observer
    0.00,  -- overstay_amount: will be calculated by observer
    NOW(),
    NOW()
);

-- ========================================
-- CHECK INITIAL RESULTS
-- ========================================

SELECT 
    '=== INITIAL RESULTS AFTER INSERT ===' as info;

SELECT 
    d.device_id,
    dr.id as retrieval_id,
    dr.boe,
    dr.date,
    dr.affixing_date,
    dr.manifest_date,
    CASE WHEN dr.long_route_id IS NOT NULL THEN 'Long Route (2 days grace)' ELSE 'Normal Route (1 day grace)' END as route_type,
    dr.overstay_days,
    CONCAT('D', dr.overstay_amount) as overstay_amount,
    -- Manual calculation for verification
    COALESCE(dr.affixing_date, dr.manifest_date, dr.date) as reference_date,
    DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, dr.manifest_date, dr.date))) as days_since_reference,
    CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END as grace_period,
    GREATEST(0, DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, dr.manifest_date, dr.date))) - CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END) as expected_overstay_days,
    CONCAT('D', GREATEST(0, DATEDIFF(CURDATE(), DATE(COALESCE(dr.affixing_date, dr.manifest_date, dr.date))) - CASE WHEN dr.long_route_id IS NOT NULL THEN 2 ELSE 1 END) * 1000) as expected_amount
FROM device_retrievals dr
JOIN devices d ON dr.device_id = d.id
WHERE d.device_id LIKE 'SQL-TEST-%'
ORDER BY d.device_id;

-- ========================================
-- EXPECTED RESULTS:
-- ========================================
-- SQL-TEST-001: 1 day overstay, D1000
-- SQL-TEST-002: 3 days overstay, D3000  
-- SQL-TEST-003: 2 days overstay, D2000 (long route)
-- SQL-TEST-004: 0 days overstay, D0 (within grace)
-- SQL-TEST-005: 5 days overstay, D5000 (using date field)
