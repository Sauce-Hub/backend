<?php

use App\Enums\IngredientUnit;
use App\Enums\ReceiptCategory;
use App\Models\Ingredient;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('search engine endpoint requires the ai api key header', function () {
    $response = $this->postJson('/api/search-engine/', []);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthorized.',
        ]);
});

test('ai service can search for receipts by filter constraints', function () {
    config(['services.ai.api_key' => 'expected-ai-key']);

    $user = User::factory()->create([
        'name' => 'Ahmed',
        'email' => 'ahmed@example.com',
    ]);

    $matchingReceipt = Receipt::factory()->create([
        'user_id' => $user->user_id,
        'name' => 'Egg Breakfast Bowl',
        'caption' => 'Protein-rich breakfast',
        'category' => ReceiptCategory::BREAKFAST->value,
        'estimated_time' => 20,
        'Calories' => 300,
        'Fats' => 15,
        'Carbs' => 25,
        'Protein' => 20,
    ]);

    Ingredient::factory()->create([
        'receipt_id' => $matchingReceipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Egg',
        'quantity' => 2,
        'unit' => IngredientUnit::PIECE->value,
        'isAssigned' => false,
    ]);

    Ingredient::factory()->create([
        'receipt_id' => $matchingReceipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Oats',
        'quantity' => 40,
        'unit' => IngredientUnit::G->value,
        'isAssigned' => false,
    ]);

    $nonMatchingReceipt = Receipt::factory()->create([
        'user_id' => $user->user_id,
        'name' => 'Milk Toast',
        'caption' => 'Contains excluded ingredient',
        'category' => ReceiptCategory::BREAKFAST->value,
        'estimated_time' => 15,
        'Calories' => 250,
        'Fats' => 10,
        'Carbs' => 30,
        'Protein' => 12,
    ]);

    Ingredient::factory()->create([
        'receipt_id' => $nonMatchingReceipt->receipt_id,
        'suggestion_id' => null,
        'name' => 'Milk',
        'quantity' => 200,
        'unit' => IngredientUnit::ML->value,
        'isAssigned' => false,
    ]);

    $response = $this->withHeader('X-API-KEY', 'expected-ai-key')
        ->postJson('/api/search-engine/', [
            'include_ingredients' => ['egg'],
            'exclude_ingredients' => ['milk'],
            'category' => 'BREAKFAST',
            'max_estimated_time_min' => 20,
            'max_calories' => 400,
            'min_protein' => 10,
            'min_carbs' => null,
            'max_carbs' => null,
            'min_fats' => null,
            'max_fats' => null,
            'min_calories' => null,
            'max_protein' => null,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'success',
            'receipt' => [
                'receipt_id' => $matchingReceipt->receipt_id,
                'name' => 'Egg Breakfast Bowl',
                'caption' => 'Protein-rich breakfast',
                'category' => 'BREAKFAST',
                'estimated_time_min' => 20,
                'calories' => 300,
                'fats' => 15,
                'carbs' => 25,
                'protein' => 20,
                'user' => [
                    'user_id' => $user->user_id,
                    'name' => 'Ahmed',
                ],
            ],
        ])
        ->assertJsonPath('receipt.ingredients.0.name', 'Egg')
        ->assertJsonPath('receipt.ingredients.0.isAssigned', false)
        ->assertJsonPath('receipt.ingredients.1.name', 'Oats');
});