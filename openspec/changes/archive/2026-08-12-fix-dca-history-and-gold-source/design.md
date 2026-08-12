## Context

`historical_prices` only grows through `SyncPricesCommand` (`sync:prices`), which is a catch-up sync: for an asset with existing rows it fetches from `max(date) + 1 day` to today; for a brand-new asset it looks back only 30 days (`DEFAULT_LOOKBACK_DAYS`). There is no path that ever pulls a full year (or more) of history, so the DCA simulator — which needs one price per month going back to an arbitrary `start_month` — only ever has a couple of months to work with in practice.

Gold (`XAUUSD`, type `gold`) is fetched via `GoldApiClient`, which calls `https://www.goldapi.io/api/XAU/USD/{date}`. GoldAPI.io's free tier only serves the current spot price; requesting a past date either 404s or silently returns the current price depending on plan, and in practice returns nothing useful for backfilling history. `getHistoricalCloses()` loops day-by-day calling this endpoint, which is also slow (one HTTP request per day) and provider-rate-limit-prone even if historical access were available.

Stocks are fetched via `YahooFinanceClient`, which already supports arbitrary date ranges in a single request (Yahoo's chart API), so stocks are not affected by the backfill gap in the same way — they just need something to actually request a year back.

## Goals / Non-Goals

**Goals:**
- Make it possible for any tracked asset (stock or gold) to have enough `historical_prices` rows for a DCA simulation starting up to N months/years in the past.
- Fetch gold historical prices from a provider that actually supports historical date-based lookups on a free/cheap tier (metals.dev or metalpriceapi.com).
- Make the DCA simulator's output honestly reflect when historical data is incomplete for the requested period, instead of quietly returning a result based on far fewer months than requested.

**Non-Goals:**
- Real-time/intraday pricing — daily closes only, unchanged.
- Automatically running the backfill on a schedule — it's an operator-invoked command (like `sync:prices` already is), not a queued/cron job in this change.
- Migrating away from Yahoo Finance for stocks — that path already works for historical ranges.

## Decisions

- **New `historical:backfill` command** (`app/Console/Commands/BackfillHistoricalPricesCommand.php`) that, per asset, fetches `[today - N days, today]` (default 2 years, overridable via `--days=`) in as few requests as possible (one Yahoo request per stock asset; the metals client batched or looped per day for gold) and upserts via the same `HistoricalPrice::updateOrCreate` pattern `SyncPricesCommand` already uses. This is separate from `sync:prices` (which stays a lightweight daily catch-up) rather than folding backfill logic into it, so operators can run a one-off deep backfill without changing the daily job's behavior/runtime.
  - Alternative considered: make `sync:prices` itself always backfill 2 years for new assets. Rejected — it would make the "just keep today's prices current" command slow and would re-run a 2-year fetch every time a new asset is added by accident; a dedicated command makes the intent (and the cost of the operation) explicit.
- **Replace `GoldApiClient` with `MetalsApiClient`** using metals.dev's (or metalpriceapi.com's) historical-by-date endpoint, keeping the same public shape (`getPriceForDate`, `getHistoricalCloses`) so `SyncPricesCommand` and the new backfill command depend on an interface-compatible class. The API key setting is renamed from `goldapi_key` to `metals_api_key` (config `services.metals.key`, env `METALS_API_KEY`), fetched via `Setting::getValue()` the same way.
  - Alternative considered: keep GoldAPI.io for "current price" and add metals.dev only for backfill. Rejected — running two providers for the same asset type adds complexity for no benefit once metals.dev covers both current and historical lookups.
- **DCA partial-data signal**: `DcaSimulationService::simulate()` returns an additional `months_with_data` vs. the already-existing `months_invested` (kept as an alias for the total count) plus a boolean/count of skipped months, so the controller/view can show something like "Data hanya tersedia untuk 8 dari 12 bulan yang diminta." The core month-iteration loop itself is not restructured — once real data covers the full range, it already produces one contribution per available month; the fix is surfacing what's missing, not changing the iteration.

## Risks / Trade-offs

- [metals.dev/metalpriceapi.com free tier may have its own rate limits or a shorter historical window than 2 years] → Confirm the specific plan's limits during implementation; if the free tier can't cover 2 years, reduce the default backfill window for gold specifically and document the limitation in the command's help text.
- [Running a 2-year backfill hits the stock/gold APIs with a burst of requests] → Backfill command loops assets sequentially (matching `SyncPricesCommand`'s existing per-asset try/catch pattern) so a single provider hiccup doesn't abort the whole run.
- [Existing `GOLDAPI_KEY` deployments stop working the moment `GoldApiClient` is removed] → Update `.env.example` and the settings page in the same change; this is a local/self-hosted app (no multi-tenant production deployment implied by the repo), so a clean cutover is acceptable rather than dual-provider support.

## Migration Plan

1. Add `MetalsApiClient` and its config/env/settings wiring alongside the existing `GoldApiClient` config.
2. Switch `SyncPricesCommand` to use `MetalsApiClient` for `type === 'gold'`.
3. Remove `GoldApiClient` and the `goldapi` config/env once nothing references it.
4. Add `historical:backfill` command.
5. Update `DcaSimulationService`, `DcaController`, and `resources/views/dca/index.blade.php` to surface partial-data info.
6. Operator runs `php artisan historical:backfill` once against existing data to populate a real history window.

No database schema changes are required — `historical_prices` already supports arbitrary dates.

## Open Questions

- ~~Which of metals.dev vs. metalpriceapi.com to use concretely~~ — **Resolved: metalpriceapi.com.** Its `/v1/timeframe` endpoint accepts up to a 365-day range per request (metals.dev's timeseries endpoint is capped at 30 days per request), so a 2-year gold backfill needs only ~3 requests out of the free tier's 100/month. Single-date endpoint: `GET https://api.metalpriceapi.com/v1/{YYYY-MM-DD}?api_key=...&base=USD&currencies=XAU`; response contains `rates.XAU` (XAU per 1 USD — invert for USD price) and a convenience `rates.USDXAU` (USD per ounce).
