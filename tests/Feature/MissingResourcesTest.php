<?php

use App\Models\Comment;
use App\Models\Ingredient;
use App\Models\Receipt;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper to clear Laravel's in-memory authentication states in tests.
 */
function resetSanctumAuth(): void
{
    auth()->forgetGuards();
    if (app()->bound('request')) {
        app('request')->setUserResolver(fn () => null);
    }
}

// --------------------------------------------------------------------------
// 1. GET /api/comments/ (receipt_id)
// --------------------------------------------------------------------------

test('GET /api/comments/ handles valid, nonexistent, malformed, and deleted receipt_id', function () {
    resetSanctumAuth();
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // A. Valid ID
    $receipt = Receipt::factory()->create();
    Comment::factory()->create(['receipt_id' => $receipt->receipt_id]);

    resetSanctumAuth();
    $resValid = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/comments/?receipt_id='.$receipt->receipt_id);
    $resValid->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);

    // B. Nonexistent ID
    resetSanctumAuth();
    $resNonexistent = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/comments/?receipt_id=999999');
    $resNonexistent->assertStatus(404)
        ->assertJson(['message' => 'Receipt not found.']);

    // C. Malformed ID
    resetSanctumAuth();
    $resMalformed = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/comments/?receipt_id=malformed-string');
    $resMalformed->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['receipt_id']]);
    $this->assertStringNotContainsString('SQLSTATE', $resMalformed->getContent());

    // D. Deleted Resource
    $deletedReceiptId = $receipt->receipt_id;
    $receipt->delete();

    resetSanctumAuth();
    $resDeleted = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/comments/?receipt_id='.$deletedReceiptId);
    $resDeleted->assertStatus(404)
        ->assertJson(['message' => 'Receipt not found.']);
});

// --------------------------------------------------------------------------
// 2. POST /api/comment/ (receipt_id)
// --------------------------------------------------------------------------

test('POST /api/comment/ handles valid, nonexistent, malformed, and deleted receipt_id', function () {
    resetSanctumAuth();
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // A. Valid ID
    $receipt = Receipt::factory()->create();

    resetSanctumAuth();
    $resValid = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/comment/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'Great recipe!',
        ]);
    $resValid->assertStatus(201)
        ->assertJsonStructure(['message', 'comment']);

    // B. Nonexistent ID
    resetSanctumAuth();
    $resNonexistent = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/comment/', [
            'receipt_id' => 999999,
            'text' => 'Great recipe!',
        ]);
    $resNonexistent->assertStatus(404)
        ->assertJson(['message' => 'Receipt not found.']);

    // C. Malformed ID
    resetSanctumAuth();
    $resMalformed = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/comment/', [
            'receipt_id' => 'invalid-id',
            'text' => 'Great recipe!',
        ]);
    $resMalformed->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['receipt_id']]);
    $this->assertStringNotContainsString('SQLSTATE', $resMalformed->getContent());

    // D. Deleted Resource
    $deletedReceiptId = $receipt->receipt_id;
    $receipt->delete();

    resetSanctumAuth();
    $resDeleted = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/comment/', [
            'receipt_id' => $deletedReceiptId,
            'text' => 'Great recipe!',
        ]);
    $resDeleted->assertStatus(404)
        ->assertJson(['message' => 'Receipt not found.']);
});

// --------------------------------------------------------------------------
// 3. POST /api/like-comment/ (comment_id)
// --------------------------------------------------------------------------

test('POST /api/like-comment/ handles valid, nonexistent, malformed, and deleted comment_id', function () {
    resetSanctumAuth();
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // A. Valid ID
    $comment = Comment::factory()->create();

    resetSanctumAuth();
    $resValid = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-comment/', [
            'comment_id' => $comment->id,
        ]);
    $resValid->assertStatus(201)
        ->assertJson(['message' => 'Comment liked successfully', 'is_liked' => true]);

    // B. Nonexistent ID
    resetSanctumAuth();
    $resNonexistent = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-comment/', [
            'comment_id' => 999999,
        ]);
    $resNonexistent->assertStatus(404)
        ->assertJson(['message' => 'Comment not found.']);

    // C. Malformed ID
    resetSanctumAuth();
    $resMalformed = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-comment/', [
            'comment_id' => 'not-a-number',
        ]);
    $resMalformed->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['comment_id']]);
    $this->assertStringNotContainsString('SQLSTATE', $resMalformed->getContent());

    // D. Deleted Resource
    $deletedCommentId = $comment->id;
    $comment->delete();

    resetSanctumAuth();
    $resDeleted = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-comment/', [
            'comment_id' => $deletedCommentId,
        ]);
    $resDeleted->assertStatus(404)
        ->assertJson(['message' => 'Comment not found.']);
});

