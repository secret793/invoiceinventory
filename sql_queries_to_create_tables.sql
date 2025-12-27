-- Drop tables if they exist
DROP TABLE IF EXISTS stores;
DROP TABLE IF EXISTS devices;

-- Create devices table
CREATE TABLE devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_type VARCHAR(255) NOT NULL,
    type VARCHAR(255) NULL,
    device_id VARCHAR(255) UNIQUE NOT NULL,
    serial_number VARCHAR(255) UNIQUE NOT NULL,
    batch_number VARCHAR(255) NOT NULL,
    date_received DATE NOT NULL,
    status ENUM('UNCONFIGURED', 'CONFIGURED', 'ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST', 'RECEIVED', 'REJECTED', 'PENDING', 'RETRIEVED', 'ACTIVE') DEFAULT 'UNCONFIGURED',
    distribution_point_id BIGINT UNSIGNED NULL,
    allocation_point_id BIGINT UNSIGNED NULL,
    sim_number VARCHAR(255) NULL,
    sim_operator VARCHAR(255) NULL,
    is_configured TINYINT(1) DEFAULT 0,
    user_id BIGINT UNSIGNED NULL,
    cancellation_reason VARCHAR(255) NULL,
    cancelled_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Add foreign keys after table creation to avoid issues
ALTER TABLE devices
    ADD CONSTRAINT fk_devices_distribution_point
    FOREIGN KEY (distribution_point_id)
    REFERENCES distribution_points(id)
    ON DELETE SET NULL;

ALTER TABLE devices
    ADD CONSTRAINT fk_devices_allocation_point
    FOREIGN KEY (allocation_point_id)
    REFERENCES allocation_points(id)
    ON DELETE SET NULL;

ALTER TABLE devices
    ADD CONSTRAINT fk_devices_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE SET NULL;

-- Create stores table
CREATE TABLE stores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED UNIQUE NOT NULL,
    device_type VARCHAR(255) NOT NULL,
    type VARCHAR(255) NULL,
    serial_number VARCHAR(255) NULL,
    batch_number VARCHAR(255) NOT NULL,
    date_received DATE NOT NULL,
    status ENUM('UNCONFIGURED', 'CONFIGURED', 'ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST', 'RECEIVED', 'REJECTED', 'PENDING', 'RETRIEVED', 'ACTIVE') DEFAULT 'UNCONFIGURED',
    distribution_point_id BIGINT UNSIGNED NULL,
    allocation_point_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    sim_number VARCHAR(255) NULL,
    sim_operator VARCHAR(255) NULL,
    cancellation_reason VARCHAR(255) NULL,
    cancelled_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Add foreign keys for stores table
ALTER TABLE stores
    ADD CONSTRAINT fk_stores_device
    FOREIGN KEY (device_id)
    REFERENCES devices(id)
    ON DELETE CASCADE;

ALTER TABLE stores
    ADD CONSTRAINT fk_stores_distribution_point
    FOREIGN KEY (distribution_point_id)
    REFERENCES distribution_points(id)
    ON DELETE SET NULL;

ALTER TABLE stores
    ADD CONSTRAINT fk_stores_allocation_point
    FOREIGN KEY (allocation_point_id)
    REFERENCES allocation_points(id)
    ON DELETE SET NULL;

ALTER TABLE stores
    ADD CONSTRAINT fk_stores_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE SET NULL;

-- Sync existing devices to stores
INSERT INTO stores (
    device_id, device_type, type, serial_number, batch_number,
    date_received, status, distribution_point_id, allocation_point_id,
    user_id, sim_number, sim_operator, cancellation_reason,
    cancelled_at, notes, created_at, updated_at
)
SELECT
    id, device_type, type, serial_number, batch_number,
    date_received, status, distribution_point_id, allocation_point_id,
    user_id, sim_number, sim_operator, cancellation_reason,
    cancelled_at, notes, created_at, updated_at
FROM devices
WHERE id NOT IN (SELECT device_id FROM stores);
