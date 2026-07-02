## ADDED Requirements

### Requirement: Fixed Watchlist Storage
The system SHALL store a catalog of tracked assets, each with a unique code, display name, and type (`stock` or `gold`).

#### Scenario: Watchlist seeded on setup
- **WHEN** the application is set up for the first time
- **THEN** the `assets` table contains entries for BBCA.JK, BBRI.JK, TLKM.JK (type `stock`) and XAU/USD (type `gold`)

#### Scenario: Asset code uniqueness enforced
- **WHEN** an attempt is made to store an asset with a code that already exists
- **THEN** the system rejects the duplicate and keeps the existing record

### Requirement: Asset Listing
The system SHALL provide access to the list of tracked assets for use by other capabilities (price sync, dashboard, analytics, DCA simulator).

#### Scenario: Retrieve all tracked assets
- **WHEN** another part of the system requests the list of tracked assets
- **THEN** the system returns all assets with their code, name, and type
