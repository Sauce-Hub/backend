<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('authenticated user can retrieve profile details successfully', function () {
    $user = User::factory()->create([
        'name' => 'Ahmed',
        'email' => 'ahmed@example.com',
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/profile/');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user_id',
            'name',
            'email',
        ])
        ->assertJson([
            'user_id' => $user->user_id,
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

    // Ensure it's a flat structure and not nested under a 'data' key or 'user' key
    $this->assertArrayHasKey('user_id', $response->json());
    $response->assertJsonMissingPath('data');
    $response->assertJsonMissingPath('user');
});

test('unauthenticated user cannot retrieve profile details', function () {
    $response = $this->getJson('/api/profile/');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('profile request with invalid token returns unauthenticated', function () {
    $response = $this->withHeader('Authorization', 'Bearer invalid-token')
        ->getJson('/api/profile/');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('profile response never includes user password hash', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/profile/');

    $response->assertStatus(200);
    
    // Explicitly assert password is not returned
    $response->assertJsonMissingPath('password');
    $response->assertJsonMissingPath('remember_token');
});

test('profile route is registered with correct url and middleware', function () {
    $route = collect(Route::getRoutes())->first(function ($route) {
        return $route->uri() === 'api/profile' && in_array('GET', $route->methods());
    });

    $this->assertNotNull($route, 'Route GET /api/profile/ was not found.');

    $middleware = $route->gatherMiddleware();

    $this->assertTrue(
        in_array('auth:sanctum', $middleware),
        'Sanctum authentication middleware not applied to profile route.'
    );
});
