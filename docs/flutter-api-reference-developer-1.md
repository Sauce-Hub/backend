# Open Sauce — Developer 1 Flutter API Reference

A clean, concise, implementation-ready reference for the Flutter team covering Developer 1 backend endpoints.

---

## 1. Scope

> **Developer 1 Scope**: This document covers only the API endpoints owned and maintained by Developer 1:
> - **Authentication**
> - **Profile**
> - **Comments**
> - **Suggestions**
>
> **Note**: Developer 2 endpoints (*For You Feed, Recipe Posting, Likes on Recipes, Favorites, Chatbot, Recipe Details*) are documented separately.

---

## 2. Base URL

All endpoints are relative to the base URL:

`{{baseUrl}}`

- **Development Server**: `http://localhost:8000` (or `http://10.0.2.2:8000` for Android emulator / machine LAN IP for physical device testing).
- Configure `baseUrl` dynamically in your Flutter environment/flavor configuration. Do not hard-code environment-specific URLs.

---

## 3. Authentication

Authentication is handled via **Laravel Sanctum** Bearer tokens.

- **Authorization Header**: For all protected endpoints, attach the Bearer token:
  ```http
  Authorization: Bearer {{authToken}}
  ```
- **Public Endpoints**:
  - `POST /api/register/`
  - `POST /api/login/`
- **Protected Endpoints**: All other Developer 1 endpoints require authentication (`DELETE /api/logout/`, `GET /api/profile/`, all Comments endpoints, all Suggestions endpoints).
- **Token Management**: On successful register or login, persist `token` in secure storage (e.g. `flutter_secure_storage`). Supply it on all subsequent authenticated calls. Discard it locally on logout.

---

## 4. API Conventions

- **Headers**: Include `Accept: application/json` and `Content-Type: application/json` on all requests.
- **Identity & Ownership**: The authenticated user is resolved server-side from the Bearer token (`auth()->id()`). **Do not send `user_id`** in request payloads or query parameters for ownership identification.
- **Exact JSON Field Casing**: Ensure serialization models preserve exact field names:
  - **PascalCase** nutritional fields: `Calories`, `Fats`, `Carbs`, `Protein`
  - **camelCase** booleans: `isApproved`, `isAssigned`
  - **snake_case** keys & timestamps: `receipt_id`, `suggestion_id`, `comment_id`, `step_number`, `is_liked`, `likes_count`, `timestamp`
- **Timestamps**: Returned in ISO 8601 UTC format (e.g. `2026-08-10T18:00:00Z`).
- **Pagination Structure**: Paginated listings return items under `data` and pagination state under `meta`:
  ```json
  {
    "data": [],
    "meta": {
      "current_page": 1,
      "per_page": 20,
      "total": 10,
      "last_page": 1
    }
  }
  ```
- **Validation Error Structure**:
  ```json
  {
    "message": "The given data was invalid.",
    "errors": {
      "email": [
        "The email field is required."
      ]
    }
  }
  ```

---

## 5. Endpoint Reference

---

### AUTHENTICATION

---

## POST /api/register/

**Purpose**  
Register a new user account.

**Auth**  
Public

**Request**
```json
{
  "name": "Ahmed",
  "email": "ahmed@example.com",
  "password": "Password123!"
}
```

**Success — 201**
```json
{
  "message": "User registered successfully",
  "user": {
    "user_id": 1,
    "name": "Ahmed",
    "email": "ahmed@example.com"
  },
  "token": "1|example-token"
}
```

**Errors**
- 422 — Validation error (e.g., email already registered; password requires minimum 8 characters, at least 1 letter, and 1 number)
- 429 — Rate limited (max 6 requests per minute)

**Flutter Notes**
- Store the returned `token` securely and pass it as Bearer token on protected endpoints.

---

## POST /api/login/

**Purpose**  
Authenticate an existing user.

**Auth**  
Public

**Request**
```json
{
  "email": "ahmed@example.com",
  "password": "Password123!"
}
```

**Success — 200**
```json
{
  "message": "Login successful",
  "user": {
    "user_id": 1,
    "name": "Ahmed",
    "email": "ahmed@example.com"
  },
  "token": "2|example-token"
}
```

