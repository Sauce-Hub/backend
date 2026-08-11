<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('user can log in successfully with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'ahmed@example.com',
        'password' => Hash::make('Password123!'),
    ]);

    $data = [
        'email' => 'ahmed@example.com',
        'password' => 'Password123!',
    ];

    $response = $this->postJson('/api/login/', $data);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'user' => [
                'user_id',
                'name',
                'email',
            ],
            'token',
        ])
        ->assertJson([
            'message' => 'Login successful',
            'user' => [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'email' => 'ahmed@example.com',
            ],
        ]);

    // Ensure password is not returned in the JSON response
    $response->assertJsonMissingPath('user.password');
    $response->assertJsonMissingPath('password');

    // Ensure the token exists and is not empty
    $this->assertNotEmpty($response->json('token'));
});

test('login fails when password is incorrect', function () {
    User::factory()->create([
        'email' => 'ahmed@example.com',
        'password' => Hash::make('Password123!'),
    ]);

    $data = [
        'email' => 'ahmed@example.com',
        'password' => 'WrongPassword!',
    ];

    $response = $this->postJson('/api/login/', $data);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Invalid credentials.',
        ]);
});

test('login fails when email does not exist', function () {
    $data = [
        'email' => 'nonexistent@example.com',
        'password' => 'Password123!',
    ];

    $response = $this->postJson('/api/login/', $data);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Invalid credentials.',
        ]);
});

test('login fails when required fields are missing', function () {
    $response = $this->postJson('/api/login/', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'email',
                'password',
            ],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('login fails when fields are empty strings', function () {
    $data = [
        'email' => '',
        'password' => '',
    ];

    $response = $this->postJson('/api/login/', $data);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('login fails with malformed email', function () {
    $data = [
        'email' => 'not-an-email',
        'password' => 'Password123!',
    ];

    $response = $this->postJson('/api/login/', $data);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'email',
            ],
        ]);
});

test('login route is registered with correct url and middleware', function () {
    $route = collect(Route::getRoutes())->first(function ($route) {
        return $route->uri() === 'api/login' && in_array('POST', $route->methods());
    });

    $this->assertNotNull($route, 'Route POST /api/login/ was not found.');
    
    $middleware = $route->gatherMiddleware();
    
    $this->assertTrue(
        in_array('throttle:6,1', $middleware) || in_array('throttle:login', $middleware),
        'Throttle middleware not applied to login route.'
    );
});
