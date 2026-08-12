<?php

use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('approve suggestion route requires authentication', function () {
    $response = $this->patchJson('/api/approve-suggestion/', [
        'suggestion_id' => 1,
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('approve suggestion requires suggestion_id', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['suggestion_id'],
        ])
        ->assertJson([
            'message' => 'The given data was invalid.',
        ]);
});

test('approve suggestion requires integer suggestion_id', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
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

test('approve suggestion returns 404 when suggestion does not exist', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => 9999,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Suggestion not found.',
        ]);
});

test('approve suggestion returns 403 when user is not the receipt owner', function () {
    $suggestion = Suggestion::factory()->create(['isApproved' => false]);
    $otherUser = User::factory()->create();
    $token = $otherUser->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'You are not allowed to approve this suggestion.',
        ]);

    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'isApproved' => false,
    ]);
});

test('receipt owner can approve suggestion successfully', function () {
    $suggestion = Suggestion::factory()->create(['isApproved' => false]);
    $receiptOwner = $suggestion->receipt->user;
    $token = $receiptOwner->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Suggestion approved successfully',
            'suggestion' => [
                'id' => $suggestion->id,
                'receipt_id' => $suggestion->receipt_id,
                'isApproved' => true,
            ],
        ]);

    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'isApproved' => true,
    ]);
});

test('approve suggestion is idempotent', function () {
    $suggestion = Suggestion::factory()->create([
        'isApproved' => true,
    ]);
    $receiptOwner = $suggestion->receipt->user;
    $token = $receiptOwner->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/approve-suggestion/', [
            'suggestion_id' => $suggestion->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Suggestion approved successfully',
            'suggestion' => [
                'id' => $suggestion->id,
                'receipt_id' => $suggestion->receipt_id,
                'isApproved' => true,
            ],
        ]);

    $this->assertDatabaseHas('suggestions', [
        'id' => $suggestion->id,
        'isApproved' => true,
    ]);
});
