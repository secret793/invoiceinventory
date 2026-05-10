-- ============================================================
-- GNSW E-Tracking System — MySQL Schema (cPanel deployment)
-- ============================================================
-- Run this on your cPanel MySQL database after creating it.
-- Use phpMyAdmin or the cPanel MySQL terminal to import this file.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ── Users ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id             BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,
    email          VARCHAR(255) NOT NULL UNIQUE,
    username       VARCHAR(100),
    password       VARCHAR(255) NOT NULL,
    role           VARCHAR(100) DEFAULT 'user',
    remember_token VARCHAR(100),
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Roles & Permissions ────────────────────────────────────
CREATE TABLE IF NOT EXISTS roles (
    id         BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(125) NOT NULL,
    guard_name VARCHAR(125) NOT NULL DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY roles_name_guard (name, guard_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id         BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(125) NOT NULL,
    guard_name VARCHAR(125) NOT NULL DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY permissions_name_guard (name, guard_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS model_has_roles (
    role_id    BIGINT NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id   BIGINT NOT NULL,
    PRIMARY KEY (role_id, model_id, model_type),
    CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS model_has_permissions (
    permission_id BIGINT NOT NULL,
    model_type    VARCHAR(255) NOT NULL,
    model_id      BIGINT NOT NULL,
    PRIMARY KEY (permission_id, model_id, model_type),
    CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_has_permissions (
    permission_id BIGINT NOT NULL,
    role_id       BIGINT NOT NULL,
    PRIMARY KEY (permission_id, role_id),
    CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    CONSTRAINT role_has_permissions_role_id_foreign       FOREIGN KEY (role_id)       REFERENCES roles(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Allocation & Distribution Points ──────────────────────
CREATE TABLE IF NOT EXISTS allocation_points (
    id         BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    location   VARCHAR(255),
    region     VARCHAR(255),
    is_active  SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS distribution_points (
    id         BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    location   VARCHAR(255),
    region     VARCHAR(255),
    is_active  SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Devices ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS devices (
    id                     BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id              VARCHAR(100) NOT NULL,
    device_type            VARCHAR(100),
    serial_number          VARCHAR(100),
    sim_number             VARCHAR(100),
    sim_operator           VARCHAR(100),
    batch_number           VARCHAR(100),
    date_received          DATE,
    status                 VARCHAR(50) DEFAULT 'UNCONFIGURED',
    is_configured          SMALLINT DEFAULT 0,
    allocation_point_id    BIGINT,
    distribution_point_id  BIGINT,
    user_id                BIGINT,
    notes                  TEXT,
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT devices_allocation_fk   FOREIGN KEY (allocation_point_id)   REFERENCES allocation_points(id)   ON DELETE SET NULL,
    CONSTRAINT devices_distribution_fk FOREIGN KEY (distribution_point_id) REFERENCES distribution_points(id) ON DELETE SET NULL,
    INDEX devices_status_idx       (status),
    INDEX devices_allocation_idx   (allocation_point_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Stores (Inventory Mirror) ─────────────────────────────
CREATE TABLE IF NOT EXISTS stores (
    id             BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id      BIGINT,
    serial_number  VARCHAR(100) NOT NULL,
    device_type    VARCHAR(100),
    batch_number   VARCHAR(100),
    date_received  DATE,
    status         VARCHAR(50) DEFAULT 'RECEIVED',
    sim_number     VARCHAR(100),
    sim_operator   VARCHAR(100),
    user_id        BIGINT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT stores_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Other Items ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS other_items (
    id         BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    item_name  VARCHAR(255) NOT NULL,
    item_type  VARCHAR(100),
    quantity   INT DEFAULT 1,
    status     VARCHAR(50) DEFAULT 'RECEIVED',
    notes      TEXT,
    user_id    BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Transfers ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS transfers (
    id                            BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id                     BIGINT,
    device_serial                 VARCHAR(100),
    transfer_type                 VARCHAR(50),
    transfer_status               VARCHAR(50) DEFAULT 'PENDING',
    from_allocation_point_id      BIGINT,
    to_allocation_point_id        BIGINT,
    from_distribution_point_id    BIGINT,
    to_distribution_point_id      BIGINT,
    original_allocation_point_id  BIGINT,
    original_status               VARCHAR(50),
    quantity                      INT DEFAULT 1,
    notes                         TEXT,
    cancellation_reason           TEXT,
    cancelled_at                  TIMESTAMP NULL,
    created_at                    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT transfers_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Routes & Long Routes ───────────────────────────────────
CREATE TABLE IF NOT EXISTS routes (
    id              BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    allowed_days    INT DEFAULT 1,
    base_usd_amount DECIMAL(10,2) DEFAULT 0,
    description     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS long_routes (
    id              BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    allowed_days    INT DEFAULT 3,
    base_usd_amount DECIMAL(10,2) DEFAULT 0,
    description     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Regimes & Destinations ────────────────────────────────
CREATE TABLE IF NOT EXISTS regimes (
    id          BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    description TEXT,
    is_active   SMALLINT DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destinations (
    id          BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    country     VARCHAR(100),
    description TEXT,
    regime_id   BIGINT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT destinations_regime_fk FOREIGN KEY (regime_id) REFERENCES regimes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── System Settings ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS system_settings (
    id          BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `key`       VARCHAR(255) NOT NULL UNIQUE,
    value       TEXT,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Notifications ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type       VARCHAR(255) NOT NULL,
    data       TEXT,
    read_at    TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX notifications_read_at_idx (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Data Entry Assignments ────────────────────────────────
CREATE TABLE IF NOT EXISTS data_entry_assignments (
    id                   BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id            BIGINT,
    allocation_point_id  BIGINT,
    status               VARCHAR(50) DEFAULT 'PENDING',
    return_note          TEXT,
    show_in_menu         SMALLINT DEFAULT 1,
    user_id              BIGINT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT dea_device_fk FOREIGN KEY (device_id)           REFERENCES devices(id)           ON DELETE SET NULL,
    CONSTRAINT dea_ap_fk     FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Confirmed Affixed (Dispatch) ──────────────────────────
CREATE TABLE IF NOT EXISTS confirmed_affixeds (
    id                    BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id             BIGINT,
    allocation_point_id   BIGINT,
    boe                   VARCHAR(100),
    sad_number            VARCHAR(100),
    transaction_type      VARCHAR(50) DEFAULT 'SAD',
    transaction_reference VARCHAR(100),
    vehicle_number        VARCHAR(100),
    truck_number          VARCHAR(100),
    driver_name           VARCHAR(255),
    regime                VARCHAR(100),
    destination           VARCHAR(255),
    destination_id        BIGINT,
    route_id              BIGINT,
    long_route_id         BIGINT,
    manifest_date         DATE,
    agency                VARCHAR(255),
    agent_contact         VARCHAR(100),
    receipt_id            BIGINT,
    status                VARCHAR(50) DEFAULT 'PENDING',
    date                  TIMESTAMP NULL,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT ca_device_fk FOREIGN KEY (device_id)           REFERENCES devices(id)           ON DELETE SET NULL,
    CONSTRAINT ca_ap_fk     FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Confirmed Affix Logs ──────────────────────────────────
CREATE TABLE IF NOT EXISTS confirmed_affix_logs (
    id                   BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id            BIGINT,
    confirmed_affixed_id BIGINT,
    boe                  VARCHAR(100),
    vehicle_number       VARCHAR(100),
    allocation_point_id  BIGINT,
    affixing_date        TIMESTAMP NULL,
    affixed_by           BIGINT,
    status               VARCHAR(50) DEFAULT 'AFFIXED',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Receipts ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS receipts (
    id                   BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    receipt_number       VARCHAR(100),
    allocation_point_id  BIGINT,
    route_id             BIGINT,
    long_route_id        BIGINT,
    destination_id       BIGINT,
    sad_number           VARCHAR(100),
    transaction_type     VARCHAR(20) DEFAULT 'SAD',
    consignment_nature   VARCHAR(50),
    moving_trucks        INT DEFAULT 1,
    used                 INT DEFAULT 0,
    billing_unit         VARCHAR(50),
    base_unit_charge_usd DECIMAL(10,2) DEFAULT 0,
    exchange_rate_used   DECIMAL(10,4) DEFAULT 60,
    unit_charge_gmd      DECIMAL(10,2) DEFAULT 0,
    total_charge_gmd     DECIMAL(10,2) DEFAULT 0,
    agent_name           VARCHAR(255),
    agent_contact        VARCHAR(100),
    consignee_details    TEXT,
    shipper_details      TEXT,
    description_of_goods TEXT,
    amount               DECIMAL(12,2) DEFAULT 0,
    date                 TIMESTAMP NULL,
    notes                TEXT,
    created_by           BIGINT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT receipts_ap_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Device Retrievals ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS device_retrievals (
    id                    BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id             BIGINT,
    allocation_point_id   BIGINT,
    distribution_point_id BIGINT,
    destination_id        BIGINT,
    route_id              BIGINT,
    long_route_id         BIGINT,
    receipt_id            BIGINT,
    boe                   VARCHAR(100),
    sad_number            VARCHAR(100),
    transaction_type      VARCHAR(50) DEFAULT 'SAD',
    transaction_reference VARCHAR(100),
    t1_validation_ref     VARCHAR(100),
    vehicle_number        VARCHAR(100),
    truck_number          VARCHAR(100),
    driver_name           VARCHAR(255),
    regime                VARCHAR(100),
    destination           VARCHAR(255),
    manifest_date         DATE,
    agency                VARCHAR(255),
    agent_contact         VARCHAR(100),
    date                  TIMESTAMP NULL,
    affixing_date         TIMESTAMP NULL,
    status                VARCHAR(50) DEFAULT 'pending',
    retrieval_status      VARCHAR(50) DEFAULT 'NOT_RETRIEVED',
    transfer_status       VARCHAR(50) DEFAULT 'pending',
    payment_status        VARCHAR(50) DEFAULT 'PP',
    overstay_days         INT DEFAULT 0,
    overstay_amount       DECIMAL(12,2) DEFAULT 0,
    overdue_hours         INT DEFAULT 0,
    finance_approval_date DATE,
    finance_approved_by   VARCHAR(255),
    finance_notes         TEXT,
    note                  TEXT,
    archive_reason        VARCHAR(500),
    user_id               BIGINT,
    is_archived           TINYINT(1) DEFAULT 0,
    archived_at           TIMESTAMP NULL,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT dr_device_fk FOREIGN KEY (device_id)           REFERENCES devices(id)           ON DELETE SET NULL,
    CONSTRAINT dr_ap_fk     FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL,
    INDEX dr_retrieval_status_idx (retrieval_status),
    INDEX dr_overstay_idx         (overstay_days)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Invoices ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS invoices (
    id                   BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_retrieval_id  BIGINT,
    device_id            BIGINT,
    boe                  VARCHAR(100),
    vehicle_number       VARCHAR(100),
    reference_number     VARCHAR(100),
    reference_date       DATE,
    regime               VARCHAR(100),
    consignee            VARCHAR(255),
    agent                VARCHAR(255),
    customs_post         VARCHAR(255),
    sad_number           VARCHAR(100),
    overstay_days        INT DEFAULT 0,
    overstay_amount      DECIMAL(12,2) DEFAULT 0,
    penalty_amount       DECIMAL(12,2) DEFAULT 0,
    total_amount         DECIMAL(12,2) DEFAULT 0,
    exchange_rate        DECIMAL(10,4) DEFAULT 60,
    status               VARCHAR(50) DEFAULT 'PENDING',
    paid_at              TIMESTAMP NULL,
    approved_by          BIGINT,
    approved_at          TIMESTAMP NULL,
    waived_by            BIGINT,
    waived_at            TIMESTAMP NULL,
    finance_notes        TEXT,
    receipt_number       VARCHAR(100),
    notes                TEXT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT invoices_retrieval_fk FOREIGN KEY (device_retrieval_id) REFERENCES device_retrievals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Monitoring ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS monitorings (
    id                  BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id           BIGINT,
    allocation_point_id BIGINT,
    boe                 VARCHAR(100),
    vehicle_number      VARCHAR(100),
    regime              VARCHAR(100),
    destination         VARCHAR(255),
    route_id            BIGINT,
    long_route_id       BIGINT,
    manifest_date       DATE,
    agency              VARCHAR(255),
    agent_contact       VARCHAR(100),
    truck_number        VARCHAR(100),
    driver_name         VARCHAR(255),
    date                TIMESTAMP NULL,
    affixing_date       TIMESTAMP NULL,
    status              VARCHAR(50) DEFAULT 'ACTIVE',
    retrieval_status    VARCHAR(50) DEFAULT 'NOT_RETRIEVED',
    overstay_days       INT DEFAULT 0,
    note                TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT monitoring_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Assign To Agents ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS assign_to_agents (
    id                   BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id            BIGINT,
    allocation_point_id  BIGINT,
    receipt_id           BIGINT,
    date                 TIMESTAMP NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Waiver History ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS waiver_history (
    id                     BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_retrieval_id    BIGINT,
    invoice_id             BIGINT,
    admin_user_id          BIGINT,
    reason                 TEXT NOT NULL,
    original_overstay_days INT DEFAULT 0,
    original_amount        DECIMAL(12,2) DEFAULT 0,
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT wh_retrieval_fk FOREIGN KEY (device_retrieval_id) REFERENCES device_retrievals(id) ON DELETE SET NULL,
    CONSTRAINT wh_invoice_fk   FOREIGN KEY (invoice_id)          REFERENCES invoices(id)          ON DELETE SET NULL,
    CONSTRAINT wh_user_fk      FOREIGN KEY (admin_user_id)       REFERENCES users(id)             ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Device Retrieval Audit Log ────────────────────────────
CREATE TABLE IF NOT EXISTS device_retrieval_logs (
    id                  BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id           BIGINT,
    device_retrieval_id BIGINT,
    boe                 VARCHAR(100),
    action_type         VARCHAR(50),
    performed_by        BIGINT,
    performed_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes               TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default Admin (password: password)
INSERT IGNORE INTO users (name, email, username, password, role, created_at)
VALUES ('Super Admin', 'admin@gnsw.gm', 'admin',
        '$2y$12$xqRlVhdxtbhmcctaJ3zrlOVZgSfZt8RLnHw8MKaRyRBoa/XGAbBiu', 'Super Admin', NOW());

-- Roles
INSERT IGNORE INTO roles (name, guard_name) VALUES
    ('Super Admin', 'web'), ('Warehouse Manager', 'web'), ('Data Entry Officer', 'web'),
    ('Retrieval Officer', 'web'), ('Finance Officer', 'web'), ('Report Viewer', 'web'),
    ('Distribution Officer', 'web'), ('Monitoring Officer', 'web'), ('Viewer', 'web');

-- System Settings
INSERT IGNORE INTO system_settings (`key`, value, description) VALUES
    ('exchange_rate_gmd_usd',      '60',                    'Exchange rate: 1 USD = X GMD'),
    ('overstay_short_route_days',  '1',                     'Short route allowed days before overstay'),
    ('overstay_long_route_days',   '3',                     'Long route allowed days before overstay'),
    ('system_name',                'GNSW E-Tracking System','System display name'),
    ('invoice_prefix',             'INV',                   'Invoice number prefix');

-- Allocation Points
INSERT IGNORE INTO allocation_points (name, location, region, is_active) VALUES
    ('Banjul Port Authority',  'Banjul Harbour, Liberation Avenue',    'Greater Banjul Area',   1),
    ('Brikama Checkpoint',     'Brikama Junction, West Coast Road',     'West Coast Region',     1),
    ('Soma Transit Hub',       'Soma Town, Trans-Gambia Highway',       'Central River Region',  1),
    ('Farafenni Border Post',  'Farafenni, North Bank Road',            'North Bank Region',     1);

-- Distribution Points
INSERT IGNORE INTO distribution_points (name, location, region, is_active) VALUES
    ('Serrekunda Distribution Centre', 'Serrekunda Market Area, Kanifing',     'Greater Banjul Area', 1),
    ('Basse Distribution Office',      'Basse Santa Su, Upper River Region',   'Upper River Region',  1),
    ('Janjanbureh Depot',              'Janjanbureh Island, Central River',     'Central River Region',1);

-- Short Routes
INSERT IGNORE INTO routes (name, allowed_days, base_usd_amount, description) VALUES
    ('Banjul – Serrekunda', 1, 25.00,  'Short urban route between Banjul and Serrekunda'),
    ('Banjul – Brikama',    1, 35.00,  'Route from Banjul Port to Brikama Checkpoint'),
    ('Serrekunda – Soma',   1, 55.00,  'West Coast to Central River short route'),
    ('Brikama – Farafenni', 1, 65.00,  'West Coast Region to North Bank crossing'),
    ('Banjul – Kanifing',   1, 20.00,  'Intra-city Banjul to Kanifing route');

-- Long Routes
INSERT IGNORE INTO long_routes (name, allowed_days, base_usd_amount, description) VALUES
    ('Banjul – Basse Santa Su', 3, 120.00, 'Full cross-country route to Upper River Region'),
    ('Banjul – Janjanbureh',    3,  95.00, 'Trans-Gambia corridor to Central River Region'),
    ('Brikama – Basse',         3, 110.00, 'South-bank long haul from West Coast to Upper River');

-- Regimes
INSERT IGNORE INTO regimes (name, description, is_active) VALUES
    ('SAD',    'Single Administrative Document regime', 1),
    ('T1',     'Transit Document T1 regime',            1),
    ('Export', 'Export declaration regime',             1);

-- Destinations (subquery works in MySQL)
INSERT IGNORE INTO destinations (name, country, description, regime_id) VALUES
    ('Dakar Port',  'Senegal',       'Dakar main sea port, Senegal',      (SELECT id FROM regimes WHERE name = 'SAD'    LIMIT 1)),
    ('Ziguinchor',  'Senegal',       'Ziguinchor region, southern Senegal',(SELECT id FROM regimes WHERE name = 'T1'     LIMIT 1)),
    ('Conakry',     'Guinea',        'Capital city and main port of Guinea',(SELECT id FROM regimes WHERE name = 'SAD'   LIMIT 1)),
    ('Bissau',      'Guinea-Bissau', 'Capital of Guinea-Bissau',           (SELECT id FROM regimes WHERE name = 'T1'     LIMIT 1)),
    ('Kaolack',     'Senegal',       'Central Senegal trading hub',         (SELECT id FROM regimes WHERE name = 'Export' LIMIT 1));
