# Authentication

## Authentication System

Laravel Sanctum

## Token Authentication

The API uses Sanctum bearer tokens.

Protected requests must include:

```http
Authorization: Bearer {{authToken}}
```

## Public Endpoints

* `POST /api/register/` (Register a new user)
* `POST /api/login/` (Login authentication)

## Protected Endpoints

All other endpoints require authentication.

### Auth Folder
* `DELETE /api/logout/` (Log out a user)

### For You Folder
* `GET /api/fyp/` (Get For you page content)
* `POST /api/like/` (Add like on post)
* `DELETE /api/like/` (Delete like on post)
* `POST /api/new-post/` (Create new post)

### Chatbot Folder
* `GET /api/chat-history/` (Get chatbot history)
* `POST /api/user-prompt/` (Send user prompt)
* `GET /api/chat-response/` (Get chatbot response)

### Favorites Folder
* `GET /api/favorites/` (Get all user favorites)
* `POST /api/favorites/` (Add post to favorites)
* `DELETE /api/remove-favorites/` (Remove post from favorites)

### Suggestions Folder
* `GET /api/suggestions/` (View all suggestions)
* `POST /api/suggestion/` (Add suggestion)
* `POST /api/like-suggestion/` (Add like on suggestion)
* `DELETE /api/like-suggestion/` (Delete like on suggestion)
* `PATCH /api/approve-suggestion/` (Approve suggestion)

### Comments Folder
* `GET /api/comments/` (View comments)
* `POST /api/comment/` (Add comment)
* `POST /api/like-comment/` (Add like on comment)
* `DELETE /api/like-comment/` (Remove like from comment)

### Details Folder
* `GET /api/profile/` (Get user profile details)
* `GET /api/receipt-details/` (Get receipt details)

---

## User Ownership

Never trust a client-provided `user_id` for resources that belong to the authenticated user. 

Always resolve ownership on the backend using the authenticated Sanctum user context:

`auth()->id()`

## Security

Never:
* Return passwords in API responses.
* Log passwords or raw request data containing authentication credentials.
* Expose authentication tokens in system logs.
* Hard-code secrets (e.g. database credentials, Sanctum keys) in application files.
* Commit `.env` files to source control.
* Trust client-provided resource identifiers (like `user_id`) for authorization.