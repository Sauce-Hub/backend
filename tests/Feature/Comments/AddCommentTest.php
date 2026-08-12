<?php

use App\Models\Comment;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('add comment route requires authentication', function () {
    $response = $this->postJson('/api/comment/', [
        'receipt_id' => 1,
        'text' => 'Delicious!',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('add comment returns 422 for missing required fields', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Missing text
    $response1 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/comment/', [
            'receipt_id' => 1,
        ]);

    $response1->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['text'],
        ]);

    // Missing receipt_id
    $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/comment/', [
            'text' => 'Good recipe!',
        ]);

    $response2->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['receipt_id'],
        ]);
});

test('add comment returns 422 when receipt_id is not an integer', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/comment/', [
            'receipt_id' => 'not-an-integer',
            'text' => 'Good recipe!',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['receipt_id'],
        ]);
});

test('add comment returns 422 when text length exceeds 1000 characters', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $longText = str_repeat('a', 1001);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/comment/', [
            'receipt_id' => 1,
            'text' => $longText,
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['text'],
        ]);
});

test('add comment returns 404 when receipt does not exist', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/comment/', [
            'receipt_id' => 9999,
            'text' => 'Amazing recipe!',
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Receipt not found.',
        ]);
});

test('authenticated user can add comment successfully', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/comment/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'Amazing recipe!',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'comment' => [
                'id',
                'user_id',
                'receipt_id',
                'text',
                'timestamp',
            ],
        ])
        ->assertJson([
            'message' => 'Comment added successfully',
            'comment' => [
                'user_id' => $user->user_id,
                'receipt_id' => $receipt->receipt_id,
                'text' => 'Amazing recipe!',
            ],
        ]);

    // Check database state
    $this->assertDatabaseHas('comments', [
        'user_id' => $user->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Amazing recipe!',
    ]);

    // Verify timestamp was manually populated and is formatted correctly
    $comment = Comment::where('user_id', $user->user_id)
        ->where('receipt_id', $receipt->receipt_id)
        ->first();

    $this->assertNotNull($comment->timestamp);

    // Assert response contains correct timestamp formatting
    $responseJson = $response->json();
    $this->assertEquals($comment->timestamp->toIso8601ZuluString(), $responseJson['comment']['timestamp']);
});
