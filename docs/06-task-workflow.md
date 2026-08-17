# Development Task Workflow

Every coding task MUST follow this workflow.

---

# Step 1 - Read Documentation

Before coding, read:

- docs/README.md
- docs/00-project-overview.md
- docs/01-database-schema.md
- docs/02-api-contract.md
- docs/04-architecture-rules.md
- docs/07-decisions-log.md
- docs/08-current-status.md
- docs/09-testing-strategy.md

Then read any task-specific documentation.

---

# Step 2 - Inspect Existing Code

Before modifying anything, inspect the relevant existing:

- routes
- models
- migrations
- controllers
- services
- form requests
- API resources
- policies
- tests

Do not assume the repository is empty.

Do not duplicate existing functionality.

---

# Step 3 - Analyze

Identify:

- required files
- affected database tables
- affected models
- affected endpoints
- dependencies
- shared files
- possible Git conflicts
- testing requirements

---

# Step 4 - Check Ambiguity

Check the task against:

- database schema
- API contract
- architecture rules
- decisions log

If something is missing or contradictory:

STOP.

Do not guess.

Report the blocker clearly.

---

# Step 5 - Plan

Create a short implementation plan.

Example:

1. Migration
2. Model relationship
3. Form Request
4. Service
5. Controller
6. API Resource
7. Route
8. Feature Tests

Do not implement before identifying the required changes.

---

# Step 6 - Implement

Implement ONLY the assigned task.

Do not:

- refactor unrelated code
- change unrelated endpoints
- modify another developer's feature
- introduce undocumented functionality

Follow the approved architecture.

---

# Step 7 - Write Automated Tests

Every implemented feature MUST have automated tests.

Depending on the feature, tests should cover:

- successful request
- validation failure
- authentication failure
- authorization failure
- resource not found
- ownership
- database changes
- duplicate operations
- edge cases
- external service failures

Follow:

docs/09-testing-strategy.md

---

# Step 8 - Run Automated Tests

Run the relevant automated test suite.

If tests fail:

1. Analyze the failure.
2. Fix the implementation or test if appropriate.
3. Run the tests again.

Do not mark the task as complete while relevant tests are failing.

---

# Step 9 - Manual API Verification

For API endpoints:

Use Postman or another approved API client after automated tests pass.

Verify:

- HTTP method
- URL
- authentication
- request body/query parameters
- status code
- response body
- validation behavior
- database state

---

# Step 10 - Security Review

Before completing the task, verify:

- authentication
- authorization
- ownership
- input validation
- no sensitive information in responses
- no secrets in source code
- no secrets in logs

---

# Step 11 - Regression Check

Run the relevant existing test suite to ensure the new implementation
did not break existing functionality.

---

# Step 12 - Documentation

Update:

- docs/08-current-status.md

If an approved architecture/business decision changed:

Update:

- docs/07-decisions-log.md

Do not document assumptions as approved decisions.

---

# Step 13 - Git Review

Run:

git status

git diff

Check:

- only intended files changed
- no `.env` changes
- no secrets
- no unrelated refactoring
- tests are included
- documentation changes are included when required

---

# Step 14 - Commit

Create a focused commit.

Example:

feat(auth): implement user registration

---

# Final Definition of Done

The task is DONE only when:

- [ ] Code implemented
- [ ] Automated tests written
- [ ] Automated tests passing
- [ ] Database behavior verified
- [ ] Authentication/authorization verified
- [ ] API manually verified when applicable
- [ ] Regression tests passing
- [ ] Documentation updated
- [ ] Git diff reviewed
- [ ] No unrelated changes
- [ ] No secrets exposed