# Open Sauce API Contract

## Base API

TBD

## Authentication

Protected endpoints use:

Authorization: Bearer <sanctum_token>

---

# AUTH

## POST /api/login/

Authentication:
Public

Purpose:
Authenticate an existing user.

Request:
TBD - must match the approved Flutter contract.

Response:
TBD

---

## POST /api/register/

Authentication:
Public

Purpose:
Create a new user.

Request:
TBD

Response:
TBD

---

## DELETE /api/logout/

Authentication:
Required

Purpose:
Logout the authenticated user.

Request:
TBD

Response:
TBD

---

# FAVORITES

## GET /api/favorites/

Authentication:
Required

Purpose:
Return the authenticated user's favorites.

---

## POST /api/favorites/

Authentication:
Required

Purpose:
Add a receipt to the authenticated user's favorites.

---

## DELETE /api/remove-favorites/

Authentication:
Required

Purpose:
Remove a receipt from the authenticated user's favorites.

---

# CHATBOT

## GET /api/chat-history/

Authentication:
Required

Purpose:
Return the authenticated user's chat history.

---

## POST /api/user-prompt/

Authentication:
Required

Purpose:
Submit a user prompt to the chatbot.

---

## GET /api/chat-response/

Authentication:
Required

Purpose:
Retrieve the chatbot response.

AI integration details:
See `05-ai-integration.md`.

---

# DETAILS

## GET /api/profile/

Authentication:
Required

Purpose:
Return the authenticated user's profile.

---

## GET /api/receipt-details/

Authentication:
TBD

Purpose:
Return details of a specific receipt.

Receipt identifier mechanism:
TBD - must be confirmed with Flutter team.

---

# SUGGESTIONS

## GET /api/suggestions/

Authentication:
TBD

Purpose:
Return available suggestions.

---

## POST /api/suggestion/

Authentication:
Required

Purpose:
Create a suggestion owned by the authenticated user.

Important:

The user_id MUST come from the authenticated Sanctum user.

Do not accept user_id from the client request.

---

# IMPORTANT

Some Postman requests currently do not contain finalized
request/response schemas.

Do not invent them.

When a schema is finalized, update this document.