// --------------------------------------------------------------------------
// 4. DELETE /api/like-comment/ (comment_id)
// --------------------------------------------------------------------------

test('DELETE /api/like-comment/ handles valid, nonexistent, malformed, and deleted comment_id', function () {
    resetSanctumAuth();
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // A. Valid ID (user liked comment first)
    $comment = Comment::factory()->create();
    $user->likedComments()->attach($comment->id);

    resetSanctumAuth();
    $resValid = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => $comment->id,
        ]);
    $resValid->assertStatus(200)
        ->assertJson(['message' => 'Comment unliked successfully', 'is_liked' => false]);

    // B. Nonexistent ID
    resetSanctumAuth();
    $resNonexistent = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => 999999,
        ]);
    $resNonexistent->assertStatus(404)
        ->assertJson(['message' => 'Comment not found.']);

    // C. Malformed ID
    resetSanctumAuth();
    $resMalformed = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => 'invalid',
        ]);
    $resMalformed->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['comment_id']]);
    $this->assertStringNotContainsString('SQLSTATE', $resMalformed->getContent());

    // D. Deleted Resource
    $deletedCommentId = $comment->id;
    $comment->delete();

    resetSanctumAuth();
    $resDeleted = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => $deletedCommentId,
        ]);
    $resDeleted->assertStatus(404)
        ->assertJson(['message' => 'Comment not found.']);
});

// --------------------------------------------------------------------------
// 5. GET /api/suggestions/ (receipt_id)
// --------------------------------------------------------------------------

test('GET /api/suggestions/ handles valid, nonexistent, malformed, and deleted receipt_id', function () {
    resetSanctumAuth();
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // A. Valid ID
    $receipt = Receipt::factory()->create();
    Suggestion::factory()->create(['receipt_id' => $receipt->receipt_id]);

    resetSanctumAuth();
    $resValid = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/suggestions/?receipt_id='.$receipt->receipt_id);
    $resValid->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);

    // B. Nonexistent ID
    resetSanctumAuth();
    $resNonexistent = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/suggestions/?receipt_id=999999');
    $resNonexistent->assertStatus(404)
        ->assertJson(['message' => 'Receipt not found.']);

    // C. Malformed ID
    resetSanctumAuth();
    $resMalformed = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/suggestions/?receipt_id=malformed');
    $resMalformed->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['receipt_id']]);
    $this->assertStringNotContainsString('SQLSTATE', $resMalformed->getContent());

    // D. Deleted Resource
    $deletedReceiptId = $receipt->receipt_id;
    $receipt->delete();

    resetSanctumAuth();
    $resDeleted = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/suggestions/?receipt_id='.$deletedReceiptId);
    $resDeleted->assertStatus(404)
        ->assertJson(['message' => 'Receipt not found.']);
});

// --------------------------------------------------------------------------
// 6. POST /api/suggestion/ (receipt_id)
// --------------------------------------------------------------------------

test('POST /api/suggestion/ handles valid, nonexistent, malformed, and deleted receipt_id', function () {
    resetSanctumAuth();
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // A. Valid ID
    $receipt = Receipt::factory()->create();

    resetSanctumAuth();
    $resValid = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => $receipt->receipt_id,
            'text' => 'Add some garlic',
        ]);
    $resValid->assertStatus(201)
        ->assertJsonStructure(['message', 'suggestion']);

    // B. Nonexistent ID
    resetSanctumAuth();
    $resNonexistent = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => 999999,
            'text' => 'Add some garlic',
        ]);
    $resNonexistent->assertStatus(404)
        ->assertJson(['message' => 'Receipt not found.']);

    // C. Malformed ID
    resetSanctumAuth();
    $resMalformed = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => 'abc',
            'text' => 'Add some garlic',
        ]);
    $resMalformed->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['receipt_id']]);
    $this->assertStringNotContainsString('SQLSTATE', $resMalformed->getContent());

    // D. Deleted Resource
    $deletedReceiptId = $receipt->receipt_id;
    $receipt->delete();

    resetSanctumAuth();
    $resDeleted = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/suggestion/', [
            'receipt_id' => $deletedReceiptId,
            'text' => 'Add some garlic',
        ]);
    $resDeleted->assertStatus(404)
        ->assertJson(['message' => 'Receipt not found.']);
});

