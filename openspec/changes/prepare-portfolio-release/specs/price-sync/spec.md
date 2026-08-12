## ADDED Requirements

### Requirement: Scheduled Daily Catch-Up Sync
The system SHALL register the Catch-Up Sync command to run automatically once per day after the local market close, and SHALL prevent a scheduled run from starting while a previous run is still in progress.

#### Scenario: Daily run without manual action
- **WHEN** the application's scheduler is running and the configured daily time is reached
- **THEN** the system executes the Catch-Up Sync command for all tracked assets without any user interaction

#### Scenario: Overlapping runs prevented
- **WHEN** the configured daily time is reached while a previous catch-up sync is still running
- **THEN** the system does not start a second concurrent sync

#### Scenario: Manual sync still available
- **WHEN** a user triggers the force sync action from the settings page
- **THEN** the catch-up sync runs immediately, independently of the schedule

#### Scenario: Scheduling inactive without a runner
- **WHEN** no scheduler process or cron entry is configured for the installation
- **THEN** the application continues to function normally with manual sync only, and no error is raised
