<?php

use App\Models\Ingredient;
use App\Models\Instruction;
use App\Models\Receipt;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\SuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('1. approve suggestion route requires authentication', function () {
    $response = $this->patchJson('/api/approve-suggestion/', [
        'suggestion_id' => 1,
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('2. approve suggestion validates missing and invalid suggestion_id', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Missing suggestion_id
    $response1 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', []);

    $response1->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['suggestion_id'],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);

    // Non-integer suggestion_id
    $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => 'not-an-integer',
        ]);

    $response2->assertStatus(422)
        ->assertJsonValidationErrors(['suggestion_id']);
});

test('3. approve suggestion returns 404 when suggestion does not exist', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => 9999,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Suggestion not found.',
        ]);
});

test('4. approve suggestion returns 403 when user is not the receipt owner', function () {
    $author = User::factory()->create();
    $receiptOwner = User::factory()->create();
    $receipt = Receipt::factory()->create(['user_id' => $receiptOwner->user_id]);

    $suggestion = Suggestion::factory()->create([
        'user_id' => $author->user_id,
        'receipt_id' => $receipt->receipt_id,
        'isApproved' => false,
    ]);

    // Author tries to approve own suggestion without owning receipt -> 403
    $authorToken = $author->createToken('author-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$authorToken)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'You are not allowed to approve this suggestion.',
        ]);

    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'isApproved' => false,
    ]);
});

test('5. receipt owner successfully approves suggestion and response matches exact contract shape', function () {
    $author = User::factory()->create();
    $receiptOwner = User::factory()->create();
    $receipt = Receipt::factory()->create(['user_id' => $receiptOwner->user_id]);

    $suggestion = Suggestion::factory()->create([
        'user_id' => $author->user_id,
        'receipt_id' => $receipt->receipt_id,
        'isApproved' => false,
    ]);

    $token = $receiptOwner->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'suggestion' => [
                'id',
                'receipt_id',
                'isApproved',
            ],
        ])
        ->assertJson([
            'message' => 'Suggestion approved successfully',
            'suggestion' => [
                'id' => $suggestion->id,
                'receipt_id' => $receipt->receipt_id,
                'isApproved' => true,
            ],
        ]);

    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'isApproved' => true,
    ]);
});

