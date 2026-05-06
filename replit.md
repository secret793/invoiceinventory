# GNSW E-Tracking System

A standalone GPS device tracking and management system for GNSW (Gambia National Standards and Weights), built with raw PHP MVC backend + React/Vite frontend. Tracks the full lifecycle of GPS trackers from warehouse intake through field deployment, retrieval, and overstay billing.

**Important**: The Laravel app at `/var/www/invoiceinventory` must NOT be touched.

## Run & Operate

### Workflows
- **PHP Backend** — `php -S 0.0.0.0:8080 -t etracking-app/backend/public etracking-app/backend/public/index.php`
- **Start application** — `cd etracking-app/frontend && npm run dev` (port 5000)

### Default Admin Credentials
- Email: `admin@gnsw.gm` / Password: `password`

### Required Env Vars
- `DATABASE_URL` — Replit PostgreSQL connection string (auto-set by Replit DB)
- `GITHUB_TOKEN` — for GitHub pushes (secret)

## Stack
- **Backend**: PHP 8.3 (raw MVC, no framework), PostgreSQL via `DATABASE_URL`
- **Frontend**: React 18 + Vite, Tailwind CSS + @tailwindcss/forms
- **Auth**: Custom JWT in `app/Core/Auth.php`
- **Ports**: Backend 8080, Frontend 5000 (Vite proxy `/api` → `localhost:8080`)

## Where things live

```
etracking-app/
  backend/
    app/Controllers/   # 25 controllers
    app/Models/        # BaseModel + 20+ models
    app/Core/          # Router, Request, Response, Database, Auth
    app/Services/      # NotificationService, PermissionService, OverstayCalculator, ExportService
    config/            # app.php, database.php
    routes/api.php     # 100+ routes (source of truth for all API endpoints)
    database/migrations/002_create_etracking_tables_pg.sql  # PostgreSQL schema + seeds
    public/index.php   # Front controller + SPL autoloader
  frontend/
    src/contexts/      # AuthContext, NotificationContext, SidebarContext
    src/hooks/         # useDevices, useRetrievals, useMonitoring, etc.
    src/services/      # api.js + service modules per resource
    src/pages/         # 30+ pages (dashboard, devices, stores, transfers, distribution, allocation, dataentry, monitoring, retrievals, confirmedAffixed, config)
    src/components/    # common/ (DataTable, Modal, StatusBadge, PageHeader, etc.) + feature-specific
    src/index.css      # Tailwind + GN Blue/Red/Green brand tokens
```

## Architecture decisions
- PHP raw MVC with SPL autoloader — zero framework overhead, full control
- `Database.php` auto-detects PostgreSQL from `DATABASE_URL`; falls back to MySQL config
- `Database::insert()` uses `RETURNING id` for PostgreSQL
- All `BaseModel::paginate()` calls use table alias syntax to avoid ambiguous column errors
- `password_hash()` with `PASSWORD_BCRYPT, cost=12` — NOT the old Laravel test hash
- Status values use ALL_CAPS strings (ONLINE, OFFLINE, DAMAGED, FIXED, LOST, RECEIVED, PENDING, RETRIEVED, UNCONFIGURED, CONFIGURED)
- Transfer records are **deleted** after approval/cancellation (per spec), not kept

## Product
- **Device Registry**: Register GPS trackers, bulk status change, bulk transfer to Distribution Points
- **Transfers**: Approve (RECEIVED + location update) or Cancel (revert location) in bulk
- **Distribution Points**: Accept devices, send to Allocation Point, return to inventory, handle returned/rejected devices
- **Allocation Points**: Send to another AP, return to inventory, change status — all with live status counts
- **Data Entry**: Dispatch form (25 fields), New Receipt, Receipts list, Dispatch Report export per allocation point
- **Confirmed Dispatch**: Pick for affixing (creates DeviceRetrieval), bulk pick, return data
- **Monitoring**: Live 10s poll, all 16 columns, Add Note with manifest date, Overdue Devices filter
- **Device Retrievals**: Retrieve Device, Return to Outstation, Generate Overstay Bill, Waiver (Super Admin), Approve Payment (Finance Officer)
- **Dashboard**: GN Blue gradient header, 6 stat cards, device status pie chart, overstay bar chart, overdue devices table

## User preferences
- Brand: GN Blue `#1E2D7A` (buttons/nav), Gambian Red `#E31E24` (danger), SW Green `#085E37` (success)
- GitHub repo: `secret793/invoiceinventory`, branch: `main` (NEVER master)
- Push all etracking-app changes to GitHub after each session

## Gotchas
- DP `findWithDevices()` must exist on the DistributionPoint model (used by DistributionDetailPage)
- `AllocationPoint::slugify()` used in AP creation to auto-generate permissions
- Monitoring polls every 10 seconds — `useRef` to avoid stale closure cleanup issues
- Transfer bulk-approve deletes records after completing (per spec § 3 Approve Transfers)
- Return to Outstation archives the device_retrievals row (`is_archived = true`) — does NOT delete it

## Pointers
- API routes: `etracking-app/backend/routes/api.php`
- DB schema: `etracking-app/backend/database/migrations/002_create_etracking_tables_pg.sql`
- Brand CSS: `etracking-app/frontend/src/index.css`
- UI/Flow spec: `attached_assets/#_UI_Buttons,_Forms_&_Flow_Document_1778067736346.txt`
