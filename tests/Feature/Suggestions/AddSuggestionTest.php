<?php

use App\Models\Ingredient;
use App\Models\Instruction;
use App\Models\Receipt;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\SuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('1. unauthenticated request returns 401', function () {
    $response = $this->postJson('/api/suggestion/', [
        'receipt_id' => 1,
        'text' => 'Add garlic',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('2. invalid parameters return 422 validation error', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Missing all fields
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

    // Invalid receipt_id (non-integer)
    $responseNonInt = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => 'not-an-integer',
            'text' => 'Add garlic',
        ]);

    $responseNonInt->assertStatus(422)
        ->assertJsonValidationErrors(['receipt_id']);

    // Text exceeds max length 2000
    $responseTooLong = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => 1,
            'text' => str_repeat('a', 2001),
        ]);

    $responseTooLong->assertStatus(422)
        ->assertJsonValidationErrors(['text']);
});

test('3. nonexistent receipt returns 404 not found', function () {
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

test('4. authenticated user can create suggestion and snapshot current recipe ingredients and instructions', function () {
    $user = User::factory()->create(['name' => 'Sara']);
    $receiptOwner = User::factory()->create(['name' => 'Ahmed']);
    $receipt = Receipt::factory()->create([
        'user_id' => $receiptOwner->user_id,
        'name' => 'Pasta Carbonara',
    ]);

    // Create recipe ingredients
    $ingredient1 = Ingredient::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Spaghetti',
        'quantity' => 200.0,
        'unit' => 'g',
        'isAssigned' => false,
    ]);
    $ingredient2 = Ingredient::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Pancetta',
        'quantity' => 100.0,
        'unit' => 'g',
        'isAssigned' => true,
    ]);

    // Create recipe instructions
    $instruction1 = Instruction::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'step_number' => 1,
        'instruction' => 'Boil salted water and cook spaghetti until al dente.',
    ]);
    $instruction2 = Instruction::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'step_number' => 2,
        'instruction' => 'Fry pancetta until crisp.',
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'Add garlic to the pancetta while frying.',
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
            'message' => 'Suggestion created successfully',
            'suggestion' => [
                'user_id' => $user->user_id,
                'receipt_id' => $receipt->receipt_id,
                'text' => 'Add garlic to the pancetta while frying.',
                'isApproved' => false,
                'ingredients' => [
                    [
                        'name' => 'Spaghetti',
                        'quantity' => 200.0,
                        'unit' => 'g',
                        'isAssigned' => false,
                    ],
                    [
                        'name' => 'Pancetta',
                        'quantity' => 100.0,
                        'unit' => 'g',
                        'isAssigned' => true,
                    ],
                ],
                'instructions' => [
                    [
                        'step_number' => 1,
                        'instruction' => 'Boil salted water and cook spaghetti until al dente.',
                    ],
                    [
                        'step_number' => 2,
                        'instruction' => 'Fry pancetta until crisp.',
                    ],
                ],
            ],
        ]);

    $suggestionData = $response->json('suggestion');
    $suggestionId = $suggestionData['id'];

    // Verify isApproved starts with false
    $this->assertFalse($suggestionData['isApproved']);

    // Verify timestamp formatting is ISO8601 UTC
    $this->assertNotNull($suggestionData['timestamp']);

    // Verify database record for suggestion
    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestionId,
        'user_id' => $user->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Add garlic to the pancetta while frying.',
        'isApproved' => false,
    ]);

    // Verify cloned ingredients belong to suggestion and have receipt_id = null
    $clonedIngredients = Ingredient::where('suggestion_id', $suggestionId)->get();
    $this->assertCount(2, $clonedIngredients);
    foreach ($clonedIngredients as $clonedIng) {
        $this->assertNull($clonedIng->receipt_id);
        $this->assertEquals($suggestionId, $clonedIng->suggestion_id);
        $this->assertNotEquals($ingredient1->id, $clonedIng->id);
        $this->assertNotEquals($ingredient2->id, $clonedIng->id);
    }

    // Verify cloned instructions belong to suggestion and have receipt_id = null
    $clonedInstructions = Instruction::where('suggestion_id', $suggestionId)->orderBy('step_number')->get();
    $this->assertCount(2, $clonedInstructions);
    foreach ($clonedInstructions as $clonedInst) {
        $this->assertNull($clonedInst->receipt_id);
        $this->assertEquals($suggestionId, $clonedInst->suggestion_id);
        $this->assertNotEquals($instruction1->id, $clonedInst->id);
        $this->assertNotEquals($instruction2->id, $clonedInst->id);
    }

    // Verify original recipe ingredients remain completely unchanged
    $originalIngredients = Ingredient::where('receipt_id', $receipt->receipt_id)->get();
    $this->assertCount(2, $originalIngredients);
    foreach ($originalIngredients as $origIng) {
        $this->assertNull($origIng->suggestion_id);
        $this->assertEquals($receipt->receipt_id, $origIng->receipt_id);
    }

    // Verify original recipe instructions remain completely unchanged
    $originalInstructions = Instruction::where('receipt_id', $receipt->receipt_id)->get();
    $this->assertCount(2, $originalInstructions);
    foreach ($originalInstructions as $origInst) {
        $this->assertNull($origInst->suggestion_id);
        $this->assertEquals($receipt->receipt_id, $origInst->receipt_id);
    }
});

