## 1. Project Scaffold

- [x] 1.1 Create new Laravel project (latest version) inside XAMPP's htdocs (or configured project root)
- [x] 1.2 Configure `.env` for local MySQL connection (database name, credentials) and create the database in phpMyAdmin/MySQL
- [x] 1.3 Install and configure Tailwind CSS (via Laravel's frontend scaffolding/Vite)
- [x] 1.4 Add `GOLDAPI_KEY` placeholder to `.env` and `.env.example`
- [x] 1.5 Set up base Blade layout with sidebar (Dashboard, Watchlist & Analytics, Simulasi DCA, System Settings links)

## 2. Data Model

- [x] 2.1 Create `assets` migration (id, code unique, name, type enum[stock,gold], timestamps)
- [x] 2.2 Create `historical_prices` migration (id, asset_id FK, date, close_price, timestamps) with unique composite index on (asset_id, date)
- [x] 2.3 Create `settings` migration (id, key unique, value, timestamps) for storing the GoldAPI.io key from the UI
- [x] 2.4 Create `Asset` and `HistoricalPrice` Eloquent models with relationships
- [x] 2.5 Create a database seeder that inserts the fixed watchlist (BBCA.JK, BBRI.JK, TLKM.JK, XAU/USD) into `assets`

## 3. Price Sync Integration

- [x] 3.1 Implement `YahooFinanceClient` service class to fetch historical daily closing prices for a stock code and date range
- [x] 3.2 Implement `GoldApiClient` service class to fetch XAU/USD price(s) using the configured API key (settings table first, `.env` fallback)
- [x] 3.3 Implement `php artisan sync:prices` console command: for each asset, find latest stored date, fetch missing days up to today, upsert into `historical_prices` without duplicates
- [x] 3.4 Add per-asset error handling/logging in the sync command so one failing source doesn't abort the whole run
- [x] 3.5 Manually verify the command backfills correctly when run against an empty database and against a database with a stale (e.g. 5-day-old) last price

## 4. Momentum Detector

- [x] 4.1 Implement `MomentumDetectorService` to compute an asset's 30-day high from `historical_prices`
- [x] 4.2 Implement discount detection logic (latest close > 5% below 30-day high → "Potensi Diskon")
- [x] 4.3 Handle assets with insufficient price history gracefully (no flag, no error)

## 5. Dashboard Page

- [x] 5.1 Implement Dashboard controller/route to load latest prices and discount statuses for all assets
- [x] 5.2 Build Dashboard Blade view: price summary section
- [x] 5.3 Build Dashboard Blade view: "Potensi Diskon" notification cards

## 6. Watchlist & Analytics Page

- [x] 6.1 Implement Watchlist controller/route to load asset table data (latest price, discount status, SMA)
- [x] 6.2 Implement simple moving average (SMA) calculation from `historical_prices`
- [x] 6.3 Build Watchlist Blade view: detail table with asset, price, and status
- [x] 6.4 Integrate a lightweight charting library (e.g. Chart.js) and render mini historical price charts per asset
- [x] 6.5 Display SMA indicator alongside each asset's price

## 7. DCA Simulator Page

- [x] 7.1 Implement `DcaSimulationService`: given asset, monthly amount, start month, compute per-month unit purchases from `historical_prices`
- [x] 7.2 Implement DCA controller/route with input validation (positive amount, valid asset, start month with available data)
- [x] 7.3 Build DCA Blade view: input form (asset, monthly amount, start month)
- [x] 7.4 Build DCA Blade view: output section (total capital invested vs. current accumulated value)

## 8. System Settings Page

- [x] 8.1 Implement Settings controller/route to view and update the GoldAPI.io API key (stored in `settings` table)
- [x] 8.2 Mask the stored API key when displaying the current value in the UI
- [x] 8.3 Implement "Force Sync Data" action that invokes the `sync:prices` command from the web request and reports success/failure
- [x] 8.4 Build Settings Blade view: API key form + Force Sync button with result feedback

## 9. Verification

- [x] 9.1 Write feature/unit tests for price sync duplicate prevention (asset_id + date uniqueness)
- [x] 9.2 Write unit tests for the Momentum Detector (flagged vs. not flagged vs. insufficient data)
- [x] 9.3 Write unit tests for the DCA simulation calculation
- [x] 9.4 Manually verify end-to-end flow: seed assets → run Catch-Up Sync → view Dashboard/Watchlist → run a DCA simulation → update API key and Force Sync from Settings
