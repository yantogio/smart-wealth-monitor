# dashboard-ui Specification

## Purpose
TBD - created by archiving change add-investment-asset-monitor. Update Purpose after archive.

## Requirements

### Requirement: Sidebar Navigation Layout
The system SHALL provide a base layout with a sidebar containing links to Dashboard, Watchlist & Analytics, Simulasi DCA, and System Settings.

#### Scenario: Sidebar visible on every page
- **WHEN** a user navigates to any page of the application
- **THEN** the sidebar with all four menu links is displayed and the current page is indicated

### Requirement: Dashboard Price Summary
The system SHALL display, on the Dashboard page, each tracked asset's latest recorded price.

#### Scenario: Dashboard shows current prices
- **WHEN** a user opens the Dashboard page
- **THEN** the system displays the latest stored price for every tracked asset

### Requirement: Discount Notification Cards
The system SHALL display a notification card on the Dashboard for each asset currently flagged as "Potensi Diskon".

#### Scenario: Discount card shown
- **WHEN** one or more assets are flagged as "Potensi Diskon"
- **THEN** the Dashboard displays a notification card for each flagged asset showing the asset and its discount

#### Scenario: No discount cards when none flagged
- **WHEN** no assets are currently flagged as "Potensi Diskon"
- **THEN** the Dashboard displays no discount notification cards
