<?php

use App\Models\Ingredient;
use App\Models\Receipt;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('view suggestions route requires authentication', function () {
    $response = $this->getJson('/api/suggestions/?receipt_id=1');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('view suggestions requires receipt_id query parameter', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/suggestions/');

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['receipt_id'],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('view suggestions receipt_id must be an integer', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/suggestions/?receipt_id=invalid');

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['receipt_id'],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('view suggestions returns 404 if receipt does not exist', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/suggestions/?receipt_id=9999');

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Receipt not found.',
        ]);
});

test('authenticated user can view suggestions with correct payload structure', function () {
    $user = User::factory()->create(['name' => 'Sara']);
    $receipt = Receipt::factory()->create();

    // Create suggestions for the receipt
    $suggestion = Suggestion::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'user_id' => $user->user_id,
        'text' => 'Add garlic',
        'isApproved' => false,
        'timestamp' => now()->startOfSecond(),
    ]);

    // Create an ingredient associated with the suggestion
    $ingredient = Ingredient::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'name' => 'Garlic',
        'quantity' => 2.0,
        'unit' => 'cloves',
        'isAssigned' => false,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/suggestions/?receipt_id={$receipt->receipt_id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'receipt_id',
                    'text',
                    'isApproved',
                    'timestamp',
                    'user' => [
                        'user_id',
                        'name',
                    ],
                    'likes_count',
                    'is_liked',
                    'ingredients' => [
                        '*' => [
                            'id',
                            'name',
                            'quantity',
                            'unit',
                            'isAssigned',
                        ],
                    ],
                ],
            ],
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
            ],
        ])
        ->assertJson([
            'data' => [
                [
                    'id' => $suggestion->id,
                    'receipt_id' => $receipt->receipt_id,
                    'text' => 'Add garlic',
                    'isApproved' => false,
                    'timestamp' => $suggestion->timestamp->toIso8601ZuluString(),
                    'user' => [
                        'user_id' => $user->user_id,
                        'name' => 'Sara',
                    ],
                    'likes_count' => 0,
                    'is_liked' => false,
                    'ingredients' => [
                        [
                            'id' => $ingredient->id,
                            'name' => 'Garlic',
                            'quantity' => 2.0,
                            'unit' => 'cloves',
                            'isAssigned' => false,
                        ],
                    ],
                ],
            ],
            'meta' => [
                'current_page' => 1,
                'per_page' => 20,
                'total' => 1,
                'last_page' => 1,
            ],
        ]);
});

test('view suggestions respects pagination parameters', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();

    // Create 5 suggestions
    Suggestion::factory()->count(5)->create([
        'receipt_id' => $receipt->receipt_id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    // Get page 1 with 2 suggestions per page
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/suggestions/?receipt_id={$receipt->receipt_id}&page=1&per_page=2");

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['data'])->toHaveCount(2);
    expect($data['meta'])->toBe([
        'current_page' => 1,
        'per_page' => 2,
        'total' => 5,
        'last_page' => 3,
    ]);

    // Get page 3 with 2 suggestions per page (should have 1 suggestion)
    $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/suggestions/?receipt_id={$receipt->receipt_id}&page=3&per_page=2");

    $response2->assertStatus(200);
    $data2 = $response2->json();

    expect($data2['data'])->toHaveCount(1);
    expect($data2['meta'])->toBe([
        'current_page' => 3,
        'per_page' => 2,
        'total' => 5,
        'last_page' => 3,
    ]);
});

test('authenticated user receives 200 with empty data if receipt has no suggestions', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/suggestions/?receipt_id={$receipt->receipt_id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'per_page' => 20,
                'total' => 0,
                'last_page' => 1,
            ],
        ]);
});

test('view suggestions returns correct likes_count and is_liked true if current user liked it', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();

    $suggestion = Suggestion::factory()->create([
        'receipt_id' => $receipt->receipt_id,
    ]);

    // User likes the suggestion
    $user->likedSuggestions()->attach($suggestion->id);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/suggestions/?receipt_id={$receipt->receipt_id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.0.likes_count', 1)
        ->assertJsonPath('data.0.is_liked', true);
});

test('view suggestions returns correct likes_count and is_liked false if current user did not like it', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $receipt = Receipt::factory()->create();

    $suggestion = Suggestion::factory()->create([
        'receipt_id' => $receipt->receipt_id,
    ]);

    // User A likes the suggestion
    $userA->likedSuggestions()->attach($suggestion->id);

    $tokenB = $userB->createToken('test-token-b')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$tokenB)
        ->getJson("/api/suggestions/?receipt_id={$receipt->receipt_id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.0.likes_count', 1)
        ->assertJsonPath('data.0.is_liked', false);
});
