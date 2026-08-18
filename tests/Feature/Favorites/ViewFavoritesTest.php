<?php

use App\Models\Comment;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('favorites route requires authentication', function () {
    $response = $this->getJson('/api/favorites/');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('add favorite requires valid receipt_id', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/add-favorite/', [
            'receipt_id' => 9999,
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['receipt_id'],
        ]);
});

test('authenticated user can add and remove a favorite receipt', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $add = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/add-favorite/', [
            'receipt_id' => $receipt->receipt_id,
        ]);

    $add->assertStatus(201)
        ->assertJson([
            'message' => 'success',
            'is_favorited' => true,
        ]);

    $user->refresh();
    expect($user->favorites()->where('receipts.receipt_id', $receipt->receipt_id)->exists())->toBeTrue();

    $remove = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/remove-favorite/', [
            'receipt_id' => $receipt->receipt_id,
        ]);

    $remove->assertStatus(200)
        ->assertJson([
            'message' => 'success',
            'is_favorited' => false,
        ]);

    $user->refresh();
    expect($user->favorites()->where('receipts.receipt_id', $receipt->receipt_id)->exists())->toBeFalse();
});

test('authenticated user can view favorites with correct payload structure', function () {
    $user = User::factory()->create(['name' => 'Ahmed']);
    $other = User::factory()->create();

    $receipt1 = Receipt::factory()->create([
        'name' => 'Pasta',
        'caption' => 'Quick pasta',
        'category' => 'DINNER',
        'timestamp' => now()->startOfSecond(),
    ]);

    $receipt2 = Receipt::factory()->create([
        'name' => 'Pizza',
        'caption' => 'The best one I got just for you',
        'category' => 'LUNCH',
        'timestamp' => now()->startOfSecond(),
    ]);

    // User favorites both receipts
    $user->favorites()->attach([$receipt1->receipt_id, $receipt2->receipt_id]);

    // Add likes: receipt2 liked by user (so is_liked true for user)
    $user->likedReceipts()->attach($receipt2->receipt_id);

    // Add some other likes to increment counts
    $other->likedReceipts()->attach($receipt1->receipt_id);
    $other->likedReceipts()->attach($receipt2->receipt_id);

    // Add comments
    Comment::factory()->create([
        'receipt_id' => $receipt1->receipt_id,
    ]);
    Comment::factory()->count(2)->create([
        'receipt_id' => $receipt2->receipt_id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/favorites/');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'receipt_id',
                    'name',
                    'caption',
                    'category',
                    'timestamp',
                    'user' => [
                        'user_id',
                        'name',
                    ],
                    'likes_count',
                    'comments_count',
                    'favorites_count',
                    'is_liked',
                    'is_favorited',
                ],
            ],
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
            ],
        ]);

    $json = $response->json();

    // Find items by receipt_id
    $item1 = collect($json['data'])->firstWhere('receipt_id', $receipt1->receipt_id);
    $item2 = collect($json['data'])->firstWhere('receipt_id', $receipt2->receipt_id);

    expect($item1)->not->toBeNull();
    expect($item2)->not->toBeNull();

    expect($item1['name'])->toBe('Pasta');
    expect($item1['is_favorited'])->toBeTrue();
    expect($item1['is_liked'])->toBeFalse();
    expect($item1['comments_count'])->toBe(1);

    expect($item2['name'])->toBe('Pizza');
    expect($item2['is_favorited'])->toBeTrue();
    expect($item2['is_liked'])->toBeTrue();
    expect($item2['comments_count'])->toBe(2);
});

test('favorites respects pagination parameters', function () {
    $user = User::factory()->create();

    // Create 5 receipts and favorite them
    $receipts = Receipt::factory()->count(5)->create();
    $user->favorites()->attach($receipts->pluck('receipt_id')->toArray());

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/favorites/?page=1&per_page=2');

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['data'])->toHaveCount(2);
    expect($data['meta'])->toBe([
        'current_page' => 1,
        'per_page' => 2,
        'total' => 5,
        'last_page' => 3,
    ]);
});
