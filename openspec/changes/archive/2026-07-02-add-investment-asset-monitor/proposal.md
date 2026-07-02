## Why

Investors tracking Indonesian blue-chip stocks (IHSG) and gold need a local tool that automatically records historical prices, surfaces buying opportunities ("potential discounts") when prices drop significantly from recent highs, and lets them simulate long-term DCA investing — without manually checking multiple price sources every day. Smart Wealth Monitor solves this by centralizing price tracking, discount detection, and DCA simulation in one local Laravel app.

## What Changes

- Add a Laravel + MySQL + Tailwind CSS local web application (fresh project) as the foundation.
- Add asset and historical price data models for a fixed watchlist: BBCA.JK, BBRI.JK, TLKM.JK (stocks) and XAU/USD (gold).
- Add price data ingestion from Yahoo Finance (stocks) and GoldAPI.io (gold), via a "Catch-Up Sync" console command that backfills any missing days since the last recorded price instead of syncing hourly.
- Add a "Momentum Detector" service that flags an asset as "Potensi Diskon" when its latest price is more than 5% below its 30-day all-time high.
- Add a Dashboard page showing today's prices and discount-status notification cards.
- Add a Watchlist & Analytics page with a price table, mini price charts, and a simple moving average indicator per asset.
- Add a DCA Simulation page: input an asset, a monthly investment amount, and a start month; output total units/value accumulated vs. capital invested, computed from historical price data.
- Add a System Settings page to manage the GoldAPI.io API key and manually trigger the Catch-Up Sync command from the UI.

## Capabilities

### New Capabilities
- `asset-catalog`: Defines and stores the fixed watchlist of tracked assets (stocks and gold) and their metadata.
- `price-sync`: Fetches and stores historical prices from Yahoo Finance and GoldAPI.io, with catch-up/backfill logic and duplicate-date prevention.
- `momentum-detection`: Analyzes stored price history to flag assets with a significant price drop from their recent high ("Potensi Diskon").
- `dashboard-ui`: Sidebar-based layout and dashboard view summarizing today's prices and discount alerts.
- `watchlist-analytics-ui`: Table + mini chart + moving average view of tracked assets.
- `dca-simulator`: Calculates simulated Dollar Cost Averaging results from historical price data.
- `system-settings-ui`: UI for managing the GoldAPI.io API key and manually triggering price sync.

### Modified Capabilities
- None (greenfield project).

## Impact

- Establishes the initial Laravel application: routes, controllers, Eloquent models/migrations (`assets`, `historical_prices`), a console command, service classes, and Blade/Tailwind views.
- Introduces external dependencies: Yahoo Finance (public endpoint or third-party package) and GoldAPI.io (requires an API key configured in `.env`).
- Runs locally against MySQL via XAMPP; no existing code or systems are affected since this is the first change.
