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
    id                  BIGSERIAL PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,
    country             VARCHAR(100),
    description         TEXT,
    regime_id           BIGINT,
    address             VARCHAR(255),
    latitude            VARCHAR(50),
    longitude           VARCHAR(50),
    status              VARCHAR(20) NOT NULL DEFAULT 'Active',
    is_default_location BOOLEAN     NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
    is_archived           BOOLEAN DEFAULT FALSE,
    archived_at           TIMESTAMP,
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

-- ── Sample Allocation Points ───────────────────────────────
INSERT INTO allocation_points (name, location, region, is_active) VALUES
    ('Banjul Port Authority', 'Banjul Harbour, Liberation Avenue', 'Greater Banjul Area', 1),
    ('Brikama Checkpoint', 'Brikama Junction, West Coast Road', 'West Coast Region', 1),
    ('Soma Transit Hub', 'Soma Town, Trans-Gambia Highway', 'Central River Region', 1),
    ('Farafenni Border Post', 'Farafenni, North Bank Road', 'North Bank Region', 1)
ON CONFLICT DO NOTHING;

-- ── Sample Distribution Points ────────────────────────────
INSERT INTO distribution_points (name, location, region, is_active) VALUES
    ('Serrekunda Distribution Centre', 'Serrekunda Market Area, Kanifing', 'Greater Banjul Area', 1),
    ('Basse Distribution Office', 'Basse Santa Su, Upper River Region', 'Upper River Region', 1),
    ('Janjanbureh Depot', 'Janjanbureh Island, Central River', 'Central River Region', 1)
ON CONFLICT DO NOTHING;

-- ── Sample Routes (Short) ─────────────────────────────────
INSERT INTO routes (name, allowed_days, base_usd_amount, description) VALUES
    ('Banjul – Serrekunda', 1, 25.00, 'Short urban route between Banjul and Serrekunda'),
    ('Banjul – Brikama', 1, 35.00, 'Route from Banjul Port to Brikama Checkpoint'),
    ('Serrekunda – Soma', 1, 55.00, 'West Coast to Central River short route'),
    ('Brikama – Farafenni', 1, 65.00, 'West Coast Region to North Bank crossing'),
    ('Banjul – Kanifing', 1, 20.00, 'Intra-city Banjul to Kanifing route')
ON CONFLICT DO NOTHING;

-- ── Sample Long Routes ────────────────────────────────────
INSERT INTO long_routes (name, allowed_days, base_usd_amount, description) VALUES
    ('Banjul – Basse Santa Su', 3, 120.00, 'Full cross-country route to Upper River Region'),
    ('Banjul – Janjanbureh', 3, 95.00, 'Trans-Gambia corridor to Central River Region'),
    ('Brikama – Basse', 3, 110.00, 'South-bank long haul from West Coast to Upper River')
ON CONFLICT DO NOTHING;

-- ── Sample Regimes & Destinations ────────────────────────
INSERT INTO regimes (name, description, is_active) VALUES
    ('SAD', 'Single Administrative Document regime', 1),
    ('T1', 'Transit Document T1 regime', 1),
    ('Export', 'Export declaration regime', 1)
ON CONFLICT DO NOTHING;

INSERT INTO destinations (name, country, description, regime_id) VALUES
    ('Dakar Port', 'Senegal', 'Dakar main sea port, Senegal', (SELECT id FROM regimes WHERE name = 'SAD' LIMIT 1)),
    ('Ziguinchor', 'Senegal', 'Ziguinchor region, southern Senegal', (SELECT id FROM regimes WHERE name = 'T1' LIMIT 1)),
    ('Conakry', 'Guinea', 'Capital city and main port of Guinea', (SELECT id FROM regimes WHERE name = 'SAD' LIMIT 1)),
    ('Bissau', 'Guinea-Bissau', 'Capital of Guinea-Bissau', (SELECT id FROM regimes WHERE name = 'T1' LIMIT 1)),
    ('Kaolack', 'Senegal', 'Central Senegal trading hub', (SELECT id FROM regimes WHERE name = 'Export' LIMIT 1))
ON CONFLICT DO NOTHING;

