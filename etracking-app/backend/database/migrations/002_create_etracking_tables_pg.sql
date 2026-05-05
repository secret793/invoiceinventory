-- ============================================================
-- GNSW E-Tracking System — PostgreSQL Schema
-- ============================================================

-- ── Users ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id             BIGSERIAL PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,
    email          VARCHAR(255) NOT NULL UNIQUE,
    username       VARCHAR(100),
    password       VARCHAR(255) NOT NULL,
    role           VARCHAR(100) DEFAULT 'user',
    remember_token VARCHAR(100),
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Roles & Permissions ────────────────────────────────────
CREATE TABLE IF NOT EXISTS roles (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(125) NOT NULL,
    guard_name VARCHAR(125) NOT NULL DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (name, guard_name)
);

CREATE TABLE IF NOT EXISTS permissions (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(125) NOT NULL,
    guard_name VARCHAR(125) NOT NULL DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (name, guard_name)
);

CREATE TABLE IF NOT EXISTS model_has_roles (
    role_id    BIGINT NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id   BIGINT NOT NULL,
    PRIMARY KEY (role_id, model_id, model_type),
    CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS model_has_permissions (
    permission_id BIGINT NOT NULL,
    model_type    VARCHAR(255) NOT NULL,
    model_id      BIGINT NOT NULL,
    PRIMARY KEY (permission_id, model_id, model_type),
    CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS role_has_permissions (
    permission_id BIGINT NOT NULL,
    role_id       BIGINT NOT NULL,
    PRIMARY KEY (permission_id, role_id),
    CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- ── Allocation & Distribution Points ──────────────────────
CREATE TABLE IF NOT EXISTS allocation_points (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    location   VARCHAR(255),
    region     VARCHAR(255),
    is_active  SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS distribution_points (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    location   VARCHAR(255),
    region     VARCHAR(255),
    is_active  SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Devices ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS devices (
    id                     BIGSERIAL PRIMARY KEY,
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
    updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT devices_allocation_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL,
    CONSTRAINT devices_distribution_fk FOREIGN KEY (distribution_point_id) REFERENCES distribution_points(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS devices_status_idx ON devices (status);
CREATE INDEX IF NOT EXISTS devices_allocation_idx ON devices (allocation_point_id);

-- ── Stores (Inventory Mirror) ─────────────────────────────
CREATE TABLE IF NOT EXISTS stores (
    id             BIGSERIAL PRIMARY KEY,
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
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT stores_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
);

-- ── Other Items ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS other_items (
    id         BIGSERIAL PRIMARY KEY,
    item_name  VARCHAR(255) NOT NULL,
    item_type  VARCHAR(100),
    quantity   INT DEFAULT 1,
    status     VARCHAR(50) DEFAULT 'RECEIVED',
    notes      TEXT,
    user_id    BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Transfers ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS transfers (
    id                            BIGSERIAL PRIMARY KEY,
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
    cancelled_at                  TIMESTAMP,
    created_at                    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT transfers_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
);

-- ── Routes & Long Routes ───────────────────────────────────
CREATE TABLE IF NOT EXISTS routes (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    allowed_days    INT DEFAULT 1,
    base_usd_amount DECIMAL(10,2) DEFAULT 0,
    description     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS long_routes (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    allowed_days    INT DEFAULT 3,
    base_usd_amount DECIMAL(10,2) DEFAULT 0,
    description     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Regimes & Destinations ────────────────────────────────
CREATE TABLE IF NOT EXISTS regimes (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    description TEXT,
    is_active   SMALLINT DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS destinations (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    country     VARCHAR(100),
    description TEXT,
    regime_id   BIGINT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT destinations_regime_fk FOREIGN KEY (regime_id) REFERENCES regimes(id) ON DELETE SET NULL
);

-- ── Data Entry Assignments ────────────────────────────────
CREATE TABLE IF NOT EXISTS data_entry_assignments (
    id                   BIGSERIAL PRIMARY KEY,
    device_id            BIGINT,
    allocation_point_id  BIGINT,
    status               VARCHAR(50) DEFAULT 'PENDING',
    return_note          TEXT,
    show_in_menu         SMALLINT DEFAULT 1,
    user_id              BIGINT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT dea_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
    CONSTRAINT dea_ap_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL
);

-- ── Confirmed Affixed (Dispatch) ──────────────────────────
CREATE TABLE IF NOT EXISTS confirmed_affixeds (
    id                    BIGSERIAL PRIMARY KEY,
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
    date                  TIMESTAMP,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ca_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
    CONSTRAINT ca_ap_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL
);

-- ── Confirmed Affix Logs ──────────────────────────────────
CREATE TABLE IF NOT EXISTS confirmed_affix_logs (
    id                   BIGSERIAL PRIMARY KEY,
    device_id            BIGINT,
    confirmed_affixed_id BIGINT,
    boe                  VARCHAR(100),
    vehicle_number       VARCHAR(100),
    allocation_point_id  BIGINT,
    affixing_date        TIMESTAMP,
    affixed_by           BIGINT,
    status               VARCHAR(50) DEFAULT 'AFFIXED',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Device Retrievals ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS device_retrievals (
    id                    BIGSERIAL PRIMARY KEY,
    device_id             BIGINT,
    allocation_point_id   BIGINT,
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
    date                  TIMESTAMP,
    affixing_date         TIMESTAMP,
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
    user_id               BIGINT,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT dr_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
    CONSTRAINT dr_ap_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS dr_retrieval_status_idx ON device_retrievals (retrieval_status);
CREATE INDEX IF NOT EXISTS dr_overstay_idx ON device_retrievals (overstay_days);

-- ── Monitoring ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS monitorings (
    id                  BIGSERIAL PRIMARY KEY,
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
    date                TIMESTAMP,
    affixing_date       TIMESTAMP,
    status              VARCHAR(50) DEFAULT 'ACTIVE',
    retrieval_status    VARCHAR(50) DEFAULT 'NOT_RETRIEVED',
    overstay_days       INT DEFAULT 0,
    note                TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT monitoring_device_fk FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
);

-- ── Receipts ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS receipts (
    id                   BIGSERIAL PRIMARY KEY,
    receipt_number       VARCHAR(100),
    allocation_point_id  BIGINT,
    route_id             BIGINT,
    long_route_id        BIGINT,
    sad_number           VARCHAR(100),
    agent_name           VARCHAR(255),
    agent_contact        VARCHAR(100),
    amount               DECIMAL(12,2) DEFAULT 0,
    date                 TIMESTAMP,
    notes                TEXT,
    created_by           BIGINT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT receipts_ap_fk FOREIGN KEY (allocation_point_id) REFERENCES allocation_points(id) ON DELETE SET NULL
);

-- ── Invoices ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS invoices (
    id                   BIGSERIAL PRIMARY KEY,
    device_retrieval_id  BIGINT,
    device_id            BIGINT,
    boe                  VARCHAR(100),
    vehicle_number       VARCHAR(100),
    overstay_days        INT DEFAULT 0,
    overstay_amount      DECIMAL(12,2) DEFAULT 0,
    exchange_rate        DECIMAL(10,4) DEFAULT 60,
    status               VARCHAR(50) DEFAULT 'PENDING',
    paid_at              TIMESTAMP,
    notes                TEXT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT invoices_retrieval_fk FOREIGN KEY (device_retrieval_id) REFERENCES device_retrievals(id) ON DELETE SET NULL
);

-- ── Notifications ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         BIGSERIAL PRIMARY KEY,
    type       VARCHAR(255) NOT NULL,
    data       TEXT,
    read_at    TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS notifications_read_at_idx ON notifications (read_at);

-- ── System Settings ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS system_settings (
    id          BIGSERIAL PRIMARY KEY,
    key         VARCHAR(255) NOT NULL UNIQUE,
    value       TEXT,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Assign To Agents ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS assign_to_agents (
    id                   BIGSERIAL PRIMARY KEY,
    device_id            BIGINT,
    allocation_point_id  BIGINT,
    receipt_id           BIGINT,
    date                 TIMESTAMP,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Seed Data ─────────────────────────────────────────────

-- Default Admin User (password: password)
INSERT INTO users (name, email, username, password, role, created_at)
VALUES ('Super Admin', 'admin@gnsw.gm', 'admin',
        '$2y$12$xqRlVhdxtbhmcctaJ3zrlOVZgSfZt8RLnHw8MKaRyRBoa/XGAbBiu', 'Super Admin', NOW())
ON CONFLICT (email) DO NOTHING;

-- Default Roles
INSERT INTO roles (name, guard_name) VALUES
    ('Super Admin', 'web'), ('Warehouse Manager', 'web'), ('Data Entry Officer', 'web'),
    ('Retrieval Officer', 'web'), ('Finance Officer', 'web'), ('Report Viewer', 'web'),
    ('Distribution Officer', 'web'), ('Monitoring Officer', 'web'), ('Viewer', 'web')
ON CONFLICT (name, guard_name) DO NOTHING;

-- System Settings
INSERT INTO system_settings (key, value, description) VALUES
    ('exchange_rate_gmd_usd', '60', 'Exchange rate: 1 USD = X GMD'),
    ('overstay_short_route_days', '1', 'Short route allowed days before overstay'),
    ('overstay_long_route_days', '3', 'Long route allowed days before overstay'),
    ('system_name', 'GNSW E-Tracking System', 'System display name'),
    ('invoice_prefix', 'INV', 'Invoice number prefix')
ON CONFLICT (key) DO NOTHING;