**Errors**
- 401 — Invalid credentials (`{"message": "Invalid credentials."}`)
- 422 — Validation error
- 429 — Rate limited (max 6 requests per minute)

**Flutter Notes**
- Store the returned `token` securely for authenticated sessions.

---

## DELETE /api/logout/

**Purpose**  
Revoke the current authenticated user's access token.

**Auth**  
Required (Bearer Token)

**Success — 204**  
*(No response body)*

**Errors**
- 401 — Unauthenticated

**Flutter Notes**
- On receiving 204, clear the token and user session locally, then route the user to the login screen.

---

### PROFILE

---

## GET /api/profile/

**Purpose**  
Retrieve authenticated user details along with the list of recipes created by this user.

**Auth**  
Required (Bearer Token)

**Success — 200**
```json
{
  "user_id": 1,
  "name": "Ahmed",
  "email": "ahmed@example.com",
  "receipts": [
    {
      "receipt_id": 1,
      "name": "Pasta",
      "caption": "Quick pasta",
      "category": "DINNER",
      "estimated_time": "20 min",
      "Calories": 500,
      "Fats": 15,
      "Carbs": 70,
      "Protein": 20,
      "timestamp": "2026-08-10T18:00:00Z"
    }
  ]
}
```

**Errors**
- 401 — Unauthenticated

**Flutter Notes**
- No `user_id` is passed by client; identity is derived from the Bearer token.
- `receipts` contains all recipes created by the authenticated user.
- `category` is an Enum value (`BREAKFAST`, `LUNCH`, `DINNER`, `SWEETS`, `HOT DRINKS`, `ICED DRINKS`).
- Note exact PascalCase nutrition fields in `receipts`: `Calories`, `Fats`, `Carbs`, `Protein`.

---

### COMMENTS

---

## GET /api/comments/

**Purpose**  
View paginated comments for a specific recipe.

**Auth**  
Required (Bearer Token)

**Query Parameters**
- `receipt_id` (integer, required): Target recipe ID.
- `page` (integer, optional, default: 1): Page number.
- `per_page` (integer, optional, default: 20): Items per page.

