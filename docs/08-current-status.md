# Current Backend Status

Last Updated: 2026-08-12

## Developer 1 Scope — Completed Work (Phases 1–11)

### API Endpoints Completed & Verified

1. **Authentication**
   - `POST /api/register/` (Public) — User registration. Validates `name`, `email` (unique), `password` (min 8, letter, number per DEC-009). Rate limited (`throttle:6,1` per DEC-015). Returns `201 Created` with Sanctum token and user payload wrapped in a DB transaction.
   - `POST /api/login/` (Public) — User authentication. Rate limited (`throttle:6,1` per DEC-016). Returns `200 OK` with Sanctum token and user payload or generic `401 Unauthorized` (`Invalid credentials.`).
   - `DELETE /api/logout/` (Protected) — Token revocation. Revokes current access token only (DEC-017). Returns `204 No Content` (DEC-018).

2. **Profile**
   - `GET /api/profile/` (Protected) — Authenticated profile. Returns flat JSON payload `{"user_id", "name", "email", "receipts": [...]}` containing the authenticated user's profile and recipe list (empty array `[]` when no recipes exist).

3. **Comments**
   - `GET /api/comments/` (Protected) — Listing comments for a receipt. Query params: `receipt_id`, `page`, `per_page`. Eager loads `user` and `likes`. Returns `404 Receipt not found.` if receipt does not exist. Returns `200 OK` with paginated `data` array and `meta` object.
   - `POST /api/comment/` (Protected) — Adding a comment. Body: `receipt_id`, `text` (max 1000 chars per DEC-010). Manual single timestamp (DEC-007). Returns `201 Created` with flat comment object.
   - `POST /api/like-comment/` (Protected) — Liking a comment. Body: `comment_id`. Idempotent (DEC-011). Returns `201 Created` with `is_liked: true` and current `likes_count`.
   - `DELETE /api/like-comment/` (Protected) — Unliking a comment. Body: `comment_id`. Detaches like. Returns `200 OK` with `is_liked: false` and current `likes_count`.

4. **Suggestions**
   - `GET /api/suggestions/` (Protected) — Listing suggestions for a receipt. Query params: `receipt_id`, `page`, `per_page`. Eager loads `user`, `ingredients`, and `likes`. Returns `404 Receipt not found.` if receipt does not exist (DEC-013). Returns `200 OK` with paginated payload.
   - `POST /api/suggestion/` (Protected) — Creating a suggestion. Body: `receipt_id`, `text` (max 2000 chars per DEC-010), `ingredients[]` (optional per DEC-012). Executed inside a DB transaction creating suggestion and associated ingredient rows (setting `suggestion_id`, keeping `receipt_id` null per DEC-007 CHECK constraint). Returns `201 Created` with suggestion payload including ingredients.
   - `POST /api/like-suggestion/` (Protected) — Liking a suggestion. Body: `suggestion_id`. Idempotent (DEC-011). Returns `201 Created` with `is_liked: true` and `likes_count`.
   - `DELETE /api/like-suggestion/` (Protected) — Unliking a suggestion. Body: `suggestion_id`. Detaches like. Returns `200 OK` with `is_liked: false` and `likes_count`.
   - `PATCH /api/approve-suggestion/` (Protected) — Approving a suggestion. Body: `suggestion_id`. Enforces authorization via `SuggestionPolicy::approve()` checking `$suggestion->receipt->user_id === auth()->id()` (DEC-008). Non-owners rejected with `403 Forbidden` (`You are not allowed to approve this suggestion.`). Returns `200 OK` with minimal approval payload.

---

## Architectural Compliance (DEC-007 & Best Practices)

