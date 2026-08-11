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

# Rule

Every new architecture or business decision must be added here before implementation when it affects other developers.