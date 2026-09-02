# Architecture

## Overview

```
PC ADMINISTRACIÓN / CAJA
│
├── Restaurant Admin.exe   (Tauri + React + TypeScript + Vite)
│
├── Restaurant Core        (Laravel / PHP, local REST API)
│
└── PostgreSQL             (local database)
```

Restaurant Admin never talks to PostgreSQL directly. The only allowed flow is:

```
Restaurant Admin  →  Restaurant Core  →  PostgreSQL
```

Restaurant Core is the single layer that owns data access and business
logic. Future clients (the waiter's Flutter app) will talk to Core over
the LAN the same way Admin does over `localhost` — same API, same rules.

## Local-First

The restaurant must be able to operate with no Internet connection.
Concretely: Core, PostgreSQL and Admin are fully local; Internet is only
used for things layered on top later (cloud sync, e-invoicing, remote
support, updates). A missing Internet connection is an expected,
non-critical state — `GET /api/system/status` always reports it as its
own field, never folded into Core's own health.

## Process separation

Admin Desktop and Restaurant Core are independent OS processes:

- Closing the Admin window must not stop Core (later phases depend on
  Core staying up for LAN clients like waiter tablets, independent of
  whether anyone has Admin open).
- Core is started as its own process (`php artisan serve` in dev; a real
  service in production — see "Production startup" below), never
  spawned from inside the Tauri/React process.
- `scripts/dev.ps1` starts Core in its own window precisely to make this
  separation visible during development, instead of hiding it behind a
  single combined process.

### Single instance

Admin Desktop uses `tauri-plugin-single-instance` (see
`apps/admin-desktop/src-tauri/src/lib.rs`): launching it a second time
focuses the existing window instead of opening a duplicate. This keeps
behavior predictable — there is exactly one Admin window per machine.

### Production startup (target design for a later phase)

```
Windows starts
      ↓
Restaurant Core starts (Windows Service / Task Scheduler)
      ↓
PostgreSQL available (its own Windows service, already true today)
      ↓
Admin can be opened, any time, independently
```

Phase 1 does not package Core as a Windows Service — that is an
installer/packaging concern out of scope here — but nothing in Core's
design assumes an interactive session or a parent process; running it as
a service later is a deployment change, not a rewrite.

## Communication: Admin → Core

All HTTP access to Core lives under `apps/admin-desktop/src/api/`:

- `client.ts` — the only place that calls `fetch`. Owns the base URL,
  timeout (`AbortController`), JSON parsing and error classification.
- `system.ts` — typed functions per endpoint (`fetchHealth`,
  `fetchSystemStatus`).
- `types.ts` — response contracts, kept in sync with
  `app/Domain/System` on the Core side.
- `ApiClientError.ts` — the one error type every call can throw
  (`network` / `timeout` / `http` / `invalid_response`), with a
  `userMessage` safe to render directly.

Components never call `fetch` and never see a raw URL — they consume
`useSystemStatus()` (`src/hooks/useSystemStatus.ts`), which polls on an
interval (`VITE_STATUS_POLL_INTERVAL_MS`, default 5s — deliberately not
aggressive) and exposes a `refresh()` for the manual "Actualizar estado"
button.

### Connectivity states

`useSystemStatus` tracks two different things that are easy to conflate:

- **`connectivity`** (`CHECKING | ONLINE | OFFLINE | ERROR`) — Admin's own
  read on whether it can reach Core at all.
- **`data.core.status`** (`online | degraded`) — what Core reports about
  *itself* once it does answer (e.g. maintenance mode).

Downstream fields (`database`, `localServer`, `internet`) only render as
"disponible/sin conexión" once `connectivity === 'ONLINE'`; while Core is
unreachable they render as *unknown*, never a guessed positive/negative.

### Startup tolerance

A Core that is merely slow to boot must not flash "sin conexión" for a
few seconds. Failures inside the first `STARTUP_GRACE_MS` (6s) after
mount keep showing `CHECKING` instead of jumping straight to `OFFLINE`.

## Admin Desktop frontend structure

```
src/
├── api/              client.ts, system.ts, types.ts — see above
├── app/              AppShell, navigation, SystemStatusContext, ErrorBoundary
├── components/
│   ├── ui/            generic primitives (Button, Dialog, Toast, ...) —
│   │                   no knowledge of tables/orders/products/restaurant
│   └── layout/         Topbar, Sidebar — the shell chrome
├── hooks/            useSystemStatus
├── screens/          Inicio, Configuración/Diagnóstico, and dev/ (below)
├── styles/           tokens.css (design tokens), global.css (reset/base)
└── utils/            small generic helpers (cx, ...)
```

The Sidebar lists every future module (Áreas y mesas, Pedidos,
Productos, ...) up front, disabled until its module ships — the shape
of the product is visible before every part of it exists, rather than
navigation growing unpredictably module by module.

`components/ui` is the one constraint that matters here: those
components take generic props (`variant`, `label`, `items`) and render
generic markup. A future `Pedidos` screen composing them is a consumer,
never a reason to add restaurant-specific branches inside `Button` or
`Dialog`.

### Component reference screen (dev-only)

`screens/dev/ComponentShowcaseScreen.tsx` renders every `components/ui`
primitive for visual review — not a demo users or a shipped POS should
ever see. Two independent things keep it out of production:

- `AppShell` only renders the trigger button when `import.meta.env.DEV`
  is true (Vite bakes this to `false` in a `tauri build`).
- It's loaded via `React.lazy()`, so its module graph is a separate
  build chunk fetched only if that trigger is clicked — a production
  build that (correctly) never shows the trigger never fetches it either.

## Restaurant Core

### Áreas y mesas

El primer módulo de negocio mantiene `Area` y `RestaurantTable` como entidades
locales con ULID. Sus reglas y decisiones de conservación están documentadas
en [`areas-and-tables.md`](areas-and-tables.md). Los estados activa/desactivada
son configurativos; ocupación y mesero responsable serán responsabilidad de
una futura `TableSession`.

### Module structure

Business modules live under `app/Domain/<Module>/{Http/Controllers,
Services,...}`. Phase 1 only implements `System` (health/status); it
exists to establish the convention future modules (`Tables`, `Orders`,
`Menu`, `Printing`, `Sync`, `Audit`, ...) follow, so adding one is a new
folder plus a new route file, not a reorganization. No empty placeholder
folders were created for modules that don't exist yet.

### API contract

- `GET /api/health` — liveness only, flat body (no envelope), the shape
  monitoring tools conventionally expect from a `/health` endpoint:
  `{status, service, version}`.
- `GET /api/system/status` — the full picture, wrapped in the standard
  envelope: `{success: true, data: {core, database, localServer,
  internet, installationId, checkedAt}}`.
- Every other response (including all errors) uses the same envelope:
  `{success: false, error: {code, message}}` — built in one place
  (`app/Support/Http/ApiResponse.php`) and wired into exception
  rendering in `bootstrap/app.php`, so no endpoint improvises its own
  shape. Messages are safe to show to a user; stack traces stay in logs.

### Why database/internet checks never throw

`DatabaseHealthService` and `InternetConnectivityService` both catch
their own failures and return a status value — `/api/system/status`
must keep answering (`{database: {status: "disconnected"}}`) even when
PostgreSQL is down, not turn into a 500. Internet reachability is
probed via a short-timeout TCP connect to a couple of well-known DNS
resolvers (`INTERNET_CHECK_HOSTS`), not a single arbitrary HTTP domain.

### Installation identity

A UUID generated on first boot and persisted to
`storage/app/private/installation.json` (gitignored) —
`InstallationIdentityService`. Deliberately not derived from a MAC
address or disk serial, and deliberately file-based rather than a DB
row: it must survive a database restore, since it will later anchor
licensing/remote-support/cloud-sync identity.

### Versioning

Core has one version (`APP_VERSION` in `.env`, surfaced via both API
endpoints); Admin has its own, independent version
(`apps/admin-desktop/package.json`, which `tauri.conf.json` reads via
`"version": "../package.json"` and the UI reads via a Vite `define`
in `vite.config.ts`). They're allowed to differ — the two apps ship and
version independently — but each is a single source of truth rather
than duplicated across files.

### PostgreSQL only

Restaurant Core's default connection is `pgsql`. No SQLite, including
in tests — `phpunit.xml` points at a dedicated `restaurant_core_test`
database so DB-dependent behavior (like the "database unreachable"
tests) exercises the real driver.

### Identifiers (future entities)

No business entities exist yet, but the convention is decided ahead of
time: primary keys for anything created after Phase 1 use ULIDs (via
Eloquent's `HasUlids`), not autoincrementing integers — they need to be
safely generatable offline, on multiple devices, and later merged with
cloud data without collisions.

### Sync and realtime (not built yet)

Nothing in Phase 1 assumes a specific sync/realtime mechanism, but the
shape is decided: future phases sync *operations/events*, not full table
copies, and Core's HTTP layer is a normal Laravel app that can grow a
broadcasting layer (Reverb/WebSockets) later without an API rewrite. No
`sync_operations` table or broadcasting infrastructure exists yet — it
would be dead code with nothing to produce or consume it.

### Security baseline

- CORS (`config/cors.php`) is restricted to Admin's known Tauri origins
  (`CORS_ALLOWED_ORIGINS`), never `*`.
- Admin Desktop's CSP (`tauri.conf.json`) restricts `connect-src` to
  local origins only.
- `.env` is never committed; `.env.example` documents every key with no
  real secrets.
- Waiter authentication and paired-device authorization are implemented; the old phase note below is retained only as historical context (see
  the product brief's "not implemented yet" list). `config/auth.php`
  is left inert (no `AUTH_MODEL`) rather than pointed at a placeholder.

### Logging

- Core: `daily` log channel (`storage/logs/laravel-*.log`), rotated and
  pruned after `LOG_DAILY_DAYS` (14) days.
- Admin: `tauri-plugin-log`, writing to the OS log directory with a
  5 MB cap and `RotationStrategy::KeepOne`, plus stdout in dev. Browser
  `console.*` calls are mirrored into it via `attachConsole()`
  (`src/main.tsx`) so communication/runtime errors are diagnosable after
  the window is closed — the same data a future "Ayuda → Diagnóstico"
  screen would read.

## Waiter mobile

`apps/waiter-mobile` is a Flutter app for Android and iOS. It talks only to Restaurant Core: it never connects directly to PostgreSQL or printers, and Core remains authoritative for identity, table ownership, prices, permissions, orders, transfers and print jobs.

Its trust chain separates a public Restaurant Code, a short-lived one-use pairing credential, an opaque revocable device credential, and an expiring waiter access token tied to that device. The app is Local-First but has no offline order queue: Internet may be absent while local Core remains reachable; if Core is unavailable the operational UI blocks and retries. Details are in `docs/waiter-app.md`.

## Deviations from the initial structure sketch

- No top-level `database/` folder: Laravel already owns
  `services/restaurant-core/database/` (migrations, seeders, factories),
  and nothing cross-service needs a separate one yet. An empty folder
  with no clear responsibility was worse than not having it — it can be
  added if/when something actually needs it (e.g. shared backup
  tooling across services).

## Timezones and dates

Core stores and reasons about time as ISO 8601 UTC timestamps (see
`checkedAt`, `installation.json`'s `created_at`) rather than manipulating
date strings by hand. A per-restaurant configurable timezone is a later
phase's concern once there's a restaurant entity to hang it off; nothing
here assumes Colombia's timezone is the only one that will ever exist.
