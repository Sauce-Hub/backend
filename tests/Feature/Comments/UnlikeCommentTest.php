<?php

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('unlike comment route requires authentication', function () {
    $response = $this->deleteJson('/api/like-comment/', [
        'comment_id' => 1,
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('unlike comment requires comment_id', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-comment/', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['comment_id'],
        ]);
});

test('unlike comment requires integer comment_id', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => 'invalid-id',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['comment_id'],
        ]);
});

test('unlike comment returns 404 when comment does not exist', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => 9999,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Comment not found.',
        ]);
});

test('unlike comment returns 404 when user has not liked the comment', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => $comment->id,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Comment like not found.',
        ]);
});

test('authenticated user can unlike a liked comment successfully', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Attach initial like
    $user->likedComments()->attach($comment->id);
    $this->assertEquals(1, $comment->likedBy()->count());

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => $comment->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Comment unliked successfully',
            'comment_id' => $comment->id,
            'is_liked' => false,
            'likes_count' => 0,
        ]);

    // Verify database state (pivot table entry removed)
    $this->assertDatabaseMissing('likes_comments', [
        'user_id' => $user->user_id,
        'comment_id' => $comment->id,
    ]);

    // Verify count in DB
    $this->assertEquals(0, $comment->likedBy()->count());
});

test('repeated unlike returns 404 comment like not found', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Attach initial like
    $user->likedComments()->attach($comment->id);

    // First unlike (successful)
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => $comment->id,
        ])->assertStatus(200);

    // Second unlike (should fail with 404 comment like not found)
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => $comment->id,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Comment like not found.',
        ]);
});

test('unliking a comment does not affect other users likes', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $comment = Comment::factory()->create();

    // Both users like the comment
    $userA->likedComments()->attach($comment->id);
    $userB->likedComments()->attach($comment->id);
    $this->assertEquals(2, $comment->likedBy()->count());

    $tokenA = $userA->createToken('token-a')->plainTextToken;

    // User A unlikes the comment
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->deleteJson('/api/like-comment/', [
            'comment_id' => $comment->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Comment unliked successfully',
            'comment_id' => $comment->id,
            'is_liked' => false,
            'likes_count' => 1,
        ]);

    // Verify database state: User A like is gone, User B like remains
    $this->assertDatabaseMissing('likes_comments', [
        'user_id' => $userA->user_id,
        'comment_id' => $comment->id,
    ]);
    $this->assertDatabaseHas('likes_comments', [
        'user_id' => $userB->user_id,
        'comment_id' => $comment->id,
    ]);

    // Verify count in DB is 1
    $this->assertEquals(1, $comment->likedBy()->count());
});
