<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * Helper to clear Laravel's in-memory authentication states in tests.
 */
function clearSanctumAuthState(): void
{
    auth()->forgetGuards();
    if (app()->bound('request')) {
        app('request')->setUserResolver(fn () => null);
    }
}

test('all developer 1 endpoints are registered with the correct http methods and urls', function () {
    $expectedEndpoints = [
        ['method' => 'POST', 'uri' => 'api/register'],
        ['method' => 'POST', 'uri' => 'api/login'],
        ['method' => 'DELETE', 'uri' => 'api/logout'],
        ['method' => 'GET', 'uri' => 'api/profile'],
        ['method' => 'GET', 'uri' => 'api/comments'],
        ['method' => 'POST', 'uri' => 'api/comment'],
        ['method' => 'POST', 'uri' => 'api/like-comment'],
        ['method' => 'DELETE', 'uri' => 'api/like-comment'],
        ['method' => 'GET', 'uri' => 'api/suggestions'],
        ['method' => 'POST', 'uri' => 'api/suggestion'],
        ['method' => 'POST', 'uri' => 'api/like-suggestion'],
        ['method' => 'DELETE', 'uri' => 'api/like-suggestion'],
        ['method' => 'PATCH', 'uri' => 'api/approve-suggestion'],
    ];

    $registeredRoutes = collect(Route::getRoutes())->map(function ($route) {
        return [
            'methods' => $route->methods(),
            'uri' => $route->uri(),
        ];
    });

    foreach ($expectedEndpoints as $expected) {
        $match = $registeredRoutes->first(function ($route) use ($expected) {
            return $route['uri'] === $expected['uri'] && in_array($expected['method'], $route['methods']);
        });

        $this->assertNotNull(
            $match,
            sprintf('Expected endpoint %s /%s/ not registered.', $expected['method'], $expected['uri'])
        );
    }
});

test('all developer 1 protected endpoints have sanctum middleware applied', function () {
    $protectedEndpoints = [
        ['method' => 'DELETE', 'uri' => 'api/logout'],
        ['method' => 'GET', 'uri' => 'api/profile'],
        ['method' => 'GET', 'uri' => 'api/comments'],
        ['method' => 'POST', 'uri' => 'api/comment'],
        ['method' => 'POST', 'uri' => 'api/like-comment'],
        ['method' => 'DELETE', 'uri' => 'api/like-comment'],
        ['method' => 'GET', 'uri' => 'api/suggestions'],
        ['method' => 'POST', 'uri' => 'api/suggestion'],
        ['method' => 'POST', 'uri' => 'api/like-suggestion'],
        ['method' => 'DELETE', 'uri' => 'api/like-suggestion'],
        ['method' => 'PATCH', 'uri' => 'api/approve-suggestion'],
    ];

    $registeredRoutes = collect(Route::getRoutes())->map(function ($route) {
        return [
            'methods' => $route->methods(),
            'uri' => $route->uri(),
            'middleware' => $route->gatherMiddleware(),
        ];
    });

    foreach ($protectedEndpoints as $expected) {
        $match = $registeredRoutes->first(function ($route) use ($expected) {
            return $route['uri'] === $expected['uri'] && in_array($expected['method'], $route['methods']);
        });

        $this->assertNotNull($match, "Route {$expected['method']} /{$expected['uri']} not found.");
        $this->assertTrue(
            in_array('auth:sanctum', $match['middleware']),
            "Sanctum authentication middleware not applied to {$expected['method']} /{$expected['uri']}"
        );
    }
});

test('unauthenticated requests to protected endpoints return 401', function () {
    $protectedEndpoints = [
        ['method' => 'DELETE', 'url' => '/api/logout/'],
        ['method' => 'GET', 'url' => '/api/profile/'],
        ['method' => 'GET', 'url' => '/api/comments/'],
        ['method' => 'POST', 'url' => '/api/comment/'],
        ['method' => 'POST', 'url' => '/api/like-comment/'],
        ['method' => 'DELETE', 'url' => '/api/like-comment/'],
        ['method' => 'GET', 'url' => '/api/suggestions/'],
        ['method' => 'POST', 'url' => '/api/suggestion/'],
        ['method' => 'POST', 'url' => '/api/like-suggestion/'],
        ['method' => 'DELETE', 'url' => '/api/like-suggestion/'],
        ['method' => 'PATCH', 'url' => '/api/approve-suggestion/'],
    ];

    foreach ($protectedEndpoints as $endpoint) {
        clearSanctumAuthState();

        $response = $this->json($endpoint['method'], $endpoint['url']);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
});

test('authenticated requests to protected endpoints pass authentication middleware and do not return 401', function () {
    $protectedEndpoints = [
        ['method' => 'DELETE', 'url' => '/api/logout/'],
        ['method' => 'GET', 'url' => '/api/profile/'],
        ['method' => 'GET', 'url' => '/api/comments/'],
        ['method' => 'POST', 'url' => '/api/comment/'],
        ['method' => 'POST', 'url' => '/api/like-comment/'],
        ['method' => 'DELETE', 'url' => '/api/like-comment/'],
        ['method' => 'GET', 'url' => '/api/suggestions/'],
        ['method' => 'POST', 'url' => '/api/suggestion/'],
        ['method' => 'POST', 'url' => '/api/like-suggestion/'],
        ['method' => 'DELETE', 'url' => '/api/like-suggestion/'],
        ['method' => 'PATCH', 'url' => '/api/approve-suggestion/'],
    ];

    foreach ($protectedEndpoints as $endpoint) {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        clearSanctumAuthState();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->json($endpoint['method'], $endpoint['url']);

        $this->assertNotEquals(
            401,
            $response->getStatusCode(),
            "Endpoint {$endpoint['method']} {$endpoint['url']} returned 401 for authenticated user."
        );
    }
});