test('6. approving suggestion replaces receipt ingredients and instructions with suggestion snapshot', function () {
    $author = User::factory()->create();
    $receiptOwner = User::factory()->create();
    $receipt = Receipt::factory()->create(['user_id' => $receiptOwner->user_id]);

    // Original receipt ingredients (should be deleted and replaced)
    $oldRecipeIngredient = Ingredient::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Old Recipe Onion',
        'quantity' => 100.0,
        'unit' => 'g',
        'isAssigned' => false,
    ]);

    // Original receipt instructions (should be deleted and replaced)
    $oldRecipeInstruction = Instruction::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'step_number' => 1,
        'instruction' => 'Old Recipe Step: Chop onion',
    ]);

    // Suggestion with new ingredients and instructions snapshot
    $suggestion = Suggestion::factory()->create([
        'user_id' => $author->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Upgrade recipe with garlic and rosemary',
        'isApproved' => false,
    ]);

    $suggIngredient1 = Ingredient::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'name' => 'Garlic',
        'quantity' => 3.0,
        'unit' => 'cloves',
        'isAssigned' => false,
    ]);

    $suggIngredient2 = Ingredient::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'name' => 'Fresh Rosemary',
        'quantity' => 1.5,
        'unit' => 'sprigs',
        'isAssigned' => true,
    ]);

    $suggInstruction2 = Instruction::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'step_number' => 2,
        'instruction' => 'Add fresh rosemary and simmer.',
    ]);

    $suggInstruction1 = Instruction::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'step_number' => 1,
        'instruction' => 'Sauté garlic in olive oil.',
    ]);

    $token = $receiptOwner->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(200);

    // Old receipt items are gone
    $this->assertDatabaseMissing('ingredients', ['id' => $oldRecipeIngredient->id]);
    $this->assertDatabaseMissing('instructions', ['id' => $oldRecipeInstruction->id]);

    // New recipe ingredients are created with receipt_id = target receipt and suggestion_id = null
    $receiptIngredients = Ingredient::where('receipt_id', $receipt->receipt_id)->get();
    $this->assertCount(2, $receiptIngredients);
    foreach ($receiptIngredients as $rIng) {
        $this->assertEquals($receipt->receipt_id, $rIng->receipt_id);
        $this->assertNull($rIng->suggestion_id);
        $this->assertNotEquals($suggIngredient1->id, $rIng->id);
        $this->assertNotEquals($suggIngredient2->id, $rIng->id);
    }
    $this->assertDatabaseHas('ingredients', [
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Garlic',
        'quantity' => 3.0,
        'unit' => 'cloves',
        'isAssigned' => false,
    ]);
    $this->assertDatabaseHas('ingredients', [
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Fresh Rosemary',
        'quantity' => 1.5,
        'unit' => 'sprigs',
        'isAssigned' => true,
    ]);

    // New recipe instructions are created with receipt_id = target receipt, suggestion_id = null, ordered step_number ASC
    $receiptInstructions = Instruction::where('receipt_id', $receipt->receipt_id)->orderBy('step_number')->get();
    $this->assertCount(2, $receiptInstructions);
    $this->assertEquals(1, $receiptInstructions[0]->step_number);
    $this->assertEquals('Sauté garlic in olive oil.', $receiptInstructions[0]->instruction);
    $this->assertNull($receiptInstructions[0]->suggestion_id);
    $this->assertEquals($receipt->receipt_id, $receiptInstructions[0]->receipt_id);

    $this->assertEquals(2, $receiptInstructions[1]->step_number);
    $this->assertEquals('Add fresh rosemary and simmer.', $receiptInstructions[1]->instruction);
    $this->assertNull($receiptInstructions[1]->suggestion_id);
    $this->assertEquals($receipt->receipt_id, $receiptInstructions[1]->receipt_id);

    // Suggestion snapshot items remain intact and preserved for history
    $this->assertDatabaseHas('ingredients', [
        'id' => $suggIngredient1->id,
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'name' => 'Garlic',
    ]);
    $this->assertDatabaseHas('ingredients', [
        'id' => $suggIngredient2->id,
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'name' => 'Fresh Rosemary',
    ]);
    $this->assertDatabaseHas('instructions', [
        'id' => $suggInstruction1->id,
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'instruction' => 'Sauté garlic in olive oil.',
    ]);
    $this->assertDatabaseHas('instructions', [
        'id' => $suggInstruction2->id,
        'suggestion_id' => $suggestion->id,
        'receipt_id' => null,
        'instruction' => 'Add fresh rosemary and simmer.',
    ]);

    // Suggestion is approved
    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'isApproved' => true,
    ]);
});

test('7. approving suggestion with empty snapshot clears recipe ingredients and instructions', function () {
    $author = User::factory()->create();
    $receiptOwner = User::factory()->create();
    $receipt = Receipt::factory()->create(['user_id' => $receiptOwner->user_id]);

    // Original receipt has items
    Ingredient::factory()->create(['receipt_id' => $receipt->receipt_id, 'suggestion_id' => null]);
    Instruction::factory()->create(['receipt_id' => $receipt->receipt_id, 'suggestion_id' => null, 'step_number' => 1]);

    // Suggestion has empty snapshot (0 ingredients, 0 instructions)
    $suggestion = Suggestion::factory()->create([
        'user_id' => $author->user_id,
        'receipt_id' => $receipt->receipt_id,
        'isApproved' => false,
    ]);

    $token = $receiptOwner->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(200);

    // Assert recipe now has 0 ingredients and 0 instructions
    $this->assertDatabaseMissing('ingredients', ['receipt_id' => $receipt->receipt_id]);
    $this->assertDatabaseMissing('instructions', ['receipt_id' => $receipt->receipt_id]);
});

