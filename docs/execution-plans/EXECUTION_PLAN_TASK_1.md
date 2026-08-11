# Execution Plan — Developer 1
## Auth, Profile, Comments & Suggestions

> **Project:** Open Sauce Backend  
> **Stack:** Laravel + PostgreSQL + Sanctum + Docker  
> **Source task specification:** `TASK_1_Auth_Profile_Comments_Suggestions.md`

---

## 0. Execution Principles

### Objective

Implement Developer 1's backend scope without rebuilding the existing foundation and without creating unnecessary merge conflicts.

The existing project already provides the Laravel/PostgreSQL foundation, migrations, Eloquent models, Sanctum, and API contract. The implementation must follow those existing contracts rather than inventing new endpoints or response formats.

The assigned scope is:

1. Authentication
2. Profile
3. Comments
4. Suggestions

The assigned ownership is explicitly defined in the task specification. fileciteturn0file0L15-L63

### Required architecture

Every feature should follow:

```text
HTTP Request
    ↓
Route
    ↓
Controller
    ↓
Form Request
    ↓
Service
    ↓
Model / Eloquent Relationship
    ↓
PostgreSQL
```

Controllers remain thin; validation belongs in Form Requests; business logic belongs in Services. fileciteturn0file0L259-L279

### Definition of Done

A feature is not complete until:

- Implementation is complete.
- Validation is implemented.
- Authentication/authorization behavior is verified.
- Database behavior is verified.
- Automated tests pass.
- Negative/edge cases are covered.
- Postman/manual API verification passes.
- API responses match the existing contract.
- No unrelated files were changed.
- Git diff has been reviewed.
- No secrets are committed.

---

# Phase 1 — Repository & Contract Verification

## Task 1.1 — Verify the existing foundation

Before writing code:

- Confirm Docker services are running.
- Confirm PostgreSQL is reachable.
- Confirm Laravel application boots.
- Confirm migrations are already applied.
- Confirm Sanctum is installed/configured.
- Confirm existing models and relationships.
- Confirm test suite currently runs.
- Confirm API contract is available.

### Do not

- Recreate migrations.
- Reinstall Laravel.
- Rebuild Docker.
- Replace existing architecture.
- Rewrite existing database migrations.

The existing migrations are the source of truth. New schema changes, if genuinely required, must use a new migration. fileciteturn0file0L283-L292

## Task 1.2 — Create an endpoint checklist

Create a local checklist containing:

- Method
- URL
- Authentication requirement
- Request body/query
- Validation rules
- Expected success status
- Expected error statuses
- Database effect
- Ownership rule

Compare every implementation against `docs/02-api-contract.md`.

---

# Phase 2 — Git Isolation

## Task 2.1 — Create feature branches

Recommended branches:

```text
feature/auth
feature/profile
feature/comments
feature/suggestions
```

The task specification explicitly recommends these feature branches and prohibits working directly on `main`. fileciteturn0file0L297-L318

## Task 2.2 — Protect shared files

Avoid unnecessary changes to:

```text
routes/api.php
composer.json
config/*
existing migrations
shared models
```

If a shared file must change, coordinate before editing it.

## Task 2.3 — Commit strategy

Use small commits:

```text
feat(auth): implement registration
test(auth): add registration feature tests

feat(auth): implement login
test(auth): add login tests

feat(comments): implement comment creation
test(comments): add comment creation tests
```

Do not mix:

- formatting;
- unrelated refactoring;
- another developer's feature;
- dependency upgrades

into the same feature commit.

---

# Phase 3 — Authentication

## Task 3.1 — Registration

Implement:

```text
POST /api/register/
```

### Implementation sequence

1. Create route in `routes/auth.php`.
2. Create registration Form Request.
3. Define validation for:
   - name
   - email
   - password
4. Ensure email uniqueness.
5. Hash the password using Laravel's supported hashing mechanism.
6. Create the user.
7. Create a Sanctum token.
8. Return the exact documented `201` response.
9. Ensure validation failures return `422`.

