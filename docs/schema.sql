-- =====================================================================
-- IT Asset Manager — rancangan skema PostgreSQL
-- Acuan untuk migration Laravel. Konvensi: id bigserial, timestamps
-- created_at/updated_at, soft delete deleted_at pada master data utama.
-- =====================================================================

-- ---------- Organisasi (sumber: HERD, read-only) ----------

CREATE TABLE departments (
    id              bigserial PRIMARY KEY,
    herd_id         varchar(64)  NOT NULL UNIQUE,
    name            varchar(150) NOT NULL,
    parent_id       bigint REFERENCES departments(id),
    is_active       boolean NOT NULL DEFAULT true,
    synced_at       timestamptz,
    created_at      timestamptz, updated_at timestamptz
);

CREATE TABLE locations (
    id              bigserial PRIMARY KEY,
    name            varchar(150) NOT NULL,          -- Kantor Pusat, Plant, Cabang, Gudang IT
    address         text,
    herd_id         varchar(64) UNIQUE,             -- terisi bila lokasi juga ada di HERD
    created_at      timestamptz, updated_at timestamptz
);

CREATE TABLE employees (
    id              bigserial PRIMARY KEY,
    herd_id         varchar(64)  NOT NULL UNIQUE,
    employee_number varchar(32)  NOT NULL UNIQUE,   -- NIK
    name            varchar(150) NOT NULL,
    email           varchar(190),
    job_title       varchar(150),
    department_id   bigint REFERENCES departments(id),
    location_id     bigint REFERENCES locations(id),
    manager_id      bigint REFERENCES employees(id),
    employment_status varchar(20) NOT NULL DEFAULT 'active'
        CHECK (employment_status IN ('active','resigned','transferred','inactive')),
    status_effective_date date,                     -- tanggal efektif resign/mutasi
    synced_at       timestamptz,
    created_at      timestamptz, updated_at      timestamptz
);
CREATE INDEX employees_department_idx ON employees(department_id);
CREATE INDEX employees_status_idx     ON employees(employment_status);

-- Pengguna aplikasi (login via SSO HERD), role lokal
CREATE TABLE users (
    id              bigserial PRIMARY KEY,
    employee_id     bigint NOT NULL UNIQUE REFERENCES employees(id),
    role            varchar(20) NOT NULL DEFAULT 'viewer'
        CHECK (role IN ('admin','technician','viewer')),
    password        varchar(255),          -- login lokal (NIK + password) saat HERD tidak tersedia
    last_login_at   timestamptz,
    remember_token  varchar(100),
    created_at      timestamptz, updated_at timestamptz
);

CREATE TABLE herd_sync_runs (
    id              bigserial PRIMARY KEY,
    started_at      timestamptz NOT NULL,
    finished_at     timestamptz,
    status          varchar(20) NOT NULL CHECK (status IN ('running','success','failed')),
    created_count   integer NOT NULL DEFAULT 0,
    updated_count   integer NOT NULL DEFAULT 0,
    offboarding_count integer NOT NULL DEFAULT 0,
    error_message   text,
    triggered_by    bigint REFERENCES users(id)     -- null = terjadwal
);

-- ---------- Master data ----------

CREATE TABLE suppliers (
    id              bigserial PRIMARY KEY,
    name            varchar(150) NOT NULL,
    contact_name    varchar(150),
    phone           varchar(50),
    email           varchar(190),
    is_service_vendor boolean NOT NULL DEFAULT false,
    created_at      timestamptz, updated_at timestamptz, deleted_at timestamptz
);

CREATE TABLE manufacturers (
    id              bigserial PRIMARY KEY,
    name            varchar(150) NOT NULL UNIQUE,
    created_at      timestamptz, updated_at timestamptz
);

CREATE TABLE categories (
    id              bigserial PRIMARY KEY,
    name            varchar(100) NOT NULL,
    type            varchar(20) NOT NULL
        CHECK (type IN ('asset','component','accessory','license')),
    tag_prefix      varchar(10),                    -- LT, DT, MN, PR, NW, PH
    created_at      timestamptz, updated_at timestamptz,
    UNIQUE (type, name)
);