test('8. failed recipe replacement rolls back transaction atomically preserving recipe and suggestion state', function () {
    $author = User::factory()->create();
    $receiptOwner = User::factory()->create();
    $receipt = Receipt::factory()->create(['user_id' => $receiptOwner->user_id]);

    $originalRecipeIngredient = Ingredient::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Original persistent recipe ingredient',
    ]);

    $originalRecipeInstruction = Instruction::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'suggestion_id' => null,
        'step_number' => 1,
        'instruction' => 'Original persistent recipe instruction',
    ]);

    $suggestion = Suggestion::factory()->create([
        'user_id' => $author->user_id,
        'receipt_id' => $receipt->receipt_id,
        'isApproved' => false,
    ]);

    Ingredient::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'name' => 'Suggestion ingredient to copy',
    ]);

    Instruction::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
        'step_number' => 1,
        'instruction' => 'Trigger failure during recipe copy',
    ]);

    // Force an exception during Instruction creating event when receipt_id is set
    Instruction::creating(function ($instruction) {
        if ($instruction->instruction === 'Trigger failure during recipe copy' && $instruction->receipt_id !== null) {
            throw new RuntimeException('Simulated database failure during recipe instruction creation');
        }
    });

    $service = app(SuggestionService::class);

    try {
        $service->approveSuggestion($suggestion->id);
        $this->fail('Exception was not thrown.');
    } catch (RuntimeException $e) {
        $this->assertEquals('Simulated database failure during recipe instruction creation', $e->getMessage());
    }

    // Verify original recipe items and suggestion state are completely untouched
    $this->assertDatabaseHas('ingredients', [
        'id' => $originalRecipeIngredient->id,
        'receipt_id' => $receipt->receipt_id,
        'name' => 'Original persistent recipe ingredient',
    ]);
    $this->assertDatabaseHas('instructions', [
        'id' => $originalRecipeInstruction->id,
        'receipt_id' => $receipt->receipt_id,
        'instruction' => 'Original persistent recipe instruction',
    ]);
    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'isApproved' => false,
    ]);
});

test('9. approval of one receipt suggestion does not affect another receipt or its items', function () {
    $receiptOwner = User::factory()->create();
    $otherOwner = User::factory()->create();

    $receiptA = Receipt::factory()->create(['user_id' => $receiptOwner->user_id]);
    $receiptB = Receipt::factory()->create(['user_id' => $otherOwner->user_id]);

    $ingredientB = Ingredient::factory()->create([
        'receipt_id' => $receiptB->receipt_id,
        'suggestion_id' => null,
        'name' => 'Untouched Recipe B Ingredient',
    ]);
    $instructionB = Instruction::factory()->create([
        'receipt_id' => $receiptB->receipt_id,
        'suggestion_id' => null,
        'step_number' => 1,
        'instruction' => 'Untouched Recipe B Instruction',
    ]);

    $suggestionA = Suggestion::factory()->create([
        'user_id' => $receiptOwner->user_id,
        'receipt_id' => $receiptA->receipt_id,
        'isApproved' => false,
    ]);
    Ingredient::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestionA->id,
        'name' => 'New Ingredient for A',
    ]);

    $token = $receiptOwner->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $suggestionA->id,
        ]);

    $response->assertStatus(200);

    // Verify Recipe B remains untouched
    $this->assertDatabaseHas('ingredients', [
        'id' => $ingredientB->id,
        'receipt_id' => $receiptB->receipt_id,
        'name' => 'Untouched Recipe B Ingredient',
    ]);
    $this->assertDatabaseHas('instructions', [
        'id' => $instructionB->id,
        'receipt_id' => $receiptB->receipt_id,
        'instruction' => 'Untouched Recipe B Instruction',
    ]);
});
