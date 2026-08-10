# Open Sauce Backend - Project Overview

## Project

Open Sauce is a food-related application with:

- User authentication
- Recipes
- Favorites
- Comments
- Suggestions
- Chatbot / AI functionality
- Recipe details

## Backend

Framework:

Laravel

Authentication:

Laravel Sanctum

Database:

PostgreSQL

## API Style

The Laravel backend exposes REST-style HTTP APIs consumed by
the Flutter application.

## Backend Responsibilities

Laravel is responsible for:

- Authentication
- User management
- Database operations
- Recipe data
- Favorites
- Suggestions
- Comments
- Chat history
- API validation
- Authorization
- Communication with the AI service

## AI

AI functionality is handled through the AI service / FastAPI layer.

Laravel should not expose AI provider API keys to Flutter.

## Development Principle

The backend implementation must follow the approved database schema
and API contract.

Do not introduce new public endpoints or database fields without approval.