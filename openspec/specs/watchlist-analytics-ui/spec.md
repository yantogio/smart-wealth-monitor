# watchlist-analytics-ui Specification

## Purpose
TBD - created by archiving change add-investment-asset-monitor. Update Purpose after archive.

## Requirements

### Requirement: Watchlist Detail Table
The system SHALL display a table listing every tracked asset with its latest price and discount status.

#### Scenario: Table lists all tracked assets
- **WHEN** a user opens the Watchlist & Analytics page
- **THEN** the system displays a table row for every tracked asset showing its code, name, latest price, and "Potensi Diskon" status if applicable

### Requirement: Mini Price Chart
The system SHALL display a small historical price chart for each asset using its stored price history.

#### Scenario: Chart renders from stored history
- **WHEN** a user views an asset's row on the Watchlist & Analytics page
- **THEN** the system renders a mini chart of that asset's recent historical closing prices

### Requirement: Simple Moving Average Indicator
The system SHALL calculate and display a simple moving average (SMA) for each asset based on stored historical prices.

#### Scenario: SMA displayed alongside price
- **WHEN** an asset has enough historical price records to compute the configured moving average period
- **THEN** the system displays the calculated SMA value next to the asset's latest price

#### Scenario: Insufficient data for SMA handled
- **WHEN** an asset does not have enough historical price records to compute the moving average period
- **THEN** the system indicates the SMA is unavailable rather than showing an incorrect value
