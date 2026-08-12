<?php

use App\Models\Ingredient;
use App\Models\Receipt;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\SuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('add suggestion route requires authentication', function () {
    $response = $this->postJson('/api/suggestion/', [
        'receipt_id' => 1,
        'text' => 'Add garlic',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('add suggestion validates required parameters', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['receipt_id', 'text'],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('add suggestion text cannot exceed max length of 2000 characters', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => 1,
            'text' => str_repeat('a', 2001),
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['text'],
        ]);
});

test('add suggestion validates nested ingredients fields', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // 1. Missing name
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => 1,
            'text' => 'Add garlic',
            'ingredients' => [
                [
                    'quantity' => 2,
                    'unit' => 'cloves',
                ]
            ]
        ]);
    $response->assertStatus(422)->assertJsonValidationErrors(['ingredients.0.name']);

    // 2. Quantity non-positive
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => 1,
            'text' => 'Add garlic',
            'ingredients' => [
                [
                    'name' => 'Garlic',
                    'quantity' => 0,
                    'unit' => 'cloves',
                ]
            ]
        ]);
    $response->assertStatus(422)->assertJsonValidationErrors(['ingredients.0.quantity']);
});

test('add suggestion returns 404 if receipt does not exist', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => 9999,
            'text' => 'Add garlic',
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Receipt not found.',
        ]);
});

test('authenticated user can create suggestion with zero ingredients', function () {
    $user = User::factory()->create(['name' => 'Ahmed']);
    $receipt = Receipt::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'Add garlic',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'suggestion' => [
                'id',
                'user_id',
                'receipt_id',
                'text',
                'isApproved',
                'timestamp',
                'ingredients',
            ],
        ])
        ->assertJson([
            'message' => 'Suggestion created successfully',
            'suggestion' => [
                'user_id' => $user->user_id,
                'receipt_id' => $receipt->receipt_id,
                'text' => 'Add garlic',
                'isApproved' => false,
                'ingredients' => [],
            ],
        ]);

    $this->assertDatabaseHas('suggestions', [
        'user_id' => $user->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Add garlic',
        'isApproved' => false,
    ]);
});

test('authenticated user can create suggestion with multiple ingredients', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'Add garlic and salt',
            'ingredients' => [
                [
                    'name' => 'Garlic',
                    'quantity' => 2,
                    'unit' => 'cloves',
                    'isAssigned' => false,
                ],
                [
                    'name' => 'Salt',
                    'quantity' => 0.5,
                    'unit' => 'tsp',
                    'isAssigned' => true,
                ],
            ]
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Suggestion created successfully',
            'suggestion' => [
                'user_id' => $user->user_id,
                'receipt_id' => $receipt->receipt_id,
                'text' => 'Add garlic and salt',
                'isApproved' => false,
                'ingredients' => [
                    [
                        'name' => 'Garlic',
                        'quantity' => 2.0,
                        'unit' => 'cloves',
                        'isAssigned' => false,
                    ],
                    [
                        'name' => 'Salt',
                        'quantity' => 0.5,
                        'unit' => 'tsp',
                        'isAssigned' => true,
                    ],
                ],
            ],
        ]);

    $this->assertDatabaseHas('suggestions', [
        'user_id' => $user->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Add garlic and salt',
    ]);

    // Check DB CHECK constraint was respected (receipt_id is NULL for suggestion ingredients)
    $this->assertDatabaseHas('ingredients', [
        'name' => 'Garlic',
        'receipt_id' => null,
    ]);

    $this->assertDatabaseHas('ingredients', [
        'name' => 'Salt',
        'receipt_id' => null,
    ]);
});

test('suggestion user_id is derived from auth and client-provided user_id is ignored', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'Ignore client user_id',
            'user_id' => 9999, // Forge user_id
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('suggestion.user_id', $user->user_id);

    $this->assertDatabaseHas('suggestions', [
        'user_id' => $user->user_id,
        'text' => 'Ignore client user_id',
    ]);
    $this->assertDatabaseMissing('suggestions', [
        'user_id' => 9999,
    ]);
});

test('failed ingredient creation rolls back suggestion creation', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();

    $service = app(SuggestionService::class);

    // Call service directly, passing an invalid ingredient payload (name => null) to trigger database NotNull integrity check failure
    try {
        $service->storeSuggestion(
            $user->user_id,
            $receipt->receipt_id,
            'Should rollback suggestion',
            [
                [
                    'name' => 'Valid Ingredient',
                    'quantity' => 1,
                    'unit' => 'pc',
                ],
                [
                    'name' => null, // SQLite NOT NULL constraint error triggers here
                    'quantity' => 2,
                    'unit' => 'pcs',
                ]
            ]
        );
        $this->fail('Database constraint exception was not thrown.');
    } catch (\Illuminate\Database\QueryException $e) {
        // Exception caught successfully
    }

    // Assert that no suggestions or ingredients rows were committed to DB
    $this->assertDatabaseMissing('suggestions', [
        'text' => 'Should rollback suggestion',
    ]);

    $this->assertDatabaseMissing('ingredients', [
        'name' => 'Valid Ingredient',
    ]);
});