// --------------------------------------------------------------------------
// 7. POST /api/like-suggestion/ (suggestion_id)
// --------------------------------------------------------------------------

test('POST /api/like-suggestion/ handles valid, nonexistent, malformed, and deleted suggestion_id', function () {
    resetSanctumAuth();
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // A. Valid ID
    $suggestion = Suggestion::factory()->create();

    resetSanctumAuth();
    $resValid = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);
    $resValid->assertStatus(201)
        ->assertJson(['message' => 'Suggestion liked successfully', 'is_liked' => true]);

    // B. Nonexistent ID
    resetSanctumAuth();
    $resNonexistent = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-suggestion/', [
            'suggestion_id' => 999999,
        ]);
    $resNonexistent->assertStatus(404)
        ->assertJson(['message' => 'Suggestion not found.']);

    // C. Malformed ID
    resetSanctumAuth();
    $resMalformed = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-suggestion/', [
            'suggestion_id' => 'bad-id',
        ]);
    $resMalformed->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['suggestion_id']]);
    $this->assertStringNotContainsString('SQLSTATE', $resMalformed->getContent());

    // D. Deleted Resource
    $deletedSuggestionId = $suggestion->id;
    $suggestion->delete();

    resetSanctumAuth();
    $resDeleted = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-suggestion/', [
            'suggestion_id' => $deletedSuggestionId,
        ]);
    $resDeleted->assertStatus(404)
        ->assertJson(['message' => 'Suggestion not found.']);
});

// --------------------------------------------------------------------------
// 8. DELETE /api/like-suggestion/ (suggestion_id)
// --------------------------------------------------------------------------

test('DELETE /api/like-suggestion/ handles valid, nonexistent, malformed, and deleted suggestion_id', function () {
    resetSanctumAuth();
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // A. Valid ID (user liked suggestion first)
    $suggestion = Suggestion::factory()->create();
    $user->likedSuggestions()->attach($suggestion->id);

    resetSanctumAuth();
    $resValid = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);
    $resValid->assertStatus(200)
        ->assertJson(['message' => 'Suggestion unliked successfully', 'is_liked' => false]);

    // B. Nonexistent ID
    resetSanctumAuth();
    $resNonexistent = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => 999999,
        ]);
    $resNonexistent->assertStatus(404)
        ->assertJson(['message' => 'Suggestion not found.']);

    // C. Malformed ID
    resetSanctumAuth();
    $resMalformed = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => 'invalid-id',
        ]);
    $resMalformed->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['suggestion_id']]);
    $this->assertStringNotContainsString('SQLSTATE', $resMalformed->getContent());

    // D. Deleted Resource
    $deletedSuggestionId = $suggestion->id;
    $suggestion->delete();

    resetSanctumAuth();
    $resDeleted = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-suggestion/', [
            'suggestion_id' => $deletedSuggestionId,
        ]);
    $resDeleted->assertStatus(404)
        ->assertJson(['message' => 'Suggestion not found.']);
});

// --------------------------------------------------------------------------
// 9. PATCH /api/approve-suggestion/ (suggestion_id)
// --------------------------------------------------------------------------

test('PATCH /api/approve-suggestion/ handles valid, nonexistent, malformed, and deleted suggestion_id', function () {
    resetSanctumAuth();
    $receiptOwner = User::factory()->create();
    $token = $receiptOwner->createToken('test-token')->plainTextToken;

    $receipt = Receipt::factory()->create(['user_id' => $receiptOwner->user_id]);

    // A. Valid ID
    $suggestion = Suggestion::factory()->create(['receipt_id' => $receipt->receipt_id, 'isApproved' => false]);

    resetSanctumAuth();
    $resValid = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);
    $resValid->assertStatus(200)
        ->assertJson(['message' => 'Suggestion approved successfully']);

    // B. Nonexistent ID
    resetSanctumAuth();
    $resNonexistent = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => 999999,
        ]);
    $resNonexistent->assertStatus(404)
        ->assertJson(['message' => 'Suggestion not found.']);

    // C. Malformed ID
    resetSanctumAuth();
    $resMalformed = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => 'malformed',
        ]);
    $resMalformed->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['suggestion_id']]);
    $this->assertStringNotContainsString('SQLSTATE', $resMalformed->getContent());

    // D. Deleted Resource
    $deletedSuggestionId = $suggestion->id;
    $suggestion->delete();

    resetSanctumAuth();
    $resDeleted = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $deletedSuggestionId,
        ]);
    $resDeleted->assertStatus(404)
        ->assertJson(['message' => 'Suggestion not found.']);
});
