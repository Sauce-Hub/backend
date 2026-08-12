# Execution Plan — Developer 1 (Revised v2)
## Auth, Profile, Comments & Suggestions

> **Project:** Open Sauce Backend
> **Stack:** Laravel + PostgreSQL + Sanctum + Docker
> **Verified against:** `00-project-overview.md`, `01-database-schema.md`, `02-api-contract.md`, `03-authentication.md`, `04-architecture-rules.md`, `06-task-workflow.md`, `07-decisions-log.md`, `09-testing-strategy.md`
> **What changed from v1:** every endpoint URL/method below was re-checked character-by-character against `02-api-contract.md` (7 of 13 were wrong in v1); DEC-007 model rules were added; the missing `ingredients[]` creation step was added to suggestion creation; undocumented behaviors are now listed explicitly as open items instead of being silently assumed.

---

## 0. Execution Principles

### Objective

Implement Developer 1's backend scope without rebuilding the existing foundation and without creating unnecessary merge conflicts. Follow the existing contracts rather than inventing endpoints, response formats, or business rules.

Assigned scope: **Authentication, Profile, Comments, Suggestions.**

### Required architecture

```text
HTTP Request
    ↓
Route
    ↓
Controller (thin — no business logic)
    ↓
Form Request (validation)
    ↓
Policy (authorization — used wherever ownership isn't a simple auth()->id() check)
    ↓
Service (business logic, DB transactions)
    ↓
Model / Eloquent Relationship
    ↓
PostgreSQL
    ↓
API Resource (shapes the JSON response — must match the contract's exact
field names and casing, not Laravel's default conventions)
```

The v1 plan stopped at "Model → PostgreSQL" and let controllers hand-build JSON. Adding an explicit **API Resource** step matters here specifically because the contract uses non-default casing (`isApproved`, `isAssigned`) and the *same* resource (comment, suggestion) has a different shape depending on the endpoint (see Phase 6/7 notes below) — that's much safer to control in one Resource class than freehand in controllers.

### Critical schema rules (DEC-007) — configure these before writing any model or migration

These are **approved decisions** (`07-decisions-log.md`), not suggestions. Getting them wrong breaks every write operation silently or loudly:

1. `users.user_id` is the primary key, not `id`. Set `protected $primaryKey = 'user_id';` on `User`. `receipts.receipt_id` similarly needs `protected $primaryKey = 'receipt_id';` on `Receipt` (owned by Dev 2, but Comments/Suggestions relate to it).
2. **`users` has no timestamp column at all** — not `created_at`/`updated_at`, not a custom `timestamp` column, nothing. Set `public $timestamps = false;` on `User`.
3. `Comment`, `Suggestion` (and `Receipt`, `Chat_History`) use a **single `timestamp` column** instead of Laravel's `created_at`/`updated_at` pair. Disable default timestamps and set the column explicitly in the Service on create — don't rely on Eloquent's automatic timestamping, it will try to write to columns that don't exist.
4. Preserve exact column casing in `$casts` and in API Resources: `isApproved`, `isAssigned`. Cast both as `boolean`. Do not let a Resource silently rename them to `is_approved`/`is_assigned`.
5. `Likes_Comment`, `Likes_Suggestion`, `Favorites` are composite-primary-key pivot tables with **no own `id` and no timestamps**. Eloquent doesn't support composite primary keys cleanly on a standalone model — don't build one. Instead, model them as `belongsToMany` relationships (e.g. `User::likedComments()`, `Comment::likedBy()`) and use `attach()` / `detach()` / `toggle()`, which respect the composite key naturally.

### Definition of Done

A feature is not complete until:

- Implementation is complete.
- Validation is implemented.
- Authentication/authorization behavior is verified.
- Database behavior is verified.
- Automated tests pass.
- Negative/edge cases are covered.
- Postman/manual API verification passes.
- **API response matches the contract exactly — field names, casing, and nesting shape**, not just the status code.
- **Endpoint URL and HTTP method re-verified character-for-character against `docs/02-api-contract.md`** (not assumed from REST convention — see the errors caught in v1 of this plan).
- No unrelated files were changed.
- Git diff has been reviewed.
- No secrets are committed.

