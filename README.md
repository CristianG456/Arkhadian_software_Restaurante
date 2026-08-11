# Restaurant System — Phase 1: Technical Base

A Local-First restaurant management system. Phase 1 delivers the
technical foundation only: a desktop admin app, a local API backend, and
a local database, all verifiably talking to each other — no tables,
orders, menu, billing, or other business features yet (see
`docs/architecture.md` for what's intentionally out of scope).

> If Internet disappears, the restaurant must be able to keep operating.
> Admin, Core and PostgreSQL are fully local; Internet is only used for
> things layered on top later (cloud sync, e-invoicing, remote support).

## Structure

```
apps/
  admin-desktop/       Tauri + React + TypeScript + Vite desktop app
services/
  restaurant-core/     Laravel local REST API + PostgreSQL access
                       (its own database/ with migrations lives here —
                        there is no separate top-level database/ folder;
                        nothing cross-service needs one yet)
scripts/                PowerShell dev scripts
docs/                   architecture.md, development.md
```

See `docs/architecture.md` for how the pieces fit together and the
reasoning behind the key decisions, and `docs/development.md` for setup,
environment variables, and troubleshooting.

## Requirements

PHP 8.3+, Composer, Node.js 20+, Rust + Cargo, PostgreSQL 16+. Full list
and versions in `docs/development.md#requirements`.

## Quick start

```powershell
.\scripts\install.ps1     # install dependencies, create .env files
# create the restaurant_core / restaurant_core_test PostgreSQL role+databases
# (see docs/development.md#database), then:
.\scripts\migrate.ps1
.\scripts\dev.ps1         # starts Restaurant Core + Admin Desktop
```

Admin Desktop opens as a native window and shows the system status
screen: Restaurant Core, database, local server and Internet, each
checked independently and re-polled automatically.

## Main commands

| Command | What it does |
|---|---|
| `.\scripts\install.ps1` | Install all dependencies |
| `.\scripts\migrate.ps1` | Run Restaurant Core's database migrations |
| `.\scripts\dev.ps1` | Start Core + Admin together |
| `.\scripts\start-core.ps1` | Start Restaurant Core only |
| `.\scripts\start-admin.ps1` | Start Admin Desktop only |
| `.\scripts\status.ps1` | CLI health/status check, no UI |

## Status

Phase 1 (technical base) — complete. See the phase's closing summary for
what's implemented and what's deliberately deferred to later phases.
