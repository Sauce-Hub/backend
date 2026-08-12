<?php

use App\Models\Comment;
use App\Models\Receipt;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper to clear Laravel's in-memory authentication states in tests.
 */
function resetSanctumAuthState(): void
{
    auth()->forgetGuards();
    if (app()->bound('request')) {
        app('request')->setUserResolver(fn () => null);
    }
}

test('profile endpoint returns authenticated user identity and ignores client-provided user_id', function () {
    resetSanctumAuthState();

    $userA = User::factory()->create(['name' => 'User A', 'email' => 'usera@example.com']);
    $userB = User::factory()->create(['name' => 'User B', 'email' => 'userb@example.com']);

    $tokenA = $userA->createToken('token-a')->plainTextToken;

    // Request profile as User A, attempting to forge user_id of User B in query/body
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->getJson('/api/profile/?user_id='.$userB->user_id);

    $response->assertStatus(200)
        ->assertJson([
            'user_id' => $userA->user_id,
            'name' => 'User A',
            'email' => 'usera@example.com',
        ])
        ->assertJsonMissing([
            'user_id' => $userB->user_id,
            'email' => 'userb@example.com',
        ]);
});

test('logout revokes current user token without affecting another user active tokens', function () {
    resetSanctumAuthState();

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $tokenA = $userA->createToken('token-a')->plainTextToken;
    $tokenB = $userB->createToken('token-b')->plainTextToken;

    // User A logs out
    $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->deleteJson('/api/logout/');

    $logoutResponse->assertStatus(204);

    // Reset in-memory guard cache so Sanctum resolves tokenA from DB (where it was deleted)
    resetSanctumAuthState();

    // User A token should now be invalid
    $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->getJson('/api/profile/')
        ->assertStatus(401);

    resetSanctumAuthState();

    // User B token must remain valid
    $this->withHeader('Authorization', 'Bearer '.$tokenB)
        ->getJson('/api/profile/')
        ->assertStatus(200)
        ->assertJson(['user_id' => $userB->user_id]);
});

test('user cannot create comment on behalf of another user via request body', function () {
    resetSanctumAuthState();

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $receipt = Receipt::factory()->create();

    $tokenA = $userA->createToken('token-a')->plainTextToken;

    // User A posts comment attempting to set user_id to User B
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->postJson('/api/comment/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'Comment by A attempting B identity',
            'user_id' => $userB->user_id,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'comment' => [
                'user_id' => $userA->user_id,
                'receipt_id' => $receipt->receipt_id,
                'text' => 'Comment by A attempting B identity',
            ],
        ]);

    // DB verify comment belongs to User A
    $this->assertDatabaseHas('comments', [
        'user_id' => $userA->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Comment by A attempting B identity',
    ]);

    $this->assertDatabaseMissing('comments', [
        'user_id' => $userB->user_id,
        'text' => 'Comment by A attempting B identity',
    ]);
});

test('user cannot remove another user comment like', function () {
    resetSanctumAuthState();

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $comment = Comment::factory()->create();

    // User A likes comment
    $userA->likedComments()->attach($comment->id);

    $tokenB = $userB->createToken('token-b')->plainTextToken;

    resetSanctumAuthState();

    // User B attempts to unlike User A's like
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenB)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => $comment->id,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Comment like not found.',
        ]);

    // Verify User A's like remains intact in database
    $this->assertDatabaseHas('likes_comments', [
        'user_id' => $userA->user_id,
        'comment_id' => $comment->id,
    ]);
});

test('users have independent comment likes', function () {
    resetSanctumAuthState();

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $comment = Comment::factory()->create();

    $tokenA = $userA->createToken('token-a')->plainTextToken;
    $tokenB = $userB->createToken('token-b')->plainTextToken;

    // User A likes comment
    resetSanctumAuthState();
    $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->postJson('/api/like-comment/', ['comment_id' => $comment->id])
        ->assertStatus(201);

    // User B likes comment
    resetSanctumAuthState();
    $this->withHeader('Authorization', 'Bearer '.$tokenB)
        ->postJson('/api/like-comment/', ['comment_id' => $comment->id])
        ->assertStatus(201);

    $this->assertDatabaseHas('likes_comments', ['user_id' => $userA->user_id, 'comment_id' => $comment->id]);
    $this->assertDatabaseHas('likes_comments', ['user_id' => $userB->user_id, 'comment_id' => $comment->id]);

    // User A unlikes
    resetSanctumAuthState();
    $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->deleteJson('/api/like-comment/', ['comment_id' => $comment->id])
        ->assertStatus(200);

    // User A's like is removed, User B's like remains
    $this->assertDatabaseMissing('likes_comments', ['user_id' => $userA->user_id, 'comment_id' => $comment->id]);
    $this->assertDatabaseHas('likes_comments', ['user_id' => $userB->user_id, 'comment_id' => $comment->id]);
});

