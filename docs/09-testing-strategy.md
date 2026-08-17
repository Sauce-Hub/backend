# Open Sauce Backend - Testing Strategy

This document defines how the Laravel backend must be tested.

---

# 1. General Rule

Every implemented feature must have automated tests.

A feature is not considered complete until its relevant tests pass.

---

# 2. Testing Framework

Use the testing framework already configured in the Laravel project.

Do not replace or introduce a different testing framework without
explicit approval.

---

# 3. API Testing

API endpoints should primarily use Feature Tests.

Feature Tests must verify the endpoint behavior from the HTTP layer
through the application and database where applicable.

---

# 4. Authentication Testing

Protected endpoints must test:

- authenticated request
- unauthenticated request
- invalid authentication token
- authenticated user identity

Expected behavior must follow the API contract.

---

# 5. Authorization and Ownership

For resources owned by a user, tests must verify:

- user can access their own resource
- user cannot access another user's protected resource
- ownership is derived from the authenticated user
- client-provided ownership identifiers are not blindly trusted

Example:

A suggestion created by User A must remain owned by User A.

---

# 6. Validation Testing

For endpoints with validation, test:

- valid request
- missing required fields
- invalid field types
- invalid formats
- invalid values
- duplicate values where applicable

Expected validation responses must follow the approved API contract.

---

# 7. Database Testing

When an endpoint changes the database, tests must verify the resulting
database state.

Examples:

- user created
- favorite created
- favorite removed
- suggestion created
- suggestion like created
- chat history stored

---

# 8. Relationships

Tests should verify important Eloquent relationships where they affect
feature behavior.

Examples:

- User → Suggestions
- User → Favorites
- User → Chat History
- Suggestion → Likes

---

# 9. Duplicate Operations

For entities that must be unique, test duplicate operations.

Examples:

- same user cannot favorite the same receipt twice
- same user cannot like the same suggestion twice

Database constraints and application behavior should work together.

---

# 10. Error Cases

Test relevant error scenarios such as:

- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 422 Validation Error

Use the status codes defined by the approved API contract.

Do not invent status codes when the contract specifies another behavior.

---

# 11. External Services

External services such as FastAPI / AI services must be mocked
during automated tests.

Automated tests must NOT depend on:

- real AI providers
- real external APIs
- external network availability

Test at least:

- successful external service response
- external service failure
- timeout/error handling where applicable

---

# 12. Security Tests

Where applicable, verify that:

- passwords are never returned
- secrets are not exposed
- tokens are not logged
- unauthorized users cannot access protected resources
- users cannot modify another user's resources
- client-provided ownership IDs are not trusted

---

# 13. Regression Testing

After implementing a feature, run:

1. tests related to the feature
2. relevant existing tests
3. the full test suite before major milestones

The full test suite should pass before merging significant work.

---

# 14. Manual API Testing

Automated tests are mandatory.

For API features, manual verification should also be performed
using Postman or another approved API client.

Verify:

- request
- authentication
- status code
- response
- database state
- error behavior

Manual testing does NOT replace automated testing.

---

# 15. Test Naming

Tests should clearly describe the behavior being tested.

Good:

authenticated_user_can_create_suggestion

Good:

user_cannot_like_same_suggestion_twice

Good:

unauthenticated_user_cannot_access_favorites

Avoid vague names such as:

test_suggestion

test_api

test_1

---

# 16. Definition of Done

A feature is considered tested only when:

- [ ] Automated tests exist
- [ ] Tests pass
- [ ] Authentication tested where applicable
- [ ] Authorization tested where applicable
- [ ] Validation tested where applicable
- [ ] Database state tested where applicable
- [ ] Duplicate behavior tested where applicable
- [ ] External services mocked where applicable
- [ ] Manual API verification completed where applicable
- [ ] No regression failures