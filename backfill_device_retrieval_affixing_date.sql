START TRANSACTION;

-- Step 1: Fill missing device_retrievals.affixing_date from latest confirmed_affix_logs by device_id
UPDATE device_retrievals dr
JOIN (
    SELECT cal.device_id, cal.affixing_date
    FROM confirmed_affix_logs cal
    JOIN (
        SELECT device_id, MAX(id) AS max_id
        FROM confirmed_affix_logs
        WHERE affixing_date IS NOT NULL
        GROUP BY device_id
    ) latest_log ON latest_log.max_id = cal.id
) src ON src.device_id = dr.device_id
SET dr.affixing_date = src.affixing_date
WHERE dr.affixing_date IS NULL;

-- Step 2 (fallback): Fill remaining nulls from latest confirmed_affixeds by device_id
UPDATE device_retrievals dr
JOIN (
    SELECT ca.device_id, ca.affixing_date
    FROM confirmed_affixeds ca
    JOIN (
        SELECT device_id, MAX(id) AS max_id
        FROM confirmed_affixeds
        WHERE affixing_date IS NOT NULL
        GROUP BY device_id
    ) latest_affixed ON latest_affixed.max_id = ca.id
) src ON src.device_id = dr.device_id
SET dr.affixing_date = src.affixing_date
WHERE dr.affixing_date IS NULL;

COMMIT;
