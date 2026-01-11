-- ============================================================================
-- FIX RECEIPTS TABLE ID COLUMN - AUTO INCREMENT ISSUE
-- ============================================================================
-- Problem: The 'id' column doesn't have a default value (missing AUTO_INCREMENT)
-- Solution: Modify the 'id' column to be AUTO_INCREMENT PRIMARY KEY
-- 
-- Run this script using one of these methods:
-- 1. MySQL CLI: mysql -u root -p your_database < fix_receipts_table_id.sql
-- 2. phpMyAdmin: Import this file
-- 3. Laravel Tinker: Run the commands below
-- ============================================================================

-- First, check the current table structure
DESCRIBE receipts;

-- Show the CREATE TABLE statement to see current definition
SHOW CREATE TABLE receipts\G

-- ============================================================================
-- SOLUTION 1: Modify the existing id column to add AUTO_INCREMENT
-- ============================================================================

-- Check if there's an existing PRIMARY KEY on id
-- If yes, we need to drop it first, then recreate with AUTO_INCREMENT

-- Drop the primary key if it exists without AUTO_INCREMENT
ALTER TABLE `receipts` 
  MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY IF NOT EXISTS (`id`);

-- ============================================================================
-- SOLUTION 2: If Solution 1 fails, try this alternative approach
-- ============================================================================

-- Step 1: Drop the existing primary key constraint
-- ALTER TABLE `receipts` DROP PRIMARY KEY;

-- Step 2: Modify the id column to be AUTO_INCREMENT
-- ALTER TABLE `receipts` 
--   MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

-- Step 3: Add back the primary key
-- ALTER TABLE `receipts` ADD PRIMARY KEY (`id`);

-- ============================================================================
-- VERIFICATION: Check if the fix worked
-- ============================================================================

-- Verify the id column now has AUTO_INCREMENT
SHOW CREATE TABLE receipts\G

-- Test insert (this should work now)
-- INSERT INTO receipts (receipt_number, date, consignment_nature, sad_number, 
--   allocation_point_id, destination_id, agent_name, agent_phone, 
--   consignee_details, description_of_goods, created_at, updated_at) 
-- VALUES ('TEST-001', NOW(), 'CN', 'TEST-SAD-001', 1, 1, 
--   'Test Agent', '1234567890', 'Test Consignee', 'Test Goods', NOW(), NOW());

-- Check the last inserted id
-- SELECT LAST_INSERT_ID();

-- Clean up test data
-- DELETE FROM receipts WHERE receipt_number = 'TEST-001';

-- ============================================================================
-- ADDITIONAL FIX: Ensure all foreign key constraints are proper
-- ============================================================================

-- Check foreign keys
SELECT 
  CONSTRAINT_NAME,
  TABLE_NAME,
  COLUMN_NAME,
  REFERENCED_TABLE_NAME,
  REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'receipts' 
  AND CONSTRAINT_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL;
