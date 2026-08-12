<?php

use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

if (!function_exists('clearSanctumAuthState')) {
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

test('unlike suggestion route requires authentication', function () {
    $response = $this->deleteJson('/api/like-suggestion/', [
        'suggestion_id' => 1,
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('unlike suggestion requires suggestion_id', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-suggestion/', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['suggestion_id'],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('unlike suggestion requires integer suggestion_id', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => 'invalid-id',
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

test('unlike suggestion returns 404 when suggestion does not exist', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => 9999,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Suggestion not found.',
        ]);
});

test('unlike suggestion returns 404 when user has not liked the suggestion', function () {
    $user = User::factory()->create();
    $suggestion = Suggestion::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Suggestion like not found.',
        ]);
});

test('authenticated user can unlike a liked suggestion successfully', function () {
    $user = User::factory()->create();
    $suggestion = Suggestion::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Attach initial like
    $user->likedSuggestions()->attach($suggestion->id);
    $this->assertEquals(1, $suggestion->likes()->count());

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Suggestion unliked successfully',
            'suggestion_id' => $suggestion->id,
            'is_liked' => false,
            'likes_count' => 0,
        ]);

    // Verify database state (pivot table entry removed)
    $this->assertDatabaseMissing('likes_suggestions', [
        'user_id' => $user->user_id,
        'suggestion_id' => $suggestion->id,
    ]);

    // Verify count in DB
    $this->assertEquals(0, $suggestion->likes()->count());
});

test('repeated unlike returns 404 suggestion like not found', function () {
    $user = User::factory()->create();
    $suggestion = Suggestion::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Attach initial like
    $user->likedSuggestions()->attach($suggestion->id);

    // First unlike (successful)
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ])->assertStatus(200);

    // Second unlike (should fail with 404 suggestion like not found)
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Suggestion like not found.',
        ]);
});

test('unliking a suggestion does not affect other users likes', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $suggestion = Suggestion::factory()->create();

    // Both users like the suggestion
    $userA->likedSuggestions()->attach($suggestion->id);
    $userB->likedSuggestions()->attach($suggestion->id);
    $this->assertEquals(2, $suggestion->likes()->count());

    $tokenA = $userA->createToken('token-a')->plainTextToken;

    // User A unlikes the suggestion
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Suggestion unliked successfully',
            'suggestion_id' => $suggestion->id,
            'is_liked' => false,
            'likes_count' => 1,
        ]);

    // Verify database state: User A like is gone, User B like remains
    $this->assertDatabaseMissing('likes_suggestions', [
        'user_id' => $userA->user_id,
        'suggestion_id' => $suggestion->id,
    ]);
    $this->assertDatabaseHas('likes_suggestions', [
        'user_id' => $userB->user_id,
        'suggestion_id' => $suggestion->id,
    ]);

    // Verify count in DB is 1
    $this->assertEquals(1, $suggestion->likes()->count());
});