test('user cannot create suggestion on behalf of another user via request body', function () {
    resetSanctumAuthState();

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $receipt = Receipt::factory()->create();

    $tokenA = $userA->createToken('token-a')->plainTextToken;

    // User A posts suggestion attempting to set user_id to User B
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->postJson('/api/suggestion/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'Suggestion by A attempting B identity',
            'user_id' => $userB->user_id,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'suggestion' => [
                'user_id' => $userA->user_id,
                'receipt_id' => $receipt->receipt_id,
                'text' => 'Suggestion by A attempting B identity',
            ],
        ]);

    $this->assertDatabaseHas('suggestions', [
        'user_id' => $userA->user_id,
        'receipt_id' => $receipt->receipt_id,
        'text' => 'Suggestion by A attempting B identity',
    ]);

    $this->assertDatabaseMissing('suggestions', [
        'user_id' => $userB->user_id,
        'text' => 'Suggestion by A attempting B identity',
    ]);
});

test('user cannot remove another user suggestion like', function () {
    resetSanctumAuthState();

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $suggestion = Suggestion::factory()->create();

    // User A likes suggestion
    $userA->likedSuggestions()->attach($suggestion->id);

    $tokenB = $userB->createToken('token-b')->plainTextToken;

    resetSanctumAuthState();

    // User B attempts to unlike User A's like
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenB)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Suggestion like not found.',
        ]);

    // Verify User A's like remains intact in database
    $this->assertDatabaseHas('likes_suggestions', [
        'user_id' => $userA->user_id,
        'suggestion_id' => $suggestion->id,
    ]);
});

test('users have independent suggestion likes', function () {
    resetSanctumAuthState();

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $suggestion = Suggestion::factory()->create();

    $tokenA = $userA->createToken('token-a')->plainTextToken;
    $tokenB = $userB->createToken('token-b')->plainTextToken;

    // User A likes suggestion
    resetSanctumAuthState();
    $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->postJson('/api/like-suggestion/', ['suggestion_id' => $suggestion->id])
        ->assertStatus(201);

    // User B likes suggestion
    resetSanctumAuthState();
    $this->withHeader('Authorization', 'Bearer '.$tokenB)
        ->postJson('/api/like-suggestion/', ['suggestion_id' => $suggestion->id])
        ->assertStatus(201);

    $this->assertDatabaseHas('likes_suggestions', ['user_id' => $userA->user_id, 'suggestion_id' => $suggestion->id]);
    $this->assertDatabaseHas('likes_suggestions', ['user_id' => $userB->user_id, 'suggestion_id' => $suggestion->id]);

    // User A unlikes
    resetSanctumAuthState();
    $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->deleteJson('/api/like-suggestion/', ['suggestion_id' => $suggestion->id])
        ->assertStatus(200);

    // User A's like is removed, User B's like remains
    $this->assertDatabaseMissing('likes_suggestions', ['user_id' => $userA->user_id, 'suggestion_id' => $suggestion->id]);
    $this->assertDatabaseHas('likes_suggestions', ['user_id' => $userB->user_id, 'suggestion_id' => $suggestion->id]);
});

test('unauthorized user cannot approve suggestion for another users receipt', function () {
    resetSanctumAuthState();

    $receiptOwner = User::factory()->create();
    $unauthorizedUser = User::factory()->create();
    $suggestionCreator = User::factory()->create();

    $receipt = Receipt::factory()->create(['user_id' => $receiptOwner->user_id]);
    $suggestion = Suggestion::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'user_id' => $suggestionCreator->user_id,
        'isApproved' => false,
    ]);

    $tokenUnauthorized = $unauthorizedUser->createToken('token-unauth')->plainTextToken;

    resetSanctumAuthState();

    // Unauthorized user attempts approval
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenUnauthorized)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'You are not allowed to approve this suggestion.',
        ]);

    // Verify database state remains unapproved
    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'isApproved' => false,
    ]);
});

test('receipt owner can approve suggestion created by another user', function () {
    resetSanctumAuthState();

    $receiptOwner = User::factory()->create();
    $suggestionCreator = User::factory()->create();

    $receipt = Receipt::factory()->create(['user_id' => $receiptOwner->user_id]);
    $suggestion = Suggestion::factory()->create([
        'receipt_id' => $receipt->receipt_id,
        'user_id' => $suggestionCreator->user_id,
        'isApproved' => false,
    ]);

    $tokenOwner = $receiptOwner->createToken('token-owner')->plainTextToken;

    resetSanctumAuthState();

    // Receipt owner approves suggestion
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenOwner)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(200)
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
