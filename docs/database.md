# Database — collaborative workflow

The database's reproducible state is **code + migrations + seeders +
environment variables** — never a copied `.sql` dump, never a manually
edited table. Any developer should be able to clone the repo, point it at
an empty local PostgreSQL, and end up with the same schema the other
developer has, without ever touching their database.

## After cloning

1. Create the local role/database (see `docs/development.md#database` for
   the exact `CREATE ROLE` / `CREATE DATABASE` statements).
2. Copy `.env.example` to `.env` and fill in `DB_PASSWORD` with the
   password you chose above.
3. `.\scripts\migrate.ps1` — applies pending migrations, keeps any data
   you already have.

## After pulling changes from the other developer

```powershell
git pull --ff-only origin develop
.\scripts\migrate.ps1
```

If the changes introduced new essential seed data, also run
`php artisan db:seed` (safe to run repeatedly — see "Seeders" below).

## Resetting your local development database

```powershell
.\scripts\db-reset.ps1
```

Drops every table, re-runs all migrations, then seeds. **Development
only.** It prints a confirmation prompt before doing anything — pass
`-Force` to skip it in a non-interactive context. Never point this at an
installation with real data; there is no undo.

## Migrations

- Every structural change to PostgreSQL goes through a Laravel
  migration. No manual `ALTER TABLE`, no structural change that isn't
  represented in a migration file.
- **A migration that has been pushed to `develop` (or merged further) is
  immutable.** Once the other developer might have pulled and run it,
  editing it to change its behavior is not safe — they'd have a
  different schema than a fresh clone would produce, with no error to
  signal the drift. Add a new migration instead:

  ```
  2026_08_11_000000_create_users_table.php     ← already on develop, don't touch
  2026_08_13_000000_add_phone_to_users_table.php  ← new migration for the new column
  ```

  Only edit a migration you're certain is still local-only (created and
  never pushed). When in doubt, treat it as shared and add a new one.
- Each migration: has both `up()` and `down()` when reversing is safe to
  support; creates the indexes/foreign keys it needs; picks
  `cascade` / `restrict` / `set null` deliberately, not by default;
  avoids one-off raw SQL when the schema builder covers the case.
- `php artisan migrate` (pending-only, non-destructive) is what real
  installations and `git pull` both use. `php artisan migrate:fresh` is
  a development/testing tool only — see `docs/architecture.md` for why
  Restaurant Core can never assume it's safe to drop and rebuild a
  restaurant's live database.

### Two developers, two new migrations at once

Git won't show a text conflict if you and the other developer each add a
differently-named migration touching the same table the same week — but
the *database* can still end up wrong (duplicate columns, conflicting
assumptions). Before pushing, check what new migrations landed on
`origin/develop` since you branched and read them, not just `git log`
one-liners.

## Seeders

`database/seeders/DatabaseSeeder.php` is the single entry point and
splits seeding into two kinds:

- **Essential** (`database/seeders/Essential/`) — data every installation
  needs to function: catalogs, base configuration, predefined states.
  Written with `updateOrCreate()` (or equivalent) so running it again
  against a database that already has the data is a no-op, not a
  duplicate. Runs every time, including in a real restaurant's
  installation.
- **Development** (`database/seeders/Development/`) — local/demo data
  only. Gated in `DatabaseSeeder::run()` to `local`/`testing`
  environments so it can never run against a real installation.

Phase 1 has no business entities, so neither folder has anything in it
yet — `DatabaseSeeder` documents the split with empty hook methods
(`runEssentialSeeders()` / `runDevelopmentSeeders()`) ready for the first
real seeder. When a future migration introduces structural data a module
needs (e.g. a `roles` table), add the matching seeder in the same
feature branch — never leave it as a "run this SQL by hand" instruction.

### Areas and tables development data

The Areas and Tables module registers
`Development/AreasAndTablesSeeder`. It is idempotent and remains behind the
existing `local`/`testing` gate, so demo areas and tables never become required
production data. See [`areas-and-tables.md`](areas-and-tables.md).

## Identifiers

Anything created after Phase 1 that might eventually sync between local
and cloud uses **ULIDs** (`HasUlids`), not autoincrementing integers —
decided and documented in `docs/architecture.md#identifiers-future-entities`.
This is a one-way door once real data exists, so it isn't revisited
per-model; it's the default for every new significant table.

## Verifying reproducibility

Before pushing anything that touches migrations or seeders:

```powershell
.\scripts\db-reset.ps1 -Force
cd services\restaurant-core
php artisan test
```

A clean database has to reach a fully-migrated, fully-seeded, all-tests-passing
state through the repository alone — never through a database copy from
the other developer's machine.
