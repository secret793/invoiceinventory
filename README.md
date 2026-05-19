# GNSW E-Tracking System

A standalone GPS device tracking and management system for **GNSW (Gambia National Standards and Weights)**, built with a raw PHP MVC backend and a React/Vite frontend. Tracks the full lifecycle of GPS trackers from warehouse intake through field deployment, retrieval, and overstay billing.

---

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3 (raw MVC, no framework), PostgreSQL |
| Frontend | React 18 + Vite, Tailwind CSS |
| Auth | Custom JWT |
| Ports | Backend: 8080 — Frontend: 5000 |

---

## Running the App

### Backend
```bash
php -S 0.0.0.0:8080 -t etracking-app/backend/public etracking-app/backend/public/index.php
```

### Frontend
```bash
cd etracking-app/frontend && npm run dev
```

The Vite dev server proxies `/api` requests to `localhost:8080`.

### Default Admin Credentials
| Field | Value |
|---|---|
| Email | `admin@gnsw.gm` |
| Password | `password` |

---

## Project Structure

```
etracking-app/
  backend/
    app/Controllers/       # 25+ controllers
    app/Models/            # BaseModel + 20+ models
    app/Core/              # Router, Request, Response, Database, Auth
    app/Services/          # NotificationService, PermissionService,
                           #   OverstayCalculatorService, ExportService
    config/                # app.php, database.php
    routes/api.php         # 100+ routes (source of truth for all endpoints)
    database/migrations/   # PostgreSQL schema + seeds
    public/index.php       # Front controller + SPL autoloader
    scripts/               # CLI scripts (e.g. recalculate_overstay.php)
  frontend/
    src/contexts/          # AuthContext, NotificationContext, SidebarContext
    src/hooks/             # useDevices, useRetrievals, useMonitoring, etc.
    src/services/          # api.js + per-resource service modules
    src/pages/             # 30+ pages
    src/components/        # DataTable, Modal, StatusBadge, PageHeader, etc.
    src/index.css          # Tailwind + GN Blue/Red/Green brand tokens
```

---

## Features

| Module | Description |
|---|---|
| **Device Registry** | Register GPS trackers, bulk status change, bulk transfer to Distribution Points |
| **Transfers** | Approve (RECEIVED + location update) or cancel in bulk |
| **Distribution Points** | Accept devices, send to Allocation Point, return to inventory, handle returned/rejected devices |
| **Allocation Points** | Send to another AP, return to inventory, change status — all with live status counts |
| **Data Entry** | Dispatch form (25 fields), New Receipt, Receipts list, Dispatch Report export per AP |
| **Confirmed Dispatch** | Pick for affixing (creates DeviceRetrieval record), bulk pick, return data |
| **Monitoring** | Live 10-second poll, 16 columns, Add Note with manifest date, Overdue Devices filter |
| **Device Retrievals** | Retrieve Device, Return to Outstation, Generate Overstay Bill, Waiver, Approve Payment |
| **Overstay Billing** | Grace: 1 day (short route) / 2 days (long route). Rate: GMD 1,000/day flat. Auto-recalculates on every monitoring poll |
| **Dashboard** | GN Blue gradient header, 6 stat cards, device status pie chart, overstay bar chart, overdue devices table |
| **Permissions** | Role-based access with per-AP and per-destination permission grants |

---

## User Roles

| Role | Access |
|---|---|
| Super Admin | Full access including user/config management |
| Warehouse Manager | Full operational access (no user/config management) |
| Finance Officer | Finance screens + limited Device Retrievals view |
| Distribution Officer | Distribution Point screens |
| Allocation Officer | Specific Allocation Points only (permission-filtered) |
| Data Entry Officer | Data Entry Assignment screens (permission-filtered) |
| Affixing Officer | Confirmed Affixed screen (destination + AP filtered) |
| Retrieval Officer | Device Retrievals (destination-filtered) |
| Monitoring Officer | Live monitoring screen only |
| Read Only Tracker | View-only on Devices, Retrievals, Confirmed Affixed |

---

## Brand

| Colour | Hex | Usage |
|---|---|---|
| GN Blue | `#1E2D7A` | Primary buttons, navigation |
| Gambian Red | `#E31E24` | Danger actions |
| SW Green | `#085E37` | Success states |

---

## Architecture Notes

- PHP raw MVC with SPL autoloader — zero framework overhead
- `Database.php` auto-detects PostgreSQL from `DATABASE_URL`
- `Database::insert()` uses `RETURNING id` for PostgreSQL
- Status values are ALL_CAPS: `ONLINE`, `OFFLINE`, `DAMAGED`, `FIXED`, `LOST`, `RECEIVED`, `PENDING`, `RETRIEVED`, `UNCONFIGURED`, `CONFIGURED`
- Transfer records are **deleted** after approval/cancellation (not soft-deleted)
- Overstay is frozen (not recalculated) once `retrieval_status = RETRIEVED`
- Monitoring page polls every 10 seconds and triggers a batch overstay recalculation on each poll
