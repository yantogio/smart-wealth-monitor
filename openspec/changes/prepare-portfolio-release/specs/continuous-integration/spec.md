## ADDED Requirements

### Requirement: Automated Test Execution
The system SHALL run the full test suite automatically on every push and every pull request targeting the main branch, on a supported PHP version, without requiring an external database service.

#### Scenario: Push triggers a test run
- **WHEN** a commit is pushed to the main branch
- **THEN** the automated workflow installs dependencies and executes the full test suite

#### Scenario: Pull request triggers a test run
- **WHEN** a pull request targeting the main branch is opened or updated
- **THEN** the automated workflow executes the full test suite against the proposed changes

#### Scenario: Failing tests fail the workflow
- **WHEN** any test in the suite fails during an automated run
- **THEN** the workflow reports a failing status

### Requirement: Visible Build Status
The README SHALL display a build status badge reflecting the latest automated test result on the main branch.

#### Scenario: Visitor checks project health
- **WHEN** a visitor views the README
- **THEN** a badge shows whether the test suite is currently passing, linking to the workflow runs
