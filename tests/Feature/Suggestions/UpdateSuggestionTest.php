<?php

use App\Models\Ingredient;
use App\Models\Instruction;
use App\Models\Receipt;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\SuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('update suggestion route requires authentication', function () {
    $response = $this->putJson('/api/suggestion/', [
        'suggestion_id' => 1,
        'text' => 'Updated suggestion',
        'ingredients' => [],
        'instructions' => [],
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('update suggestion validates required and present fields', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Missing everything
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/suggestion/', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['suggestion_id', 'text', 'ingredients', 'instructions'],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('update suggestion validates field constraints and types', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Non-integer suggestion_id
    $response1 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => 'invalid-id',
            'text' => 'Updated text',
            'ingredients' => [],
            'instructions' => [],
        ]);
    $response1->assertStatus(422)->assertJsonValidationErrors(['suggestion_id']);

    // Text exceeds max length 2000
    $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => 1,
            'text' => str_repeat('a', 2001),
            'ingredients' => [],
            'instructions' => [],
        ]);
    $response2->assertStatus(422)->assertJsonValidationErrors(['text']);

    // Invalid ingredient quantity <= 0 or missing name
    $response3 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => 1,
            'text' => 'Valid text',
            'ingredients' => [
                [
                    'name' => 'Garlic',
                    'quantity' => 0, // must be > 0
                    'unit' => 'piece',
                ],
            ],
            'instructions' => [],
        ]);
    $response3->assertStatus(422)->assertJsonValidationErrors(['ingredients.0.quantity']);

    // Invalid instruction step_number < 1
    $response4 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => 1,
            'text' => 'Valid text',
            'ingredients' => [],
            'instructions' => [
                [
                    'step_number' => 0, // must be >= 1
                    'instruction' => 'Step 0',
                ],
            ],
        ]);
    $response4->assertStatus(422)->assertJsonValidationErrors(['instructions.0.step_number']);
});

test('update suggestion returns 404 if suggestion does not exist', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => 9999,
            'text' => 'Updated text',
            'ingredients' => [],
            'instructions' => [],
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Suggestion not found.',
        ]);
});

test('update suggestion returns 403 when user is not the suggestion author', function () {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $receipt = Receipt::factory()->create();

    $suggestion = Suggestion::factory()->create([
        'user_id' => $author->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Author initial suggestion',
        'isApproved' => false,
    ]);

    $token = $otherUser->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => $suggestion->id,
            'text' => 'Hacked text',
            'ingredients' => [],
            'instructions' => [],
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'You are not allowed to update this suggestion.',
        ]);

    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'text' => 'Author initial suggestion',
    ]);
});

test('update suggestion returns 403 when suggestion is already approved', function () {
    $author = User::factory()->create();
    $receipt = Receipt::factory()->create();

    $suggestion = Suggestion::factory()->create([
        'user_id' => $author->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Already approved suggestion',
        'isApproved' => true,
    ]);

    $token = $author->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => $suggestion->id,
            'text' => 'Trying to update approved suggestion',
            'ingredients' => [],
            'instructions' => [],
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Approved suggestions cannot be updated.',
        ]);

    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'text' => 'Already approved suggestion',
    ]);
});

