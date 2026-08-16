# Open Sauce - Decisions Log

This file contains approved project decisions.

Do not override these decisions without explicit approval.

---

## DEC-001 - Authentication

Status: APPROVED

Decision:
Use Laravel Sanctum for API authentication.

Date:
2026-08-10

---

## DEC-002 - Suggestion Ownership

Status: APPROVED

Decision:
Every Suggestion belongs to the user who created it.
The user_id must be derived from the authenticated user.
The client must not be trusted to provide ownership.

---

## DEC-003 - Suggestion Likes

Status: APPROVED

Decision:
A user can like a specific suggestion.
Likes_Suggestion references:
* user_id
* suggestion_id

A unique constraint should prevent duplicate likes.

---

## DEC-004 - Receipt Details Identifier

Status: APPROVED

Decision:
The query parameter:

`?receipt_id={id}`

is finalized as the receipt lookup mechanism in `GET /api/receipt-details/` to identify the receipt. This is approved in Postman Contract v4.

Date:
2026-08-10

---

## DEC-005 - Comment Relationships

Status: APPROVED

Decision:
A Comment belongs to exactly one User and one Receipt. Both foreign keys use ON DELETE CASCADE. The same user can comment multiple times on the same receipt (no unique constraint on user_id + receipt_id).

---

## DEC-007 - Database Schema and Constraints

Status: APPROVED

Decision:
1. Users table uses user_id as its primary key (BIGINT UNSIGNED AUTO_INCREMENT).
2. Suggestions table requires receipt_id referencing receipts.receipt_id (ON DELETE CASCADE) to link it to the original recipe.
3. Likes_Comments targets comment_id (not receipt_id) and enforces user_id + comment_id composite primary key.
4. Ingredients table has nullable receipt_id and suggestion_id, with a CHECK constraint enforcing that exactly one of the two is NOT NULL.
5. Exact column casing from docs/01-database-schema.md is preserved (Calories, Fats, Carbs, Protein, isApproved, isAssigned, timestamp).
6. Single timestamp field is used in tables instead of Laravel's created_at/updated_at.

Date:
2026-08-10

---

## DEC-006 - AI Contract

Status: PENDING (NEEDS DECISION)

Decision:
The Laravel ↔ FastAPI request and response schema must be confirmed by the AI team. The client-facing contract is finalized (e.g. `POST /api/user-prompt/` etc. in Postman Contract v4), but the backend-to-backend interface details remain open.

## DEC-009 - Password Complexity

Status: APPROVED

Decision:
The user registration endpoint validation requires:
* Minimum of 8 characters
* At least one letter
* At least one number
Enforced using Laravel's `Password::min(8)->letters()->numbers()` validation rule.

Date:
2026-08-11

---

## DEC-010 - Max Text Length for Comments and Suggestions

Status: APPROVED

Decision:
The maximum character length for comment and suggestion text is constrained to:
* `Comment`: 1000 characters
* `Suggestion`: 2000 characters
Enforced during validation.

Date:
2026-08-11

---

## DEC-011 - Duplicate Like Behavior on Comments and Suggestions

Status: APPROVED

Decision:
If a user attempts to like a comment or suggestion that they have already liked, the operation is treated as idempotent. The API returns a `201 Created` success response with `is_liked: true` and the current count rather than an error or duplicate record.

Date:
2026-08-11

---

## DEC-015 - Registration Throttling

Status: APPROVED

Decision:
The public user registration endpoint `POST /api/register/` has a rate limiting throttle applied (`throttle:6,1`) to reduce brute-force registration risks.

Date:
2026-08-11

## DEC-016 - Login Throttling

Status: APPROVED

Decision:
The public user login endpoint `POST /api/login/` has a rate limiting throttle applied (`throttle:6,1`) to reduce brute-force login risks.

Date:
2026-08-11

## DEC-017 - Logout Token Revocation Scope

Status: APPROVED

Decision:
The user logout endpoint `DELETE /api/logout/` revokes only the current Sanctum access token used for the request (`$request->user()->currentAccessToken()->delete()`) rather than revoking all active user tokens.

Date:
2026-08-11

---

## DEC-018 - Logout Response Shape

Status: APPROVED

Decision:
A successful user logout returns an empty HTTP `204 No Content` response body. No throttling is applied to this endpoint.

Date:
2026-08-11

---

## DEC-012 - Optional Ingredients on Suggestion Creation

Status: APPROVED

Decision:
The `ingredients` parameter in the request body for creating suggestions (`POST /api/suggestion/`) is optional. If omitted by the client, it defaults to an empty array.

Date:
2026-08-12

---

## DEC-008 - Suggestion Approval Authorization

Status: APPROVED

Decision:
Only the owner of the receipt that a suggestion targets is authorized to approve the suggestion (`PATCH /api/approve-suggestion/`). Any other user's attempt to approve it will be rejected with a `403 Forbidden` status.

Date:
2026-08-12

---

## DEC-013 - Nonexistent Receipt Behavior for Suggestions Listing

Status: APPROVED

Decision:
When listing suggestions for a receipt (`GET /api/suggestions/`), if the specified `receipt_id` does not exist in the database, the API returns a `404 Not Found` response with `{"message": "Receipt not found."}`. This mirrors the behavior of `GET /api/comments/` for consistency across resource listings.

Date:
2026-08-12

---

## DEC-019 - Instructions Table & Conditional Foreign Key Constraint

Status: APPROVED

Decision:
The `instructions` table stores step-by-step cooking instructions for receipts and suggestions.
Fields:
- `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
- `step_number` (INT)
- `instruction` (TEXT)
- `receipt_id` (BIGINT UNSIGNED, Nullable, FK → receipts.receipt_id, ON DELETE CASCADE)
- `suggestion_id` (BIGINT UNSIGNED, Nullable, FK → suggestions.id, ON DELETE CASCADE)

A CHECK constraint enforces that exactly one of `receipt_id` and `suggestion_id` is NOT NULL:
`((receipt_id IS NULL AND suggestion_id IS NOT NULL) OR (receipt_id IS NOT NULL AND suggestion_id IS NULL))`

Date:
2026-08-16

---

## DEC-020 - Stale Suggestions Overwrite Policy

Status: APPROVED

Decision:
Option 1 (Owner Discretion / Unconditional Overwrite) is adopted.
When a recipe owner approves a pending suggestion, the snapshot of ingredients and instructions contained in that suggestion unconditionally replaces the recipe's current ingredients and instructions, regardless of whether the recipe was modified after the suggestion was created.

Date:
2026-08-16

---

## DEC-021 - Profile Response Key

Status: APPROVED

Decision:
The key returned in `GET /api/profile/` containing the user's recipes is finalized as `"receipts"`.

Date:
2026-08-16

---

## DEC-022 - Suggestion Re-Approval Idempotency

Status: APPROVED

Decision:
Re-approving an already approved suggestion (`PATCH /api/approve-suggestion/`) is idempotent.
- The recipe owner may approve the same suggestion again.
- The approved suggestion snapshot is reapplied atomically to the target recipe's ingredients and instructions.
- The endpoint remains successful with HTTP 200.
- No conflict/409 is generated.

Date:
2026-08-16

---

# Rule

Every new architecture or business decision must be added here before implementation when it affects other developers.