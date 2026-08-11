# Open Sauce API Contract

This document defines the REST API contract for the Open Sauce Laravel backend. The current version is fully aligned with the Postman collection at `/postman/Open Sauce Documentation - Complete API Contract v4.postman_collection.json`.

## Base URL

All endpoint paths are prefixed with the base URL variable:

`{{baseUrl}}`

In the local Postman environment configuration, `baseUrl` is set to the local development server address (e.g., `http://localhost:8000`), but this can be replaced dynamically depending on the deployment environment (e.g., staging or production).

Do not hard-code server addresses.

## Endpoint Checklist (Developer 1 Scope)

Below is the verified checklist of all endpoints assigned for Authentication, Profile, Comments, and Suggestions:

| HTTP Method | Endpoint URL | Authentication | Purpose | Request Parameters / Fields |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/api/register/` | Public | Register a new user | `name`, `email`, `password` |
| `POST` | `/api/login/` | Public | Login authentication | `email`, `password` |
| `DELETE` | `/api/logout/` | Required | Log out a user | — |
| `GET` | `/api/profile/` | Required | Get user profile details | — |
| `GET` | `/api/comments/` | Required | View comments on a receipt | Query: `receipt_id`, `page`, `per_page` |
| `POST` | `/api/comment/` | Required | Add a comment to a receipt | Body: `receipt_id`, `text` |
| `POST` | `/api/like-comment/` | Required | Like a comment | Body: `comment_id` |
| `DELETE` | `/api/like-comment/` | Required | Remove like from a comment | Body: `comment_id` |
| `GET` | `/api/suggestions/` | Required | View suggestions for a receipt | Query: `receipt_id`, `page`, `per_page` |
| `POST` | `/api/suggestion/` | Required | Add suggestion with ingredients | Body: `receipt_id`, `text`, `ingredients[]` |
| `POST` | `/api/like-suggestion/` | Required | Like a suggestion | Body: `suggestion_id` |
| `DELETE` | `/api/like-suggestion/` | Required | Remove like from a suggestion | Body: `suggestion_id` |
| `PATCH` | `/api/approve-suggestion/` | Required | Approve a suggestion | Body: `suggestion_id` |

## Authentication

Authentication is handled via **Laravel Sanctum** bearer-token authentication. 

Protected endpoints require the following header:

```http
Authorization: Bearer {{authToken}}
```

The user is authenticated on the backend using this bearer token. For operations involving resource creation or ownership checks, **do not** send `user_id` in the client request body or query parameters; the backend automatically derives the authenticated user ID from the token (e.g., `auth()->id()`).

---

# AUTH

## Register a new user

### Method
`POST`

### URL
`{{baseUrl}}/api/register/`

### Authentication
Public (Not Required)

### Headers
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "name": "Ahmed",
  "email": "ahmed@example.com",
  "password": "Password123!"
}
```

### Responses
#### `201 Created`
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

#### `422 Validation`
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

## Login authentication

### Method
`POST`

### URL
`{{baseUrl}}/api/login/`

### Authentication
Public (Not Required)

### Headers
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "email": "ahmed@example.com",
  "password": "Password123!"
}
```

### Responses
#### `200 OK`
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

#### `401 Invalid credentials`
```json
{
    "message": "Invalid credentials."
}
```

#### `422 Validation`
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

## Log out a user

### Method
`DELETE`

### URL
`{{baseUrl}}/api/logout/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Responses
#### `204 No Content`
*(No response body)*

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

---

# FOR YOU

## Get For you page content

### Method
`GET`