test('suggestion author can update own pending suggestion and replace snapshot completely', function () {
    $author = User::factory()->create(['name' => 'Sara']);
    $receipt = Receipt::factory()->create();

    $suggestion = Suggestion::factory()->create([
        'user_id' => $author->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Original suggestion text',
        'isApproved' => false,
        'timestamp' => now()->startOfSecond(),
    ]);

    // Initial suggestion ingredients (Ingredient A to be removed, Ingredient B to be modified)
    Ingredient::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'name' => 'Old Ingredient A',
        'quantity' => 100.0,
        'unit' => 'g',
        'isAssigned' => false,
    ]);
    Ingredient::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'name' => 'Old Ingredient B',
        'quantity' => 1.0,
        'unit' => 'tsp',
        'isAssigned' => false,
    ]);

    // Initial suggestion instructions (Instruction 1 to be removed, Instruction 2 to be modified)
    Instruction::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'step_number' => 1,
        'instruction' => 'Old step 1',
    ]);
    Instruction::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'step_number' => 2,
        'instruction' => 'Old step 2',
    ]);

    $token = $author->createToken('test-token')->plainTextToken;

    // Submit new complete snapshot: modified Ingredient B, new Ingredient C, modified Instruction 2, new Instruction 3
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => $suggestion->id,
            'text' => 'Updated suggestion text with revised steps',
            'ingredients' => [
                [
                    'name' => 'Modified Ingredient B',
                    'quantity' => 2.5,
                    'unit' => 'tbsp',
                    'isAssigned' => true,
                ],
                [
                    'name' => 'New Ingredient C',
                    'quantity' => 50.0,
                    'unit' => 'g',
                    'isAssigned' => false,
                ],
            ],
            'instructions' => [
                [
                    'step_number' => 2,
                    'instruction' => 'New step 2 (added first in array to test ordering)',
                ],
                [
                    'step_number' => 1,
                    'instruction' => 'Modified step 1',
                ],
            ],
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'suggestion' => [
                'id',
                'user_id',
                'receipt_id',
                'text',
                'isApproved',
                'timestamp',
                'ingredients' => [
                    '*' => [
                        'id',
                        'name',
                        'quantity',
                        'unit',
                        'isAssigned',
                    ],
                ],
                'instructions' => [
                    '*' => [
                        'id',
                        'step_number',
                        'instruction',
                    ],
                ],
            ],
        ])
        ->assertJson([
            'message' => 'Suggestion updated successfully',
            'suggestion' => [
                'id' => $suggestion->id,
                'user_id' => $author->user_id,
                'receipt_id' => $receipt->receipt_id,
                'text' => 'Updated suggestion text with revised steps',
                'isApproved' => false,
                'ingredients' => [
                    [
                        'name' => 'Modified Ingredient B',
                        'quantity' => 2.5,
                        'unit' => 'tbsp',
                        'isAssigned' => true,
                    ],
                    [
                        'name' => 'New Ingredient C',
                        'quantity' => 50.0,
                        'unit' => 'g',
                        'isAssigned' => false,
                    ],
                ],
                'instructions' => [
                    [
                        'step_number' => 1,
                        'instruction' => 'Modified step 1',
                    ],
                    [
                        'step_number' => 2,
                        'instruction' => 'New step 2 (added first in array to test ordering)',
                    ],
                ],
            ],
        ]);

    // Verify DB state
    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'text' => 'Updated suggestion text with revised steps',
        'isApproved' => false,
    ]);

    // Old items are deleted
    $this->assertDatabaseMissing('ingredients', ['name' => 'Old Ingredient A']);
    $this->assertDatabaseMissing('instructions', ['instruction' => 'Old step 1']);

    // New items exist with suggestion_id and null receipt_id
    $this->assertDatabaseHas('ingredients', [
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'name' => 'Modified Ingredient B',
    ]);
    $this->assertDatabaseHas('ingredients', [
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'name' => 'New Ingredient C',
    ]);
    $this->assertDatabaseHas('instructions', [
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'step_number' => 1,
        'instruction' => 'Modified step 1',
    ]);
    $this->assertDatabaseHas('instructions', [
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'step_number' => 2,
        'instruction' => 'New step 2 (added first in array to test ordering)',
    ]);
});

