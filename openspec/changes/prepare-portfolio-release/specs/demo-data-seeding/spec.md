## ADDED Requirements

### Requirement: Offline Demo Price Dataset
The system SHALL include a version-controlled dataset of historical closing prices covering every asset in the catalog, sufficient to populate the dashboard, watchlist chart, and DCA simulator without any network access or API key.

#### Scenario: Dataset present in repository
- **WHEN** the repository is cloned
- **THEN** a committed data file containing at least 90 calendar days of closing prices per asset is present, and no API key is required to read it

#### Scenario: Dataset records its capture date
- **WHEN** a reader inspects the demo dataset file
- **THEN** the file states the real calendar dates the prices were observed on, so the data can be verified as genuine market data

### Requirement: Demo Seeding Without External Services
The system SHALL provide a seeder that loads the demo dataset into `historical_prices` for every asset, requiring no API key, no network access, and no database server beyond the default connection.

#### Scenario: Seeding a fresh install
- **WHEN** a user runs the database seeder on a freshly migrated database with no API key configured
- **THEN** the system stores a price history for every asset in the catalog and reports how many records were created

#### Scenario: Seeding is idempotent
- **WHEN** the demo seeder runs a second time against a database that already contains the demo data
- **THEN** the system updates the existing records and does not create duplicate rows for the same asset and date

#### Scenario: Dashboard populated after seeding
- **WHEN** the dashboard is opened after the demo seeder has run
- **THEN** every asset displays a latest closing price rather than a placeholder, and at least one asset is flagged as "Potensi Diskon"

### Requirement: Demo Dates Anchored To Current Date
The system SHALL shift all demo price dates by a single constant offset at seed time so that the most recent record falls on the current date, preserving the original spacing between records including non-trading-day gaps.

#### Scenario: Seeding long after capture
- **WHEN** the demo seeder runs on a date months later than the dataset's capture date
- **THEN** the newest seeded record is dated today, the oldest is dated the same number of days before today as it was before the capture date, and momentum detection over the trailing 30 days still finds data

#### Scenario: Price values unchanged by shifting
- **WHEN** the demo seeder shifts dates
- **THEN** every closing price value is stored exactly as recorded in the dataset file

#### Scenario: Date shifting is disclosed
- **WHEN** the demo seeder completes
- **THEN** the system reports that demo dates were shifted to end on the current date, so the user is not misled into reading seeded dates as real observation dates

### Requirement: Demo Dataset Regeneration Command
The system SHALL provide a console command that regenerates the demo dataset file from the closing prices currently stored in the database.

#### Scenario: Exporting current history
- **WHEN** the export command runs against a database containing synced price history
- **THEN** the system writes a dataset file containing those prices and their real dates, in the format the demo seeder reads

#### Scenario: Export refuses on empty history
- **WHEN** the export command runs against a database with no stored prices
- **THEN** the system reports the error, exits with a failure code, and leaves the existing dataset file unmodified
