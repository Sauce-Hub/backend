# Open Sauce Database Schema

## Database

PostgreSQL

## Important

This document represents the approved database design.

Do not change table names, column names, relationships, or data types
without explicit approval.

---

# User

Fields:

- user_id
- name
- email
- password

Relationships:

- User has many Chat_History
- User has many Favorites
- User has many Suggestions
- User has many Likes_Suggestion

---

# Chat_History

Fields:

- id
- user_prompt
- response
- timestamp
- user_id

Relationship:

- belongs to User

---

# Receipt

Fields:

- receipt_id
- name
- caption
- category
- estimated_time
- Calories
- Fats
- Carbs
- Protein
- timestamp
- user_id

Relationships:

- belongs to User
- has many Ingredients
- has many Favorites

---

# Ingredient

Fields:

- id
- name
- quantity
- unit
- isAssigned
- receipt_id
- suggestion_id

---

# Favorites

Fields:

- user_id
- receipt_id

Relationships:

- belongs to User
- belongs to Receipt

Constraint:

- user_id + receipt_id should be unique

---

# Likes_Receipt

Fields:

- user_id
- receipt_id

Constraint:

- user_id + receipt_id should be unique

---

# Comment

Fields:

- id
- text
- timestamp

NOTE:

The final relationship between Comment, User, and Receipt
must be explicitly confirmed before implementation if not already
represented in the approved schema.

Do not invent missing foreign keys.

---

# Likes_Comment

Fields:

- user_id
- receipt_id

NOTE:

The exact meaning of `receipt_id` in this table must follow
the approved database decision.

Do not rename it without approval.

---

# Suggestion

Fields:

- id
- user_id
- text
- isApproved
- timestamp

Relationship:

- belongs to User

---

# Likes_Suggestion

Fields:

- user_id
- suggestion_id

Constraint:

- user_id + suggestion_id should be unique