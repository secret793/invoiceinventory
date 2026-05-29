-- Idempotent local rollout for dispatch report fields
SET @db := DATABASE();

-- confirmed_affixeds: add ETD/ETA
SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affixeds' AND COLUMN_NAME='etd')=0,
  'ALTER TABLE confirmed_affixeds ADD COLUMN etd DATETIME NULL AFTER manifest_date', 'SELECT "confirmed_affixeds.etd exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affixeds' AND COLUMN_NAME='eta')=0,
  'ALTER TABLE confirmed_affixeds ADD COLUMN eta DATETIME NULL AFTER etd', 'SELECT "confirmed_affixeds.eta exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- confirmed_affix_logs: preserve historical dispatch fields
SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affix_logs' AND COLUMN_NAME='sad_number')=0,
  'ALTER TABLE confirmed_affix_logs ADD COLUMN sad_number VARCHAR(100) NULL AFTER boe', 'SELECT "confirmed_affix_logs.sad_number exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affix_logs' AND COLUMN_NAME='regime')=0,
  'ALTER TABLE confirmed_affix_logs ADD COLUMN regime VARCHAR(100) NULL AFTER vehicle_number', 'SELECT "confirmed_affix_logs.regime exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affix_logs' AND COLUMN_NAME='destination')=0,
  'ALTER TABLE confirmed_affix_logs ADD COLUMN destination VARCHAR(255) NULL AFTER regime', 'SELECT "confirmed_affix_logs.destination exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affix_logs' AND COLUMN_NAME='destination_id')=0,
  'ALTER TABLE confirmed_affix_logs ADD COLUMN destination_id BIGINT NULL AFTER destination', 'SELECT "confirmed_affix_logs.destination_id exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affix_logs' AND COLUMN_NAME='route_id')=0,
  'ALTER TABLE confirmed_affix_logs ADD COLUMN route_id BIGINT NULL AFTER destination_id', 'SELECT "confirmed_affix_logs.route_id exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affix_logs' AND COLUMN_NAME='long_route_id')=0,
  'ALTER TABLE confirmed_affix_logs ADD COLUMN long_route_id BIGINT NULL AFTER route_id', 'SELECT "confirmed_affix_logs.long_route_id exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affix_logs' AND COLUMN_NAME='agency')=0,
  'ALTER TABLE confirmed_affix_logs ADD COLUMN agency VARCHAR(255) NULL AFTER long_route_id', 'SELECT "confirmed_affix_logs.agency exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affix_logs' AND COLUMN_NAME='driver_name')=0,
  'ALTER TABLE confirmed_affix_logs ADD COLUMN driver_name VARCHAR(255) NULL AFTER agency', 'SELECT "confirmed_affix_logs.driver_name exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affix_logs' AND COLUMN_NAME='etd')=0,
  'ALTER TABLE confirmed_affix_logs ADD COLUMN etd DATETIME NULL AFTER driver_name', 'SELECT "confirmed_affix_logs.etd exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='confirmed_affix_logs' AND COLUMN_NAME='eta')=0,
  'ALTER TABLE confirmed_affix_logs ADD COLUMN eta DATETIME NULL AFTER etd', 'SELECT "confirmed_affix_logs.eta exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SHOW COLUMNS FROM confirmed_affixeds;
SHOW COLUMNS FROM confirmed_affix_logs;