-- ── Sample Devices (20 devices, varied statuses) ──────────
INSERT INTO devices (device_id, device_type, serial_number, sim_number, sim_operator, batch_number, date_received, status, is_configured, allocation_point_id, notes) VALUES
    ('DEV-001', 'GPS Tracker', 'SN-GT-001', '220-1001', 'Gamcel', 'BATCH-2024-A', '2024-01-15', 'IN_USE',       1, (SELECT id FROM allocation_points WHERE name = 'Banjul Port Authority'), 'Assigned to active shipment'),
    ('DEV-002', 'GPS Tracker', 'SN-GT-002', '220-1002', 'Gamcel', 'BATCH-2024-A', '2024-01-15', 'IN_USE',       1, (SELECT id FROM allocation_points WHERE name = 'Banjul Port Authority'), 'Assigned to active shipment'),
    ('DEV-003', 'GPS Tracker', 'SN-GT-003', '220-1003', 'Africell', 'BATCH-2024-A', '2024-01-15', 'IN_USE',     1, (SELECT id FROM allocation_points WHERE name = 'Brikama Checkpoint'), 'In transit monitoring'),
    ('DEV-004', 'GPS Tracker', 'SN-GT-004', '220-1004', 'Africell', 'BATCH-2024-A', '2024-01-15', 'CONFIGURED', 1, (SELECT id FROM allocation_points WHERE name = 'Brikama Checkpoint'), 'Ready for deployment'),
    ('DEV-005', 'GPS Tracker', 'SN-GT-005', '220-1005', 'Gamcel', 'BATCH-2024-A', '2024-01-15', 'CONFIGURED',  1, (SELECT id FROM allocation_points WHERE name = 'Soma Transit Hub'), 'Ready for deployment'),
    ('DEV-006', 'GPS Tracker', 'SN-GT-006', '220-1006', 'Gamcel', 'BATCH-2024-B', '2024-02-10', 'IN_USE',      1, (SELECT id FROM allocation_points WHERE name = 'Soma Transit Hub'), 'Long-route assignment active'),
    ('DEV-007', 'GPS Tracker', 'SN-GT-007', '220-1007', 'Africell', 'BATCH-2024-B', '2024-02-10', 'IN_USE',    1, (SELECT id FROM allocation_points WHERE name = 'Farafenni Border Post'), 'Border crossing active'),
    ('DEV-008', 'GPS Tracker', 'SN-GT-008', '220-1008', 'Africell', 'BATCH-2024-B', '2024-02-10', 'CONFIGURED',1, (SELECT id FROM allocation_points WHERE name = 'Farafenni Border Post'), 'Awaiting assignment'),
    ('DEV-009', 'GPS Tracker', 'SN-GT-009', '220-1009', 'Gamcel', 'BATCH-2024-B', '2024-02-10', 'CONFIGURED',  1, (SELECT id FROM allocation_points WHERE name = 'Banjul Port Authority'), 'Awaiting assignment'),
    ('DEV-010', 'GPS Tracker', 'SN-GT-010', '220-1010', 'Gamcel', 'BATCH-2024-B', '2024-02-10', 'CONFIGURED',  1, (SELECT id FROM allocation_points WHERE name = 'Banjul Port Authority'), 'Awaiting assignment'),
    ('DEV-011', 'GPS Tracker', 'SN-GT-011', '220-1011', 'Africell', 'BATCH-2024-C', '2024-03-05', 'UNCONFIGURED', 0, NULL, 'Newly received, not yet configured'),
    ('DEV-012', 'GPS Tracker', 'SN-GT-012', '220-1012', 'Africell', 'BATCH-2024-C', '2024-03-05', 'UNCONFIGURED', 0, NULL, 'Newly received, not yet configured'),
    ('DEV-013', 'GPS Tracker', 'SN-GT-013', '220-1013', 'Gamcel', 'BATCH-2024-C', '2024-03-05', 'UNCONFIGURED', 0, NULL, 'Awaiting SIM activation'),
    ('DEV-014', 'GPS Tracker', 'SN-GT-014', '220-1014', 'Gamcel', 'BATCH-2024-C', '2024-03-05', 'UNCONFIGURED', 0, NULL, 'Awaiting SIM activation'),
    ('DEV-015', 'GPS Tracker', 'SN-GT-015', '220-1015', 'Africell', 'BATCH-2024-C', '2024-03-05', 'UNCONFIGURED', 0, NULL, 'Pending configuration'),
    ('DEV-016', 'GPS Tracker', 'SN-GT-016', '220-1016', 'Gamcel', 'BATCH-2024-D', '2024-04-20', 'UNCONFIGURED', 0, NULL, 'Pending configuration'),
    ('DEV-017', 'GPS Tracker', 'SN-GT-017', '220-1017', 'Africell', 'BATCH-2024-D', '2024-04-20', 'CONFIGURED', 1, (SELECT id FROM allocation_points WHERE name = 'Brikama Checkpoint'), 'Ready for assignment'),
    ('DEV-018', 'GPS Tracker', 'SN-GT-018', '220-1018', 'Africell', 'BATCH-2024-D', '2024-04-20', 'IN_USE',     1, (SELECT id FROM allocation_points WHERE name = 'Soma Transit Hub'), 'Active on cargo route'),
    ('DEV-019', 'GPS Tracker', 'SN-GT-019', '220-1019', 'Gamcel', 'BATCH-2024-D', '2024-04-20', 'IN_USE',      1, (SELECT id FROM allocation_points WHERE name = 'Banjul Port Authority'), 'Active on cargo route'),
    ('DEV-020', 'GPS Tracker', 'SN-GT-020', '220-1020', 'Gamcel', 'BATCH-2024-D', '2024-04-20', 'UNCONFIGURED', 0, NULL, 'Pending configuration')
