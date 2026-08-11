<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('user can register successfully with valid data', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
    ];

    $response = $this->postJson('/api/register/', $data);

    $response->assertStatus(201)
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
            'message' => 'User registered successfully',
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ]);

    // Ensure password is not returned in the JSON response
    $response->assertJsonMissingPath('user.password');
    $response->assertJsonMissingPath('password');

    // Ensure the token exists
    $this->assertNotEmpty($response->json('token'));

    // Verify database state
    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // Verify password is saved hashed
    $user = User::where('email', 'john@example.com')->first();
    $this->assertNotNull($user);
    $this->assertTrue(Hash::check('Password123!', $user->password));
});

test('registration fails when required fields are missing', function () {
    $response = $this->postJson('/api/register/', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'name',
                'email',
                'password',
            ],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('registration fails when name or email is empty strings', function () {
    $data = [
        'name' => '',
        'email' => '',
        'password' => '',
    ];

    $response = $this->postJson('/api/register/', $data);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('registration fails with malformed email', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'not-an-email',
        'password' => 'Password123!',
    ];

    $response = $this->postJson('/api/register/', $data);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'email',
            ],
        ]);
});

test('registration fails with duplicate email', function () {
    // Create an existing user
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $data = [
        'name' => 'Jane Doe',
        'email' => 'existing@example.com',
        'password' => 'Password123!',
    ];

    $response = $this->postJson('/api/register/', $data);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'email',
            ],
        ]);
});

test('registration fails if password is less than 8 characters', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Pass1!',
    ];

    $response = $this->postJson('/api/register/', $data);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'password',
            ],
        ]);
});

test('registration fails if password has no letters', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => '12345678!',
    ];

    $response = $this->postJson('/api/register/', $data);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'password',
            ],
        ]);
});

test('registration fails if password has no numbers', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password!',
    ];

    $response = $this->postJson('/api/register/', $data);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'password',
            ],
        ]);
});

test('unexpected extra fields in request are ignored', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'extra_field' => 'hacker_payload',
        'user_id' => 999, // Attempting to forge user_id
    ];

    $response = $this->postJson('/api/register/', $data);

    $response->assertStatus(201);

    // Verify user created has the system-generated ID, not the forged one
    $user = User::where('email', 'john@example.com')->first();
    $this->assertNotNull($user);
    $this->assertNotEquals(999, $user->user_id);
});

test('registration route is registered with correct url and middleware', function () {
    $route = collect(Route::getRoutes())->first(function ($route) {
        return $route->uri() === 'api/register' && in_array('POST', $route->methods());
    });

    $this->assertNotNull($route, 'Route POST /api/register/ was not found.');
    
    $middleware = $route->gatherMiddleware();
    
    $this->assertTrue(
        in_array('throttle:6,1', $middleware) || in_array('throttle:register', $middleware),
        'Throttle middleware not applied to register route.'
    );
});
