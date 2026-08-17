import json
import os

collection = {
    "info": {
        "_postman_id": "open-sauce-developer-1-v1",
        "name": "Open Sauce - Developer 1 Endpoints (Auth, Profile, Comments, Suggestions)",
        "description": "### Open Sauce REST API - Developer 1 Endpoints Collection\n\nThis collection contains all API endpoints assigned to **Developer 1**, covering:\n- **Authentication** (Register, Login, Logout)\n- **User Profile** (View authenticated profile & receipts)\n- **Comments** (List comments, Add comment, Like/Unlike comments)\n- **Suggestions** (List suggestions, Add suggestion snapshot, Update suggestion snapshot, Like/Unlike suggestions, Approve suggestion)\n\n---\n\n### 🚀 Quick Start Guide\n1. Make sure your local Laravel backend is running (`php artisan serve` on port `8000`).\n2. Set the `baseUrl` collection variable (default is `http://localhost:8000`).\n3. Execute **Auth -> Register a new user** or **Auth -> Login**.\n   - *Note:* The test scripts in Register and Login will **automatically** extract and save the Sanctum Bearer token into the `{{authToken}}` collection variable, so you don't need to copy-paste tokens manually!\n4. Use all protected endpoints freely; they automatically inherit `Authorization: Bearer {{authToken}}`.",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "variable": [
        {
            "key": "baseUrl",
            "value": "http://localhost:8000",
            "type": "string"
        },
        {
            "key": "authToken",
            "value": "",
            "type": "string"
        },
        {
            "key": "receiptId",
            "value": "1",
            "type": "string"
        },
        {
            "key": "commentId",
            "value": "1",
            "type": "string"
        },
        {
            "key": "suggestionId",
            "value": "1",
            "type": "string"
        }
    ],
    "auth": {
        "type": "bearer",
        "bearer": [
            {
                "key": "token",
                "value": "{{authToken}}",
                "type": "string"
            }
        ]
    },
    "item": [
        {
            "name": "1. Authentication",
            "description": "Authentication endpoints for user registration, login, and token revocation.",
            "item": [
                {
                    "name": "Register a new user",
                    "event": [
                        {
                            "listen": "test",
                            "script": {
                                "exec": [
                                    "if (pm.response.code === 201) {",
                                    "    var jsonData = pm.response.json();",
                                    "    if (jsonData.token) {",
                                    "        pm.collectionVariables.set('authToken', jsonData.token);",
                                    "        console.log('Sanctum token automatically stored in {{authToken}}');",
                                    "    }",
                                    "}"
                                ],
                                "type": "text/javascript"
                            }
                        }
                    ],
                    "request": {
                        "auth": {
                            "type": "noauth"
                        },
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            },
                            {
                                "key": "Content-Type",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": json.dumps({
                                "name": "Sara Ahmed",
                                "email": "sara.ahmed@example.com",
                                "password": "Password123"
                            }, indent=2),
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "{{baseUrl}}/api/register/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "register", ""]
                        },
                        "description": "**Public Endpoint**\nCreates a new user account.\n\n**Validation Rules:**\n- `name`: string, required, max 255\n- `email`: string, required, valid email, max 255, unique in `users` table\n- `password`: string, required, min 8 chars, must contain letters and numbers\n\n**Automation:**\nUpon a successful `201 Created` response, the bearer token is saved to the `{{authToken}}` collection variable automatically."
                    },
                    "response": [
                        {
                            "name": "201 Created",
                            "originalRequest": {
                                "method": "POST",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"name\": \"Sara Ahmed\",\n  \"email\": \"sara.ahmed@example.com\",\n  \"password\": \"Password123\"\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/register/", "host": ["{{baseUrl}}"], "path": ["api", "register", ""]}
                            },
                            "status": "Created",
                            "code": 201,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "User registered successfully",
                                "user": {
                                    "user_id": 1,
                                    "name": "Sara Ahmed",
                                    "email": "sara.ahmed@example.com"
                                },
                                "token": "1|abcdef1234567890sanctumtokenhere"
                            }, indent=4)
                        },
                        {
                            "name": "422 Validation Error",
                            "originalRequest": {
                                "method": "POST",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"name\": \"\",\n  \"email\": \"invalid-email\",\n  \"password\": \"123\"\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/register/", "host": ["{{baseUrl}}"], "path": ["api", "register", ""]}
                            },
                            "status": "Unprocessable Content",
                            "code": 422,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "The given data was invalid.",
                                "errors": {
                                    "name": ["The name field is required."],
                                    "email": ["The email field must be a valid email address."],
                                    "password": ["The password field must be at least 8 characters."]
                                }
                            }, indent=4)
                        }
                    ]
                },
                {
                    "name": "Login user",
                    "event": [
                        {
                            "listen": "test",
                            "script": {
                                "exec": [
                                    "if (pm.response.code === 200) {",
                                    "    var jsonData = pm.response.json();",
                                    "    if (jsonData.token) {",
                                    "        pm.collectionVariables.set('authToken', jsonData.token);",
                                    "        console.log('Sanctum token automatically stored in {{authToken}}');",
                                    "    }",
                                    "}"
                                ],
                                "type": "text/javascript"
                            }
                        }
                    ],
                    "request": {
                        "auth": {
                            "type": "noauth"
                        },
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            },
                            {
                                "key": "Content-Type",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": json.dumps({
                                "email": "sara.ahmed@example.com",
                                "password": "Password123"
                            }, indent=2),
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "{{baseUrl}}/api/login/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "login", ""]
                        },
                        "description": "**Public Endpoint**\nAuthenticates an existing user and returns a Laravel Sanctum bearer token.\n\n**Rate Limiting:** `6 requests / minute`.\n\n**Automation:**\nUpon a successful `200 OK` response, the bearer token is saved to `{{authToken}}` automatically."
                    },
                    "response": [
                        {
                            "name": "200 OK",
                            "originalRequest": {
                                "method": "POST",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"email\": \"sara.ahmed@example.com\",\n  \"password\": \"Password123\"\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/login/", "host": ["{{baseUrl}}"], "path": ["api", "login", ""]}
                            },
                            "status": "OK",
                            "code": 200,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Login successful",
                                "user": {
                                    "user_id": 1,
                                    "name": "Sara Ahmed",
                                    "email": "sara.ahmed@example.com"
                                },
                                "token": "2|abcdef1234567890sanctumtokenhere"
                            }, indent=4)
                        },
                        {
                            "name": "401 Invalid Credentials",
                            "originalRequest": {
                                "method": "POST",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"email\": \"sara.ahmed@example.com\",\n  \"password\": \"WrongPassword\"\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/login/", "host": ["{{baseUrl}}"], "path": ["api", "login", ""]}
                            },
                            "status": "Unauthorized",
                            "code": 401,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Invalid credentials."
                            }, indent=4)
                        }
                    ]
                },
                {
                    "name": "Logout user",
                    "event": [
                        {
                            "listen": "test",
                            "script": {
                                "exec": [
                                    "if (pm.response.code === 204) {",
                                    "    pm.collectionVariables.set('authToken', '');",
                                    "    console.log('authToken cleared successfully.');",
                                    "}"
                                ],
                                "type": "text/javascript"
                            }
                        }
                    ],
                    "request": {
                        "method": "DELETE",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "{{baseUrl}}/api/logout/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "logout", ""]
                        },
                        "description": "**Protected Endpoint**\nRevokes the current Sanctum token used for this session (DEC-017).\n\nReturns `204 No Content` on success (DEC-018)."
                    },
                    "response": [
                        {
                            "name": "204 No Content",
                            "originalRequest": {
                                "method": "DELETE",
                                "header": [{"key": "Accept", "value": "application/json"}],
                                "url": {"raw": "{{baseUrl}}/api/logout/", "host": ["{{baseUrl}}"], "path": ["api", "logout", ""]}
                            },
                            "status": "No Content",
                            "code": 204,
                            "_postman_previewlanguage": "plain",
                            "header": [],
                            "body": ""
                        },
                        {
                            "name": "401 Unauthenticated",
                            "originalRequest": {
                                "method": "DELETE",
                                "header": [{"key": "Accept", "value": "application/json"}],
                                "url": {"raw": "{{baseUrl}}/api/logout/", "host": ["{{baseUrl}}"], "path": ["api", "logout", ""]}
                            },
                            "status": "Unauthorized",
                            "code": 401,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Unauthenticated."
                            }, indent=4)
                        }
                    ]
                }
            ]
        },
        {
            "name": "2. Profile",
            "description": "User profile endpoints.",
            "item": [
                {
                    "name": "Get Authenticated User Profile",
                    "request": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "{{baseUrl}}/api/profile/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "profile", ""]
                        },
                        "description": "**Protected Endpoint**\nReturns the authenticated user's profile information along with all recipes (`receipts`) created by them."
                    },
                    "response": [
                        {
                            "name": "200 OK (With Recipes)",
                            "originalRequest": {
                                "method": "GET",
                                "header": [{"key": "Accept", "value": "application/json"}],
                                "url": {"raw": "{{baseUrl}}/api/profile/", "host": ["{{baseUrl}}"], "path": ["api", "profile", ""]}
                            },
                            "status": "OK",
                            "code": 200,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "user_id": 1,
                                "name": "Sara Ahmed",
                                "email": "sara.ahmed@example.com",
                                "receipts": [
                                    {
                                        "receipt_id": 1,
                                        "name": "Creamy Garlic Pasta",
                                        "caption": "A quick and delicious homemade Italian pasta.",
                                        "category": "DINNER",
                                        "estimated_time": 25,
                                        "Calories": 450,
                                        "Fats": 15,
                                        "Carbs": 60,
                                        "Protein": 12,
                                        "timestamp": "2026-08-16T12:00:00Z"
                                    }
                                ]
                            }, indent=4)
                        },
                        {
                            "name": "401 Unauthenticated",
                            "originalRequest": {
                                "method": "GET",
                                "header": [{"key": "Accept", "value": "application/json"}],
                                "url": {"raw": "{{baseUrl}}/api/profile/", "host": ["{{baseUrl}}"], "path": ["api", "profile", ""]}
                            },
                            "status": "Unauthorized",
                            "code": 401,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Unauthenticated."
                            }, indent=4)
                        }
                    ]
                }
            ]
        },
        {
            "name": "3. Comments",
            "description": "Endpoints for viewing, creating, and liking/unliking comments on recipes.",
            "item": [
                {
                    "name": "View all comments for a recipe",
                    "request": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "{{baseUrl}}/api/comments/?receipt_id={{receiptId}}&page=1&per_page=20",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "comments", ""],
                            "query": [
                                {
                                    "key": "receipt_id",
                                    "value": "{{receiptId}}",
                                    "description": "Target recipe ID (Required)"
                                },
                                {
                                    "key": "page",
                                    "value": "1",
                                    "description": "Page number (Optional, default 1)"
                                },
                                {
                                    "key": "per_page",
                                    "value": "20",
                                    "description": "Items per page (Optional, default 20)"
                                }
                            ]
                        },
                        "description": "**Protected Endpoint**\nReturns paginated comments for the specified recipe (`receipt_id`).\n\nIncludes comment author details, likes count, and whether the authenticated user liked the comment (`is_liked`)."
                    },
                    "response": [
                        {
                            "name": "200 OK",
                            "originalRequest": {
                                "method": "GET",
                                "header": [{"key": "Accept", "value": "application/json"}],
                                "url": {"raw": "{{baseUrl}}/api/comments/?receipt_id=1&page=1&per_page=20", "host": ["{{baseUrl}}"], "path": ["api", "comments", ""], "query": [{"key": "receipt_id", "value": "1"}, {"key": "page", "value": "1"}, {"key": "per_page", "value": "20"}]}
                            },
                            "status": "OK",
                            "code": 200,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "data": [
                                    {
                                        "id": 1,
                                        "receipt_id": 1,
                                        "text": "This recipe looks amazing! Can I replace heavy cream with milk?",
                                        "timestamp": "2026-08-16T14:30:00Z",
                                        "user": {
                                            "user_id": 2,
                                            "name": "Omar Khaled"
                                        },
                                        "likes_count": 4,
                                        "is_liked": True
                                    }
                                ],
                                "meta": {
                                    "current_page": 1,
                                    "per_page": 20,
                                    "total": 1,
                                    "last_page": 1
                                }
                            }, indent=4)
                        },
                        {
                            "name": "404 Receipt Not Found",
                            "originalRequest": {
                                "method": "GET",
                                "header": [{"key": "Accept", "value": "application/json"}],
                                "url": {"raw": "{{baseUrl}}/api/comments/?receipt_id=9999", "host": ["{{baseUrl}}"], "path": ["api", "comments", ""], "query": [{"key": "receipt_id", "value": "9999"}]}
                            },
                            "status": "Not Found",
                            "code": 404,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Receipt not found."
                            }, indent=4)
                        }
                    ]
                },
                {
                    "name": "Add comment to recipe",
                    "event": [
                        {
                            "listen": "test",
                            "script": {
                                "exec": [
                                    "if (pm.response.code === 201) {",
                                    "    var jsonData = pm.response.json();",
                                    "    if (jsonData.comment && jsonData.comment.id) {",
                                    "        pm.collectionVariables.set('commentId', jsonData.comment.id);",
                                    "        console.log('Saved created commentId:', jsonData.comment.id);",
                                    "    }",
                                    "}"
                                ],
                                "type": "text/javascript"
                            }
                        }
                    ],
                    "request": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            },
                            {
                                "key": "Content-Type",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": json.dumps({
                                "receipt_id": 1,
                                "text": "This recipe looks amazing! Can I replace heavy cream with milk?"
                            }, indent=2),
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "{{baseUrl}}/api/comment/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "comment", ""]
                        },
                        "description": "**Protected Endpoint**\nAdds a new comment to a recipe.\n\n**Validation:**\n- `receipt_id`: integer, required\n- `text`: string, required, max 1000 characters (DEC-010)"
                    },
                    "response": [
                        {
                            "name": "201 Created",
                            "originalRequest": {
                                "method": "POST",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"receipt_id\": 1,\n  \"text\": \"This recipe looks amazing! Can I replace heavy cream with milk?\"\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/comment/", "host": ["{{baseUrl}}"], "path": ["api", "comment", ""]}
                            },
                            "status": "Created",
                            "code": 201,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Comment created successfully",
                                "comment": {
                                    "id": 1,
                                    "user_id": 1,
                                    "receipt_id": 1,
                                    "text": "This recipe looks amazing! Can I replace heavy cream with milk?",
                                    "timestamp": "2026-08-16T14:30:00Z"
                                }
                            }, indent=4)
                        }
                    ]
                },
                {
                    "name": "Like a comment",
                    "request": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            },
                            {
                                "key": "Content-Type",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": json.dumps({
                                "comment_id": 1
                            }, indent=2),
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "{{baseUrl}}/api/like-comment/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "like-comment", ""]
                        },
                        "description": "**Protected Endpoint**\nLikes a comment. Idempotent: repeated likes return `201 Created` with `is_liked: true` without duplicating records (DEC-011)."
                    },
                    "response": [
                        {
                            "name": "201 Created",
                            "originalRequest": {
                                "method": "POST",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"comment_id\": 1\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/like-comment/", "host": ["{{baseUrl}}"], "path": ["api", "like-comment", ""]}
                            },
                            "status": "Created",
                            "code": 201,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Comment liked successfully",
                                "comment_id": 1,
                                "is_liked": True,
                                "likes_count": 5
                            }, indent=4)
                        }
                    ]
                },
                {
                    "name": "Unlike a comment",
                    "request": {
                        "method": "DELETE",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            },
                            {
                                "key": "Content-Type",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": json.dumps({
                                "comment_id": 1
                            }, indent=2),
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "{{baseUrl}}/api/like-comment/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "like-comment", ""]
                        },
                        "description": "**Protected Endpoint**\nRemoves the authenticated user's like from a comment."
                    },
                    "response": [
                        {
                            "name": "200 OK",
                            "originalRequest": {
                                "method": "DELETE",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"comment_id\": 1\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/like-comment/", "host": ["{{baseUrl}}"], "path": ["api", "like-comment", ""]}
                            },
                            "status": "OK",
                            "code": 200,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Comment unliked successfully",
                                "comment_id": 1,
                                "is_liked": False,
                                "likes_count": 4
                            }, indent=4)
                        },
                        {
                            "name": "404 Like Not Found",
                            "originalRequest": {
                                "method": "DELETE",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"comment_id\": 1\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/like-comment/", "host": ["{{baseUrl}}"], "path": ["api", "like-comment", ""]}
                            },
                            "status": "Not Found",
                            "code": 404,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Comment like not found."
                            }, indent=4)
                        }
                    ]
                }
            ]
        },
        {
            "name": "4. Suggestions",
            "description": "Endpoints for recipe modifications and fork snapshots (Suggestions).",
            "item": [
                {
                    "name": "View all suggestions for a recipe",
                    "request": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "{{baseUrl}}/api/suggestions/?receipt_id={{receiptId}}&page=1&per_page=20",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "suggestions", ""],
                            "query": [
                                {
                                    "key": "receipt_id",
                                    "value": "{{receiptId}}",
                                    "description": "Target recipe ID (Required)"
                                },
                                {
                                    "key": "page",
                                    "value": "1",
                                    "description": "Page number (Optional, default 1)"
                                },
                                {
                                    "key": "per_page",
                                    "value": "20",
                                    "description": "Items per page (Optional, default 20)"
                                }
                            ]
                        },
                        "description": "**Protected Endpoint**\nReturns paginated suggestions for a recipe along with their cloned ingredients and instructions snapshots, user info, likes count, and approval status."
                    },
                    "response": [
                        {
                            "name": "200 OK",
                            "originalRequest": {
                                "method": "GET",
                                "header": [{"key": "Accept", "value": "application/json"}],
                                "url": {"raw": "{{baseUrl}}/api/suggestions/?receipt_id=1&page=1&per_page=20", "host": ["{{baseUrl}}"], "path": ["api", "suggestions", ""], "query": [{"key": "receipt_id", "value": "1"}, {"key": "page", "value": "1"}, {"key": "per_page", "value": "20"}]}
                            },
                            "status": "OK",
                            "code": 200,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "data": [
                                    {
                                        "id": 1,
                                        "receipt_id": 1,
                                        "text": "Add minced garlic and a pinch of oregano for richer aroma.",
                                        "isApproved": False,
                                        "timestamp": "2026-08-16T15:00:00Z",
                                        "user": {
                                            "user_id": 2,
                                            "name": "Omar Khaled"
                                        },
                                        "likes_count": 3,
                                        "is_liked": False,
                                        "ingredients": [
                                            {
                                                "id": 10,
                                                "name": "Pasta",
                                                "quantity": 250,
                                                "unit": "g",
                                                "isAssigned": False
                                            },
                                            {
                                                "id": 11,
                                                "name": "Garlic",
                                                "quantity": 2,
                                                "unit": "piece",
                                                "isAssigned": False
                                            }
                                        ],
                                        "instructions": [
                                            {
                                                "id": 5,
                                                "step_number": 1,
                                                "instruction": "Boil pasta in salted water."
                                            },
                                            {
                                                "id": 6,
                                                "step_number": 2,
                                                "instruction": "Sauté minced garlic in oil."
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
                            }, indent=4)
                        }
                    ]
                },
                {
                    "name": "Add suggestion (Auto snapshot)",
                    "event": [
                        {
                            "listen": "test",
                            "script": {
                                "exec": [
                                    "if (pm.response.code === 201) {",
                                    "    var jsonData = pm.response.json();",
                                    "    if (jsonData.suggestion && jsonData.suggestion.id) {",
                                    "        pm.collectionVariables.set('suggestionId', jsonData.suggestion.id);",
                                    "        console.log('Saved created suggestionId:', jsonData.suggestion.id);",
                                    "    }",
                                    "}"
                                ],
                                "type": "text/javascript"
                            }
                        }
                    ],
                    "request": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            },
                            {
                                "key": "Content-Type",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": json.dumps({
                                "receipt_id": 1,
                                "text": "Add minced garlic and a pinch of oregano for richer aroma."
                            }, indent=2),
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "{{baseUrl}}/api/suggestion/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "suggestion", ""]
                        },
                        "description": "**Protected Endpoint**\nCreates a suggestion on a recipe.\n\n**How it works:**\n1. Client provides `receipt_id` and summary `text`.\n2. Backend automatically **clones** the target recipe's current ingredients and instructions into this suggestion snapshot inside a DB transaction.\n3. The original recipe remains completely unmodified."
                    },
                    "response": [
                        {
                            "name": "201 Created",
                            "originalRequest": {
                                "method": "POST",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"receipt_id\": 1,\n  \"text\": \"Add minced garlic and a pinch of oregano for richer aroma.\"\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/suggestion/", "host": ["{{baseUrl}}"], "path": ["api", "suggestion", ""]}
                            },
                            "status": "Created",
                            "code": 201,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Suggestion created successfully",
                                "suggestion": {
                                    "id": 1,
                                    "user_id": 1,
                                    "receipt_id": 1,
                                    "text": "Add minced garlic and a pinch of oregano for richer aroma.",
                                    "isApproved": False,
                                    "timestamp": "2026-08-16T15:00:00Z",
                                    "ingredients": [
                                        {
                                            "id": 10,
                                            "name": "Pasta",
                                            "quantity": 250,
                                            "unit": "g",
                                            "isAssigned": False
                                        }
                                    ],
                                    "instructions": [
                                        {
                                            "id": 5,
                                            "step_number": 1,
                                            "instruction": "Boil pasta in salted water."
                                        }
                                    ]
                                }
                            }, indent=4)
                        }
                    ]
                },
                {
                    "name": "Update pending suggestion snapshot",
                    "request": {
                        "method": "PUT",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            },
                            {
                                "key": "Content-Type",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": json.dumps({
                                "suggestion_id": 1,
                                "text": "Updated suggestion with adjusted garlic quantity",
                                "ingredients": [
                                    {
                                        "name": "Pasta",
                                        "quantity": 250,
                                        "unit": "g",
                                        "isAssigned": False
                                    },
                                    {
                                        "name": "Garlic",
                                        "quantity": 3,
                                        "unit": "piece",
                                        "isAssigned": False
                                    },
                                    {
                                        "name": "Oregano",
                                        "quantity": 1,
                                        "unit": "tsp",
                                        "isAssigned": False
                                    }
                                ],
                                "instructions": [
                                    {
                                        "step_number": 1,
                                        "instruction": "Boil pasta in salted water until al dente."
                                    },
                                    {
                                        "step_number": 2,
                                        "instruction": "Sauté minced garlic in olive oil, then sprinkle oregano."
                                    }
                                ]
                            }, indent=2),
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "{{baseUrl}}/api/suggestion/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "suggestion", ""]
                        },
                        "description": "**Protected Endpoint**\nEdits and updates the complete ingredients and instructions snapshot of a pending suggestion.\n\n**Rules:**\n- Only the suggestion author can update it (`403 Forbidden` for other users).\n- Approved suggestions cannot be updated (`403 Forbidden`).\n- The request body contains the complete new snapshot of ingredients and instructions."
                    },
                    "response": [
                        {
                            "name": "200 OK",
                            "originalRequest": {
                                "method": "PUT",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"suggestion_id\": 1,\n  \"text\": \"Updated suggestion with adjusted garlic quantity\",\n  \"ingredients\": [\n    {\n      \"name\": \"Pasta\",\n      \"quantity\": 250,\n      \"unit\": \"g\",\n      \"isAssigned\": false\n    },\n    {\n      \"name\": \"Garlic\",\n      \"quantity\": 3,\n      \"unit\": \"piece\",\n      \"isAssigned\": false\n    }\n  ],\n  \"instructions\": [\n    {\n      \"step_number\": 1,\n      \"instruction\": \"Boil pasta in salted water until al dente.\"\n    },\n    {\n      \"step_number\": 2,\n      \"instruction\": \"Sauté minced garlic in olive oil.\"\n    }\n  ]\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/suggestion/", "host": ["{{baseUrl}}"], "path": ["api", "suggestion", ""]}
                            },
                            "status": "OK",
                            "code": 200,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Suggestion updated successfully",
                                "suggestion": {
                                    "id": 1,
                                    "user_id": 1,
                                    "receipt_id": 1,
                                    "text": "Updated suggestion with adjusted garlic quantity",
                                    "isApproved": False,
                                    "timestamp": "2026-08-16T15:00:00Z",
                                    "ingredients": [
                                        {
                                            "id": 12,
                                            "name": "Pasta",
                                            "quantity": 250,
                                            "unit": "g",
                                            "isAssigned": False
                                        },
                                        {
                                            "id": 13,
                                            "name": "Garlic",
                                            "quantity": 3,
                                            "unit": "piece",
                                            "isAssigned": False
                                        }
                                    ],
                                    "instructions": [
                                        {
                                            "id": 7,
                                            "step_number": 1,
                                            "instruction": "Boil pasta in salted water until al dente."
                                        },
                                        {
                                            "id": 8,
                                            "step_number": 2,
                                            "instruction": "Sauté minced garlic in olive oil."
                                        }
                                    ]
                                }
                            }, indent=4)
                        },
                        {
                            "name": "403 Forbidden (Not Author or Already Approved)",
                            "originalRequest": {
                                "method": "PUT",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"suggestion_id\": 1,\n  \"text\": \"Updated text\",\n  \"ingredients\": [],\n  \"instructions\": []\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/suggestion/", "host": ["{{baseUrl}}"], "path": ["api", "suggestion", ""]}
                            },
                            "status": "Forbidden",
                            "code": 403,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "You are not allowed to update this suggestion."
                            }, indent=4)
                        }
                    ]
                },
                {
                    "name": "Like a suggestion",
                    "request": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            },
                            {
                                "key": "Content-Type",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": json.dumps({
                                "suggestion_id": 1
                            }, indent=2),
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "{{baseUrl}}/api/like-suggestion/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "like-suggestion", ""]
                        },
                        "description": "**Protected Endpoint**\nLikes a suggestion. Idempotent: duplicate likes return `201 Created` with `is_liked: true`."
                    },
                    "response": [
                        {
                            "name": "201 Created",
                            "originalRequest": {
                                "method": "POST",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"suggestion_id\": 1\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/like-suggestion/", "host": ["{{baseUrl}}"], "path": ["api", "like-suggestion", ""]}
                            },
                            "status": "Created",
                            "code": 201,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Suggestion liked successfully",
                                "suggestion_id": 1,
                                "is_liked": True,
                                "likes_count": 3
                            }, indent=4)
                        }
                    ]
                },
                {
                    "name": "Unlike a suggestion",
                    "request": {
                        "method": "DELETE",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            },
                            {
                                "key": "Content-Type",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": json.dumps({
                                "suggestion_id": 1
                            }, indent=2),
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "{{baseUrl}}/api/like-suggestion/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "like-suggestion", ""]
                        },
                        "description": "**Protected Endpoint**\nRemoves the authenticated user's like from a suggestion."
                    },
                    "response": [
                        {
                            "name": "200 OK",
                            "originalRequest": {
                                "method": "DELETE",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"suggestion_id\": 1\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/like-suggestion/", "host": ["{{baseUrl}}"], "path": ["api", "like-suggestion", ""]}
                            },
                            "status": "OK",
                            "code": 200,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Suggestion unliked successfully",
                                "suggestion_id": 1,
                                "is_liked": False,
                                "likes_count": 2
                            }, indent=4)
                        }
                    ]
                },
                {
                    "name": "Approve a suggestion",
                    "request": {
                        "method": "PATCH",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            },
                            {
                                "key": "Content-Type",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": json.dumps({
                                "suggestion_id": 1
                            }, indent=2),
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "{{baseUrl}}/api/approve-suggestion/",
                            "host": ["{{baseUrl}}"],
                            "path": ["api", "approve-suggestion", ""]
                        },
                        "description": "**Protected Endpoint (Recipe Owner Only)**\nApproves a suggestion.\n\n**Business Logic:**\n- Only the owner of the recipe can approve suggestions targeting it (`403 Forbidden` for anyone else).\n- When approved, the suggestion's ingredients & instructions snapshot atomically replace the recipe's current ingredients & instructions (DEC-020)."
                    },
                    "response": [
                        {
                            "name": "200 OK",
                            "originalRequest": {
                                "method": "PATCH",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"suggestion_id\": 1\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/approve-suggestion/", "host": ["{{baseUrl}}"], "path": ["api", "approve-suggestion", ""]}
                            },
                            "status": "OK",
                            "code": 200,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "Suggestion approved successfully",
                                "suggestion": {
                                    "id": 1,
                                    "receipt_id": 1,
                                    "isApproved": True
                                }
                            }, indent=4)
                        },
                        {
                            "name": "403 Forbidden (Non-Owner Approval)",
                            "originalRequest": {
                                "method": "PATCH",
                                "header": [
                                    {"key": "Accept", "value": "application/json"},
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": "{\n  \"suggestion_id\": 1\n}",
                                    "options": {"raw": {"language": "json"}}
                                },
                                "url": {"raw": "{{baseUrl}}/api/approve-suggestion/", "host": ["{{baseUrl}}"], "path": ["api", "approve-suggestion", ""]}
                            },
                            "status": "Forbidden",
                            "code": 403,
                            "_postman_previewlanguage": "json",
                            "header": [{"key": "Content-Type", "value": "application/json"}],
                            "body": json.dumps({
                                "message": "You are not allowed to approve this suggestion."
                            }, indent=4)
                        }
                    ]
                }
            ]
        }
    ]
}

output_path = os.path.join(os.getcwd(), 'postman', 'Open_Sauce_Developer_1_Endpoints.postman_collection.json')
with open(output_path, 'w', encoding='utf-8') as f:
    json.dump(collection, f, indent=2, ensure_ascii=False)

print(f"Collection successfully written to {output_path}")
