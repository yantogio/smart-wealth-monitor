# dca-simulator Specification

## Purpose
TBD - created by archiving change add-investment-asset-monitor. Update Purpose after archive.

## Requirements

### Requirement: DCA Simulation Input Form
The system SHALL provide a form for simulating Dollar Cost Averaging with inputs: asset, monthly investment amount, and start month.

#### Scenario: Valid simulation input accepted
- **WHEN** a user submits an asset, a positive monthly investment amount, and a start month with available historical price data
- **THEN** the system accepts the input and runs the simulation

#### Scenario: Invalid input rejected
- **WHEN** a user submits a non-positive investment amount, no asset, or a start month with no historical price data available
- **THEN** the system rejects the request with a validation error and does not run the simulation

### Requirement: DCA Calculation From Historical Prices
The system SHALL calculate simulated DCA results by investing the specified monthly amount at the historical price recorded closest to each month in the simulated period, from the start month through the most recent available data.

#### Scenario: Simulation computes accumulated units and capital
- **WHEN** a DCA simulation runs for an asset from a given start month to the present
- **THEN** the system computes, for each simulated month, the units purchased at that month's historical price, and sums them into total units accumulated and total capital invested

### Requirement: DCA Simulation Output
The system SHALL display the simulation's total accumulated asset value (based on the latest known price) alongside total capital invested.

#### Scenario: Output shows value vs. capital
- **WHEN** a DCA simulation completes
- **THEN** the system displays total capital invested and the current value of accumulated units at the latest stored price
