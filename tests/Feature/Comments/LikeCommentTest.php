<?php

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('like comment route requires authentication', function () {
    $response = $this->postJson('/api/like-comment/', [
        'comment_id' => 1,
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('like comment requires comment_id', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-comment/', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['comment_id'],
        ]);
});

test('like comment requires integer comment_id', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-comment/', [
            'comment_id' => 'invalid-id',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['comment_id'],
        ]);
});

test('like comment returns 404 when comment does not exist', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-comment/', [
            'comment_id' => 9999,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Comment not found.',
        ]);
});

test('authenticated user can like a comment successfully (first like)', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-comment/', [
            'comment_id' => $comment->id,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Comment liked successfully',
            'comment_id' => $comment->id,
            'is_liked' => true,
            'likes_count' => 1,
        ]);

    // Verify database state (pivot table entry exists)
    $this->assertDatabaseHas('likes_comments', [
        'user_id' => $user->user_id,
        'comment_id' => $comment->id,
    ]);

    // Verify count in DB
    $this->assertEquals(1, $comment->likedBy()->count());
});

test('comment like operation is idempotent on duplicate requests', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // First like
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-comment/', [
            'comment_id' => $comment->id,
        ])->assertStatus(201);

    // Duplicate like
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/like-comment/', [
            'comment_id' => $comment->id,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Comment liked successfully',
            'comment_id' => $comment->id,
            'is_liked' => true,
            'likes_count' => 1,
        ]);

    // Verify pivot table count is still 1
    $pivotCount = DB::table('likes_comments')
        ->where('user_id', $user->user_id)
        ->where('comment_id', $comment->id)
        ->count();

    $this->assertEquals(1, $pivotCount);
    $this->assertEquals(1, $comment->likedBy()->count());
});