### URL
`{{baseUrl}}/api/fyp/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Query Parameters
* `page` = `1` (The page number for pagination)
* `per_page` = `20` (The number of items per page)

### Responses
#### `200 OK`
```json
{
    "data": [
        {
            "receipt_id": 1,
            "name": "Pasta",
            "caption": "Quick pasta",
            "category": "Dinner",
            "estimated_time": "20 min",
            "Calories": 500,
            "Fats": 15,
            "Carbs": 70,
            "Protein": 20,
            "timestamp": "2026-08-10T18:00:00Z",
            "user": {
                "user_id": 1,
                "name": "Ahmed"
            },
            "likes_count": 3,
            "comments_count": 2,
            "favorites_count": 1,
            "is_liked": false,
            "is_favorited": true
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

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

---

## Add like on post

### Method
`POST`

### URL
`{{baseUrl}}/api/like/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "receipt_id": 1
}
```

### Responses
#### `201 Created`
```json
{
    "message": "Receipt liked successfully",
    "receipt_id": 1,
    "is_liked": true,
    "likes_count": 4
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `404 Not Found`
```json
{
    "message": "Receipt not found."
}
```

---

## Delete like on post

### Method
`DELETE`

### URL
`{{baseUrl}}/api/like/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "receipt_id": 1
}
```

### Responses
#### `200 OK`
```json
{
    "message": "Receipt unliked successfully",
    "receipt_id": 1,
    "is_liked": false,
    "likes_count": 3
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `404 Not Found`
```json
{
    "message": "Like not found."
}
```

---

## View all comments

This endpoint is identical to the canonical comment viewing endpoint. Please refer to [View comments](#view-comments) under the **COMMENTS** section for full details.

---

## Create new post

### Method
`POST`

### URL
`{{baseUrl}}/api/new-post/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "name": "Pasta",
  "caption": "Quick pasta",
  "category": "Dinner",
  "estimated_time": "20 min",
  "Calories": 500,
  "Fats": 15,
  "Carbs": 70,
  "Protein": 20,
  "ingredients": [
    {
      "name": "Pasta",
      "quantity": 200,
      "unit": "g",
      "isAssigned": false
    }
  ]
}
```

### Responses
#### `201 Created`
```json
{
    "message": "Post created successfully",
    "receipt": {
        "receipt_id": 1,
        "name": "Pasta",
        "caption": "Quick pasta",
        "category": "Dinner",
        "estimated_time": "20 min",
        "Calories": 500,
        "Fats": 15,
        "Carbs": 70,
        "Protein": 20,
        "timestamp": "2026-08-10T18:00:00Z",
        "user": {
            "user_id": 1,
            "name": "Ahmed"
        },
        "ingredients": [
            {
                "id": 1,
                "name": "Pasta",
                "quantity": 200,
                "unit": "g",
                "isAssigned": false
            }
        ]
    }
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `422 Validation`
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field": [
            "The field is invalid."
        ]
    }
}
```

---

# CHATBOT

## Get chatbot history

### Method
`GET`

### URL
`{{baseUrl}}/api/chat-history/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Query Parameters
* `page` = `1`
* `per_page` = `20`

### Responses
#### `200 OK`
```json
{
    "data": [
        {
            "id": 1,
            "user_prompt": "How do I make pasta?",
            "response": "Try a simple tomato pasta.",
            "timestamp": "2026-08-10T18:00:00Z"
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

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

---

## Send user prompt

### Method
`POST`

### URL
`{{baseUrl}}/api/user-prompt/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "prompt": "How do I make pasta?"
}
```

### Responses
#### `201 Created`
```json
{
    "message": "Prompt processed successfully",
    "chat": {
        "id": 1,
        "user_prompt": "How do I make pasta?",
        "response": "Try a simple tomato pasta.",
        "timestamp": "2026-08-10T18:00:00Z"
    }
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `422 Validation`
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "prompt": [
            "The prompt field is required."
        ]
    }
}
```

#### `502 AI unavailable`
```json
{
    "message": "AI service is temporarily unavailable."
}
```

---

## Get chatbot response

### Method
`GET`

### URL
`{{baseUrl}}/api/chat-response/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Query Parameters
* `chat_history_id` = `1`

### Responses
#### `200 OK`
```json
{
    "id": 1,
    "response": "Try a simple tomato pasta.",
    "timestamp": "2026-08-10T18:00:00Z"
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `404 Not Found`
```json
{
    "message": "Chat history not found."
}
```

---

# FAVORITES

## Get all user favorites

### Method
`GET`

### URL
`{{baseUrl}}/api/favorites/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Query Parameters
* `page` = `1`
* `per_page` = `20`

### Responses
#### `200 OK`
```json
{
    "data": [
        {
            "receipt_id": 1,
            "name": "Pasta",
            "caption": "Quick pasta",
            "category": "Dinner",
            "estimated_time": "20 min",
            "Calories": 500,
            "Fats": 15,
            "Carbs": 70,
            "Protein": 20,
            "timestamp": "2026-08-10T18:00:00Z"
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

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

---

## Add post to favorites

### Method
`POST`

### URL
`{{baseUrl}}/api/favorites/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "receipt_id": 1
}
```

### Responses
#### `201 Created`
```json
{
    "message": "Receipt added to favorites successfully",
    "receipt_id": 1,
    "is_favorited": true
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `404 Not Found`
```json
{
    "message": "Receipt not found."
}
```

#### `409 Already favorited`
```json
{
    "message": "Receipt already in favorites."
}
```

---

## Remove post from favorites

### Method
`DELETE`

### URL
`{{baseUrl}}/api/remove-favorites/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "receipt_id": 1
}
```

### Responses
#### `200 OK`
```json
{
    "message": "Receipt removed from favorites successfully",
    "receipt_id": 1,
    "is_favorited": false
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `404 Not Found`
```json
{
    "message": "Favorite not found."
}
```

---

# SUGGESTIONS

## View all suggestions

### Method
`GET`

### URL
`{{baseUrl}}/api/suggestions/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Query Parameters
* `receipt_id` = `1`
* `page` = `1`
* `per_page` = `20`

### Responses
#### `200 OK`
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
            "ingredients": []
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

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

---

## Add suggestion

### Method
`POST`

### URL
`{{baseUrl}}/api/suggestion/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "receipt_id": 1,
  "text": "Add garlic",
  "ingredients": [
    {
      "name": "Garlic",
      "quantity": 2,
      "unit": "cloves",
      "isAssigned": false
    }
  ]
}
```

### Responses
#### `201 Created`
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
        ]
    }
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `422 Validation`
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field": [
            "The field is invalid."
        ]
    }
}
```

---

## Add like on suggestion

### Method
`POST`

### URL
`{{baseUrl}}/api/like-suggestion/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "suggestion_id": 1
}
```

### Responses
#### `201 Created`
```json
{
    "message": "Suggestion liked successfully",
    "suggestion_id": 1,
    "is_liked": true,
    "likes_count": 3
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `404 Not Found`
```json
{
    "message": "Suggestion not found."
}
```

---

## Delete like on suggestion

### Method
`DELETE`

### URL
`{{baseUrl}}/api/like-suggestion/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "suggestion_id": 1
}
```

### Responses
#### `200 OK`
```json
{
    "message": "Suggestion unliked successfully",
    "suggestion_id": 1,
    "is_liked": false,
    "likes_count": 2
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `404 Not Found`
```json
{
    "message": "Suggestion like not found."
}
```

---

## Approve suggestion

### Method
`PATCH`

### URL
`{{baseUrl}}/api/approve-suggestion/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "suggestion_id": 1
}
```

### Responses
#### `200 OK`
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

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `403 Forbidden`
```json
{
    "message": "You are not allowed to approve this suggestion."
}
```

#### `404 Not Found`
```json
{
    "message": "Suggestion not found."
}
```

---

# COMMENTS

## View comments

### Method
`GET`

### URL
`{{baseUrl}}/api/comments/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Query Parameters
* `receipt_id` = `1`
* `page` = `1`
* `per_page` = `20`

### Responses
#### `200 OK`
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

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `404 Not Found`
```json
{
    "message": "Receipt not found."
}
```

---

## Add comment

### Method
`POST`

### URL
`{{baseUrl}}/api/comment/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "receipt_id": 1,
  "text": "Looks delicious!"
}
```

### Responses
#### `201 Created`
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

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `422 Validation`
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field": [
            "The field is invalid."
        ]
    }
}
```

---

## Add like on comment

### Method
`POST`

### URL
`{{baseUrl}}/api/like-comment/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "comment_id": 1
}
```

### Responses
#### `201 Created`
```json
{
    "message": "Comment liked successfully",
    "comment_id": 1,
    "is_liked": true,
    "likes_count": 3
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `404 Not Found`
```json
{
    "message": "Comment not found."
}
```

---

## Remove like from comment

### Method
`DELETE`

### URL
`{{baseUrl}}/api/like-comment/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Request Body (raw JSON)
```json
{
  "comment_id": 1
}
```

### Responses
#### `200 OK`
```json
{
    "message": "Comment unliked successfully",
    "comment_id": 1,
    "is_liked": false,
    "likes_count": 2
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `404 Not Found`
```json
{
    "message": "Comment like not found."
}
```

---

# DETAILS

## Get user profile details

### Method
`GET`

### URL
`{{baseUrl}}/api/profile/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Responses
#### `200 OK`
```json
{
    "user_id": 1,
    "name": "Ahmed",
    "email": "ahmed@example.com"
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

---

## Get receipt details

### Method
`GET`

### URL
`{{baseUrl}}/api/receipt-details/`

### Authentication
Required

### Headers
* `Authorization: Bearer {{authToken}}`
* `Accept: application/json`
* `Content-Type: application/json`

### Query Parameters
* `receipt_id` = `1`

### Responses
#### `200 OK`
```json
{
    "receipt_id": 1,
    "name": "Pasta",
    "caption": "Quick pasta",
    "category": "Dinner",
    "estimated_time": "20 min",
    "Calories": 500,
    "Fats": 15,
    "Carbs": 70,
    "Protein": 20,
    "timestamp": "2026-08-10T18:00:00Z",
    "user": {
        "user_id": 1,
        "name": "Ahmed"
    },
    "likes_count": 3,
    "comments_count": 2,
    "favorites_count": 1,
    "is_liked": false,
    "is_favorited": true,
    "ingredients": [
        {
            "id": 1,
            "name": "Pasta",
            "quantity": 200,
            "unit": "g",
            "isAssigned": false
        }
    ],
    "comments": [
        {
            "id": 1,
            "text": "Looks great!",
            "timestamp": "2026-08-10T18:00:00Z",
            "user": {
                "user_id": 1,
                "name": "Ahmed"
            },
            "likes_count": 2,
            "is_liked": false
        }
    ],
    "suggestions": []
}
```

#### `401 Unauthenticated`
```json
{
    "message": "Unauthenticated."
}
```

#### `404 Not Found`
```json
{
    "message": "Receipt not found."
}
```