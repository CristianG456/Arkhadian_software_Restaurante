# Development

## Requirements

- PHP 8.3+ with `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `intl`, `zip`
- Composer 2
- Node.js 20+ and npm
- Rust (stable, MSVC toolchain on Windows) + Cargo
- PostgreSQL 16+, running locally
- Windows: Microsoft C++ Build Tools + Windows 10/11 SDK (Tauri's native
  dependency), WebView2 (preinstalled on current Windows)

## First-time setup

```powershell
# From the repo root
.\scripts\install.ps1
```

This runs `composer install` for Restaurant Core, `npm install` for
Admin Desktop, and creates each app's `.env` from `.env.example` if
missing.

### Database

Restaurant Core expects a dedicated PostgreSQL role and database (not
the `postgres` superuser):

```sql
CREATE ROLE restaurant_core WITH LOGIN PASSWORD '...';
CREATE DATABASE restaurant_core OWNER restaurant_core;
-- for running the test suite:
CREATE DATABASE restaurant_core_test OWNER restaurant_core;
```

Set `DB_PASSWORD` in `services/restaurant-core/.env` to match, then:

```powershell
.\scripts\migrate.ps1
```

## Running the stack

```powershell
.\scripts\dev.ps1          # Core in its own window + Admin here
# or individually:
.\scripts\start-core.ps1   # Restaurant Core only
.\scripts\start-admin.ps1  # Admin Desktop only (native window, not a browser tab)
.\scripts\status.ps1       # quick CLI health/status check, no UI needed
```

Admin Desktop keeps running and retrying if Core isn't up yet — you
don't need to start things in a particular order.

## Environment variables

### `services/restaurant-core/.env`

| Variable | Purpose |
|---|---|
| `APP_VERSION` | Core's version, surfaced by both API endpoints |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | PostgreSQL connection (never sqlite/mysql) |
| `CORE_HOST` / `CORE_PORT` | Where Core's HTTP server binds |
| `CORS_ALLOWED_ORIGINS` | Comma-separated origins allowed to call the API |
| `INTERNET_CHECK_HOSTS` / `INTERNET_CHECK_TIMEOUT` | TCP targets/timeout for the reachability probe |
| `LOG_CHANNEL` / `LOG_DAILY_DAYS` | Logging channel and rotation window |

### `apps/admin-desktop/.env` (optional — every value has a local default)

| Variable | Purpose |
|---|---|
| `VITE_CORE_BASE_URL` | Restaurant Core's base URL |
| `VITE_STATUS_POLL_INTERVAL_MS` | How often the status screen re-polls Core |
| `VITE_CORE_REQUEST_TIMEOUT_MS` | Per-request timeout before Core is treated as unreachable |

## Common commands

```powershell
# Restaurant Core
cd services\restaurant-core
php artisan test              # run the test suite
.\vendor\bin\pint              # format PHP
php artisan route:list         # inspect registered routes
php artisan migrate:fresh      # reset the local database

# Admin Desktop
cd apps\admin-desktop
npm run lint                   # ESLint
npm run build                  # tsc + vite build (frontend only)
npm run tauri -- build         # full native build (installers in src-tauri/target/release/bundle)
```

## Troubleshooting

**Admin shows "Sin conexión" for Restaurant Core.**
Confirm Core is running and reachable: `.\scripts\status.ps1`, or
`curl http://127.0.0.1:8000/api/health`. Check
`services/restaurant-core/storage/logs/` for startup errors.

**Status screen shows "Base de datos: Sin conexión".**
Core is up but can't reach PostgreSQL. Check the Windows service
(`Get-Service postgresql*`) and that `DB_*` in `.env` matches an
existing role/database. This is expected, non-crashing behavior — Core
keeps serving `/api/system/status` either way.

**`php artisan migrate` fails with a connection error.**
Same as above — verify PostgreSQL is running and the credentials in
`.env` are correct before re-running.

**Tauri build fails looking for a C++ linker/Windows SDK.**
Install "Desktop development with C++" via Visual Studio Build Tools —
Tauri's Rust side links natively on Windows and needs `cl.exe` /
`link.exe` and the Windows SDK libraries.

**Port already in use.**
`CORE_PORT` (default 8000) and Vite's dev port (fixed at `1420` for
Tauri, see `vite.config.ts`) need to be free. Change `CORE_PORT` in
`.env` if 8000 is taken by something else — don't hardcode a different
port anywhere else; everything reads it from configuration.
