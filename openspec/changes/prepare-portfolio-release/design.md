## Context

Smart Wealth Monitor is a Laravel 13 / PHP 8.3 application that tracks four fixed assets (three IDX stocks via Yahoo Finance, gold via metalpriceapi.com), flags "potensi diskon" when the latest close sits more than 5% below the trailing 30-day high, and runs DCA simulations over stored history. The domain logic lives in three services and is covered by 22 passing tests.

The obstacles to publishing are all in the shell around that logic:

```
  Repo surface                      Reality underneath
  ────────────────                  ──────────────────
  README = Laravel template   ──▶   8 written OpenSpec capabilities
  Clone → needs MySQL + key   ──▶   22 passing tests, clean services
  Dashboard → empty table     ──▶   working momentum + DCA engine
```

Two existing constraints shape the design. First, `phpunit.xml` already pins tests to SQLite in-memory, so the test suite is database-agnostic today and the default-connection switch carries no test risk. Second, `MomentumDetectorService::thirtyDayHigh()` filters on `date >= today - 30 days`. Any demo dataset with fixed calendar dates stops producing discount flags once it ages past that window — the dashboard would go blank again a month after publication.

## Goals / Non-Goals

**Goals:**
- A newcomer reaches a populated, interactive dashboard in under five minutes, offline, with no API key and no database server.
- The demo dataset is reproducible and explainable, not an opaque committed blob.
- The demo stays visually alive indefinitely — discount badges, charts, and DCA results keep working months after the snapshot was taken.
- The repository communicates competence at a glance: real README, screenshots, green CI badge, license.
- Production behavior is unchanged; a real deployment with real API keys works exactly as before.

**Non-Goals:**
- Authentication or authorization. The app stays single-user and local-first; the `/settings` page remains unprotected. Publishing the source does not mean deploying a public instance, and the README will say so explicitly.
- Asset CRUD. `asset-catalog` specifies a fixed watchlist; that stays.
- New analytics, indicators, or UI features.
- Hosting a live demo. Screenshots carry that weight instead.

## Decisions

### 1. Demo data is a committed JSON snapshot of real prices, rebased at seed time

**Decision:** Commit `database/data/demo-prices.json` — roughly 90 days of genuine closing prices for all four assets, captured once from the live APIs. `DemoPriceSeeder` reads it and, at seed time, shifts every date forward by a constant offset so the newest record lands on today.

The file stores real absolute dates so a reader can verify the data is genuine. The seeder computes `offset = today − max(date in file)` and applies it uniformly, preserving weekend gaps, trading-day rhythm, and the exact shape of every price series.

```
  file (captured 2026-08-12)          seeded on 2027-01-20
  ────────────────────────            ────────────────────
  2026-05-15  9,250    ─┐             2026-10-23  9,250
  2026-05-16  9,310     │  +161 days  2026-10-24  9,310
     ...                │  ────────▶     ...
  2026-08-12  8,700    ─┘             2027-01-20  8,700
                                       ▲
                        newest point always lands on today,
                        so the 30-day momentum window is
                        always populated
```

**Alternatives considered:**
- *Synthetic random-walk data.* No API key needed to generate, but the numbers are fiction — a reviewer who recognizes BBCA pricing sees it immediately, and it undercuts the "real API integration" claim the project is making.
- *Absolute dates, no rebasing.* Honest and simpler, but the demo decays: discount detection goes silent after 30 days and charts drift to the left edge. A portfolio repo is read for years.
- *SQL dump.* Ties the fixture to one database engine and is unreadable in a diff.
- *Storing relative day offsets in the file.* Equivalent behavior, but loses the provenance that makes the data verifiable.

**Trade-off accepted:** seeded dates do not correspond to the dates those prices actually occurred. The seeder output and the README will state this plainly, and the file retains the true capture date so nothing is misrepresented.

### 2. A separate export command regenerates the snapshot

`php artisan demo:export-prices` reads current `historical_prices` rows and writes the JSON file. This keeps the fixture reproducible: the README documents that the snapshot came from `sync:prices` followed by this command, rather than appearing by magic. It also gives the maintainer a one-command refresh.

The command is a maintenance tool, not part of the newcomer path. It requires a populated database and therefore real API access.

**Dependency resolved during implementation:** no `METALS_API_KEY` exists in either `.env` or the `settings` table, so spot gold from metalpriceapi is unavailable for building the snapshot. Probing Yahoo Finance showed `XAUUSD=X` returns no data but `GC=F` (COMEX gold futures) returns a full series with no API key.

**Decision:** the demo snapshot sources gold from Yahoo `GC=F`. This affects the fixture only — `SyncPricesCommand` and `BackfillHistoricalPricesCommand` still route gold through `MetalsApiClient` for live syncing, so production behavior and the `price-sync` spec are untouched.

