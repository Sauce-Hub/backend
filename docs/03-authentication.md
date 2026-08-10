# Authentication

## Authentication System

Laravel Sanctum

## Token Authentication

The API uses Sanctum bearer tokens.

Protected requests use:

Authorization: Bearer <token>

## Public Endpoints

- POST /api/login/
- POST /api/register/

## Protected Endpoints

- DELETE /api/logout/
- GET /api/profile/
- GET /api/favorites/
- POST /api/favorites/
- DELETE /api/remove-favorites/
- GET /api/chat-history/
- POST /api/user-prompt/
- GET /api/chat-response/
- POST /api/suggestion/

Additional protected endpoints must be explicitly documented.

## User Ownership

Never trust user_id received from Flutter for resources
that belong to the authenticated user.

Use the authenticated Sanctum user.

Example:

auth()->id()

## Security

Never:

- return password
- log passwords
- expose tokens in logs
- hardcode secrets
- commit `.env`
- trust client-provided user_id for ownership