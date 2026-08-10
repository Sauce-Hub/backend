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

- user_id
- suggestion_id

A unique constraint should prevent duplicate likes.

---

## DEC-004 - Receipt Details Identifier

Status: PENDING

Decision:

The method for identifying the receipt in:

GET /api/receipt-details/

must be confirmed with the Flutter/API team.

Candidate:

query parameter:
?receipt_id={id}

Do not implement the final behavior until approved.

---

## DEC-005 - Comment Relationships

Status: PENDING

The final relationship between Comment, User and Receipt
must be confirmed before implementation.

---

## DEC-006 - AI Contract

Status: PENDING

The Laravel ↔ FastAPI request and response schema
must be confirmed by the AI team.

---

# Rule

Every new architecture or business decision must be added here
before implementation when it affects other developers.