### Edge cases

Test:

- missing name;
- empty name;
- missing email;
- malformed email;
- duplicate email;
- missing password;
- password below required constraints;
- password confirmation mismatch if required by the contract;
- extra/unexpected fields;
- successful registration;
- password is never returned in the response;
- password is stored hashed;
- token is generated.

### Data integrity

If user creation and token generation involve multiple dependent operations, make sure failures do not leave an invalid user state.

---

## Task 3.2 — Login

Implement:

```text
POST /api/login/
```

### Sequence

1. Create route.
2. Create login Form Request.
3. Validate credentials.
4. Attempt authentication using the correct Laravel mechanism.
5. Reject invalid credentials with `401`.
6. Generate Sanctum token only after successful authentication.
7. Return documented `200` response.

### Edge cases

Test:

- missing email;
- malformed email;
- missing password;
- wrong password;
- nonexistent email;
- valid credentials;
- repeated login;
- no token generated for failed login;
- password never exposed.

Never reveal whether a particular email exists through a different error message if the project contract expects generic invalid-credential handling.

---

## Task 3.3 — Logout

Implement:

```text
DELETE /api/logout/
```

### Sequence

1. Protect route with `auth:sanctum`.
2. Identify the current authenticated token/user.
3. Revoke/delete the current token.
4. Return `204 No Content`.

The endpoint must return `401` when no valid authentication exists. fileciteturn0file0L101-L109

### Edge cases

Test:

- valid token;
- missing token;
- malformed token;
- expired/invalid token;
- logging out twice;
- another user's token cannot be revoked;
- token cannot be reused after logout.

---

# Phase 4 — Authentication Security

## Task 4.1 — Sanctum verification

All protected endpoints must use:

```text
auth:sanctum
```

## Task 4.2 — Ownership verification

Never use client-provided `user_id` as the authoritative owner.

Use:

```php
auth()->id()
```

or the authenticated user instance.

This rule is explicitly required for the assigned scope. fileciteturn0file0L114-L124

### Security edge cases

Test attempts to:

- create content for another user by sending another `user_id`;
- modify/delete another user's data;
- like/unlike another user's operation;
- access another user's protected information.

---

# Phase 5 — Profile

## Task 5.1 — Get authenticated profile

Implement:

```text
GET /api/profile/
```

### Sequence

1. Protect with Sanctum.
2. Retrieve the authenticated user.
3. Return the documented profile response.
4. Do not accept a user ID to select the current profile.

The profile endpoint must always operate on the authenticated identity. fileciteturn0file0L130-L142

### Edge cases

Test:

- authenticated user;
- missing token;
- invalid token;
- deleted/nonexistent authenticated user state;
- response does not expose password/hash;
- response matches API contract.

---

# Phase 6 — Comments

## Task 6.1 — View comments

Implement:

```text
GET /api/comments/
```

### Sequence

1. Protect route.
2. Validate required receipt identifier/query parameters.
3. Verify receipt exists.
4. Query comments through Eloquent relationships.
5. Load only required user/comment data.
6. Apply documented pagination.
7. Return documented JSON structure.

The specification requires the receipt identifier, documented response structure, required user/comment information, and pagination where specified. fileciteturn0file0L146-L160

### Edge cases

Test:

- missing receipt ID;
- invalid receipt ID format;
- nonexistent receipt;
- receipt with zero comments;
- one comment;
- many comments;
- invalid page;
- invalid `per_page`;
- page beyond available results;
- authenticated vs unauthenticated request.

---

## Task 6.2 — Add comment

Implement:

```text
POST /api/comments/
```

### Sequence

1. Protect with Sanctum.
2. Validate receipt ID.
3. Validate comment content.
4. Verify receipt exists.
5. Derive user from authenticated session.
6. Create comment.
7. Return documented response.

