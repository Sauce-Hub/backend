# Open Sauce Backend - Agent Instructions

You are working on the Open Sauce Laravel backend.

Before modifying any code, you MUST read:

- docs/README.md
- docs/00-project-overview.md
- docs/01-database-schema.md
- docs/02-api-contract.md
- docs/03-authentication.md
- docs/04-architecture-rules.md
- docs/07-decisions-log.md
- docs/08-current-status.md
- docs/09-testing-strategy.md

Then read any task-specific documentation.

---

# 1. Absolute Rules

1. Do not invent API fields.
2. Do not invent API responses.
3. Do not invent database columns.
4. Do not change database relationships without approval.
5. Do not rename API endpoints.
6. Do not create undocumented public endpoints.
7. Do not change business rules without approval.
8. Do not modify another developer's feature unnecessarily.
9. Do not expose secrets.
10. Do not modify `.env` values without explicit instruction.
11. Do not work directly on `main`.
12. Do not consider a task complete without passing tests.

---

# 2. Source of Truth

The official project sources are:

1. Final Database Schema
2. API Contract
3. Authentication decisions
4. Architecture rules
5. Testing strategy
6. Decisions Log

If two sources conflict:

STOP.

Do not silently choose one.

Report the conflict and wait for clarification.

---

# 3. Ambiguity Rule

If the documentation does not provide enough information to safely
implement a feature:

STOP.

Do not guess.

Report:

BLOCKED:
<what information is missing>

Wait for clarification before modifying code.

---

# 4. Task Scope

Implement ONLY the requested task.

Do not:

- refactor unrelated code
- rename unrelated files
- modify another developer's feature
- change existing API behavior without approval
- add undocumented functionality

---

# 5. Authentication

Authentication uses:

Laravel Sanctum

Protected API requests use:

Authorization: Bearer <token>

Never trust a client-provided `user_id` for ownership.

For authenticated resources, use the authenticated user:

`auth()->id()`

Never expose:

- passwords
- authentication secrets
- API keys
- tokens in logs

---

# 6. Database

Follow:

docs/01-database-schema.md

Do not invent:

- tables
- columns
- relationships
- constraints

Use Laravel migrations for database changes.

Do not modify the approved database schema without explicit approval.

---

# 7. API

API routes and behavior must follow:

docs/02-api-contract.md

Do not:

- rename endpoints
- change HTTP methods
- invent request fields
- invent response fields
- create undocumented public endpoints

If the API contract is incomplete:

STOP and report the missing information.

---

# 8. Architecture

Follow:

docs/04-architecture-rules.md

Keep controllers thin.

Use appropriate:

- Form Requests
- Services
- Eloquent Models
- API Resources
- Policies / authorization mechanisms

Do not introduce unnecessary architecture.

---

# 9. Testing

Every implemented feature MUST have automated tests.

A task is NOT complete unless:

1. Tests are written.
2. Tests pass.
3. Relevant database behavior is verified.
4. Authentication/authorization is tested when applicable.
5. API behavior is verified when applicable.
6. No existing tests are broken.

Follow:

docs/09-testing-strategy.md

---

# 10. External Services

External services, including AI/FastAPI services, must be mocked
in automated tests.

Do not call real external services during automated test execution
unless explicitly instructed.

---

# 11. Manual API Verification

For API tasks, automated tests are required.

After automated tests pass, manually verify the endpoint using
Postman or another approved API client when applicable.

Verify:

- request
- authentication
- status code
- response
- validation
- database state

---

# 12. Documentation

After completing an approved task:

Update:

- docs/08-current-status.md

If a project decision changed:

Update:

- docs/07-decisions-log.md

Do not document assumptions as decisions.

---

# 13. Git

Never work directly on `main`.

Use feature branches.

Keep commits focused.

Example commit message:

feat(auth): implement user registration

Before committing:

- review `git diff`
- ensure no secrets are included
- ensure unrelated files were not changed
- ensure tests pass

---

# 14. Definition of Done

A task is considered DONE only when:

- [ ] Implementation is complete
- [ ] Automated tests are written
- [ ] Automated tests pass
- [ ] Relevant database behavior is verified
- [ ] Authentication/authorization is verified
- [ ] API behavior is verified when applicable
- [ ] Manual API verification is completed when applicable
- [ ] No unrelated functionality was changed
- [ ] Documentation/status is updated
- [ ] Git diff has been reviewed
- [ ] No secrets are exposed

Never report a task as completed while relevant tests are failing.