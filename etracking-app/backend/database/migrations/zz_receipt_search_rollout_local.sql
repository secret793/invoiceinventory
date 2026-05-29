-- Idempotent local rollout for receipt search performance indexes
SET @db := DATABASE();

SET @s := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='receipts' AND INDEX_NAME='receipts_receipt_number_idx')=0,
  'ALTER TABLE receipts ADD INDEX receipts_receipt_number_idx (receipt_number)', 'SELECT "receipts_receipt_number_idx exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='receipts' AND INDEX_NAME='receipts_sad_number_idx')=0,
  'ALTER TABLE receipts ADD INDEX receipts_sad_number_idx (sad_number)', 'SELECT "receipts_sad_number_idx exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='receipts' AND INDEX_NAME='receipts_used_idx')=0,
  'ALTER TABLE receipts ADD INDEX receipts_used_idx (used)', 'SELECT "receipts_used_idx exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SHOW INDEX FROM receipts;
