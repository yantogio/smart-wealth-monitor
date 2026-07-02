## Context

Smart Wealth Monitor is a new local Laravel application running on XAMPP (PHP + MySQL) with Tailwind CSS for styling. It tracks a fixed, small watchlist (3 IHSG blue-chip stocks + gold) and combines two external data sources with different shapes: Yahoo Finance (free/unofficial public endpoints for stock history) and GoldAPI.io (API-key-based service for gold prices). This is the first change in the project, so it establishes the Laravel app skeleton, data model, sync mechanism, analytics logic, and all four UI pages from the blueprint.

## Goals / Non-Goals

**Goals:**
- Stand up a working local Laravel + MySQL + Tailwind app with the full blueprint's four pages (Dashboard, Watchlist & Analytics, Simulasi DCA, System Settings).
- Store daily historical closing prices per asset without duplicates.
- Sync prices via an explicit, on-demand "catch-up" mechanism (console command, optionally triggered by cron/scheduler or manually via UI) rather than continuous polling.
- Detect and surface "Potensi Diskon" (>5% drop from 30-day high) per asset.
- Support a DCA simulation driven entirely by data already stored in the local database (no live API calls during simulation).

**Non-Goals:**
- No real-time/intraday price streaming — daily closing prices only.
- No multi-user accounts/auth — this is a local single-user tool for this change.
- No support for assets beyond the fixed watchlist (BBCA.JK, BBRI.JK, TLKM.JK, XAU/USD) in this change; adding arbitrary assets is a future capability.
- No automated background scheduling is required to be wired into production infra — the Catch-Up Sync command must work correctly whether invoked manually, via UI, or via Laravel's scheduler.

## Decisions

- **Framework/stack**: Laravel (latest), PHP, MySQL, Tailwind CSS, Blade views — as specified. Rationale: matches the user's explicit requirement and is well-suited to a CRUD + scheduled-job style app.
- **Data model**:
  - `assets(id, code, name, type[stock|gold], created_at, updated_at)` — `code` unique (e.g. `BBCA.JK`, `XAUUSD`).
  - `historical_prices(id, asset_id FK, date, close_price, created_at, updated_at)` with a **unique composite index on `(asset_id, date)`** to guarantee no duplicate price per asset per day. Rationale: a DB-level unique constraint is the most reliable way to prevent duplication described in the blueprint, and pairs naturally with `upsert`/`updateOrCreate` semantics in the sync command.
- **Stock price fetching**: Call Yahoo Finance's public chart/history endpoint directly via Laravel's HTTP client (`Http::get(...)`), parsing the JSON response for daily closes over the requested range. Rationale: avoids depending on an unofficial, potentially unmaintained third-party package; keeps the integration inspectable and swappable. A thin `YahooFinanceClient` class wraps this so it can be swapped for a package later without touching calling code.
  - Alternative considered: a Composer package wrapping Yahoo Finance — rejected as a hard dependency for v1 due to maintenance risk of unofficial packages, but the wrapper class keeps this swap low-cost later.
- **Gold price fetching**: A `GoldApiClient` class calls GoldAPI.io's REST endpoint with the API key read from config/`.env` (and, per the Settings page requirement, optionally overridable via a stored settings value — see below).
- **API key storage**: Store the GoldAPI.io key primarily via `.env`/config for the initial setup, but since the blueprint requires an in-UI settings form to *update* it, persist a UI-updated key in a small `settings` table (key/value) that the `GoldApiClient` checks first, falling back to `.env`. Rationale: `.env` alone can't be edited safely from a web request; a settings table is the simplest way to support the "update via UI" requirement without a secrets manager.
- **Catch-Up Sync command**: `php artisan sync:prices` iterates each asset in the catalog, finds `MAX(date)` in `historical_prices` for that asset (or a sensible lookback default if no data exists yet), and fetches/upserts all missing daily prices up to today. Implemented as one Artisan command with per-asset error isolation (a failure on one asset logs and continues rather than aborting the whole run) so the "Force Sync Data" button and any future scheduler entry both call the same command.
- **Momentum Detector**: A `MomentumDetectorService` queries the last 30 days of `historical_prices` for an asset, computes `MAX(close_price)` as the 30-day high, compares it to the latest close, and returns a boolean/status. Computed on read (dashboard/watchlist load) rather than stored, since the watchlist is small (4 assets) and this keeps the flag always consistent with current data.
- **Moving average / mini chart**: Computed from stored `historical_prices` on read using a simple SQL/collection-based SMA (e.g., average of last N closes); charts rendered client-side with a lightweight JS charting library (e.g., Chart.js) fed by a small JSON endpoint or Blade-embedded data — avoids a heavy charting stack for "mini" charts.
- **DCA Simulator**: A `DcaSimulationService` takes asset, monthly amount, and start month, then for each month from start to the latest available month picks the price record closest to that month (e.g., first available trading day on/after the 1st) and computes units bought; sums units and capital. Entirely driven by local `historical_prices` data, no external calls at simulation time.

## Risks / Trade-offs

- [Yahoo Finance public endpoints are unofficial and can change/break without notice] → Isolate all Yahoo access behind `YahooFinanceClient`; log failures per-asset in the sync command so one broken source doesn't block gold sync or the rest of the app.
- [GoldAPI.io free-tier rate limits] → Catch-up sync design (only fetch missing days, not continuous polling) naturally minimizes call volume; document the limitation in Settings UI.
- [Storing an API key in a database table] → Acceptable for a local single-user tool; note as a limitation if this app is ever exposed beyond localhost.
- [DCA "closest price to month" approximation] → Document the exact rule (first available trading day on/after the 1st of the month) so simulation results are explainable and reproducible.
- [Unique constraint migration on `historical_prices`] → Must be added at initial migration time (no pre-existing data to conflict with), avoiding a later painful de-dup migration.

## Migration Plan

Not applicable — this is the first change in a new project; there is no existing schema or data to migrate. Initial migrations create `assets`, `historical_prices`, and `settings` tables directly.