ON CONFLICT DO NOTHING;

-- ── Sample Data Entry Assignments ─────────────────────────
INSERT INTO data_entry_assignments (device_id, allocation_point_id, status, show_in_menu, user_id) VALUES
    ((SELECT id FROM devices WHERE device_id = 'DEV-001'), (SELECT id FROM allocation_points WHERE name = 'Banjul Port Authority'), 'COMPLETED', 1, (SELECT id FROM users WHERE email = 'admin@gnsw.gm')),
    ((SELECT id FROM devices WHERE device_id = 'DEV-003'), (SELECT id FROM allocation_points WHERE name = 'Brikama Checkpoint'), 'COMPLETED', 1, (SELECT id FROM users WHERE email = 'admin@gnsw.gm')),
    ((SELECT id FROM devices WHERE device_id = 'DEV-006'), (SELECT id FROM allocation_points WHERE name = 'Soma Transit Hub'), 'PENDING', 1, (SELECT id FROM users WHERE email = 'admin@gnsw.gm')),
    ((SELECT id FROM devices WHERE device_id = 'DEV-007'), (SELECT id FROM allocation_points WHERE name = 'Farafenni Border Post'), 'PENDING', 1, (SELECT id FROM users WHERE email = 'admin@gnsw.gm')),
    ((SELECT id FROM devices WHERE device_id = 'DEV-019'), (SELECT id FROM allocation_points WHERE name = 'Banjul Port Authority'), 'PENDING', 1, (SELECT id FROM users WHERE email = 'admin@gnsw.gm'))
ON CONFLICT DO NOTHING;

-- ── Sample Confirmed Affixed Records ──────────────────────
INSERT INTO confirmed_affixeds (device_id, allocation_point_id, boe, sad_number, transaction_type, vehicle_number, truck_number, driver_name, regime, destination, destination_id, route_id, long_route_id, manifest_date, agency, agent_contact, status, date) VALUES
    (
        (SELECT id FROM devices WHERE device_id = 'DEV-001'),
        (SELECT id FROM allocation_points WHERE name = 'Banjul Port Authority'),
        'BOE-2024-0045', 'SAD-2024-0312', 'SAD', 'BJL-2341-A', 'T-0045', 'Ousman Jallow',
        'SAD', 'Dakar Port',
        (SELECT id FROM destinations WHERE name = 'Dakar Port'),
        (SELECT id FROM routes WHERE name = 'Banjul – Serrekunda'), NULL,
        '2024-05-10', 'West Africa Freight Ltd', '+220-7001234', 'CONFIRMED', NOW() - INTERVAL '10 days'
    ),
    (
        (SELECT id FROM devices WHERE device_id = 'DEV-002'),
        (SELECT id FROM allocation_points WHERE name = 'Banjul Port Authority'),
        'BOE-2024-0046', 'SAD-2024-0313', 'SAD', 'BJL-2342-B', 'T-0046', 'Lamin Touray',
        'SAD', 'Ziguinchor',
        (SELECT id FROM destinations WHERE name = 'Ziguinchor'),
        NULL, (SELECT id FROM long_routes WHERE name = 'Banjul – Basse Santa Su'),
        '2024-05-12', 'Trans-Sene Logistics', '+220-7005678', 'CONFIRMED', NOW() - INTERVAL '8 days'
    ),
    (
        (SELECT id FROM devices WHERE device_id = 'DEV-003'),
        (SELECT id FROM allocation_points WHERE name = 'Brikama Checkpoint'),
        'BOE-2024-0051', 'SAD-2024-0320', 'T1', 'BJL-2350-C', 'T-0051', 'Buba Sanneh',
        'T1', 'Conakry',
        (SELECT id FROM destinations WHERE name = 'Conakry'),
        NULL, (SELECT id FROM long_routes WHERE name = 'Banjul – Janjanbureh'),
        '2024-05-15', 'Guinea Cargo Express', '+220-9001122', 'CONFIRMED', NOW() - INTERVAL '5 days'
    )
