# AI Integration

## Architecture

```
  Flutter (App)
       ↓  (Protected REST API)
    Laravel (Backend)
       ↓  (FastAPI / Internal network)
    FastAPI (AI Service)
       ↓
   AI Model Provider
```

## Architecture Constraints

* **Flutter must NOT call the AI provider or the FastAPI service directly.** All requests must go through the Laravel backend.
* API keys and prompt orchestration details must remain hidden from the client, stored securely on the backend / FastAPI service environment.

## Chatbot Endpoints

The Laravel API exposes the following endpoints under the Chatbot contract (see [Open Sauce API Contract](file:///D:/Programming/Open_Sauce_IEEE_Final_Project/docs/02-api-contract.md#chatbot) for schema examples):

1. **Send user prompt**: `POST /api/user-prompt/`
   * Request requires a JSON body containing: `"prompt": "..."`
   * Response returns a `chat` object containing server-managed fields: `id`, `user_prompt` (matches the prompt sent), `response`, and `timestamp`.
2. **Get chatbot response**: `GET /api/chat-response/`
   * Query parameter: `chat_history_id`
   * Retrieves the chatbot reply for a specific history entry.
3. **Get chatbot history**: `GET /api/chat-history/`
   * Retrieves paginated past conversations for the authenticated user context.

## Laravel Responsibility

The Laravel backend is responsible for:
1. Receiving the prompt request via `POST /api/user-prompt/`.
2. Validating the client-provided `prompt` string.
3. Forwarding the necessary prompt or context to the internal FastAPI service.
4. Storing the exchange in the `Chat_History` table (saving `user_prompt`, `response`, `timestamp`, and the authenticated `user_id`).
5. Returning the standard `201 Created` payload back to the client.

## AI Service Contract (Laravel ↔ FastAPI)

> [!WARNING]
> ### NEEDS DECISION
> The exact request/response payload schemas and communication mechanism (e.g. REST API, queues, or gRPC) between the Laravel backend and the FastAPI service are not defined in the Postman collection or current backend code.
> 
> **Do not invent** a custom schema or protocol for this backend-to-backend communication. It must be finalized with the AI/FastAPI team.

## Error Handling

* AI service failures (e.g., timeouts, down services, rate limits) must not expose API keys, internal network addresses, or stack traces to the client.
* In the event of an AI service failure, Laravel must return a controlled `502 AI unavailable` error with:
  ```json
  {
      "message": "AI service is temporarily unavailable."
  }
  ```