CREATE TABLE asset_models (
    id              bigserial PRIMARY KEY,
    name            varchar(150) NOT NULL,
    model_number    varchar(100),
    manufacturer_id bigint REFERENCES manufacturers(id),
    category_id     bigint NOT NULL REFERENCES categories(id),
    depreciation_months integer,                    -- mis. 48 = 4 tahun garis lurus
    created_at      timestamptz, updated_at timestamptz, deleted_at timestamptz
);

-- ---------- Aset ----------

CREATE TABLE assets (
    id              bigserial PRIMARY KEY,
    asset_tag       varchar(30)  NOT NULL UNIQUE,   -- LT-00214
    serial_number   varchar(100),
    asset_model_id  bigint NOT NULL REFERENCES asset_models(id),
    status          varchar(20) NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending','ready','deployed','maintenance','retired')),
    -- pemegang saat ini: tepat satu terisi bila deployed
    assigned_employee_id bigint REFERENCES employees(id),
    assigned_location_id bigint REFERENCES locations(id),
    assigned_at     timestamptz,
    expected_return_date date,
    location_id     bigint REFERENCES locations(id), -- lokasi fisik / default
    supplier_id     bigint REFERENCES suppliers(id),
    purchase_order  varchar(50),
    purchase_date   date,
    purchase_cost   numeric(15,2),
    warranty_until  date,
    last_audited_at date,
    notes           text,
    retired_at      timestamptz,
    retire_reason   varchar(30)
        CHECK (retire_reason IN ('end_of_life','broken','lost','sold','donated','destroyed')),
    created_by      bigint REFERENCES users(id),
    created_at      timestamptz, updated_at timestamptz, deleted_at timestamptz,
    CONSTRAINT assets_single_assignee CHECK (
        NOT (assigned_employee_id IS NOT NULL AND assigned_location_id IS NOT NULL)
    ),
    CONSTRAINT assets_deployed_has_assignee CHECK (
        status <> 'deployed' OR assigned_employee_id IS NOT NULL OR assigned_location_id IS NOT NULL
    )
);
CREATE UNIQUE INDEX assets_serial_unique ON assets(serial_number) WHERE serial_number IS NOT NULL AND deleted_at IS NULL;
CREATE INDEX assets_status_idx    ON assets(status);
CREATE INDEX assets_employee_idx  ON assets(assigned_employee_id);
CREATE INDEX assets_warranty_idx  ON assets(warranty_until);

