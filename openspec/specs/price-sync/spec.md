# price-sync Specification

## Purpose
TBD - created by archiving change add-investment-asset-monitor. Update Purpose after archive.

## Requirements

### Requirement: Historical Price Storage Without Duplication
The system SHALL store one closing price per asset per calendar date in the `historical_prices` table, and SHALL prevent more than one price record for the same asset and date.

#### Scenario: New price recorded
- **WHEN** a price is fetched for an asset and date not already present in `historical_prices`
- **THEN** the system inserts a new record with the asset, date, and closing price

#### Scenario: Duplicate date prevented
- **WHEN** a price fetch attempts to store a price for an asset and date that already has a record
- **THEN** the system either skips the insert or updates the existing record, and does not create a second row for that asset and date

### Requirement: Stock Price Fetching via Yahoo Finance
The system SHALL fetch historical closing prices for tracked stock assets (BBCA.JK, BBRI.JK, TLKM.JK) from Yahoo Finance for a given date range.

#### Scenario: Successful stock price fetch
- **WHEN** the sync process requests historical prices for a stock asset over a date range
- **THEN** the system retrieves closing prices for each trading day in that range from Yahoo Finance

#### Scenario: Stock fetch failure handled
- **WHEN** the Yahoo Finance request fails or returns an error
- **THEN** the system logs the failure and does not crash the sync process for other assets

### Requirement: Gold Price Fetching via Metals API
The system SHALL fetch historical or current XAU/USD prices from a metals price provider (metalpriceapi.com) that supports historical date-based lookups, using an API key configured in the environment.

#### Scenario: Successful gold price fetch for a past date
- **WHEN** the sync or backfill process requests a gold price for a specific past date
- **THEN** the system retrieves the XAU/USD closing price for that exact date from the metals API using the configured API key

#### Scenario: Successful gold price fetch for today
- **WHEN** the sync process requests a gold price for the current date
- **THEN** the system retrieves the current XAU/USD price from the metals API using the configured API key

#### Scenario: Missing API key handled
- **WHEN** the metals API key is not configured
- **THEN** the system skips the gold price fetch, logs a clear error, and does not crash the sync process for other assets

### Requirement: Catch-Up Sync Command
The system SHALL provide a console command that determines, per asset, the most recent date with a stored price and fetches all missing daily prices from that date up to the current date ("catch-up"), rather than syncing continuously.

#### Scenario: Backfill after multiple missed days
- **WHEN** the Catch-Up Sync command runs and the latest stored price for an asset is 5 days old
- **THEN** the system fetches and stores prices for each missing day from that date up to today for that asset

#### Scenario: No sync needed when already current
- **WHEN** the Catch-Up Sync command runs and an asset's latest stored price is already dated today (or the latest available trading day)
- **THEN** the system does not fetch or insert new prices for that asset

#### Scenario: Sync runs across all tracked assets
- **WHEN** the Catch-Up Sync command is executed
- **THEN** the system performs the catch-up check and fetch independently for every asset in the catalog
