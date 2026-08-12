## MODIFIED Requirements

### Requirement: Gold Price Fetching via Metals API
The system SHALL fetch historical or current XAU/USD prices from a metals price provider (metals.dev or metalpriceapi.com) that supports historical date-based lookups, using an API key configured in the environment.

#### Scenario: Successful gold price fetch for a past date
- **WHEN** the sync or backfill process requests a gold price for a specific past date
- **THEN** the system retrieves the XAU/USD closing price for that exact date from the metals API using the configured API key

#### Scenario: Successful gold price fetch for today
- **WHEN** the sync process requests a gold price for the current date
- **THEN** the system retrieves the current XAU/USD price from the metals API using the configured API key

#### Scenario: Missing API key handled
- **WHEN** the metals API key is not configured
- **THEN** the system skips the gold price fetch, logs a clear error, and does not crash the sync process for other assets

## REMOVED Requirements

### Requirement: Gold Price Fetching via GoldAPI.io
**Reason**: GoldAPI.io's free tier only returns the current spot price and does not support historical date-based lookups, making it unsuitable for backfilling the history the DCA simulator requires. Replaced by the Metals API requirement above.
**Migration**: Replace the `GOLDAPI_KEY` environment variable and `goldapi_key` setting with the new metals API key setting; `GoldApiClient` is replaced by a metals API client with the same interface shape used by `SyncPricesCommand` and the new backfill command.
