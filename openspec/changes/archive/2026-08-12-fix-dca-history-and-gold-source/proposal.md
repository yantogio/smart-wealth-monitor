## Why

The DCA simulator only ever shows 1-2 months invested even when a user picks a start month more than a year ago, because `historical_prices` is never actually backfilled that far back: `SyncPricesCommand` only fetches from an asset's latest stored date forward (or the last 30 days for a brand-new asset), so there is no way to accumulate a year of history through normal operation. Separately, the gold asset (XAU/USD) rarely has any history at all because GoldAPI.io's free tier only returns the current spot price, not a price for an arbitrary past date, so `GoldApiClient::getHistoricalCloses()` silently fails for every date except today. Together these two gaps mean the DCA simulator is effectively broken for any start month older than a couple of months, and totally broken for the gold asset.

## What Changes

- Add a one-off/backfill console command that seeds a configurable amount of history (default 2 years) for all assets, so existing and new assets can have enough `historical_prices` rows for realistic DCA simulations instead of relying solely on the daily catch-up sync.
- Replace `GoldApiClient` with a new metals price client backed by metals.dev (or metalpriceapi.com), which supports historical XAU/USD lookups by date, and wire it into `SyncPricesCommand` and the new backfill command in place of GoldAPI.io.
- Update configuration (`config/services.php`, `.env.example`, settings page) to use the new metals API key instead of (or alongside, during migration) `GOLDAPI_KEY`.
- Fix `DcaSimulationService` so a missing price for a given simulated month doesn't just silently skip that month's contribution without surfacing that the result is based on partial data, and confirm the month-iteration/price-lookup logic behaves correctly once a full year of data is present.

## Capabilities

### New Capabilities
- `historical-price-backfill`: A console command that seeds a configurable window of historical daily prices for all tracked assets (stocks via Yahoo Finance, gold via the new metals API), independent of the daily catch-up sync.

### Modified Capabilities
- `price-sync`: Gold price fetching switches from GoldAPI.io to a metals API provider (metals.dev / metalpriceapi.com) that supports historical date-based lookups, since GoldAPI.io's free tier cannot return historical prices.
- `dca-simulator`: Simulation output must indicate when one or more simulated months had no available historical price (partial data), rather than silently under-counting months invested.

## Impact

- `app/Services/GoldApiClient.php` replaced/renamed to a metals API client; `app/Console/Commands/SyncPricesCommand.php` updated to use it.
- New `app/Console/Commands/BackfillHistoricalPricesCommand.php` (or similar) using `YahooFinanceClient` and the new metals client.
- `app/Services/DcaSimulationService.php` and `resources/views/dca/index.blade.php` updated to surface partial-data warnings.
- `config/services.php`, `.env.example`, `app/Http/Controllers/SettingsController.php`, `resources/views/settings/index.blade.php` updated for the new metals API key setting.
- Existing `openspec/specs/price-sync` and `openspec/specs/dca-simulator` specs get delta updates.
