## 1. Demo Dataset Generation

- [x] 1.1 Confirm `METALS_API_KEY` is funded and Yahoo Finance is reachable — no key found anywhere; resolved by sourcing demo gold from Yahoo `GC=F` (see design Decision 2)
- [x] 1.2 Run `php artisan historical:backfill --days=365` for stocks, plus a one-off `GC=F` fetch for `XAUUSD` — 244 records per stock, 252 for gold
- [x] 1.3 Verify the synced data produces live output — TLKM.JK flags "Potensi Diskon", SMA populated for all four, DCA returns 13/13 months

## 2. Demo Export Command

- [x] 2.1 Create `app/Console/Commands/ExportDemoPricesCommand.php` with signature `demo:export-prices`, writing to `database/data/demo-prices.json`
- [x] 2.2 Define the JSON structure: capture date, plus per-asset code, name, type, `source` provenance, and an ordered list of `{date, close}` entries
- [x] 2.3 Make the command fail with a non-zero exit code and leave the file untouched when no price history exists
- [x] 2.4 Add `tests/Feature/ExportDemoPricesCommandTest.php` — 4 tests covering export, gold provenance options, window limiting, and empty-history failure
- [x] 2.5 Run the command and commit `database/data/demo-prices.json` — 984 prices across 4 assets, 111 KB

## 3. Demo Seeder

- [x] 3.1 Create `database/seeders/DemoPriceSeeder.php` that reads the dataset file and resolves each asset by code
- [x] 3.2 Implement date rebasing: compute the offset between the dataset's newest date and today, apply it uniformly to every record
- [x] 3.3 Store prices with chunked `upsert` against the `(asset_id, date)` unique index so re-seeding updates rather than duplicates (faster than per-row `updateOrCreate` for ~1000 rows)
- [x] 3.4 Print a summary on completion, including an explicit warning that dates were shifted to end on today
- [x] 3.5 Register `DemoPriceSeeder` in `DatabaseSeeder` after `AssetSeeder`; also removed the unused dummy `User` creation since the app has no auth
- [x] 3.6 Add `tests/Feature/DemoPriceSeederTest.php` — 6 tests including seeding in 2029 to prove the demo never goes stale

## 4. Zero-Friction Setup

- [x] 4.1 Change `.env.example` to `DB_CONNECTION=sqlite`, keeping the MySQL lines commented for reference; documented `METALS_API_KEY` as optional
- [x] 4.2 Update the `setup` script in `composer.json` to create `database/database.sqlite` before migrating, and to run `db:seed --force` afterward
- [x] 4.3 Add `database/database.sqlite` and its journal to `.gitignore`; confirmed `database/data/demo-prices.json` is not ignored
- [x] 4.4 Verify migrate + seed against a throwaway SQLite file — 984 prices in 117 ms, all four assets rendering live prices, SMA, 60+ chart points, and DCA results
- [x] 4.5 Add `tests/Feature/PageRenderingTest.php` — 7 HTTP smoke tests covering the spec's "dashboard populated after seeding" scenario, which had no test coverage at any level before

## 5. Scheduled Sync

- [x] 5.1 Add `Schedule::command('sync:prices')->dailyAt('18:00')->withoutOverlapping()` to `routes/console.php`; removed the unused `inspire` scaffold command
- [x] 5.2 Verify the entry appears in `php artisan schedule:list` — `0 18 * * * php artisan sync:prices`
- [x] 5.3 Add `tests/Feature/ScheduledSyncTest.php` asserting the cron expression and the no-overlap guard

## 6. Continuous Integration

- [x] 6.1 Create `.github/workflows/tests.yml` triggering on push and pull request to `main`
- [x] 6.2 Configure the job: checkout, PHP 8.3 **and** 8.4 matrix, cached `composer install`, `php artisan test` — no service container needed thanks to in-memory SQLite
- [ ] 6.3 Push the branch and confirm the workflow passes on GitHub before relying on the badge — **requires push**

## 7. Repository Cleanup

- [x] 7.1 Delete `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php`
- [x] 7.2 Delete `resources/views/welcome.blade.php` after confirming no route or view references it
- [x] 7.3 Add an MIT `LICENSE` file at the repository root
- [x] 7.4 Run `php artisan test` and `vendor/bin/pint` — 39 tests / 108 assertions pass, formatting clean

## 8. Screenshots

- [x] 8.1 Create `docs/screenshots/` with a capture guide listing required filenames and visible content
- [ ] 8.2 Capture `dashboard.png` with a "Potensi Diskon" card visible — **requires manual capture**
- [ ] 8.3 Capture `watchlist.png` showing the price chart and SMA column — **requires manual capture**
- [ ] 8.4 Capture `dca.png` showing a completed simulation result — **requires manual capture**

## 9. README

- [x] 9.1 Replace `README.md` entirely: project title, one-line description, CI/license/PHP/Laravel badges, and screenshots near the top
- [x] 9.2 Write the feature section: momentum/discount detection, DCA simulator, watchlist analytics, dual-source price sync
- [x] 9.3 Write the architecture section: ASCII data-flow diagram from API clients through `historical_prices` to the views, plus the tech stack
- [x] 9.4 Write the demo setup section — four commands, no API key, no database server — marked as the recommended starting point
- [x] 9.5 Write the live-data section: metalpriceapi key, backfill, catch-up sync, and both ways to activate the scheduler
- [x] 9.6 Document the MySQL alternative in a collapsed `<details>` section
- [x] 9.7 Add the Security note stating the app is unauthenticated and local-first
- [x] 9.8 Add the spec-driven development section listing all ten capabilities, plus the demo-data note on date shifting and gold provenance
- [x] 9.9 Verify the README top to bottom on a clean copy — `composer setup` succeeded, DB seeded with 984 prices, all four pages returned 200 with live prices and a discount badge

## 10. Release

- [x] 10.1 Archive the completed `fix-dca-history-and-gold-source` change — used `--skip-specs` because the main specs had already been synced, which was why the normal archive aborted
- [x] 10.2 Stage the work in logical commits on branch `prepare-portfolio-release` — 9 commits, including one for the previously uncommitted change
- [x] 10.3 Confirm `.env` is gitignored and the dataset contains no API keys or personal data
- [ ] 10.4 Push and verify on GitHub: README renders, images load, badge is green, license is detected — **requires push**