- **Thin Controllers:** All business logic, DB transactions, and relationship manipulation reside in Services (`AuthService`, `CommentService`, `SuggestionService`).
- **Form Requests:** Input validation is handled cleanly via Form Requests (`RegisterRequest`, `LoginRequest`, `GetCommentsRequest`, `StoreCommentRequest`, `LikeCommentRequest`, `GetSuggestionsRequest`, `StoreSuggestionRequest`, `LikeSuggestionRequest`, `ApproveSuggestionRequest`).
- **Policies:** Ownership authorization for approval is handled via `SuggestionPolicy`.
- **API Resources:** Response formatting strictly follows contract casing and nesting via dedicated API Resources (`UserResource`, `CommentResource`, `CommentStoreResource`, `SuggestionResource`, `SuggestionStoreResource`, `SuggestionApproveResource`, `IngredientResource`).
- **Database & Schema (DEC-007):**
  - `User` primary key configured as `user_id` with `public $timestamps = false`.
  - `Comment`, `Suggestion`, `Ingredient`, `Receipt` configured with single `timestamp` column.
  - `Likes_Comment` and `Likes_Suggestion` modeled via Eloquent `belongsToMany` relationships on pivot tables.
  - `Ingredient` `CHECK` constraint enforced: `(receipt_id IS NULL AND suggestion_id IS NOT NULL) OR (receipt_id IS NOT NULL AND suggestion_id IS NULL)`.

---

## Testing & Quality Assurance

- **Full Suite Status:** 165 automated tests passing (718 assertions).
- **Test Organization:**
  - `tests/Feature/Auth/` (Register, Login, Logout, Sanctum token handling, Throttling)
  - `tests/Feature/Profile/` (Profile retrieval, Unauthenticated rejection)
  - `tests/Feature/Comments/` (Listing, Pagination, Creation, Validation, Like, Unlike, Idempotency, 404s)
  - `tests/Feature/Suggestions/` (Listing, Pagination, Creation with/without ingredients, Like, Unlike, Approval authorization, 403s, 404s)
  - `tests/Feature/EndpointRegistrationTest.php` (Regression test verifying exact HTTP methods and URLs for all assigned endpoints against `docs/02-api-contract.md`)
