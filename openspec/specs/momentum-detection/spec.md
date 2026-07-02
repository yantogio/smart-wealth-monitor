# momentum-detection Specification

## Purpose
TBD - created by archiving change add-investment-asset-monitor. Update Purpose after archive.

## Requirements

### Requirement: 30-Day High Calculation
The system SHALL determine an asset's highest closing price over the trailing 30 days of stored historical prices.

#### Scenario: High computed from available history
- **WHEN** the momentum detector evaluates an asset with at least one stored price in the last 30 days
- **THEN** the system identifies the maximum closing price among those records as the 30-day high

### Requirement: Discount Status Flagging
The system SHALL flag an asset as "Potensi Diskon" when its latest closing price is more than 5% below its 30-day high.

#### Scenario: Asset flagged as potential discount
- **WHEN** an asset's latest closing price is more than 5% lower than its 30-day high
- **THEN** the system marks that asset with status "Potensi Diskon"

#### Scenario: Asset not flagged when within threshold
- **WHEN** an asset's latest closing price is within 5% of its 30-day high (or higher)
- **THEN** the system does not mark that asset as "Potensi Diskon"

#### Scenario: Insufficient history handled
- **WHEN** an asset has no stored price history within the last 30 days
- **THEN** the system does not flag the asset and does not error
