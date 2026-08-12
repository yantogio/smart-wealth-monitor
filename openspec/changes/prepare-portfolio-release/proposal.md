## Why

The application works — 22 tests pass, the service layer is clean, and eight capabilities are already specified — but none of that is visible to a stranger who opens the repository. The README is still the stock Laravel template, a fresh clone requires MySQL plus a paid API key before anything renders, and the dashboard shows an empty table until someone manually triggers a sync. The gap blocking publication is presentation and onboarding, not missing features.

## What Changes

- Replace the stock Laravel README with a real project README: what the app does, screenshots, architecture overview, setup in under five commands, and a pointer to the OpenSpec specs as evidence of the design process.
- Ship a committed demo price dataset so `db:seed` produces a populated dashboard, working charts, and a meaningful DCA simulation with **no API key and no network access**.
- Add a console command that regenerates that demo dataset from live API data, so the snapshot stays reproducible rather than being an unexplained blob.
- **BREAKING** for local setup: switch the default database connection to SQLite so a clone needs no database server. MySQL remains supported via `.env`; existing local installs must keep their `DB_CONNECTION=mysql` line.
- Schedule `sync:prices` to run daily so a deployed instance stays current without manual clicks.
- Add a GitHub Actions workflow running the test suite on push and pull request, plus a status badge in the README.
- Add an MIT LICENSE file.
- Remove leftover scaffolding (`tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`, the unused `welcome.blade.php`) so the repository reads as finished work.

Explicitly out of scope: authentication, asset CRUD, new analytics, and public deployment. The watchlist stays fixed at four assets as `asset-catalog` already specifies.

## Capabilities

### New Capabilities
- `demo-data-seeding`: A committed, offline price dataset and the command that regenerates it, so the application is fully explorable without external API access.
- `project-onboarding`: What the repository must tell and give a newcomer — README content, license, screenshots, and a setup path that works without a database server.
- `continuous-integration`: Automated test execution on push and pull request, with a visible result badge.

### Modified Capabilities
- `price-sync`: adds a requirement that the catch-up sync runs automatically on a daily schedule, rather than only when invoked manually.

## Impact

- **Docs and meta**: `README.md` (full rewrite), new `LICENSE`, new `docs/screenshots/`.
- **Config**: `.env.example` and `config/database.php` default connection; `composer.json` setup script.
- **Code**: `routes/console.php` (schedule entry), new demo seeder and export command under `database/seeders/` and `app/Console/Commands/`.
- **Data**: a committed dataset file (roughly 90 days × 4 assets) under `database/data/`.
- **CI**: new `.github/workflows/tests.yml`.
- **Tests**: two scaffold test files removed; new coverage for the demo seeder and export command.
- **Dependencies**: none added. Requires a working `METALS_API_KEY` once, only to generate the committed snapshot.
