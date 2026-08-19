<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('authenticated_user_can_create_receipt_and_fetch_details_by_receipt_id', function () {
    Http::fake([
        '*' => Http::response([
            'estimated_time' => 25,
            'Calories' => 500,
            'Fats' => 15,
            'Carbs' => 60,
            'Protein' => 18,
        ], 200),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/new-post/', [
            'receipt' => [
                'name' => 'Pasta',
                'caption' => 'Quick pasta',
                'category' => 'DINNER',
                'image' => UploadedFile::fake()->image('receipt.png'),
            ],
            'ingredients' => [
                ['name' => 'Pasta', 'quantity' => 200, 'unit' => 'g'],
                ['name' => 'Tomato', 'quantity' => 50, 'unit' => 'g'],
            ],
            'instructions' => [
                'Boil the pasta.',
                'Add tomato sauce.',
            ],
        ]);

    $response->assertStatus(201);

    $receipt = \App\Models\Receipt::first();
    expect($receipt)->not->toBeNull();
    expect($receipt->image_url)->not->toBeNull();
    expect($receipt->user_id)->toBe($user->user_id);

    $details = $this->actingAs($user, 'sanctum')
        ->getJson('/api/receipt-details/?receipt_id=' . $receipt->receipt_id);

    $details->assertStatus(200);
    $details->assertJsonPath('receipt.receipt_id', $receipt->receipt_id);
    $details->assertJsonPath('user.user_id', $user->user_id);
    $details->assertJsonPath('user.name', $user->name);
    $details->assertJsonPath('ingredients.0.name', 'Pasta');
    $details->assertJsonPath('instructions.0', 'Boil the pasta.');
});
