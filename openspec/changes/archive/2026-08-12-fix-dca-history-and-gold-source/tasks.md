## 1. Metals API Client (replaces GoldAPI.io)

- [x] 1.1 Confirm the concrete provider (metals.dev or metalpriceapi.com), its historical-by-date endpoint format, and free-tier date range limit
- [x] 1.2 Add `services.metals.key` config (`METALS_API_KEY` env var) to `config/services.php` and `.env.example`
- [x] 1.3 Create `app/Services/MetalsApiClient.php` implementing `getPriceForDate(CarbonInterface $date): ?float` and `getHistoricalCloses(CarbonInterface $from, CarbonInterface $to): array`, following the same error-handling/logging pattern as the current `GoldApiClient`
- [x] 1.4 Update `SettingsController` and `resources/views/settings/index.blade.php` to read/write the new `metals_api_key` setting instead of `goldapi_key`
- [x] 1.5 Update `app/Console/Commands/SyncPricesCommand.php` to inject and use `MetalsApiClient` for `type === 'gold'` instead of `GoldApiClient`
- [x] 1.6 Delete `app/Services/GoldApiClient.php` and remove the `goldapi` config block and `GOLDAPI_KEY` references once nothing depends on them
- [x] 1.7 Add/update unit tests for `MetalsApiClient` covering: successful historical fetch, missing API key, request failure

## 2. Historical Backfill Command

- [x] 2.1 Create `app/Console/Commands/BackfillHistoricalPricesCommand.php` with signature `historical:backfill {--days=730}`
- [x] 2.2 For each asset, compute the fetch window (`today - days` to `today`) and call `YahooFinanceClient::getHistoricalCloses` (stocks) or `MetalsApiClient::getHistoricalCloses` (gold)
- [x] 2.3 Upsert results via `HistoricalPrice::query()->updateOrCreate(['asset_id' => ..., 'date' => ...], ['close_price' => ...])`, matching the existing dedup pattern in `SyncPricesCommand`
- [x] 2.4 Wrap each asset's fetch in try/catch so one asset's provider failure doesn't stop the others; log failures clearly
- [x] 2.5 Add feature/unit tests: default 2-year window, custom `--days` window, no duplicate rows created, continues after a single asset failure

## 3. DCA Simulator Partial-Data Reporting

- [x] 3.1 Update `DcaSimulationService::simulate()` to also return the number of months requested in the period vs. `months_invested` (months with actual data), so callers can detect partial data
- [x] 3.2 Update `DcaController::simulate()` to pass through the partial-data info to the view
- [x] 3.3 Update `resources/views/dca/index.blade.php` to show a warning (e.g., "Data hanya tersedia untuk M dari N bulan yang diminta") when `months_invested < months_requested`, and show no warning when they match
- [x] 3.4 Update `tests/Unit/DcaSimulationServiceTest.php` to cover: full data (no warning case), partial data (some months missing), and confirm existing full-year behavior once `historical:backfill` has populated data

## 4. Spec Sync

- [x] 4.1 Run `php artisan test` to confirm no regressions in existing DCA and price-sync tests
- [x] 4.2 After implementation, sync the delta specs in this change into `openspec/specs/price-sync`, `openspec/specs/dca-simulator`, and the new `openspec/specs/historical-price-backfill`
