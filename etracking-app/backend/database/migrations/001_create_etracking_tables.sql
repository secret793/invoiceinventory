-- ============================================================
-- GNSW E-Tracking System — Full Database Schema
-- Run this against your MySQL database (first_crud or new DB)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Users ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,
    email          VARCHAR(255) NOT NULL UNIQUE,
    username       VARCHAR(100) NULL,
    password       VARCHAR(255) NOT NULL,
    role           VARCHAR(100) NULL DEFAULT 'user',
    remember_token VARCHAR(100) NULL,
    created_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Roles & Permissions (Spatie-style) ────────────────────
CREATE TABLE IF NOT EXISTS roles (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(125) NOT NULL,
    guard_name VARCHAR(125) NOT NULL DEFAULT 'web',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY roles_name_guard_name_unique (name, guard_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(125) NOT NULL,
    guard_name VARCHAR(125) NOT NULL DEFAULT 'web',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY permissions_name_guard_name_unique (name, guard_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS model_has_roles (
    role_id    BIGINT UNSIGNED NOT NULL,
    model_type VARCHAR(255)    NOT NULL,
    model_id   BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, model_id, model_type),
    CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS model_has_permissions (
    permission_id BIGINT UNSIGNED NOT NULL,
    model_type    VARCHAR(255)    NOT NULL,
    model_id      BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (permission_id, model_id, model_type),
    CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_has_permissions (
    permission_id BIGINT UNSIGNED NOT NULL,
    role_id       BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (permission_id, role_id),
    CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Allocation & Distribution Points ──────────────────────
CREATE TABLE IF NOT EXISTS allocation_points (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    location   VARCHAR(255) NULL,
    region     VARCHAR(255) NULL,
    is_active  TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS distribution_points (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    location   VARCHAR(255) NULL,
    region     VARCHAR(255) NULL,
    is_active  TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Devices ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS devices (
    id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id              VARCHAR(100) NOT NULL,
    device_type            VARCHAR(100) NULL,
    serial_number          VARCHAR(100) NULL,
    sim_number             VARCHAR(100) NULL,
    sim_operator           VARCHAR(100) NULL,
    batch_number           VARCHAR(100) NULL,
    date_received          DATE NULL,
    status                 VARCHAR(50) DEFAULT 'UNCONFIGURED',
    is_configured          TINYINT(1) DEFAULT 0,
    allocation_point_id    BIGINT UNSIGNED NULL,
    distribution_point_id  BIGINT UNSIGNED NULL,
    user_id                BIGINT UNSIGNED NULL,
    notes                  TEXT NULL,
    created_at             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX devices_status_idx (status),
    INDEX devices_allocation_idx (allocation_point_id),
    CONSTRAINT devices_allocation_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL,
    CONSTRAINT devices_distribution_fk FOREIGN KEY (distribution_point_id) REFERENCES distribution_points(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Stores (Inventory Mirror) ─────────────────────────────
CREATE TABLE IF NOT EXISTS stores (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id      BIGINT UNSIGNED NULL,
    serial_number  VARCHAR(100) NOT NULL,
    device_type    VARCHAR(100) NULL,
    batch_number   VARCHAR(100) NULL,
    date_received  DATE NULL,
    status         VARCHAR(50) DEFAULT 'RECEIVED',
    sim_number     VARCHAR(100) NULL,
    sim_operator   VARCHAR(100) NULL,
    user_id        BIGINT UNSIGNED NULL,
    created_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT stores_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Other Items ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS other_items (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_name  VARCHAR(255) NOT NULL,
    item_type  VARCHAR(100) NULL,
    quantity   INT DEFAULT 1,
    status     VARCHAR(50) DEFAULT 'RECEIVED',
    notes      TEXT NULL,
    user_id    BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transfers ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS transfers (
    id                            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id                     BIGINT UNSIGNED NULL,
    device_serial                 VARCHAR(100) NULL,
    transfer_type                 VARCHAR(50) NULL,
    transfer_status               VARCHAR(50) DEFAULT 'PENDING',
    from_allocation_point_id      BIGINT UNSIGNED NULL,
    to_allocation_point_id        BIGINT UNSIGNED NULL,
    from_distribution_point_id    BIGINT UNSIGNED NULL,
    to_distribution_point_id      BIGINT UNSIGNED NULL,
    original_allocation_point_id  BIGINT UNSIGNED NULL,
    original_status               VARCHAR(50) NULL,
    quantity                      INT DEFAULT 1,
    notes                         TEXT NULL,
    cancellation_reason           TEXT NULL,
    cancelled_at                  TIMESTAMP NULL,
    created_at                    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT transfers_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Routes & Long Routes ───────────────────────────────────
CREATE TABLE IF NOT EXISTS routes (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    allowed_days    INT DEFAULT 1,
    base_usd_amount DECIMAL(10,2) DEFAULT 0,
    description     TEXT NULL,
    created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS long_routes (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    allowed_days    INT DEFAULT 3,
    base_usd_amount DECIMAL(10,2) DEFAULT 0,
    description     TEXT NULL,
    created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Regimes & Destinations ────────────────────────────────
CREATE TABLE IF NOT EXISTS regimes (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS destinations (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    country     VARCHAR(100) NULL,
    description TEXT NULL,
    regime_id   BIGINT UNSIGNED NULL,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT destinations_regime_fk FOREIGN KEY (regime_id) REFERENCES regimes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Data Entry Assignments ────────────────────────────────
CREATE TABLE IF NOT EXISTS data_entry_assignments (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id            BIGINT UNSIGNED NULL,
    allocation_point_id  BIGINT UNSIGNED NULL,
    status               VARCHAR(50) DEFAULT 'PENDING',
    return_note          TEXT NULL,
    show_in_menu         TINYINT(1) DEFAULT 1,
    user_id              BIGINT UNSIGNED NULL,
    created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT dea_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
    CONSTRAINT dea_ap_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Confirmed Affixed (Dispatch) ──────────────────────────
CREATE TABLE IF NOT EXISTS confirmed_affixeds (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id            BIGINT UNSIGNED NULL,
    allocation_point_id  BIGINT UNSIGNED NULL,
    boe                  VARCHAR(100) NULL,
    sad_number           VARCHAR(100) NULL,
    transaction_type     VARCHAR(50) DEFAULT 'SAD',
    transaction_reference VARCHAR(100) NULL,
    vehicle_number       VARCHAR(100) NULL,
    truck_number         VARCHAR(100) NULL,
    driver_name          VARCHAR(255) NULL,
    regime               VARCHAR(100) NULL,
    destination          VARCHAR(255) NULL,
    destination_id       BIGINT UNSIGNED NULL,
    route_id             BIGINT UNSIGNED NULL,
    long_route_id        BIGINT UNSIGNED NULL,
    manifest_date        DATE NULL,
    agency               VARCHAR(255) NULL,
    agent_contact        VARCHAR(100) NULL,
    receipt_id           BIGINT UNSIGNED NULL,
    status               VARCHAR(50) DEFAULT 'PENDING',
    date                 DATETIME NULL,
    created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT ca_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
    CONSTRAINT ca_ap_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Confirmed Affix Logs ──────────────────────────────────
CREATE TABLE IF NOT EXISTS confirmed_affix_logs (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id            BIGINT UNSIGNED NULL,
    confirmed_affixed_id BIGINT UNSIGNED NULL,
    boe                  VARCHAR(100) NULL,
    vehicle_number       VARCHAR(100) NULL,
    allocation_point_id  BIGINT UNSIGNED NULL,
    affixing_date        DATETIME NULL,
    affixed_by           BIGINT UNSIGNED NULL,
    status               VARCHAR(50) DEFAULT 'AFFIXED',
    created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Device Retrievals ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS device_retrievals (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id             BIGINT UNSIGNED NULL,
    allocation_point_id   BIGINT UNSIGNED NULL,
    destination_id        BIGINT UNSIGNED NULL,
    route_id              BIGINT UNSIGNED NULL,
    long_route_id         BIGINT UNSIGNED NULL,
    receipt_id            BIGINT UNSIGNED NULL,
    boe                   VARCHAR(100) NULL,
    sad_number            VARCHAR(100) NULL,
    transaction_type      VARCHAR(50) DEFAULT 'SAD',
    transaction_reference VARCHAR(100) NULL,
    t1_validation_ref     VARCHAR(100) NULL,
    vehicle_number        VARCHAR(100) NULL,
    truck_number          VARCHAR(100) NULL,
    driver_name           VARCHAR(255) NULL,
    regime                VARCHAR(100) NULL,
    destination           VARCHAR(255) NULL,
    manifest_date         DATE NULL,
    agency                VARCHAR(255) NULL,
    agent_contact         VARCHAR(100) NULL,
    date                  DATETIME NULL,
    affixing_date         DATETIME NULL,
    status                VARCHAR(50) DEFAULT 'pending',
    retrieval_status      VARCHAR(50) DEFAULT 'NOT_RETRIEVED',
    transfer_status       VARCHAR(50) DEFAULT 'pending',
    payment_status        VARCHAR(50) DEFAULT 'PP',
    overstay_days         INT DEFAULT 0,
    overstay_amount       DECIMAL(12,2) DEFAULT 0,
    overdue_hours         INT DEFAULT 0,
    finance_approval_date DATE NULL,
    finance_approved_by   VARCHAR(255) NULL,
    finance_notes         TEXT NULL,
    note                  TEXT NULL,
    user_id               BIGINT UNSIGNED NULL,
    created_at            TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX dr_retrieval_status_idx (retrieval_status),
    INDEX dr_overstay_idx (overstay_days),
    CONSTRAINT dr_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
    CONSTRAINT dr_ap_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Monitoring ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS monitorings (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id           BIGINT UNSIGNED NULL,
    allocation_point_id BIGINT UNSIGNED NULL,
    boe                 VARCHAR(100) NULL,
    vehicle_number      VARCHAR(100) NULL,
    regime              VARCHAR(100) NULL,
    destination         VARCHAR(255) NULL,
    route_id            BIGINT UNSIGNED NULL,
    long_route_id       BIGINT UNSIGNED NULL,
    manifest_date       DATE NULL,
    agency              VARCHAR(255) NULL,
    agent_contact       VARCHAR(100) NULL,
    truck_number        VARCHAR(100) NULL,
    driver_name         VARCHAR(255) NULL,
    date                DATETIME NULL,
    affixing_date       DATETIME NULL,
    status              VARCHAR(50) DEFAULT 'ACTIVE',
    retrieval_status    VARCHAR(50) DEFAULT 'NOT_RETRIEVED',
    overstay_days       INT DEFAULT 0,
    note                TEXT NULL,
    created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT monitoring_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Receipts ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS receipts (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_number       VARCHAR(100) NULL,
    allocation_point_id  BIGINT UNSIGNED NULL,
    route_id             BIGINT UNSIGNED NULL,
    long_route_id        BIGINT UNSIGNED NULL,
    sad_number           VARCHAR(100) NULL,
    agent_name           VARCHAR(255) NULL,
    agent_contact        VARCHAR(100) NULL,
    amount               DECIMAL(12,2) DEFAULT 0,
    date                 DATETIME NULL,
    notes                TEXT NULL,
    created_by           BIGINT UNSIGNED NULL,
    created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT receipts_ap_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Invoices ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS invoices (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_retrieval_id  BIGINT UNSIGNED NULL,
    device_id            BIGINT UNSIGNED NULL,
    boe                  VARCHAR(100) NULL,
    vehicle_number       VARCHAR(100) NULL,
    overstay_days        INT DEFAULT 0,
    overstay_amount      DECIMAL(12,2) DEFAULT 0,
    exchange_rate        DECIMAL(10,4) DEFAULT 60,
    status               VARCHAR(50) DEFAULT 'PENDING',
    paid_at              TIMESTAMP NULL,
    notes                TEXT NULL,
    created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT invoices_retrieval_fk FOREIGN KEY (device_retrieval_id) REFERENCES device_retrievals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Notifications ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type       VARCHAR(255) NOT NULL,
    data       TEXT NULL,
    read_at    TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX notifications_read_at_idx (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── System Settings ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS system_settings (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`       VARCHAR(255) NOT NULL UNIQUE,
    value       TEXT NULL,
    description TEXT NULL,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Assign To Agents (helper table) ──────────────────────
CREATE TABLE IF NOT EXISTS assign_to_agents (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id            BIGINT UNSIGNED NULL,
    allocation_point_id  BIGINT UNSIGNED NULL,
    receipt_id           BIGINT UNSIGNED NULL,
    date                 DATETIME NULL,
    created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Default Seed Data ─────────────────────────────────────

-- Default Admin User (password: password)
INSERT IGNORE INTO users (name, email, username, password, role, created_at)
VALUES ('Super Admin', 'admin@gnsw.gm', 'admin',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', NOW());

-- Default Roles
INSERT IGNORE INTO roles (name, guard_name) VALUES
    ('Super Admin', 'web'), ('Warehouse Manager', 'web'), ('Data Entry Officer', 'web'),
    ('Retrieval Officer', 'web'), ('Finance Officer', 'web'), ('Report Viewer', 'web'),
    ('Distribution Officer', 'web'), ('Monitoring Officer', 'web'), ('Viewer', 'web');

-- System Settings
INSERT IGNORE INTO system_settings (`key`, value, description) VALUES
    ('exchange_rate_gmd_usd', '60', 'Exchange rate: 1 USD = X GMD'),
    ('overstay_short_route_days', '1', 'Short route allowed days before overstay'),
    ('overstay_long_route_days', '3', 'Long route allowed days before overstay'),
    ('system_name', 'GNSW E-Tracking System', 'System display name'),
    ('invoice_prefix', 'INV', 'Invoice number prefix');
