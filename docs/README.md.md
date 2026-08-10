# Open Sauce Backend Documentation

This folder contains the official documentation and project decisions
for the Open Sauce Laravel backend.

## Source of Truth

The following sources define the project:

1. Final Database Schema
2. API Contract
3. Authentication Decision
4. Architecture Rules
5. Decisions recorded in `07-decisions-log.md`

## Important Rule

Before implementing any task, the coding agent MUST read:

- this file
- `00-project-overview.md`
- `01-database-schema.md`
- `02-api-contract.md`
- `04-architecture-rules.md`
- `07-decisions-log.md`

Then read the documentation related to the assigned task.

## Do Not Guess

If a required field, request body, response format, relationship,
business rule, or integration behavior is not documented:

DO NOT invent it.

Mark it as BLOCKED and ask for clarification.

## Documentation Priority

If two documents conflict:

1. `07-decisions-log.md`
2. `01-database-schema.md`
3. `02-api-contract.md`
4. Other documentation

Do not silently resolve conflicts.