test('5. suggestion creates empty snapshots when recipe has zero ingredients and zero instructions', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'New idea for recipe',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Suggestion created successfully',
            'suggestion' => [
                'user_id' => $user->user_id,
                'receipt_id' => $receipt->receipt_id,
                'text' => 'New idea for recipe',
                'isApproved' => false,
                'ingredients' => [],
                'instructions' => [],
            ],
        ]);
});

test('6. instructions are returned ordered by step_number', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();

    // Create instructions out of order
    Instruction::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'step_number' => 3,
        'instruction' => 'Step 3',
    ]);
    Instruction::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'step_number' => 1,
        'instruction' => 'Step 1',
    ]);
    Instruction::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'step_number' => 2,
        'instruction' => 'Step 2',
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'Suggestions for steps',
        ]);

    $response->assertStatus(201);
    $instructions = $response->json('suggestion.instructions');
    $this->assertCount(3, $instructions);
    $this->assertEquals(1, $instructions[0]['step_number']);
    $this->assertEquals('Step 1', $instructions[0]['instruction']);
    $this->assertEquals(2, $instructions[1]['step_number']);
    $this->assertEquals('Step 2', $instructions[1]['instruction']);
    $this->assertEquals(3, $instructions[2]['step_number']);
    $this->assertEquals('Step 3', $instructions[2]['instruction']);
});

test('7. suggestion user_id is derived from auth and client-provided user_id is ignored', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'Ignore client user_id',
            'user_id' => 9999, // Attempt to forge user_id
            'ingredients' => [['name' => 'Fake', 'quantity' => 1, 'unit' => 'pc']], // Client-provided ingredients ignored
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
    $this->assertDatabaseMissing('ingredients', [
        'name' => 'Fake',
    ]);
});

test('8. failed instruction cloning rolls back suggestion and ingredient creation in transaction', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();

    // Create an ingredient on receipt
    Ingredient::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Garlic',
    ]);

    // Create a valid instruction on receipt
    Instruction::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'step_number' => 1,
        'instruction' => 'Chop garlic',
    ]);

    // Listen to Instruction creating event and throw an exception during cloning
    Instruction::creating(function ($instruction) {
        if ($instruction->suggestion_id !== null) {
            throw new RuntimeException('Simulated failure during instruction snapshot cloning');
        }
    });

    $service = app(SuggestionService::class);

    try {
        $service->storeSuggestion(
            $user->user_id,
            $receipt->receipt_id,
            'Should rollback transaction'
        );
        $this->fail('Exception was not thrown.');
    } catch (RuntimeException $e) {
        $this->assertEquals('Simulated failure during instruction snapshot cloning', $e->getMessage());
    }

    // Assert that no suggestion or cloned ingredient rows were committed to DB
    $this->assertDatabaseMissing('suggestions', [
        'text' => 'Should rollback transaction',
    ]);

    $this->assertDatabaseMissing('ingredients', [
        'name' => 'Garlic',
        'receipt_id' => null,
    ]);
});