Gold futures track spot within a small carry basis, so the series shape, volatility, and momentum behavior the demo is meant to show are faithful. To keep this honest rather than hidden, every asset entry in the dataset file carries a `source` field, and the entry for `XAUUSD` records that the demo values are COMEX futures rather than spot. The README repeats this in one line.

**Alternative considered:** shipping a stock-only demo. Rejected because it leaves a visible `—` on the dashboard and in every screenshot, which is precisely the "unfinished" impression this change exists to remove.

### 3. SQLite becomes the default connection; MySQL stays supported

`.env.example` ships `DB_CONNECTION=sqlite`, and `composer setup` touches `database/database.sqlite` before migrating. Nothing in the codebase uses MySQL-specific SQL — the queries are Eloquent aggregates (`max`, `orderByDesc`) that behave identically — and the test suite has run on SQLite all along, so this is a configuration change rather than a portability effort.

The README documents the MySQL path in a collapsed section for anyone who wants it.

**Impact on the existing local install:** the working `.env` is gitignored and untouched, so the current MySQL setup keeps running. Only fresh clones get SQLite.

### 4. Scheduling lives in `routes/console.php`, daily after IDX close

```php
Schedule::command('sync:prices')->dailyAt('18:00')->withoutOverlapping();
```

IDX closes at 16:00 WIB; 18:00 in the app timezone leaves margin for the provider to settle end-of-day data. `withoutOverlapping()` matters because a catch-up sync after a long gap can run for minutes and issue many sequential requests.

Scheduling is inert unless someone runs `php artisan schedule:work` or installs a cron entry — the README documents both, and notes that the demo path never needs either.

**Alternative considered:** queueing each asset's sync as a job. Better for a real deployment, worse here — it adds a queue worker to the setup instructions, which is exactly the friction this change exists to remove.

### 5. CI runs the existing suite unchanged

A single GitHub Actions job on push and PR to `main`: PHP 8.3, `composer install`, `php artisan test`. No database service container is needed because of the in-memory SQLite configuration. The badge in the README is the point — it converts 22 invisible passing tests into a visible signal.

### 6. Screenshots are committed images, not a live demo

Three PNGs under `docs/screenshots/` (dashboard with a discount card visible, watchlist with chart and SMA, DCA result), captured from the seeded demo. Committing images is a minor repo-weight cost that buys the single highest-leverage element of the README, since most readers never clone.

**These must be captured by hand after seeding** — the implementation can prepare the directory and the README references, but cannot produce the images itself. This is the second task in the change requiring human action.

## Risks / Trade-offs

- **Rebased dates could read as fabricated data** → The file keeps real dates, the seeder announces the shift in its console output, and the README explains the mechanism in one sentence. The prices themselves are never altered.
- **Demo gold comes from futures while live sync uses spot** → The dataset file records the source per asset and the README states it. A reader comparing demo numbers to spot gold sees a small basis difference with a documented cause, not an unexplained discrepancy.
- **SQLite default confuses someone deploying for real** → README documents MySQL explicitly rather than treating SQLite as the only option.
- **A published unprotected `/settings` page invites API-key abuse if anyone deploys it** → Out of scope to fix, but the README states in a Security note that this is a local-first single-user tool and should not be exposed publicly without adding authentication. Naming a known limitation reads as engineering judgment, not as an oversight.
- **Rebasing collides with real synced data if someone seeds a live database** → The seeder uses `updateOrCreate` on `(asset_id, date)`, matching the existing pattern, so it overwrites rather than duplicating. The command warns when it finds existing history and the README scopes it to fresh installs.
- **Screenshots go stale as the UI changes** → Accepted. Low churn expected on a portfolio project, and stale screenshots still beat none.

## Migration Plan

1. Land the demo dataset, seeder, and export command — the app remains fully functional throughout; nothing existing changes behavior.
2. Switch `.env.example` and `composer setup` to SQLite. Existing installs are unaffected because `.env` is gitignored.
3. Add the schedule entry, CI workflow, and LICENSE.
4. Capture screenshots from the seeded demo, then write the README against what the screenshots actually show.
5. Remove scaffolding files and archive the completed `fix-dca-history-and-gold-source` change.
6. Verify the whole newcomer path on a clean clone before pushing.

Rollback is per-item and trivial; no data migration or destructive step is involved.

## Open Questions

- ~~Is the `METALS_API_KEY` still funded for the one sync needed to build the snapshot?~~ Resolved: no key exists; demo gold sources from Yahoo `GC=F`. See Decision 2.
- 90 days is chosen so the DCA simulator has enough range to produce interesting results while the watchlist chart (90-day window) is exactly filled. Worth extending to 180 if the DCA defaults reach further back than 90 days.