The client must not control the comment owner through `user_id`. fileciteturn0file0L162-L173

### Edge cases

Test:

- empty comment;
- whitespace-only comment;
- too-long comment;
- missing receipt ID;
- nonexistent receipt;
- invalid receipt ID;
- unauthenticated request;
- malicious HTML/script input;
- client sends a forged `user_id`;
- successful creation.

---

## Task 6.3 — Like comment

Implement:

```text
POST /api/comment-like/
```

### Sequence

1. Authenticate.
2. Validate comment identifier.
3. Verify comment exists.
4. Create like for authenticated user.
5. Respect database uniqueness constraints.
6. Return documented response.

### Edge cases

Test:

- valid like;
- duplicate like;
- nonexistent comment;
- malformed comment ID;
- unauthenticated request;
- same comment liked by two different users;
- one user's like cannot affect another user's like.

---

## Task 6.4 — Remove comment like

Implement:

```text
DELETE /api/comment-like/
```

### Edge cases

Test:

- existing like;
- missing like;
- nonexistent comment;
- unauthenticated request;
- user A cannot remove user B's like;
- repeated unlike.

The operation must only affect the authenticated user's like. fileciteturn0file0L187-L195

---

# Phase 7 — Suggestions

## Task 7.1 — View suggestions

Implement:

```text
GET /api/suggestions/
```

### Sequence

1. Authenticate.
2. Validate `receipt_id`, `page`, `per_page`.
3. Verify referenced receipt.
4. Query suggestions.
5. Apply documented pagination.
6. Return exact API response.

The endpoint must support the documented receipt and pagination parameters. fileciteturn0file0L200-L210

### Edge cases

Test:

- missing receipt ID;
- invalid receipt ID;
- nonexistent receipt;
- empty suggestions;
- invalid page;
- invalid per-page;
- page beyond data;
- unauthorized request.

---

## Task 7.2 — Add suggestion

Implement:

```text
POST /api/suggestions/
```

### Sequence

1. Authenticate.
2. Validate request.
3. Verify referenced receipt.
4. Derive creator from authenticated user.
5. Create suggestion.
6. Return documented response.

The creator must not come from a trusted client-provided owner ID. fileciteturn0file0L212-L222

### Edge cases

Test:

- missing required fields;
- empty suggestion;
- invalid receipt;
- nonexistent receipt;
- unauthenticated request;
- forged `user_id`;
- duplicate/invalid suggestion if prohibited by contract;
- successful creation.

---

## Task 7.3 — Like suggestion

Implement:

```text
POST /api/suggestion-like/
```

### Edge cases

Test:

- valid like;
- duplicate like;
- nonexistent suggestion;
- malformed ID;
- unauthenticated request;
- user isolation.

---

## Task 7.4 — Remove suggestion like

Implement:

```text
DELETE /api/suggestion-like/
```

### Edge cases

Test:

- existing like;
- missing like;
- nonexistent suggestion;
- unauthenticated request;
- repeated unlike;
- user A cannot remove user B's like.

The specification explicitly requires removal to affect only the authenticated user's like. fileciteturn0file0L235-L243

---

## Task 7.5 — Approve suggestion

Implement:

```text
POST /api/suggestions/approve/
```

### Sequence

1. Authenticate.
2. Validate suggestion identifier.
3. Load suggestion and required relationships.
4. Determine the authorized owner using server-side identity/relationships.
5. Reject unauthorized users.
6. Approve the suggestion according to the project's database/API rules.
7. Return documented response.

The authorization rule must be based on the project definition and must not trust an arbitrary client-provided owner ID. fileciteturn0file0L245-L255

### Critical edge cases

Test:

- authorized owner approves;
- unrelated user attempts approval;
- nonexistent suggestion;
- already-approved suggestion;
- repeated approval;
- malformed ID;
- unauthenticated request;
- concurrent approval attempts;
- state remains consistent after failed authorization.

---

# Phase 8 — Cross-Feature Data Integrity

