# Open Sauce Database Schema

## Database

PostgreSQL

## Important

This document represents the finalized and approved database design. All migrations, models, and constraints must follow this schema exactly.

---

# User

Fields:

- user_id (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
- name (VARCHAR)
- email (VARCHAR, UNIQUE)
- password (VARCHAR)

Relationships:

- User has many Chat_History (ON DELETE CASCADE)
- User has many Favorites (ON DELETE CASCADE)
- User has many Suggestions (ON DELETE CASCADE)
- User has many Likes_Suggestion (ON DELETE CASCADE)
- User has many Comments (ON DELETE CASCADE)
- User has many Receipts (ON DELETE CASCADE)

---

# Chat_History

Fields:

- id (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
- user_prompt (TEXT)
- response (TEXT)
- timestamp (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- user_id (BIGINT UNSIGNED, FK → users.user_id, ON DELETE CASCADE)

Relationship:

- belongs to User

---

# Receipt

Fields:

- receipt_id (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
- name (VARCHAR)
- caption (VARCHAR)
- category (VARCHAR)
- estimated_time (VARCHAR)
- Calories (INT)
- Fats (INT)
- Carbs (INT)
- Protein (INT)
- timestamp (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- user_id (BIGINT UNSIGNED, FK → users.user_id, ON DELETE CASCADE)

Relationships:

- belongs to User
- has many Ingredients (ON DELETE CASCADE)
- has many Favorites (ON DELETE CASCADE)
- has many Comments (ON DELETE CASCADE)
- has many Suggestions (ON DELETE CASCADE)

---

# Ingredient

Fields:

- id (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
- name (VARCHAR)
- quantity (DOUBLE PRECISION)
- unit (VARCHAR)
- isAssigned (BOOLEAN, DEFAULT FALSE)
- receipt_id (BIGINT UNSIGNED, Nullable, FK → receipts.receipt_id, ON DELETE CASCADE)
- suggestion_id (BIGINT UNSIGNED, Nullable, FK → suggestions.id, ON DELETE CASCADE)

Constraints:

- CHECK constraint enforcing exactly one of `receipt_id` and `suggestion_id` is NOT NULL:
  `(receipt_id IS NULL AND suggestion_id IS NOT NULL) OR (receipt_id IS NOT NULL AND suggestion_id IS NULL)`

---

# Favorites

Fields:

- user_id (BIGINT UNSIGNED, FK → users.user_id, ON DELETE CASCADE)
- receipt_id (BIGINT UNSIGNED, FK → receipts.receipt_id, ON DELETE CASCADE)

Constraints:

- Primary Key: `(user_id, receipt_id)`

---

# Likes_Receipt

Fields:

- user_id (BIGINT UNSIGNED, FK → users.user_id, ON DELETE CASCADE)
- receipt_id (BIGINT UNSIGNED, FK → receipts.receipt_id, ON DELETE CASCADE)

Constraints:

- Primary Key: `(user_id, receipt_id)`

---

# Comment

Fields:

- id (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
- user_id (BIGINT UNSIGNED, FK → users.user_id, ON DELETE CASCADE)
- receipt_id (BIGINT UNSIGNED, FK → receipts.receipt_id, ON DELETE CASCADE)
- text (TEXT)
- timestamp (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

Relationships:

- belongs to User
- belongs to Receipt
- has many Likes_Comment (ON DELETE CASCADE)

Note: Same user is permitted to create multiple comments on the same receipt (no unique constraint on `user_id` and `receipt_id`).

---

# Likes_Comment

Fields:

- user_id (BIGINT UNSIGNED, FK → users.user_id, ON DELETE CASCADE)
- comment_id (BIGINT UNSIGNED, FK → comments.id, ON DELETE CASCADE)

Constraints:

- Primary Key: `(user_id, comment_id)`

---

# Suggestion

Fields:

- id (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
- user_id (BIGINT UNSIGNED, FK → users.user_id, ON DELETE CASCADE)
- receipt_id (BIGINT UNSIGNED, FK → receipts.receipt_id, ON DELETE CASCADE)
- text (TEXT)
- isApproved (BOOLEAN, DEFAULT FALSE)
- timestamp (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

Relationships:

- belongs to User
- belongs to Receipt
- has many Likes_Suggestion (ON DELETE CASCADE)

---

# Likes_Suggestion

Fields:

- user_id (BIGINT UNSIGNED, FK → users.user_id, ON DELETE CASCADE)
- suggestion_id (BIGINT UNSIGNED, FK → suggestions.id, ON DELETE CASCADE)

Constraints:

- Primary Key: `(user_id, suggestion_id)`