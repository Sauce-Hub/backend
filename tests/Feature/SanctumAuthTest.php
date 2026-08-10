<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access user endpoint', function () {
    $response = $this->getJson('/api/user');

    $response->assertStatus(401);
});

test('authenticated user can access user endpoint using sanctum token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/user');

    $response->assertStatus(200)
        ->assertJson([
            'user_id' => $user->user_id,
            'email' => $user->email,
        ]);
});
