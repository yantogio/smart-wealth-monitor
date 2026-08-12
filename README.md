# Smart Wealth Monitor

A personal investment dashboard that tracks Indonesian stocks and gold, flags assets trading at a discount to their recent highs, and simulates what a dollar-cost-averaging plan would have returned.

[![tests](https://github.com/yantogio/smart-wealth-monitor/actions/workflows/tests.yml/badge.svg)](https://github.com/yantogio/smart-wealth-monitor/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4.svg)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20.svg)](https://laravel.com)

> **Try it in one minute** — the repository ships with a year of real market data, so `composer setup` gives you a fully populated dashboard with no API key and no database server. See [Quick start](#quick-start).

---

## Screenshots

| Dashboard | Watchlist & Analytics |
|---|---|
| ![Dashboard](docs/screenshots/dashboard.png) | ![Watchlist](docs/screenshots/watchlist.png) |

| DCA Simulator |
|---|
| ![DCA Simulator](docs/screenshots/dca.png) |

*Interface language is Indonesian.*

---

## What it does

**Momentum / discount detection.** Every asset is compared against its own trailing 30-day high. When the latest close sits more than 5% below that high, the asset is flagged as *Potensi Diskon* — surfaced as a card on the dashboard and a badge in the table. The idea is to catch pullbacks in assets you already want to own, rather than to predict the market.

**DCA simulator.** Pick an asset, a monthly amount, and a starting month. The simulator walks month by month through stored history, buys at the first available closing price of each month, accumulates fractional units, and values the position at the latest close. It reports capital invested, units held, current value, and how many months actually had price data — so a gap in history is visible rather than silently skipped.

**Watchlist analytics.** A detail table per asset with a 90-day mini price chart (Chart.js) and a 7-day simple moving average alongside the discount status.

**Dual-source price sync.** Stock prices come from Yahoo Finance; gold comes from metalpriceapi.com. Sync is *catch-up* rather than continuous: it finds the newest stored date per asset and fetches only the missing days, so it is cheap to run and safe to re-run.

## How it works

```
   ┌──────────────────┐   ┌──────────────────┐
   │  Yahoo Finance   │   │  metalpriceapi   │
   │   (IDX stocks)   │   │    (XAU/USD)     │
   └────────┬─────────┘   └────────┬─────────┘
            │                      │
   ┌────────▼─────────┐   ┌────────▼─────────┐
   │YahooFinanceClient│   │ MetalsApiClient  │   fetch + normalize,
   └────────┬─────────┘   └────────┬─────────┘   failures logged
            │                      │             never thrown
            └──────────┬───────────┘
                       │
            ┌──────────▼───────────┐
            │  sync:prices         │   catch-up per asset,
            │  historical:backfill │   one row per asset per day
            └──────────┬───────────┘
                       │
            ┌──────────▼───────────┐
            │  historical_prices   │◀── DemoPriceSeeder
            │  unique(asset, date) │    (offline demo data)
            └──────────┬───────────┘
                       │
      ┌────────────────┼────────────────┐
      │                │                │
┌─────▼──────┐  ┌──────▼──────┐  ┌──────▼──────┐
│  Momentum  │  │     DCA     │  │  Asset SMA  │
│  Detector  │  │  Simulation │  │  + history  │
└─────┬──────┘  └──────┬──────┘  └──────┬──────┘
      │                │                │
      └────────────────┼────────────────┘
                       │
            ┌──────────▼───────────┐
            │  Blade views         │
            │  Dashboard·Watchlist │
            │  DCA·Settings        │
            └──────────────────────┘
```

Calculation lives in services (`MomentumDetectorService`, `DcaSimulationService`), not in controllers or views. The API clients degrade quietly: a failed request logs a warning and returns an empty result, so one unreachable provider never breaks the sync for other assets.

**Stack:** Laravel 13 · PHP 8.3+ · Blade · Tailwind CSS 4 · Vite · Chart.js 4 · SQLite (MySQL supported)

## Quick start

Requires PHP 8.3+, Composer, and Node.js. No database server needed.

```bash
git clone https://github.com/yantogio/smart-wealth-monitor.git
cd smart-wealth-monitor
composer setup
php artisan serve
```

Open <http://localhost:8000>. The dashboard is already populated — `composer setup` creates a SQLite file, migrates it, and seeds a year of real market data bundled with the repository.

### About the demo data

`database/data/demo-prices.json` holds roughly a year of genuine closing prices for all four assets, captured from the live APIs. Two things are worth knowing:

- **Dates are shifted, prices are not.** The seeder moves every date forward by one constant offset so the newest record lands on the day you seed. Without this, the 30-day momentum window would empty out and the dashboard would go blank a month after capture. Price values are stored exactly as recorded, and the file keeps the real observation dates.
- **Demo gold is COMEX futures.** Spot XAU/USD requires a paid metalpriceapi key, so the bundled gold series uses Yahoo's `GC=F` gold futures instead. Live sync still uses metalpriceapi spot. The `source` field on every asset in the dataset records its provenance.

Regenerate the dataset from your own synced database at any time:

```bash
php artisan demo:export-prices
```

## Running against live prices

The demo path needs nothing below. This section is for tracking real current prices.

1. Get a free API key from [metalpriceapi.com](https://metalpriceapi.com) (gold only; stocks need no key).
2. Add it to `.env` as `METALS_API_KEY=...`, or paste it into **System Settings** in the app.
3. Fetch history and keep it current:

```bash
php artisan historical:backfill --days=365   # deep backfill, one time
php artisan sync:prices                      # catch-up, safe to re-run
```

`sync:prices` is registered to run daily at 18:00 (after the IDX close). To make that fire, either run `php artisan schedule:work` alongside the app, or add the standard Laravel cron entry:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

You can also trigger a sync by hand from the **System Settings** page.

<details>
<summary><strong>Using MySQL instead of SQLite</strong></summary>

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_wealth_monitor
DB_USERNAME=root
DB_PASSWORD=
```

Create the database, then run `php artisan migrate --seed`. Nothing in the codebase is engine-specific.

</details>

## Testing

```bash
php artisan test
```

39 tests covering the momentum and DCA calculations, both API clients, the sync and backfill commands, demo seeding and export, schedule registration, and HTTP rendering of every page. The suite runs against in-memory SQLite and touches no network, so CI needs no service containers.

## Spec-driven development

This project was built specification-first using [OpenSpec](https://github.com/Fission-AI/OpenSpec). Every capability has a written spec with testable scenarios before implementation:

```
openspec/specs/
├── asset-catalog/            fixed watchlist storage and listing
├── dashboard-ui/             sidebar layout, price summary, discount cards
├── dca-simulator/            input form, calculation, output
├── demo-data-seeding/        offline dataset, date anchoring, regeneration
├── historical-price-backfill/deep backfill command
├── momentum-detection/       30-day high, discount flagging
├── price-sync/               storage, dual sources, catch-up, scheduling
├── project-onboarding/       README, setup path, license
├── system-settings-ui/       API key management, force sync
└── watchlist-analytics-ui/   detail table, mini chart, SMA
```

Requirements are written as `WHEN … THEN` scenarios, which map directly onto tests. `openspec/changes/archive/` keeps the history of how each change was proposed, designed, and applied.

## Security note

This application has **no authentication**. It is a local, single-user tool. The System Settings page — which stores the API key and can trigger syncs that consume your API quota — is reachable by anyone who can reach the app. Do not expose an instance publicly without adding access control first.

## Scope

Deliberately kept small: the watchlist is fixed at four assets (BBCA, BBRI, TLKM, and gold) defined in `AssetSeeder`, with no asset CRUD. The goal was a focused, well-specified tool rather than a broad one.

Nothing here is investment advice. "Potensi Diskon" is a mechanical comparison against a 30-day high, not a recommendation.

## License

[MIT](LICENSE) © Muhammad Hariyanto Gionova
