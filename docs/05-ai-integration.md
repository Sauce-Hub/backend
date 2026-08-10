# AI Integration

## Architecture

Flutter
    ↓
Laravel
    ↓
FastAPI / AI Service
    ↓
AI Model Provider

## Important

Flutter must NOT call the AI provider directly.

API keys must remain on the backend / AI service.

## Laravel Responsibility

Laravel should:

1. Receive authenticated user prompt.
2. Validate the request.
3. Send the required data to the AI service.
4. Receive the AI response.
5. Return the approved API response.
6. Store chat history according to the approved flow.

## Chat History

Chat_History contains:

- user_prompt
- response
- timestamp
- user_id

## AI Contract

FastAPI request/response schema:
TBD

Do not invent the FastAPI contract.

## Error Handling

AI service failures must not expose internal credentials
or implementation details.

The Laravel API should return a controlled error response.