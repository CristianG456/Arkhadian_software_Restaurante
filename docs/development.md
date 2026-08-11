# Development

For the Git branching model both developers follow, see
`docs/git-workflow.md`. For everything about migrations, seeders, and
keeping two local databases in sync, see `docs/database.md`.

## Requirements

Exact versions this project is built and verified against — install
these, don't guess:

- PHP **8.4** with `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `intl`, `zip` (`composer.json` requires `^8.3` as the floor)
- Composer **2.10+**
- Node.js **20+** (built against 24) and npm — see `apps/admin-desktop/package.json` `engines`
- Rust stable (MSVC toolchain on Windows) + Cargo — install via [rustup](https://rustup.rs)
- PostgreSQL **16+** (built against 18), running locally
- Windows: Microsoft C++ Build Tools ("Desktop development with C++"
  workload) + Windows 10/11 SDK (Tauri's native dependency), WebView2
  (preinstalled on current Windows)

Both developers should stay on the same major versions above — lockfiles
(`composer.lock`, `package-lock.json`, `src-tauri/Cargo.lock`) pin exact
dependency versions and are committed; don't delete or regenerate them
casually (see `docs/git-workflow.md`).

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

See `docs/database.md` for the full collaborative workflow: what to run
after pulling changes, the migration-immutability rule, and the seeders
convention.

## Running the stack

```powershell
.\scripts\dev.ps1          # checks prerequisites, Core in its own window, then Admin here
# or individually:
.\scripts\start-core.ps1   # Restaurant Core only
.\scripts\start-admin.ps1  # Admin Desktop only - opens a native Tauri window, never a browser
.\scripts\status.ps1       # quick CLI health/status check, no UI needed
```

`.\scripts\dev.ps1` is the normal way to run Restaurant Admin: it
validates PHP/Node/npm/Rust are on PATH, starts Core, waits for
`/api/health` to actually respond (failing fast with a clear message if
it doesn't within ~20s), then launches Admin as a native window. It
never opens a web browser.

Once running, Admin keeps retrying on its own if Core later goes down
and comes back — that resilience is independent of how the two were
started.

**`npm run dev` inside `apps/admin-desktop` is not "the app".** It's
Vite alone, the same command Tauri's `beforeDevCommand` runs internally
- opening `http://localhost:1420` in a browser shows the React UI but
skips Tauri entirely (no native window, no window title, no
single-instance behavior). Use it (or the identical `npm run dev:web`)
only for fast frontend-only iteration; use `.\scripts\start-admin.ps1`
or `npm run tauri -- dev` to actually run Restaurant Admin.

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
cd ..\..\
.\scripts\db-reset.ps1         # DEV ONLY: drop, re-migrate, reseed — see docs/database.md

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

**A `.ps1` script throws `TerminatorExpectedAtEndOfString` on someone's
machine but not yours.**
Windows PowerShell 5.1 reads `.ps1` files without a BOM using the system
codepage, not UTF-8 — an em dash or curly quote inside a string literal
gets misread as a different byte sequence and breaks parsing. Keep
`scripts/*.ps1` plain ASCII (use `-` instead of `—`, straight quotes
only); this bit us once already while building `db-reset.ps1`.
