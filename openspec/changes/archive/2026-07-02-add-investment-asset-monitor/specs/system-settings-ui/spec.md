## ADDED Requirements

### Requirement: API Key Management
The system SHALL provide a settings form for entering and updating the GoldAPI.io API key used by the price sync capability.

#### Scenario: API key saved
- **WHEN** a user submits a new GoldAPI.io API key on the System Settings page
- **THEN** the system stores the key so subsequent gold price fetches use it

#### Scenario: Existing key not exposed in full
- **WHEN** a user opens the System Settings page and an API key is already configured
- **THEN** the system does not display the full stored key in plain text in the UI

### Requirement: Manual Force Sync Trigger
The system SHALL provide a "Force Sync Data" button on the System Settings page that manually runs the Catch-Up Sync command.

#### Scenario: Manual sync triggered from UI
- **WHEN** a user clicks "Force Sync Data"
- **THEN** the system executes the Catch-Up Sync command and reports whether it completed successfully

#### Scenario: Sync failure surfaced to user
- **WHEN** the manually triggered sync fails for one or more assets
- **THEN** the system displays an error message indicating the sync did not fully succeed
