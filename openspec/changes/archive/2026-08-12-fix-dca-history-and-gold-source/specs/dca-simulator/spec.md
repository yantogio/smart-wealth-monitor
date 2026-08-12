## MODIFIED Requirements

### Requirement: DCA Simulation Output
The system SHALL display the simulation's total accumulated asset value (based on the latest known price) alongside total capital invested, and SHALL indicate when one or more months in the requested simulation period had no available historical price so the result is based on partial data.

#### Scenario: Output shows value vs. capital
- **WHEN** a DCA simulation completes
- **THEN** the system displays total capital invested and the current value of accumulated units at the latest stored price

#### Scenario: Output flags partial data
- **WHEN** a DCA simulation runs for a requested period of N months but historical price data is only available for M months where M < N
- **THEN** the system displays a warning indicating that only M of N requested months had available data, in addition to the simulation results based on those M months

#### Scenario: Output shows no warning when data is complete
- **WHEN** a DCA simulation runs for a requested period and every month in that period has available historical price data
- **THEN** the system displays the simulation results without any partial-data warning
