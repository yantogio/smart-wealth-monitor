# historical-price-backfill Specification

## Purpose
Provide an operator-invoked deep backfill of historical daily prices so tracked assets have enough history for long-range DCA simulations, independent of the daily catch-up sync.

## Requirements

### Requirement: Deep Historical Backfill Command
The system SHALL provide a console command that fetches and stores historical daily closing prices for all tracked assets over a configurable window (default 2 years back from today), independent of the daily catch-up sync.

#### Scenario: Default backfill window
- **WHEN** the backfill command is run without a `--days` option
- **THEN** the system fetches and stores historical prices for each tracked asset for the last 2 years (or as far back as the provider returns data), up to today

#### Scenario: Custom backfill window
- **WHEN** the backfill command is run with a `--days=N` option
- **THEN** the system fetches and stores historical prices for each tracked asset for the last N days, up to today

#### Scenario: Backfill does not duplicate existing rows
- **WHEN** the backfill command fetches a price for an asset and date that already has a stored record
- **THEN** the system updates the existing record rather than creating a duplicate row for that asset and date

#### Scenario: Backfill continues after a single asset failure
- **WHEN** fetching historical prices for one asset fails (e.g., provider error)
- **THEN** the system logs the failure for that asset and continues backfilling the remaining assets