**Success — 200**
```json
{
  "data": [
    {
      "id": 1,
      "text": "Looks delicious!",
      "timestamp": "2026-08-10T18:00:00Z",
      "user": {
        "user_id": 1,
        "name": "Ahmed"
      },
      "likes_count": 2,
      "is_liked": false
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

**Errors**
- 401 — Unauthenticated
- 404 — Recipe not found (`{"message": "Receipt not found."}`)

**Flutter Notes**
- Pass `receipt_id` as query parameter: `/api/comments/?receipt_id=1`.
- `is_liked` indicates whether the authenticated user liked the comment.

---

## POST /api/comment/

**Purpose**  
Add a comment to a recipe.

**Auth**  
Required (Bearer Token)

**Request**
```json
{
  "receipt_id": 1,
  "text": "Looks delicious!"
}
```

**Success — 201**
```json
{
  "message": "Comment added successfully",
  "comment": {
    "id": 1,
    "user_id": 1,
    "receipt_id": 1,
    "text": "Looks delicious!",
    "timestamp": "2026-08-10T18:00:00Z"
  }
}
```

**Errors**
- 401 — Unauthenticated
- 422 — Validation error (`text` is required, max 1000 characters; `receipt_id` must exist)

**Flutter Notes**
- Endpoint name is singular: `/api/comment/`.

---

## POST /api/like-comment/

**Purpose**  
Like a comment.

**Auth**  
Required (Bearer Token)

**Request**
```json
{
  "comment_id": 1
}
```

**Success — 201**
```json
{
  "message": "Comment liked successfully",
  "comment_id": 1,
  "is_liked": true,
  "likes_count": 3
}
```

**Errors**
- 401 — Unauthenticated
- 404 — Comment not found (`{"message": "Comment not found."}`)

**Flutter Notes**
- Idempotent: Liking an already-liked comment returns 201 with `is_liked: true` and the current like count.

---

## DELETE /api/like-comment/

**Purpose**  
Remove like from a comment.

**Auth**  
Required (Bearer Token)

**Request**
```json
{
  "comment_id": 1
}
```

**Success — 200**
```json
{
  "message": "Comment unliked successfully",
  "comment_id": 1,
  "is_liked": false,
  "likes_count": 2
}
```

**Errors**
- 401 — Unauthenticated
- 404 — Comment like not found (`{"message": "Comment like not found."}`)

**Flutter Notes**
- Send `comment_id` in request body.

---

### SUGGESTIONS

---

## GET /api/suggestions/

**Purpose**  
Retrieve paginated suggestions for a recipe, including each suggestion's ingredients and step-ordered instructions snapshot.

**Auth**  
Required (Bearer Token)

**Query Parameters**
- `receipt_id` (integer, required): Target recipe ID.
- `page` (integer, optional, default: 1): Page number.
- `per_page` (integer, optional, default: 20): Items per page.

**Success — 200**
```json
{
  "data": [
    {
      "id": 1,
      "receipt_id": 1,
      "text": "Add garlic",
      "isApproved": false,
      "timestamp": "2026-08-10T18:00:00Z",
      "user": {
        "user_id": 2,
        "name": "Sara"
      },
      "likes_count": 2,
      "is_liked": false,
      "ingredients": [
        {
          "id": 2,
          "name": "Garlic",
          "quantity": 2,
          "unit": "cloves",
          "isAssigned": false
        }
      ],
      "instructions": [
        {
          "id": 1,
          "step_number": 1,
          "instruction": "Boil pasta in salted water."
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

**Errors**
- 401 — Unauthenticated
- 404 — Recipe not found (`{"message": "Receipt not found."}`)

**Flutter Notes**
- Each suggestion embeds its own `ingredients[]` and `instructions[]` (ordered by `step_number`).

---

## POST /api/suggestion/

**Purpose**  
Create a new suggestion for a recipe. The backend automatically takes a snapshot of the recipe's current ingredients and instructions.

**Auth**  
Required (Bearer Token)

**Request**
```json
{
  "receipt_id": 1,
  "text": "Add garlic"
}
```

**Success — 201**
```json
{
  "message": "Suggestion created successfully",
  "suggestion": {
    "id": 1,
    "user_id": 2,
    "receipt_id": 1,
    "text": "Add garlic",
    "isApproved": false,
    "timestamp": "2026-08-10T18:00:00Z",
    "ingredients": [
      {
        "id": 2,
        "name": "Garlic",
        "quantity": 2,
        "unit": "cloves",
        "isAssigned": false
      }
    ],
    "instructions": [
      {
        "id": 1,
        "step_number": 1,
        "instruction": "Boil pasta in salted water."
      }
    ]
  }
}
```

**Errors**
- 401 — Unauthenticated
- 422 — Validation error (`text` is required, max 2000 characters; `receipt_id` must exist)

**Flutter Notes**
- **Do NOT send `ingredients` or `instructions`**. The backend automatically clones the recipe's current ingredients and instructions into the suggestion snapshot.

---

## PUT /api/suggestion/

**Purpose**  
Update a pending suggestion's text and its full snapshot of ingredients and instructions.

**Auth**  
Required (Bearer Token)

**Request**
```json
{
  "suggestion_id": 1,
  "text": "Updated suggestion text",
  "ingredients": [
    {
      "name": "Pasta",
      "quantity": 200,
      "unit": "g",
      "isAssigned": false
    },
    {
      "name": "Garlic",
      "quantity": 2,
      "unit": "cloves",
      "isAssigned": false
    }
  ],
  "instructions": [
    {
      "step_number": 1,
      "instruction": "Boil the pasta."
    },
    {
      "step_number": 2,
      "instruction": "Add garlic and sauté."
    }
  ]
}
```

**Success — 200**
```json
{
  "message": "Suggestion updated successfully",
  "suggestion": {
    "id": 1,
    "user_id": 2,
    "receipt_id": 1,
    "text": "Updated suggestion text",
    "isApproved": false,
    "timestamp": "2026-08-10T18:00:00Z",
    "ingredients": [
      {
        "id": 1,
        "name": "Pasta",
        "quantity": 200,
        "unit": "g",
        "isAssigned": false
      },
      {
        "id": 2,
        "name": "Garlic",
        "quantity": 2,
        "unit": "cloves",
        "isAssigned": false
      }
    ],
    "instructions": [
      {
        "id": 1,
        "step_number": 1,
        "instruction": "Boil the pasta."
      },
      {
        "id": 2,
        "step_number": 2,
        "instruction": "Add garlic and sauté."
      }
    ]
  }
}
```

**Errors**
- 401 — Unauthenticated
- 403 — Forbidden (`{"message": "You are not allowed to update this suggestion."}`) if authenticated user is not the author, or if suggestion is already approved
- 404 — Suggestion not found (`{"message": "Suggestion not found."}`)
- 422 — Validation error

**Flutter Notes**
- Only the suggestion author can update.
- Client sends the full updated snapshot.
- Updating a suggestion does NOT alter the live recipe.

---

## POST /api/like-suggestion/

**Purpose**  
Like a suggestion.

**Auth**  
Required (Bearer Token)

**Request**
```json
{
  "suggestion_id": 1
}
```

**Success — 201**
```json
{
  "message": "Suggestion liked successfully",
  "suggestion_id": 1,
  "is_liked": true,
  "likes_count": 3
}
```

**Errors**
- 401 — Unauthenticated
- 404 — Suggestion not found (`{"message": "Suggestion not found."}`)

**Flutter Notes**
- Idempotent: Liking an already-liked suggestion succeeds and returns 201 with `is_liked: true` and current count.

---

## DELETE /api/like-suggestion/

**Purpose**  
Remove like from a suggestion.

**Auth**  
Required (Bearer Token)

**Request**
```json
{
  "suggestion_id": 1
}
```

**Success — 200**
```json
{
  "message": "Suggestion unliked successfully",
  "suggestion_id": 1,
  "is_liked": false,
  "likes_count": 2
}
```

**Errors**
- 401 — Unauthenticated
- 404 — Suggestion like not found (`{"message": "Suggestion like not found."}`)

**Flutter Notes**
- Send `suggestion_id` in request body.

---

## PATCH /api/approve-suggestion/

**Purpose**  
Approve a suggestion. The suggestion's ingredients and instructions atomically replace the target recipe's current ingredients and instructions.

**Auth**  
Required (Bearer Token)

**Request**
```json
{
  "suggestion_id": 1
}
```

**Success — 200**
```json
{
  "message": "Suggestion approved successfully",
  "suggestion": {
    "id": 1,
    "receipt_id": 1,
    "isApproved": true
  }
}
```

**Errors**
- 401 — Unauthenticated
- 403 — Forbidden (`{"message": "You are not allowed to approve this suggestion."}`) if user is not the recipe owner
- 404 — Suggestion not found (`{"message": "Suggestion not found."}`)

**Flutter Notes**
- Only the target recipe owner can approve a suggestion.
- **Re-approval (DEC-022)**: Re-approving an already approved suggestion is idempotent and returns 200 OK (no 409 conflict).
- After approval succeeds, re-fetch the recipe details endpoint to load the updated recipe ingredients and instructions.

---

## 6. Suggestion Lifecycle Workflow

The Open Sauce suggestion system operates on an automated snapshot model:

```
+-----------------------------------------------------------------------------------+
| 1. CREATE SUGGESTION                                                              |
|    POST /api/suggestion/  ->  Body: { receipt_id, text }                          |
|    (Backend automatically copies current recipe ingredients & instructions)       |
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
| 2. VIEW SUGGESTIONS                                                               |
|    GET /api/suggestions/?receipt_id={id}                                          |
|    (Returns suggestion list; each suggestion contains its snapshot items)         |
+-----------------------------------------------------------------------------------+
                                         |
                     +-------------------+-------------------+
                     |                                       |
                     v                                       v
+------------------------------------------+ +--------------------------------------+
| 3. EDIT PENDING SUGGESTION               | | 4. APPROVE SUGGESTION                |
|    PUT /api/suggestion/                  | |    PATCH /api/approve-suggestion/    |
|    Body: { suggestion_id, text,          | |    Body: { suggestion_id }           |
|            ingredients[], instructions[]}| |    - Only recipe owner can approve   |
|    - Only suggestion author can edit     | |    - Atomically replaces recipe items|
|    - Does NOT modify original recipe     | |    - Idempotent re-approval (DEC-022)|
+------------------------------------------+ +--------------------------------------+
```

### Detailed Workflow Steps:

1. **A. Create Suggestion (`POST /api/suggestion/`)**
   - Flutter sends **only** `receipt_id` and `text`.
   - Client **must not** send `ingredients[]` or `instructions[]`.
   - The backend automatically copies the recipe's current ingredients and instructions into the suggestion snapshot inside a database transaction.
   - The response returns the created suggestion and its snapshot items.

2. **B. View Suggestions (`GET /api/suggestions/?receipt_id=1`)**
   - Retrieves all suggestions for a recipe.
   - Each suggestion includes `user`, `isApproved`, `likes_count`, `is_liked`, `ingredients[]`, and `instructions[]` (ordered by `step_number`).

3. **C. Edit Pending Suggestion (`PUT /api/suggestion/`)**
   - The suggestion author can update the suggestion text and edit the snapshot (`ingredients[]` and `instructions[]`).
   - Authorization: Only the author can update. Approved suggestions cannot be updated (`403 Forbidden`).
   - Editing a suggestion does **not** affect the live recipe.

4. **D. Like / Unlike Suggestion (`POST / DELETE /api/like-suggestion/`)**
   - Authenticated users can like or unlike suggestions.
   - Duplicate likes are idempotent and return 201 with the current count.

5. **E. Approve Suggestion (`PATCH /api/approve-suggestion/`)**
   - **Authorization**: Only the owner of the target recipe is authorized to approve. Non-owners receive `403 Forbidden`.
   - **Atomic Overwrite**: The suggestion's ingredients and instructions replace the recipe's ingredients and instructions in a single atomic database transaction.
   - **Preservation**: The suggestion record itself remains preserved in the suggestions list with `isApproved: true`.

6. **F. Re-Approval (DEC-022)**
   - Re-approving an already approved suggestion is **idempotent**.
   - The recipe owner can approve it again. The snapshot is reapplied atomically.
   - Returns `200 OK` (no 409 conflict error).

---

## 7. Common Errors

| Status Code | Description | Client Handling |
| :--- | :--- | :--- |
| **401 Unauthenticated** | Invalid, expired, or missing Bearer token | Direct user to login screen. |
| **403 Forbidden** | Authorization failure | Action not allowed (e.g. non-owner approving suggestion, non-author updating suggestion). Display error message. |
| **404 Not Found** | Resource does not exist | Target recipe, suggestion, comment, or like was not found. |
| **422 Validation Error** | Request validation failed | Inspect `errors` map to display field validation messages. |
| **429 Too Many Requests** | Rate limit exceeded | Throttled on `POST /api/register/` and `POST /api/login/` (6 req/min). Display retry cooldown. |

---

## 8. Flutter Integration Notes

1. **Token Persistence**: Store the token from Login/Register using `flutter_secure_storage`.
2. **Authorization Interceptor**: Attach `Authorization: Bearer <token>` and `Accept: application/json` to every protected Dio/HTTP call.
3. **No `user_id` in Create Payloads**: Do not send `user_id` when creating comments or suggestions; the server sets ownership automatically from the token.
4. **Preserve Model Casing**:
   - `isApproved`, `isAssigned` (camelCase)
   - `Calories`, `Fats`, `Carbs`, `Protein` (PascalCase)
   - `receipt_id`, `suggestion_id`, `comment_id`, `step_number` (snake_case)
5. **Handle Pagination**: Use `meta.last_page` and `meta.current_page` to drive infinite-scroll lists on `GET /api/comments/` and `GET /api/suggestions/`.
6. **Suggestion Rendering**: Display ingredients and instructions directly from each suggestion item's nested `ingredients` and `instructions` arrays.
7. **Post-Approval UI Update**: Do not mark the main recipe as updated locally until `PATCH /api/approve-suggestion/` returns 200. After success, re-fetch the recipe details to display the newly approved recipe content.