test('updating suggestion does not modify original recipe or its ingredients and instructions', function () {
    $author = User::factory()->create();
    $receiptOwner = User::factory()->create();
    $receipt = Receipt::factory()->create([
        'user_id' => $receiptOwner->user_id,
        'name' => 'Original Recipe Name',
    ]);

    $recipeIngredient = Ingredient::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Recipe Garlic',
        'quantity' => 2.0,
    ]);

    $recipeInstruction = Instruction::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'step_number' => 1,
        'instruction' => 'Recipe Boil Water',
    ]);

    $suggestion = Suggestion::factory()->create([
        'user_id' => $author->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Suggestion text',
        'isApproved' => false,
    ]);

    $token = $author->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => $suggestion->id,
            'text' => 'Completely new suggestion text',
            'ingredients' => [
                [
                    'name' => 'Suggestion Onion',
                    'quantity' => 1.0,
                    'unit' => 'piece',
                    'isAssigned' => false,
                ],
            ],
            'instructions' => [
                [
                    'step_number' => 1,
                    'instruction' => 'Suggestion Step 1',
                ],
            ],
        ]);

    $response->assertStatus(200);

    // Verify recipe and its items remain completely untouched
    $this->assertDatabaseHas('receipts', [
        'receipt_id' => $receipt->receipt_id,
        'name' => 'Original Recipe Name',
    ]);
    $this->assertDatabaseHas('ingredients', [
        'id' => $recipeIngredient->id,
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Recipe Garlic',
    ]);
    $this->assertDatabaseHas('instructions', [
        'id' => $recipeInstruction->id,
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'instruction' => 'Recipe Boil Water',
    ]);
});

test('failed update rolls back transaction and preserves old snapshot and text', function () {
    $author = User::factory()->create();
    $receipt = Receipt::factory()->create();

    $suggestion = Suggestion::factory()->create([
        'user_id' => $author->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Original persistent text',
        'isApproved' => false,
    ]);

    $oldIngredient = Ingredient::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'name' => 'Original persistent ingredient',
    ]);

    $oldInstruction = Instruction::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'step_number' => 1,
        'instruction' => 'Original persistent instruction',
    ]);

    // Force an exception during Instruction creating event
    Instruction::creating(function ($instruction) {
        if ($instruction->instruction === 'Trigger failure') {
            throw new RuntimeException('Simulated database exception during update');
        }
    });

    $service = app(SuggestionService::class);

    try {
        $service->updateSuggestion(
            $suggestion,
            'New text that must be rolled back',
            [
                [
                    'name' => 'New ingredient that must be rolled back',
                    'quantity' => 10.0,
                    'unit' => 'g',
                ],
            ],
            [
                [
                    'step_number' => 1,
                    'instruction' => 'Trigger failure',
                ],
            ]
        );
        $this->fail('Exception was not thrown.');
    } catch (RuntimeException $e) {
        $this->assertEquals('Simulated database exception during update', $e->getMessage());
    }

    // Verify original text and snapshot are preserved
    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'text' => 'Original persistent text',
    ]);
    $this->assertDatabaseHas('ingredients', [
        'id' => $oldIngredient->id,
        'name' => 'Original persistent ingredient',
    ]);
    $this->assertDatabaseHas('instructions', [
        'id' => $oldInstruction->id,
        'instruction' => 'Original persistent instruction',
    ]);
    $this->assertDatabaseMissing('ingredients', [
        'name' => 'New ingredient that must be rolled back',
    ]);
});

test('updating suggestion rejects invalid unit with 422 unprocessable entity', function () {
    $author = User::factory()->create();
    $receipt = Receipt::factory()->create();
    $suggestion = Suggestion::factory()->create([
        'user_id' => $author->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Original text',
        'isApproved' => false,
    ]);

    $token = $author->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => $suggestion->id,
            'text' => 'Updated text',
            'ingredients' => [
                [
                    'name' => 'Garlic',
                    'quantity' => 2,
                    'unit' => 'invalid_unit_not_in_enum',
                ],
            ],
            'instructions' => [
                [
                    'step_number' => 1,
                    'instruction' => 'Step 1',
                ],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['ingredients.0.unit']);
});