ON CONFLICT DO NOTHING;

-- ── Schema additions (v2 — Device Retrievals module) ─────
-- Added 2026-05

ALTER TABLE device_retrievals ADD COLUMN IF NOT EXISTS archive_reason        VARCHAR(500);
ALTER TABLE device_retrievals ADD COLUMN IF NOT EXISTS distribution_point_id BIGINT;
ALTER TABLE device_retrievals ADD COLUMN IF NOT EXISTS receipt_number        VARCHAR(100);
ALTER TABLE device_retrievals ADD COLUMN IF NOT EXISTS consignee             VARCHAR(255);

ALTER TABLE invoices ADD COLUMN IF NOT EXISTS reference_number VARCHAR(100);
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS reference_date   DATE;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS regime           VARCHAR(100);
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS consignee        VARCHAR(255);
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS agent            VARCHAR(255);
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS customs_post     VARCHAR(255);
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS sad_number       VARCHAR(100);
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS penalty_amount   DECIMAL(12,2) DEFAULT 0;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS total_amount     DECIMAL(12,2) DEFAULT 0;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS approved_by      BIGINT;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS approved_at      TIMESTAMP;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS waived_by        BIGINT;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS waived_at        TIMESTAMP;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS finance_notes    TEXT;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS receipt_number   VARCHAR(100);

-- ── Waiver History ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS waiver_history (
    id                     BIGSERIAL PRIMARY KEY,
    device_retrieval_id    BIGINT REFERENCES device_retrievals(id) ON DELETE SET NULL,
    invoice_id             BIGINT REFERENCES invoices(id) ON DELETE SET NULL,
    admin_user_id          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    reason                 TEXT NOT NULL,
    original_overstay_days INT DEFAULT 0,
    original_amount        DECIMAL(12,2) DEFAULT 0,
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Device Retrieval Audit Log ────────────────────────────
CREATE TABLE IF NOT EXISTS device_retrieval_logs (
    id                  BIGSERIAL PRIMARY KEY,
    device_id           BIGINT,
    device_retrieval_id BIGINT,
    boe                 VARCHAR(100),
    action_type         VARCHAR(50),
    performed_by        BIGINT,
    performed_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes               TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Receipt extra columns (added for Data Entry spec) ─────
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS transaction_type      VARCHAR(20)    DEFAULT 'SAD';
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS consignment_nature    VARCHAR(50);
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS moving_trucks         INT            DEFAULT 1;
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS used                  INT            DEFAULT 0;
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS billing_unit          VARCHAR(50);
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS base_unit_charge_usd  DECIMAL(10,2)  DEFAULT 0;
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS exchange_rate_used    DECIMAL(10,4)  DEFAULT 60;
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS unit_charge_gmd       DECIMAL(10,2)  DEFAULT 0;
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS total_charge_gmd      DECIMAL(10,2)  DEFAULT 0;
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS destination_id        BIGINT;
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS consignee_details     TEXT;
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS shipper_details       TEXT;
ALTER TABLE receipts ADD COLUMN IF NOT EXISTS description_of_goods  TEXT;

-- ── Companies (device owner / company assignment) ─────────────────────────
CREATE TABLE IF NOT EXISTS companies (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(200) NOT NULL,
    status     VARCHAR(20)  NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE devices ADD COLUMN IF NOT EXISTS company_id BIGINT NULL;
ALTER TABLE devices DROP CONSTRAINT IF EXISTS devices_company_fk;
ALTER TABLE devices ADD CONSTRAINT devices_company_fk
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL;
CREATE INDEX IF NOT EXISTS devices_company_idx ON devices (company_id);
