-- Update all pending overstay invoices to PAID status
UPDATE invoices 
SET 
    status = 'PD',
    approved_by = 1,
    approved_at = NOW()
WHERE 
    status = 'PP' 
    AND overstay_days > 0;

-- Verify the update
SELECT 
    status, 
    COUNT(*) as count 
FROM invoices 
GROUP BY status;
