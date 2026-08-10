# Backend Architecture Rules

## Framework

Laravel

## Architecture

Use:

Controller
    ↓
Form Request
    ↓
Service
    ↓
Model / Repository when appropriate
    ↓
Database

## Controllers

Controllers must remain thin.

Do not put large business logic inside controllers.

## Validation

Use Laravel Form Requests for non-trivial validation.

## API Responses

Use a consistent JSON response structure.

Do not create different response formats randomly.

## Models

Define relationships explicitly.

Use Eloquent relationships.

## Database

Use migrations.

Do not manually modify production database structures.

## Authorization

Use authenticated user context.

Never trust client-provided ownership IDs.

## Routes

Routes must match the approved API contract exactly.

Do not rename existing endpoints.

Do not create undocumented public endpoints.

## Naming

Follow Laravel conventions unless the existing API contract
requires a specific name.

## Secrets

Never hardcode:

- API keys
- passwords
- tokens
- database credentials

Use `.env`.

## Error Handling

Return appropriate HTTP status codes.

Do not expose internal exceptions or stack traces to clients.

## Git

Never work directly on main.

Use feature branches.

Do not modify another developer's feature unnecessarily.

## Shared Files

Be careful with:

- routes/api.php
- composer.json
- config/*
- database migrations
- shared models
- common API response classes

Coordinate changes to shared files.