<?php

use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

if (! function_exists('clearSanctumAuthState')) {
    /**
     * Helper to clear Laravel's in-memory authentication states in tests.
     */
    function clearSanctumAuthState(): void
    {
        auth()->forgetGuards();
        if (app()->bound('request')) {
            app('request')->setUserResolver(fn () => null);
        }
    }
}

test('like suggestion route requires authentication', function () {
    $response = $this->postJson('/api/like-suggestion/', [
        'suggestion_id' => 1,
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('like suggestion requires suggestion_id query parameter', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-suggestion/', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['suggestion_id'],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('like suggestion suggestion_id must be an integer', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-suggestion/', [
            'suggestion_id' => 'invalid',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['suggestion_id'],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('like suggestion returns 404 if suggestion does not exist', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-suggestion/', [
            'suggestion_id' => 9999,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Suggestion not found.',
        ]);
});

test('authenticated user can like a suggestion', function () {
    $user = User::factory()->create();
    $suggestion = Suggestion::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'suggestion_id',
            'is_liked',
            'likes_count',
        ])
        ->assertJson([
            'message' => 'Suggestion liked successfully',
            'suggestion_id' => $suggestion->id,
            'is_liked' => true,
            'likes_count' => 1,
        ]);

    $this->assertDatabaseHas('likes_suggestions', [
        'user_id' => $user->user_id,
        'suggestion_id' => $suggestion->id,
    ]);
});

test('like suggestion is idempotent', function () {
    $user = User::factory()->create();
    $suggestion = Suggestion::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // First like
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);
    $response->assertStatus(201);

    clearSanctumAuthState();

    // Second like (idempotent call)
    $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response2->assertStatus(201)
        ->assertJson([
            'message' => 'Suggestion liked successfully',
            'suggestion_id' => $suggestion->id,
            'is_liked' => true,
            'likes_count' => 1,
        ]);

    // Assert only one DB pivot row exists
    $count = DB::table('likes_suggestions')
        ->where('user_id', $user->user_id)
        ->where('suggestion_id', $suggestion->id)
        ->count();

    expect($count)->toBe(1);
});

test('user isolation and likes_count aggregation for suggestion likes', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $suggestion = Suggestion::factory()->create();

    $tokenA = $userA->createToken('token-a')->plainTextToken;
    $tokenB = $userB->createToken('token-b')->plainTextToken;

    // 1. User A likes the suggestion
    $responseA = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->postJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $responseA->assertStatus(201)
        ->assertJson([
            'suggestion_id' => $suggestion->id,
            'is_liked' => true,
            'likes_count' => 1,
        ]);

    clearSanctumAuthState();

    // Verify User B's state through listing (should show is_liked = false, likes_count = 1)
    $responseBListBefore = $this->withHeader('Authorization', 'Bearer '.$tokenB)
        ->getJson("/api/suggestions/?receipt_id={$suggestion->receipt_id}");
    $responseBListBefore->assertStatus(200)
        ->assertJsonPath('data.0.is_liked', false)
        ->assertJsonPath('data.0.likes_count', 1);

    clearSanctumAuthState();

    // 2. User B likes the suggestion
    $responseB = $this->withHeader('Authorization', 'Bearer '.$tokenB)
        ->postJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $responseB->assertStatus(201)
        ->assertJson([
            'suggestion_id' => $suggestion->id,
            'is_liked' => true,
            'likes_count' => 2,
        ]);

    clearSanctumAuthState();

    // Verify User A's state through listing (should show is_liked = true, likes_count = 2)
    $responseAListAfter = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->getJson("/api/suggestions/?receipt_id={$suggestion->receipt_id}");
    $responseAListAfter->assertStatus(200)
        ->assertJsonPath('data.0.is_liked', true)
        ->assertJsonPath('data.0.likes_count', 2);
});
