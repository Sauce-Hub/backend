<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * Task 1.2 Endpoint Registration Matrix:
 * Verifies exact HTTP method + exact URL for every endpoint defined in Developer 1's scope.
 * Prevents URL drift, singular/plural mismatch, wrong HTTP method, or missing route errors.
 */
dataset('task_1_2_endpoints', [
    'POST /api/register/' => ['POST', 'api/register', '/api/register/', false],
    'POST /api/login/' => ['POST', 'api/login', '/api/login/', false],
    'DELETE /api/logout/' => ['DELETE', 'api/logout', '/api/logout/', true],
    'GET /api/profile/' => ['GET', 'api/profile', '/api/profile/', true],
    'GET /api/comments/' => ['GET', 'api/comments', '/api/comments/', true],
    'POST /api/comment/' => ['POST', 'api/comment', '/api/comment/', true],
    'POST /api/like-comment/' => ['POST', 'api/like-comment', '/api/like-comment/', true],
    'DELETE /api/like-comment/' => ['DELETE', 'api/like-comment', '/api/like-comment/', true],
    'GET /api/suggestions/' => ['GET', 'api/suggestions', '/api/suggestions/', true],
    'POST /api/suggestion/' => ['POST', 'api/suggestion', '/api/suggestion/', true],
    'POST /api/like-suggestion/' => ['POST', 'api/like-suggestion', '/api/like-suggestion/', true],
    'DELETE /api/like-suggestion/' => ['DELETE', 'api/like-suggestion', '/api/like-suggestion/', true],
    'PATCH /api/approve-suggestion/' => ['PATCH', 'api/approve-suggestion', '/api/approve-suggestion/', true],
]);

test('every task 1.2 endpoint is registered with exact HTTP method and exact URI in route table', function (string $method, string $uri) {
    $registeredRoutes = collect(Route::getRoutes())->map(function ($route) {
        return [
            'methods' => $route->methods(),
            'uri' => $route->uri(),
        ];
    });

    $match = $registeredRoutes->first(function ($route) use ($method, $uri) {
        return $route['uri'] === $uri && in_array($method, $route['methods']);
    });

    $this->assertNotNull(
        $match,
        sprintf('Task 1.2 requirement failed: Endpoint %s /%s/ is missing or not registered.', $method, $uri)
    );
})->with('task_1_2_endpoints');

test('every task 1.2 protected endpoint requires Sanctum auth middleware', function (string $method, string $uri, string $url, bool $authRequired) {
    if (! $authRequired) {
        $this->assertFalse($authRequired);

        return;
    }

    $route = collect(Route::getRoutes())->first(function ($route) use ($method, $uri) {
        return $route->uri() === $uri && in_array($method, $route->methods());
    });

    $this->assertNotNull($route);
    $middleware = $route->gatherMiddleware();

    $this->assertTrue(
        in_array('auth:sanctum', $middleware),
        sprintf('Endpoint %s /%s/ missing auth:sanctum middleware.', $method, $uri)
    );
})->with('task_1_2_endpoints');

dataset('drifted_or_incorrect_endpoints', [
    'POST /api/comments/ (plural drift on create)' => ['POST', '/api/comments/'],
    'POST /api/suggestions/ (plural drift on create)' => ['POST', '/api/suggestions/'],
    'POST /api/comment-like/ (word order drift)' => ['POST', '/api/comment-like/'],
    'POST /api/suggestion-like/ (word order drift)' => ['POST', '/api/suggestion-like/'],
    'POST /api/approve-suggestion/ (wrong HTTP method - POST instead of PATCH)' => ['POST', '/api/approve-suggestion/'],
    'PATCH /api/suggestions/approve/ (restful nested path drift)' => ['PATCH', '/api/suggestions/approve/'],
    'GET /api/comment/ (wrong method/URI for comments listing)' => ['GET', '/api/comment/'],
    'GET /api/suggestion/ (wrong method/URI for suggestions listing)' => ['GET', '/api/suggestion/'],
]);

test('regression guard detects drifted or incorrect endpoint URLs and methods', function (string $method, string $url) {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    auth()->forgetGuards();
    if (app()->bound('request')) {
        app('request')->setUserResolver(fn () => null);
    }

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->json($method, $url);

    // Any drifted or invalid route must return 404 or 405, NOT match a Task 1.2 route
    $this->assertContains(
        $response->getStatusCode(),
        [404, 405],
        sprintf('Drifted route %s %s incorrectly matched a valid handler!', $method, $url)
    );
})->with('drifted_or_incorrect_endpoints');