- **Negative & Edge-Case Coverage:**
  - Unauthenticated requests (401)
  - Validation failures (422)
  - Non-existent resource lookups (404)
  - Unauthorized suggestion approval by non-receipt owner (403)
  - Idempotent duplicate likes
  - User isolation (User A cannot modify User B's resources or act on their behalf)
  - Boundary text length enforcement and missing optional parameters
  - Transaction rollbacks on partial failure
- **Static Analysis & Formatting:**
  - Laravel Pint passes cleanly with 0 style violations.
  - PHPStan: Not installed in `composer.json` require-dev (reported availability: unavailable).

---

## Section 0.5 Open Items & Resolved Decisions Log

All 6 open items identified in Section 0.5 of `EXECUTION_PLAN_TASK_1_v2.md` have been resolved and logged in `docs/07-decisions-log.md`:
- `DEC-008`: Suggestion approval authorization (Receipt owner only)
- `DEC-009`: Password complexity rules (min 8, letter, number)
- `DEC-010`: Max text length (Comments: 1000, Suggestions: 2000)
- `DEC-011`: Idempotent duplicate like behavior
- `DEC-012`: Optional `ingredients[]` on suggestion creation
- `DEC-013`: Nonexistent receipt behavior on `GET /api/suggestions/` (Returns `404 Receipt not found.`)

---

## Developer 2 Integration Notes & Dependencies

Developer 2 must adhere to the following decisions and patterns when implementing remaining features:

1. **Receipt Model Configuration (DEC-007):**
   - Must set `protected $primaryKey = 'receipt_id';` on the `Receipt` model.
   - Receipts use a single `timestamp` column (not `created_at`/`updated_at`).
   - The `user_id` on `receipts` represents receipt ownership and is required for Developer 1's `SuggestionPolicy::approve()` check (`$suggestion->receipt->user_id === auth()->id()`).

2. **Ingredients Schema (DEC-007):**
   - `Ingredient` rows created for receipts must set `receipt_id` and leave `suggestion_id` as `NULL` to comply with the database `CHECK` constraint.

3. **Pivot Tables & Relationships:**
   - `Favorites` and `Likes_Receipt` are composite-primary-key pivot tables without an `id` column or timestamps. Implement via Eloquent `belongsToMany` relationships (`User::favorites()`, `Receipt::favoritedBy()`, etc.).

4. **Authentication Context:**
   - Always derive user identity from `auth()->id()` / Sanctum token context. Never trust client-provided `user_id`.

---

## Current Sprint Progress (Recipe Suggestions & Instructions Refactor)

### Completed Tasks
- **Task 1.1 — Create `instructions` database migration**: COMPLETED
  - Migration file created: `database/migrations/2026_08_16_000000_create_instructions_table.php`
  - Fields: `id`, `step_number` (int), `instruction` (text), `receipt_id` (nullable FK → `receipts.receipt_id`, CASCADE), `suggestion_id` (nullable FK → `suggestions.id`, CASCADE).
  - PostgreSQL `CHECK` constraint: `check_instruction_receipt_or_suggestion` enforcing exactly one of `receipt_id` or `suggestion_id` is NOT NULL.
  - Verified: Migration and rollback against live PostgreSQL 16 instance in Docker; 165 automated tests passing on SQLite test environment.
  - Documented in: `docs/01-database-schema.md` and `docs/07-decisions-log.md` (DEC-019, DEC-020, DEC-021).
- **Task 1.2 — Instruction Model & Eloquent Relationships**: COMPLETED
  - Model created: `app/Models/Instruction.php` with `$table = 'instructions'`, `$timestamps = false`, `$fillable = ['step_number', 'instruction', 'receipt_id', 'suggestion_id']`, `$casts = ['step_number' => 'integer']`.
  - Relationships: `Instruction::receipt()`, `Instruction::suggestion()`, `Receipt::instructions()` (ordered by `step_number`), `Suggestion::instructions()` (ordered by `step_number`).
  - Factory created: `database/factories/InstructionFactory.php`.
  - Verified: 167 automated tests passing (727 assertions), Pint code formatting verified.

- **Task 1.3 — Update Profile Endpoint Response Shape**: COMPLETED
  - Implemented `ProfileResource` and `ReceiptResource` formatting profile data and user's receipts matching the exact approved contract casing and structure (`receipt_id`, `name`, `caption`, `category`, `estimated_time`, `Calories`, `Fats`, `Carbs`, `Protein`, `timestamp`).
  - Updated `ProfileService::getProfile()` to eager-load `receipts`.
  - Updated `ProfileController::show()` to return `ProfileResource`.
  - Added comprehensive test suite in `tests/Feature/Profile/ProfileTest.php` covering profile retrieval with receipts, user isolation, empty state array (`[]`), unauthenticated access, deleted user handling, and contract casing guards.
  - Verified: 170 automated tests passing (767 assertions), Pint code formatting verified.

- **Task 3.1 — Suggestion Snapshot Creation**: COMPLETED
  - Implemented initial Suggestion Snapshot Creation where creating a suggestion for a receipt (`POST /api/suggestion/`) captures the current state of recipe ingredients and instructions into the suggestion inside a `DB::transaction()`.
  - Created `InstructionResource` and updated `SuggestionStoreResource` to include cloned instructions alongside ingredients.
  - Updated `StoreSuggestionRequest` to validate only `receipt_id` and `text` (max 2000 chars), ignoring any client-sent snapshot items.
  - Cloned ingredients and instructions have `receipt_id = null` and `suggestion_id = $suggestion->id`, preserving database `CHECK` constraints.
  - Original recipe ingredients and instructions remain completely unmodified.
  - Suggestions start with `isApproved = false`.
  - Added full test suite in `tests/Feature/Suggestions/AddSuggestionTest.php` covering authenticated creation, snapshot cloning, ordering by `step_number`, user isolation, validation, nonexistent receipt 404 handling, and transaction rollback on failure.
  - Verified: 169 automated tests passing (819 assertions), Pint code formatting verified with 0 violations.

### Next Pending Task
- **Task 3.2 — Recipe Suggestions Pipeline: Update & Approval Workflow** (Awaiting approval)