-- Riwayat semua kejadian aset (append-only)
CREATE TABLE asset_logs (
    id              bigserial PRIMARY KEY,
    asset_id        bigint NOT NULL REFERENCES assets(id),
    action          varchar(30) NOT NULL
        CHECK (action IN ('created','tagged','checkout','checkin','maintenance_start',
                          'maintenance_done','component_installed','component_removed',
                          'updated','audited','retired')),
    from_status     varchar(20),
    to_status       varchar(20),
    employee_id     bigint REFERENCES employees(id),   -- user penerima/pengembali
    location_id     bigint REFERENCES locations(id),
    note            text,
    meta            jsonb,                              -- kondisi, nomor BAST, dll
    performed_by    bigint REFERENCES users(id),
    created_at      timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX asset_logs_asset_idx ON asset_logs(asset_id, created_at DESC);

-- ---------- Komponen (dipasang ke aset) ----------

CREATE TABLE components (
    id              bigserial PRIMARY KEY,
    name            varchar(150) NOT NULL,          -- RAM DDR4 8 GB
    spec            varchar(150),                   -- SODIMM 3200 MHz
    category_id     bigint NOT NULL REFERENCES categories(id),
    manufacturer_id bigint REFERENCES manufacturers(id),
    qty_total       integer NOT NULL DEFAULT 0 CHECK (qty_total >= 0),
    min_qty         integer NOT NULL DEFAULT 0,
    unit_cost       numeric(15,2),
    location_id     bigint REFERENCES locations(id),
    created_at      timestamptz, updated_at timestamptz, deleted_at timestamptz
);

CREATE TABLE component_installations (
    id              bigserial PRIMARY KEY,
    component_id    bigint NOT NULL REFERENCES components(id),
    asset_id        bigint NOT NULL REFERENCES assets(id),
    qty             integer NOT NULL DEFAULT 1 CHECK (qty > 0),
    is_factory      boolean NOT NULL DEFAULT false, -- bawaan pabrik, tidak mengurangi stok
    maintenance_id  bigint,                         -- FK ditambah setelah tabel maintenances
    installed_at    timestamptz NOT NULL DEFAULT now(),
    installed_by    bigint REFERENCES users(id),
    removed_at      timestamptz,
    removed_by      bigint REFERENCES users(id)
);
CREATE INDEX component_installations_active_idx ON component_installations(component_id) WHERE removed_at IS NULL;

-- ---------- Aksesori (dipinjamkan ke user) ----------

CREATE TABLE accessories (
    id              bigserial PRIMARY KEY,
    name            varchar(150) NOT NULL,
    model           varchar(150),
    category_id     bigint NOT NULL REFERENCES categories(id),
    manufacturer_id bigint REFERENCES manufacturers(id),
    qty_total       integer NOT NULL DEFAULT 0 CHECK (qty_total >= 0),
    min_qty         integer NOT NULL DEFAULT 0,
    unit_cost       numeric(15,2),
    location_id     bigint REFERENCES locations(id),
    created_at      timestamptz, updated_at timestamptz, deleted_at timestamptz
);

CREATE TABLE accessory_checkouts (
    id              bigserial PRIMARY KEY,
    accessory_id    bigint NOT NULL REFERENCES accessories(id),
    employee_id     bigint NOT NULL REFERENCES employees(id),
    qty             integer NOT NULL DEFAULT 1 CHECK (qty > 0),
    checked_out_at  timestamptz NOT NULL DEFAULT now(),
    checked_out_by  bigint REFERENCES users(id),
    checked_in_at   timestamptz,
    checked_in_by   bigint REFERENCES users(id),
    note            text
);
CREATE INDEX accessory_checkouts_active_idx ON accessory_checkouts(accessory_id) WHERE checked_in_at IS NULL;
CREATE INDEX accessory_checkouts_employee_idx ON accessory_checkouts(employee_id) WHERE checked_in_at IS NULL;

-- ---------- Lisensi software ----------

CREATE TABLE licenses (
    id              bigserial PRIMARY KEY,
    name            varchar(150) NOT NULL,
    manufacturer_id bigint REFERENCES manufacturers(id),
    category_id     bigint REFERENCES categories(id),
    license_type    varchar(20) NOT NULL
        CHECK (license_type IN ('subscription','perpetual','oem','concurrent')),
    product_key     text,                           -- disimpan terenkripsi (Laravel encrypted cast)
    seats_total     integer NOT NULL CHECK (seats_total > 0),
    supplier_id     bigint REFERENCES suppliers(id),
    purchase_order  varchar(50),
    purchase_date   date,
    annual_cost     numeric(15,2),
    expires_at      date,                           -- null = tidak kedaluwarsa
    notes           text,
    created_at      timestamptz, updated_at timestamptz, deleted_at timestamptz
);
CREATE INDEX licenses_expires_idx ON licenses(expires_at);

CREATE TABLE license_seats (
    id              bigserial PRIMARY KEY,
    license_id      bigint NOT NULL REFERENCES licenses(id),
    employee_id     bigint REFERENCES employees(id),
    asset_id        bigint REFERENCES assets(id),
    assigned_at     timestamptz NOT NULL DEFAULT now(),
    assigned_by     bigint REFERENCES users(id),
    released_at     timestamptz,
    released_by     bigint REFERENCES users(id),
    CONSTRAINT license_seats_target CHECK (employee_id IS NOT NULL OR asset_id IS NOT NULL)
);
CREATE INDEX license_seats_active_idx ON license_seats(license_id) WHERE released_at IS NULL;
-- Batas seats_total dicek di Action AssignLicenseSeat dengan SELECT ... FOR UPDATE pada licenses.

-- ---------- Maintenance ----------

CREATE TABLE maintenance_plans (                    -- jadwal preventif
    id              bigserial PRIMARY KEY,
    name            varchar(150) NOT NULL,
    category_id     bigint REFERENCES categories(id),   -- berlaku untuk kategori, atau
    location_id     bigint REFERENCES locations(id),    -- untuk lokasi tertentu
    interval_months integer NOT NULL CHECK (interval_months > 0),
    next_due_date   date NOT NULL,
    pic             varchar(150),
    supplier_id     bigint REFERENCES suppliers(id),
    is_active       boolean NOT NULL DEFAULT true,
    created_at      timestamptz, updated_at timestamptz
);

CREATE TABLE maintenances (                         -- tiket
    id              bigserial PRIMARY KEY,
    ticket_number   varchar(20) NOT NULL UNIQUE,    -- MT-2026-0001
    asset_id        bigint NOT NULL REFERENCES assets(id),
    plan_id         bigint REFERENCES maintenance_plans(id),
    type            varchar(20) NOT NULL
        CHECK (type IN ('repair','preventive','upgrade','calibration')),
    status          varchar(20) NOT NULL DEFAULT 'reported'
        CHECK (status IN ('reported','in_progress','waiting_parts','done','cancelled')),
    priority        varchar(10) NOT NULL DEFAULT 'medium'
        CHECK (priority IN ('low','medium','high')),
    title           varchar(200) NOT NULL,
    description     text,
    handled_by      varchar(20) NOT NULL DEFAULT 'internal'
        CHECK (handled_by IN ('internal','vendor','warranty')),
    supplier_id     bigint REFERENCES suppliers(id),
    reported_by_employee_id bigint REFERENCES employees(id),
    estimated_cost  numeric(15,2),
    actual_cost     numeric(15,2),
    start_date      date,
    target_date     date,
    completed_at    timestamptz,
    asset_status_before varchar(20),                -- untuk mengembalikan status setelah selesai
    created_by      bigint REFERENCES users(id),
    created_at      timestamptz, updated_at timestamptz
);
CREATE INDEX maintenances_status_idx ON maintenances(status);
CREATE INDEX maintenances_asset_idx  ON maintenances(asset_id);

ALTER TABLE component_installations
    ADD CONSTRAINT component_installations_maintenance_fk
    FOREIGN KEY (maintenance_id) REFERENCES maintenances(id);

-- ---------- Peringatan ----------

CREATE TABLE alerts (
    id              bigserial PRIMARY KEY,
    type            varchar(40) NOT NULL
        CHECK (type IN ('employee_offboarding','warranty_expiring','license_expiring',
                        'license_seats_full','overdue_return','low_stock','maintenance_due')),
    severity        varchar(10) NOT NULL DEFAULT 'warning' CHECK (severity IN ('info','warning','critical')),
    subject_type    varchar(50) NOT NULL,           -- morph: employee, asset, license, component, ...
    subject_id      bigint NOT NULL,
    message         text NOT NULL,
    due_date        date,
    resolved_at     timestamptz,
    resolved_by     bigint REFERENCES users(id),
    created_at      timestamptz, updated_at timestamptz
);
CREATE UNIQUE INDEX alerts_open_unique ON alerts(type, subject_type, subject_id) WHERE resolved_at IS NULL;

-- ---------- View bantu ----------

-- Stok tersedia dihitung, bukan disimpan
CREATE VIEW component_stock AS
SELECT c.id AS component_id,
       c.qty_total,
       COALESCE(SUM(ci.qty) FILTER (WHERE ci.removed_at IS NULL AND NOT ci.is_factory), 0) AS qty_installed,
       c.qty_total - COALESCE(SUM(ci.qty) FILTER (WHERE ci.removed_at IS NULL AND NOT ci.is_factory), 0) AS qty_available
FROM components c
LEFT JOIN component_installations ci ON ci.component_id = c.id
GROUP BY c.id;

CREATE VIEW accessory_stock AS
SELECT a.id AS accessory_id,
       a.qty_total,
       COALESCE(SUM(ac.qty) FILTER (WHERE ac.checked_in_at IS NULL), 0) AS qty_out,
       a.qty_total - COALESCE(SUM(ac.qty) FILTER (WHERE ac.checked_in_at IS NULL), 0) AS qty_available
FROM accessories a
LEFT JOIN accessory_checkouts ac ON ac.accessory_id = a.id
GROUP BY a.id;