---

## 0.5 Open Items — confirm and log, don't silently assume

Per `README.md`'s "Do Not Guess" rule and `06-task-workflow.md` Step 4, the following are genuinely undocumented in the current docs (not in the contract, not in the decisions log). A recommended default is given so implementation isn't blocked, but **each one must be confirmed with the team and added to `07-decisions-log.md` before the PR is merged**, and called out explicitly in the PR description until then.

| # | Open question | Recommended default | Log as |
|---|---|---|---|
| 1 | Who is authorized to `PATCH /api/approve-suggestion/`? Contract shows a `403` but no rule defines the authorized party. | The owner of the `receipt` the suggestion targets: `$suggestion->receipt->user_id === auth()->id()`. Enforce via a `SuggestionPolicy::approve()`. | `DEC-008` |
| 2 | Password complexity rule for registration — not defined anywhere. | Laravel's default: `Password::min(8)->letters()->numbers()`. | `DEC-009` |
| 3 | Max length for `comment.text` / `suggestion.text` — column is unconstrained `TEXT`. | `comment.text`: 1000 chars. `suggestion.text`: 2000 chars. | `DEC-010` |
| 4 | Behavior on a duplicate like (`POST /api/like-comment/` / `POST /api/like-suggestion/`) — contract shows no `409` for these two (unlike Favorites' `409 Already favorited`). | Treat as idempotent: return `201`/current state with `is_liked: true` rather than an error. | `DEC-011` |
| 5 | Is `ingredients[]` required when creating a suggestion? | Optional — defaults to an empty array if omitted. | `DEC-012` |
| 6 | `GET /api/suggestions/` contract shows no `404` for a nonexistent `receipt_id` (unlike `GET /api/comments/`, which does). Omission or intentional? | Mirror Comments' behavior for consistency: `404 Receipt not found.` | `DEC-013` |

---

# Phase 1 — Repository & Contract Verification

## Task 1.1 — Verify the existing foundation

Before writing code:

- Confirm Docker services are running, PostgreSQL is reachable, the app boots, migrations are applied, Sanctum is configured, the test suite currently runs.
- **Confirm `User`, `Comment`, `Suggestion` models are configured per the DEC-007 rules in Section 0** (primary keys, timestamp handling, casts) — if they aren't yet, that configuration is part of this task, not something to patch later.
- Confirm existing `belongsToMany` pivot relationships for likes exist or need to be added (see DEC-007 point 5).

### Do not

- Recreate migrations, reinstall Laravel, rebuild Docker, replace existing architecture, rewrite existing migrations.

The existing migrations are the source of truth. New schema changes, if genuinely required, must use a new migration and be logged in `07-decisions-log.md`.

## Task 1.2 — Endpoint checklist (verified against `02-api-contract.md`)

Use this table directly — every row was cross-checked line-by-line against the contract, so it replaces guesswork:

| Method | URL | Auth | Body / Query | Success | Errors |
|---|---|---|---|---|---|
| POST | `/api/register/` | Public | `name`, `email`, `password` | `201` | `422` |
| POST | `/api/login/` | Public | `email`, `password` | `200` | `401`, `422` |
| DELETE | `/api/logout/` | Required | — | `204` | `401` |
| GET | `/api/profile/` | Required | — | `200` | `401` |
| GET | `/api/comments/` | Required | query: `receipt_id`, `page`, `per_page` | `200` | `401`, `404` |
| POST | `/api/comment/` | Required | `receipt_id`, `text` | `201` | `401`, `422` |
| POST | `/api/like-comment/` | Required | `comment_id` | `201` | `401`, `404` |
| DELETE | `/api/like-comment/` | Required | `comment_id` | `200` | `401`, `404` |
| GET | `/api/suggestions/` | Required | query: `receipt_id`, `page`, `per_page` | `200` | `401` (see open item #6) |
| POST | `/api/suggestion/` | Required | `receipt_id`, `text`, `ingredients[]` | `201` | `401`, `422` |
| POST | `/api/like-suggestion/` | Required | `suggestion_id` | `201` | `401`, `404` |
| DELETE | `/api/like-suggestion/` | Required | `suggestion_id` | `200` | `401`, `404` |
| PATCH | `/api/approve-suggestion/` | Required | `suggestion_id` | `200` | `401`, `403`, `404` |

Note the naming is **not uniformly RESTful** — `comment/` and `suggestion/` are singular for creation, `like-comment/`/`like-suggestion/` put "like" first, and approve is `PATCH` on a flat `/approve-suggestion/` path, not `PATCH /suggestions/{id}/approve`. Copy these exactly; don't "clean them up" to a conventional shape.

---

# Phase 2 — Git Isolation

## Task 2.1 — Feature branches

```text
feature/auth
feature/profile
feature/comments
feature/suggestions
```

Never work directly on `main` (`04-architecture-rules.md`).

## Task 2.2 — Protect shared files

Avoid unnecessary changes to `routes/api.php`, `composer.json`, `config/*`, existing migrations, shared models, common API response classes. Coordinate before editing any of these.

## Task 2.3 — Commit strategy

Small, focused commits, one concern per commit:

```text
feat(auth): implement registration
test(auth): add registration feature tests

feat(comments): implement comment creation
test(comments): add comment creation tests
```

Don't mix formatting, unrelated refactoring, another developer's feature, or dependency upgrades into a feature commit.

---

# Phase 3 — Authentication

## Task 3.1 — Registration

`POST /api/register/`

### Sequence

1. Route in `routes/auth.php` — confirm this file is actually loaded (via `bootstrap/app.php` route registration or the relevant service provider); a route defined in an unregistered file silently does nothing.
2. Registration Form Request validating `name`, `email` (unique), `password` (per **DEC-009**, pending confirmation — see open item #2).
3. Hash the password (`Hash::make` / Laravel's configured hasher).
4. Create the user, create a Sanctum token.
5. Return the exact `201` shape from the contract:
   ```json
   { "message": "User registered successfully", "user": { "user_id": 1, "name": "...", "email": "..." }, "token": "1|..." }
   ```
6. Validation failures return `422` with the contract's `{ "message", "errors" }` shape.
7. **Recommended addition (not contract-required, doesn't conflict with it):** apply `throttle:6,1` to `/register` and `/login` to reduce brute-force risk. Flag as an addition in the PR description.

### Edge cases

Missing/empty name; missing/malformed email; duplicate email; missing password; password below the DEC-009 default; extra/unexpected fields; successful registration; password never in the response; password stored hashed; token generated.

### Data integrity

Wrap user creation + token generation in `DB::transaction()` so a failure doesn't leave a user with no usable token.

---

## Task 3.2 — Login

`POST /api/login/`

### Sequence

1. Route + login Form Request (`email`, `password`).
2. Attempt authentication via Laravel's Auth facade / Sanctum-compatible mechanism.
3. Invalid credentials → `401` with the contract's exact message: `{ "message": "Invalid credentials." }`. Use one generic message for both "wrong password" and "email doesn't exist" — don't leak which one it was.
4. Valid credentials → generate a Sanctum token, return the `200` shape matching registration's `user` object shape plus `token`.

### Edge cases

Missing/malformed email; missing password; wrong password; nonexistent email; valid credentials; repeated login; no token on failed login; password never exposed.

---

## Task 3.3 — Logout

`DELETE /api/logout/`

### Sequence

1. `auth:sanctum` middleware.
2. Revoke the current token only (`$request->user()->currentAccessToken()->delete()`), not all of the user's tokens.
3. Return `204 No Content`.
4. No valid auth → `401` with `{ "message": "Unauthenticated." }`.

### Edge cases

Valid token; missing token; malformed token; expired/invalid token; logging out twice; another user's token cannot be revoked; token cannot be reused after logout.

---

# Phase 4 — Authentication Security

## Task 4.1 — Sanctum verification

Every protected endpoint in the table in Task 1.2 uses `auth:sanctum`.

## Task 4.2 — Ownership verification

Never trust a client-provided `user_id`. Always resolve via `auth()->id()` or the authenticated user instance (`03-authentication.md`).

### Security edge cases

Attempting to create content for another user via a forged `user_id`; modifying/deleting another user's data; liking/unliking on behalf of another user; accessing another user's protected data.

---

# Phase 5 — Profile

## Task 5.1 — Get authenticated profile

`GET /api/profile/`

### Sequence

1. `auth:sanctum`.
2. Retrieve the authenticated user.
3. Return the **flat** shape from the contract — not nested under a `user` key like the receipt/comment "author" objects are elsewhere:
   ```json
   { "user_id": 1, "name": "Ahmed", "email": "ahmed@example.com" }
   ```
4. No user ID accepted from the client to select the profile — it's always the authenticated identity.

### Edge cases

Authenticated user; missing token; invalid token; response never includes the password hash; response matches the flat contract shape exactly (no accidental nesting).

---

# Phase 6 — Comments

## Task 6.1 — View comments

`GET /api/comments/`

### Sequence

1. Protect route.
2. Validate `receipt_id` (required), `page`, `per_page`.
3. Verify the receipt exists → `404 { "message": "Receipt not found." }` if not.
4. Query comments via the `Receipt->comments()` relationship, eager-loading `with('user', 'likes')` explicitly in this step (not discovered later during a performance review).
5. Apply pagination, return the contract's nested shape:
   ```json
   { "data": [{ "id", "text", "timestamp", "user": { "user_id", "name" }, "likes_count", "is_liked" }], "meta": { "current_page", "per_page", "total", "last_page" } }
   ```

### Edge cases

Missing/invalid/nonexistent `receipt_id`; zero/one/many comments; invalid `page`/`per_page`; page beyond available results; authenticated vs unauthenticated.

---

## Task 6.2 — Add comment

`POST /api/comment/` — **not** `/api/comments/`.

### Sequence

1. `auth:sanctum`.
2. Validate `receipt_id`, `text` (max length per **DEC-010**, pending confirmation).
3. Verify the receipt exists.
4. Derive `user_id` from `auth()->id()`.
5. Create the comment (set the `timestamp` column manually per DEC-007 point 3).
6. Return the contract's **flat** shape — note this differs from the listing shape in 6.1 (no nested `user`, no `likes_count`):
   ```json
   { "message": "Comment added successfully", "comment": { "id", "user_id", "receipt_id", "text", "timestamp" } }
   ```

### Edge cases

Empty/whitespace-only comment; too-long comment (per DEC-010); missing/nonexistent/invalid `receipt_id`; unauthenticated; malicious HTML/script input (rely on output escaping on the Flutter side plus basic sanitization here); forged `user_id` in the body is ignored; successful creation.

---

## Task 6.3 — Like comment

`POST /api/like-comment/` — **not** `/api/comment-like/`.

### Sequence

1. Authenticate, validate `comment_id`, verify the comment exists.
2. Use `auth()->user()->likedComments()->toggle([$commentId])` or an explicit existence check before `attach()` — per **DEC-011**, a duplicate like should return current state, not an error.
3. Return `{ "message", "comment_id", "is_liked": true, "likes_count" }`.

### Edge cases

Valid like; duplicate like (idempotent per DEC-011); nonexistent comment; malformed ID; unauthenticated; two different users liking the same comment; one user's like cannot affect another's.

---

## Task 6.4 — Remove comment like

`DELETE /api/like-comment/` — **not** `/api/comment-like/`.

Use `detach()` scoped to the authenticated user.

### Edge cases

Existing like; missing like; nonexistent comment; unauthenticated; user A cannot remove user B's like; repeated unlike.

---

# Phase 7 — Suggestions

## Task 7.1 — View suggestions

`GET /api/suggestions/`

### Sequence

1. Authenticate, validate `receipt_id`, `page`, `per_page`.
2. Verify the receipt exists (see **open item #6** — contract doesn't document a `404` here; recommended default is to mirror Comments' behavior).
3. Query suggestions, eager-loading `with('user', 'ingredients', 'likes')`.
4. Paginate, return the contract shape including the nested `ingredients` array (empty array if none).

### Edge cases

Missing/invalid/nonexistent `receipt_id`; empty suggestions; invalid `page`/`per_page`; page beyond data; unauthenticated.

---

## Task 7.2 — Add suggestion

`POST /api/suggestion/` — **not** `/api/suggestions/`.

### Sequence

1. Authenticate, validate `receipt_id`, `text` (max length per DEC-010), and `ingredients[]` (optional per **DEC-012** — each item: `name`, `quantity`, `unit`, `isAssigned`).
2. Verify the receipt exists.
3. Derive `user_id` from `auth()->id()`.
4. **Wrap in `DB::transaction()`:** create the suggestion, then create each `Ingredient` row with `suggestion_id` set and `receipt_id` left `null` — this must respect the schema's `CHECK` constraint (exactly one of `receipt_id`/`suggestion_id` is set). This step was missing entirely from v1 of this plan; it's not optional, the contract's request/response both include `ingredients`.
5. Return the contract's shape (flat, `user_id` not nested, includes `ingredients`):
   ```json
   { "message": "Suggestion created successfully", "suggestion": { "id", "user_id", "receipt_id", "text", "isApproved": false, "timestamp", "ingredients": [...] } }
   ```

### Edge cases

Missing required fields; empty suggestion; invalid/nonexistent receipt; unauthenticated; forged `user_id`; suggestion created with zero ingredients (per DEC-012); suggestion created with multiple ingredients; a failure partway through ingredient creation must not leave a partially-created suggestion (covered by the transaction).

---

## Task 7.3 — Like suggestion

`POST /api/like-suggestion/` — **not** `/api/suggestion-like/`.

Same pattern as Task 6.3, using `likedSuggestions()->toggle()`/idempotent duplicate handling per DEC-011.

### Edge cases

Valid like; duplicate like; nonexistent suggestion; malformed ID; unauthenticated; user isolation.

---

## Task 7.4 — Remove suggestion like

`DELETE /api/like-suggestion/` — **not** `/api/suggestion-like/`.

### Edge cases

Existing like; missing like; nonexistent suggestion; unauthenticated; repeated unlike; user A cannot remove user B's like.

---

## Task 7.5 — Approve suggestion

`PATCH /api/approve-suggestion/` — **not** `POST /api/suggestions/approve/`. Note the method is `PATCH` and `suggestion_id` arrives in the **request body**, not as a route parameter.

### Sequence

1. Authenticate, validate `suggestion_id` in the body.
2. Load the suggestion with its `receipt` relationship.
3. Authorize via `SuggestionPolicy::approve()` implementing **DEC-008** (pending confirmation: receipt owner) — reject with `403 { "message": "You are not allowed to approve this suggestion." }` for anyone else.
4. Nonexistent suggestion → `404 { "message": "Suggestion not found." }`.
5. Set `isApproved = true`, save.
6. Return the contract's minimal shape — only these three fields, not the full suggestion object:
   ```json
   { "message": "Suggestion approved successfully", "suggestion": { "id", "receipt_id", "isApproved": true } }
   ```

### Critical edge cases

Authorized owner approves; unrelated user attempts approval (403); nonexistent suggestion; already-approved suggestion (decide: idempotent success or a rejection — not documented, treat as idempotent success unless told otherwise); malformed ID; unauthenticated; concurrent approval attempts; state remains consistent after a failed authorization attempt.

---

# Phase 8 — Cross-Feature Data Integrity

## Task 8.1 — Relationships

Verify Eloquent relationships for `User↔Comments`, `User↔Suggestions`, `Receipt↔Comments`, `Receipt↔Suggestions`, plus the `belongsToMany` pivot relationships from DEC-007 point 5 (`User↔Comments` via `likes_comment`, `User↔Suggestions` via `likes_suggestion`). Use relationships instead of duplicated query logic.

## Task 8.2 — User isolation

Create at least two test users. Verify User A's data is never exposed to or mutable by User B for every operation where ownership matters (comments, suggestions, likes, approval).

## Task 8.3 — Missing resources

Every endpoint receiving a resource ID must handle: valid ID, nonexistent ID, malformed ID, deleted resource — consistently, without leaking raw SQL/database exceptions to the client.

---

# Phase 9 — Automated Testing

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

## Regression guard for the exact bug found in v1

Add a lightweight test that asserts every route from the Task 1.2 table is registered with its exact method + URL (e.g. iterate the table and assert `Route::has()`/hit each one). This directly prevents the URL-drift error this plan had in its first draft from recurring silently.

---

# Phase 10 — Manual API Verification

After automated tests pass, verify every endpoint in Postman against the Task 1.2 table: method, exact URL, token, request body, query parameters, status code, response JSON (field names and casing), validation errors, database state.

```text
Happy path → Missing required field → Invalid value → Unauthorized →
Resource not found → Ownership violation → Duplicate/repeated operation →
Verify database state
```

---

# Phase 11 — Quality & Security Review

Before opening the PR:

### Code review

No fat controllers; no duplicated business logic; no hard-coded user IDs/credentials; no raw SQL where Eloquent suffices; no unnecessary N+1 queries; no debug statements or commented-out code; no unrelated refactoring.

### Static analysis & style (new)

- Larastan/PHPStan passes at the project's configured level.
- Laravel Pint applied.

### Security

Sanctum protects every endpoint in the Task 1.2 table; passwords hashed and never serialized; ownership always server-derived; correct status codes for unauthorized access; input validated; no sensitive exceptions leaked; throttling present on `/register` and `/login`.

### Database

Foreign keys respected; duplicate likes handled per DEC-011; failed operations don't leave inconsistent data (transactions in place for suggestion+ingredients and registration+token); existing migrations unchanged; model primary keys and timestamp config match DEC-007.

---

# Phase 12 — Documentation & Finalization

Update `docs/08-current-status.md` with completed endpoints, tests, known limitations, integration dependencies, and any decisions affecting Developer 2.

**If any Section 0.5 open item was resolved during this task, add the corresponding `DEC-0XX` entry to `docs/07-decisions-log.md` before opening the PR** — this is a project governance rule (`README.md`), not optional cleanup.

Then:

```bash
git status
git diff
```

Confirm only intended files changed. Run the full test suite. Only then open the PR.

---

# Final PR Checklist

- [x] Branch is based on current integration branch
- [x] Auth / Profile / Comments / Suggestions complete
- [x] All endpoint URLs and methods re-verified against `docs/02-api-contract.md` (see Task 1.2 table)
- [x] Model primary keys and timestamp configuration match DEC-007
- [x] Suggestion creation handles `ingredients[]` inside a DB transaction
- [x] Open items from Section 0.5 are either confirmed and logged in `07-decisions-log.md`, or explicitly flagged in the PR description
- [x] All Form Requests / Services / Policies / API Resources complete
- [x] Automated tests pass, including the endpoint-registration regression test
- [x] Negative/edge tests pass
- [x] Postman tests pass
- [x] Authorization and user isolation verified
- [x] No secrets committed, no unrelated files changed
- [x] Documentation updated, git diff reviewed
- [x] PR description explains implementation, testing, and any pending open items
