<?php

use App\Models\Comment;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('view comments route requires authentication', function () {
    $response = $this->getJson('/api/comments/?receipt_id=1');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('view comments requires receipt_id query parameter', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/comments/');

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['receipt_id'],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('view comments receipt_id must be an integer', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/comments/?receipt_id=invalid');

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['receipt_id'],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('view comments returns 404 if receipt does not exist', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/comments/?receipt_id=9999');

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Receipt not found.',
        ]);
});

test('authenticated user can view comments with correct payload structure', function () {
    $user = User::factory()->create(['name' => 'Ahmed']);
    $receipt = Receipt::factory()->create();
    
    // Create comments for the receipt
    $comment = Comment::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'user_id' => $user->user_id,
        'text' => 'Looks delicious!',
        'timestamp' => now()->startOfSecond(),
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/comments/?receipt_id={$receipt->receipt_id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'text',
                    'timestamp',
                    'user' => [
                        'user_id',
                        'name',
                    ],
                    'likes_count',
                    'is_liked',
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
                    'id' => $comment->id,
                    'text' => 'Looks delicious!',
                    'timestamp' => $comment->timestamp->toIso8601ZuluString(),
                    'user' => [
                        'user_id' => $user->user_id,
                        'name' => 'Ahmed',
                    ],
                    'likes_count' => 0,
                    'is_liked' => false,
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

test('view comments respects pagination parameters', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();
    
    // Create 5 comments
    Comment::factory()->count(5)->create([
        'receipt_id' => $receipt->receipt_id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    // Get page 1 with 2 comments per page
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/comments/?receipt_id={$receipt->receipt_id}&page=1&per_page=2");

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['data'])->toHaveCount(2);
    expect($data['meta'])->toBe([
        'current_page' => 1,
        'per_page' => 2,
        'total' => 5,
        'last_page' => 3,
    ]);

    // Get page 3 with 2 comments per page (should have 1 comment)
    $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/comments/?receipt_id={$receipt->receipt_id}&page=3&per_page=2");

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

test('view comments returns correct likes_count and is_liked true if current user liked it', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();
    
    $comment = Comment::factory()->create([
        'receipt_id' => $receipt->receipt_id,
    ]);

    // User A likes the comment
    $user->likedComments()->attach($comment->id);

    $token = $user->createToken('test-token-a')->plainTextToken;

    // Request from User -> should show likes_count = 1 and is_liked = true
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/comments/?receipt_id={$receipt->receipt_id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.0.likes_count', 1)
        ->assertJsonPath('data.0.is_liked', true);
});

test('view comments returns correct likes_count and is_liked false if current user did not like it', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $receipt = Receipt::factory()->create();
    
    $comment = Comment::factory()->create([
        'receipt_id' => $receipt->receipt_id,
    ]);

    // User A likes the comment
    $userA->likedComments()->attach($comment->id);

    $tokenB = $userB->createToken('test-token-b')->plainTextToken;

    // Request from User B -> should show likes_count = 1 and is_liked = false
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenB)
        ->getJson("/api/comments/?receipt_id={$receipt->receipt_id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.0.likes_count', 1)
        ->assertJsonPath('data.0.is_liked', false);
});
