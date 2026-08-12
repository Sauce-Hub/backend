<?php

use App\Models\Comment;
use App\Models\Ingredient;
use App\Models\Receipt;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user has receipts relationship', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create(['user_id' => $user->user_id]);

    expect($user->receipts)->toHaveCount(1);
    expect($user->receipts->first()->receipt_id)->toBe($receipt->receipt_id);
    expect($receipt->user->user_id)->toBe($user->user_id);
});

test('user has suggestions relationship', function () {
    $user = User::factory()->create();
    $suggestion = Suggestion::factory()->create(['user_id' => $user->user_id]);

    expect($user->suggestions)->toHaveCount(1);
    expect($user->suggestions->first()->id)->toBe($suggestion->id);
    expect($suggestion->user->user_id)->toBe($user->user_id);
});

test('user has comments relationship', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $user->user_id]);

    expect($user->comments)->toHaveCount(1);
    expect($user->comments->first()->id)->toBe($comment->id);
    expect($comment->user->user_id)->toBe($user->user_id);
});

test('user can favorite receipts (many-to-many)', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();

    $user->favorites()->attach($receipt->receipt_id);

    expect($user->favorites)->toHaveCount(1);
    expect($user->favorites->first()->receipt_id)->toBe($receipt->receipt_id);
    expect($receipt->favoritedBy)->toHaveCount(1);
    expect($receipt->favoritedBy->first()->user_id)->toBe($user->user_id);
});

test('user can like receipts (many-to-many)', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create();

    $user->likedReceipts()->attach($receipt->receipt_id);

    expect($user->likedReceipts)->toHaveCount(1);
    expect($user->likedReceipts->first()->receipt_id)->toBe($receipt->receipt_id);
    expect($receipt->likedBy)->toHaveCount(1);
    expect($receipt->likedBy->first()->user_id)->toBe($user->user_id);
});

test('user can like comments (many-to-many)', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create();

    $user->likedComments()->attach($comment->id);

    expect($user->likedComments)->toHaveCount(1);
    expect($user->likedComments->first()->id)->toBe($comment->id);
    expect($comment->likedBy)->toHaveCount(1);
    expect($comment->likedBy->first()->user_id)->toBe($user->user_id);
});

test('user can like suggestions (many-to-many)', function () {
    $user = User::factory()->create();
    $suggestion = Suggestion::factory()->create();

    $user->likedSuggestions()->attach($suggestion->id);

    expect($user->likedSuggestions)->toHaveCount(1);
    expect($user->likedSuggestions->first()->id)->toBe($suggestion->id);
    expect($suggestion->likedBy)->toHaveCount(1);
    expect($suggestion->likedBy->first()->user_id)->toBe($user->user_id);
});

test('receipt has ingredients, comments and suggestions', function () {
    $receipt = Receipt::factory()->create();
    $ingredient = Ingredient::factory()->create(['receipt_id' => $receipt->receipt_id]);
    $comment = Comment::factory()->create(['receipt_id' => $receipt->receipt_id]);
    $suggestion = Suggestion::factory()->create(['receipt_id' => $receipt->receipt_id]);

    expect($receipt->ingredients)->toHaveCount(1);
    expect($receipt->ingredients->first()->id)->toBe($ingredient->id);
    expect($ingredient->receipt->receipt_id)->toBe($receipt->receipt_id);

    expect($receipt->comments)->toHaveCount(1);
    expect($receipt->comments->first()->id)->toBe($comment->id);
    expect($comment->receipt->receipt_id)->toBe($receipt->receipt_id);

    expect($receipt->suggestions)->toHaveCount(1);
    expect($receipt->suggestions->first()->id)->toBe($suggestion->id);
    expect($suggestion->receipt->receipt_id)->toBe($receipt->receipt_id);
});

test('ingredient belongs to suggestion', function () {
    $suggestion = Suggestion::factory()->create();
    $ingredient = Ingredient::factory()->forSuggestion()->create([
        'suggestion_id' => $suggestion->id,
    ]);

    expect($suggestion->ingredients)->toHaveCount(1);
    expect($suggestion->ingredients->first()->id)->toBe($ingredient->id);
    expect($ingredient->suggestion->id)->toBe($suggestion->id);
});

test('comment likes relationship alias works for eager loading', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create();

    $user->likedComments()->attach($comment->id);

    $commentWithLikes = Comment::with('likes')->find($comment->id);
    expect($commentWithLikes->likes)->toHaveCount(1);
    expect($commentWithLikes->likes->first()->user_id)->toBe($user->user_id);
});

test('suggestion likes relationship alias works for eager loading', function () {
    $user = User::factory()->create();
    $suggestion = Suggestion::factory()->create();

    $user->likedSuggestions()->attach($suggestion->id);

    $suggestionWithLikes = Suggestion::with('likes')->find($suggestion->id);
    expect($suggestionWithLikes->likes)->toHaveCount(1);
    expect($suggestionWithLikes->likes->first()->user_id)->toBe($user->user_id);
});
