<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * Helper to clear Laravel's in-memory authentication states in tests.
 */
function clearAuthState(): void
{
    auth()->forgetGuards();
    if (app()->bound('request')) {
        app('request')->setUserResolver(fn () => null);
    }
}

test('authenticated user can log out successfully and token is deleted', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Send logout request
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->deleteJson('/api/logout/');

    // Assert status is 204
    $response->assertStatus(204);

    // Verify token was deleted
    $this->assertCount(0, $user->tokens);

    // Clear in-memory authentication state
    clearAuthState();

    // Assert token cannot be reused
    $responseUser = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/user');
    
    $responseUser->assertStatus(401);
});

test('logging out revokes only the current token and preserves other active tokens', function () {
    $user = User::factory()->create();
    
    // Create multiple tokens
    $token1 = $user->createToken('device-1')->plainTextToken;
    $token2 = $user->createToken('device-2')->plainTextToken;

    // Log out from device-1 session
    $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
        ->deleteJson('/api/logout/');

    $response->assertStatus(204);

    // Clear in-memory authentication state
    clearAuthState();

    // Verify token1 is invalid now
    $responseUser1 = $this->withHeader('Authorization', 'Bearer ' . $token1)
        ->getJson('/api/user');
    $responseUser1->assertStatus(401);

    // Verify token2 is still valid
    $responseUser2 = $this->withHeader('Authorization', 'Bearer ' . $token2)
        ->getJson('/api/user');
    $responseUser2->assertStatus(200);
});

test('logout fails when unauthenticated', function () {
    // Missing token
    $response = $this->deleteJson('/api/logout/');
    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);

    // Invalid token
    $responseInvalid = $this->withHeader('Authorization', 'Bearer invalid-token')
        ->deleteJson('/api/logout/');
    $responseInvalid->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('logout route is registered with correct url and middleware', function () {
    $route = collect(Route::getRoutes())->first(function ($route) {
        return $route->uri() === 'api/logout' && in_array('DELETE', $route->methods());
    });

    $this->assertNotNull($route, 'Route DELETE /api/logout/ was not found.');
    
    $middleware = $route->gatherMiddleware();
    
    $this->assertTrue(
        in_array('auth:sanctum', $middleware),
        'Sanctum authentication middleware not applied to logout route.'
    );
});
