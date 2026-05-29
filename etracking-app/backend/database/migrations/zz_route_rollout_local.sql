SET @db := DATABASE();

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='routes' AND COLUMN_NAME='allocation_point_id')=0,
  'ALTER TABLE routes ADD COLUMN allocation_point_id BIGINT NULL AFTER name', 'SELECT "routes.allocation_point_id exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='routes' AND COLUMN_NAME='destination_id')=0,
  'ALTER TABLE routes ADD COLUMN destination_id BIGINT NULL AFTER allocation_point_id', 'SELECT "routes.destination_id exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='routes' AND COLUMN_NAME='is_active')=0,
  'ALTER TABLE routes ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER destination_id', 'SELECT "routes.is_active exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='long_routes' AND COLUMN_NAME='allocation_point_id')=0,
  'ALTER TABLE long_routes ADD COLUMN allocation_point_id BIGINT NULL AFTER name', 'SELECT "long_routes.allocation_point_id exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='long_routes' AND COLUMN_NAME='destination_id')=0,
  'ALTER TABLE long_routes ADD COLUMN destination_id BIGINT NULL AFTER allocation_point_id', 'SELECT "long_routes.destination_id exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='long_routes' AND COLUMN_NAME='is_active')=0,
  'ALTER TABLE long_routes ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER destination_id', 'SELECT "long_routes.is_active exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='routes' AND INDEX_NAME='routes_ap_idx')=0,
  'ALTER TABLE routes ADD INDEX routes_ap_idx (allocation_point_id)', 'SELECT "routes_ap_idx exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='routes' AND INDEX_NAME='routes_destination_idx')=0,
  'ALTER TABLE routes ADD INDEX routes_destination_idx (destination_id)', 'SELECT "routes_destination_idx exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='long_routes' AND INDEX_NAME='long_routes_ap_idx')=0,
  'ALTER TABLE long_routes ADD INDEX long_routes_ap_idx (allocation_point_id)', 'SELECT "long_routes_ap_idx exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='long_routes' AND INDEX_NAME='long_routes_destination_idx')=0,
  'ALTER TABLE long_routes ADD INDEX long_routes_destination_idx (destination_id)', 'SELECT "long_routes_destination_idx exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db AND TABLE_NAME='routes' AND CONSTRAINT_NAME='routes_ap_fk')=0,
  'ALTER TABLE routes ADD CONSTRAINT routes_ap_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL', 'SELECT "routes_ap_fk exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db AND TABLE_NAME='routes' AND CONSTRAINT_NAME='routes_destination_fk')=0,
  'ALTER TABLE routes ADD CONSTRAINT routes_destination_fk FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE SET NULL', 'SELECT "routes_destination_fk exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db AND TABLE_NAME='long_routes' AND CONSTRAINT_NAME='long_routes_ap_fk')=0,
  'ALTER TABLE long_routes ADD CONSTRAINT long_routes_ap_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL', 'SELECT "long_routes_ap_fk exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db AND TABLE_NAME='long_routes' AND CONSTRAINT_NAME='long_routes_destination_fk')=0,
  'ALTER TABLE long_routes ADD CONSTRAINT long_routes_destination_fk FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE SET NULL', 'SELECT "long_routes_destination_fk exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SHOW COLUMNS FROM routes;
SHOW COLUMNS FROM long_routes;
SELECT TABLE_NAME, CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('routes','long_routes')
  AND CONSTRAINT_TYPE='FOREIGN KEY'
ORDER BY TABLE_NAME, CONSTRAINT_NAME;