## Task 8.1 — Relationships

Verify Eloquent relationships for:

```text
User ↔ Comments
User ↔ Suggestions
User ↔ Likes
Receipt ↔ Comments
Receipt ↔ Suggestions
```

Use relationships instead of duplicated query logic.

## Task 8.2 — User isolation

Create at least two test users.

Verify:

```text
User A data ≠ User B private ownership
```

Test cross-user access for every operation where ownership matters.

## Task 8.3 — Missing resources

Every endpoint that receives a resource ID must define consistent behavior for:

- valid ID;
- nonexistent ID;
- malformed ID;
- deleted resource.

Do not allow null dereferences or raw SQL/database exceptions to escape to the client.

---

# Phase 9 — Automated Testing

The task specification requires automated tests for success, validation failure, authentication, authorization, not-found cases, database changes, duplicates, and edge cases. fileciteturn0file0L322-L346

## Test organization

```text
tests/Feature/Auth/
tests/Feature/Profile/
tests/Feature/Comments/
tests/Feature/Suggestions/
```

## Minimum test matrix

| Area | Success | Validation | Auth | Ownership | Not Found | Duplicate | Edge |
|---|---:|---:|---:|---:|---:|---:|---:|
| Register | ✓ | ✓ | N/A | N/A | N/A | ✓ | ✓ |
| Login | ✓ | ✓ | N/A | N/A | ✓ | N/A | ✓ |
| Logout | ✓ | N/A | ✓ | ✓ | N/A | ✓ | ✓ |
| Profile | ✓ | N/A | ✓ | ✓ | ✓ | N/A | ✓ |
| Comments | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Suggestions | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

---

# Phase 10 — Manual API Verification

After automated tests pass, test every endpoint in Postman.

Required checks include:

- HTTP method;
- exact URL;
- token;
- request body;
- query parameters;
- status code;
- response JSON;
- validation errors;
- database state. fileciteturn0file0L351-L367

For each endpoint execute:

```text
Happy path
→ Missing required field
→ Invalid value
→ Unauthorized
→ Resource not found
→ Ownership violation
→ Duplicate/repeated operation
→ Verify database state
```

---

# Phase 11 — Quality & Security Review

Before opening the PR:

### Code review

- No fat controllers.
- No duplicated business logic.
- No hard-coded user IDs.
- No hard-coded credentials.
- No raw SQL where Eloquent is sufficient.
- No unnecessary N+1 queries.
- No debug statements.
- No commented-out production code.
- No unrelated refactoring.

### Security

- Sanctum protects every required endpoint.
- Passwords are hashed.
- Password/hash fields are not serialized.
- User ownership is derived server-side.
- Unauthorized users receive the correct status.
- Input validation exists.
- Sensitive exceptions are not leaked.

### Database

- Foreign-key relationships are respected.
- Duplicate likes are prevented.
- Failed operations do not leave inconsistent data.
- Existing migrations remain unchanged.

---

# Phase 12 — Documentation & Finalization

Update:

```text
docs/08-current-status.md
```

Document:

- completed endpoints;
- tests;
- known limitations;
- integration dependencies;
- any database changes;
- any decisions that affect Developer 2.

Then run:

```bash
git status
git diff
```

Confirm only intended files changed.

Run the complete test suite.

Only then open the Pull Request.

---

# Final PR Checklist

- [ ] Branch is based on current integration branch
- [ ] Auth complete
- [ ] Profile complete
- [ ] Comments complete
- [ ] Suggestions complete
- [ ] All Form Requests complete
- [ ] All Services complete
- [ ] Automated tests pass
- [ ] Negative/edge tests pass
- [ ] Postman tests pass
- [ ] Authorization verified
- [ ] User isolation verified
- [ ] No secrets committed
- [ ] No unrelated files changed
- [ ] Documentation updated
- [ ] Git diff reviewed
- [ ] PR description explains implementation and testing
