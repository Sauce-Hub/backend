<?php

use App\Models\Ingredient;
use App\Models\Instruction;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;

uses(RefreshDatabase::class);

/**
 * Helper to reset Laravel Sanctum guard state between simulated client requests.
 */
function resetSanctumState(): void
{
    auth()->forgetGuards();
    if (app()->bound('request')) {
        app('request')->setUserResolver(fn () => null);
    }
}

test('Complete End-to-End Lifecycle Scenario: Auth, Profile, Comments, Suggestions, Snapshot Editing, Approval & Security Isolation', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    // =========================================================================
    // STEP 1: AUTHENTICATION & REGISTRATION LIFECYCLE
    // =========================================================================

    // 1.1 Validation Rejection: Invalid Registration (missing fields, weak password)
    $regFail = $this->postJson('/api/register/', [
        'name' => 'Gordon',
        'email' => 'invalid-email-format',
        'password' => 'short', // less than 8 chars
    ]);
    $regFail->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['email', 'password']]);

    // 1.2 Register Chef Gordon (Recipe Owner)
    $regGordon = $this->postJson('/api/register/', [
        'name' => 'Chef Gordon',
        'email' => 'gordon@kitchen.com',
        'password' => 'ChefPass123!',
    ]);
    $regGordon->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'user' => ['user_id', 'name', 'email'],
            'token',
        ]);
    $gordonId = $regGordon->json('user.user_id');
    $gordonToken = $regGordon->json('token');
    expect($gordonId)->not->toBeNull();
    expect($gordonToken)->not->toBeEmpty();

    // 1.3 Register Sous Chef Alex (Suggestion Author)
    $regAlex = $this->postJson('/api/register/', [
        'name' => 'Alex',
        'email' => 'alex@kitchen.com',
        'password' => 'SousPass123!',
    ]);
    $regAlex->assertStatus(201);
    $alexId = $regAlex->json('user.user_id');
    $alexToken = $regAlex->json('token');

    // 1.4 Register Hacker Bob (Malicious / Unauthorized User)
    $regBob = $this->postJson('/api/register/', [
        'name' => 'Hacker Bob',
        'email' => 'bob@kitchen.com',
        'password' => 'HackPass123!',
    ]);
    $regBob->assertStatus(201);
    $bobId = $regBob->json('user.user_id');
    $bobToken = $regBob->json('token');

    // 1.5 Duplicate Email Registration Guard
    $dupReg = $this->postJson('/api/register/', [
        'name' => 'Imposter Gordon',
        'email' => 'gordon@kitchen.com',
        'password' => 'AnotherPass123!',
    ]);
    $dupReg->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['email']]);

    // 1.6 Login with Invalid Credentials
    $loginFail = $this->postJson('/api/login/', [
        'email' => 'gordon@kitchen.com',
        'password' => 'WrongPassword123!',
    ]);
    $loginFail->assertStatus(401)
        ->assertJson(['message' => 'Invalid credentials.']);

    // 1.7 Login Successfully
    $loginSuccess = $this->postJson('/api/login/', [
        'email' => 'gordon@kitchen.com',
        'password' => 'ChefPass123!',
    ]);
    $loginSuccess->assertStatus(200)
        ->assertJsonStructure(['message', 'user', 'token']);
    $gordonToken = $loginSuccess->json('token');

    // =========================================================================
    // STEP 2: PROFILE LIFECYCLE & RECIPE INTEGRATION
    // =========================================================================

    // 2.1 Unauthenticated Profile Rejection
    resetSanctumState();
    $unauthProfile = $this->getJson('/api/profile/');
    $unauthProfile->assertStatus(401);

    // 2.2 Gordon Views Initial Empty Profile
    resetSanctumState();
    $gordonProfile = $this->withHeader('Authorization', 'Bearer '.$gordonToken)
        ->getJson('/api/profile/');
    $gordonProfile->assertStatus(200)
        ->assertJson([
            'user_id' => $gordonId,
            'name' => 'Chef Gordon',
            'email' => 'gordon@kitchen.com',
            'receipts' => [],
        ]);

    // 2.3 Create a Recipe for Gordon in Database
    $recipe = Receipt::create([
        'user_id' => $gordonId,
        'name' => 'Classic Carbonara',
        'caption' => 'Authentic Roman Carbonara',
        'category' => 'DINNER',
        'estimated_time' => '25 min',
        'Calories' => 650,
        'Fats' => 28,
        'Carbs' => 75,
        'Protein' => 24,
        'timestamp' => now()->toIso8601String(),
    ]);

    // Add initial ingredients to Recipe
    Ingredient::create(['receipt_id' => $recipe->receipt_id, 'name' => 'Spaghetti', 'quantity' => 200, 'unit' => 'g', 'isAssigned' => true]);
    Ingredient::create(['receipt_id' => $recipe->receipt_id, 'name' => 'Guanciale', 'quantity' => 100, 'unit' => 'g', 'isAssigned' => true]);
    Ingredient::create(['receipt_id' => $recipe->receipt_id, 'name' => 'Pecorino Romano', 'quantity' => 50, 'unit' => 'g', 'isAssigned' => true]);

    // Add initial instructions to Recipe
    Instruction::create(['receipt_id' => $recipe->receipt_id, 'step_number' => 1, 'instruction' => 'Boil salted water and cook spaghetti al dente.']);
    Instruction::create(['receipt_id' => $recipe->receipt_id, 'step_number' => 2, 'instruction' => 'Crisp guanciale in a dry pan until golden.']);
    Instruction::create(['receipt_id' => $recipe->receipt_id, 'step_number' => 3, 'instruction' => 'Toss pasta with egg yolks and pecorino off heat.']);

    // 2.4 Verify Gordon's Profile Now Includes the Recipe with Exact Casing
    resetSanctumState();
    $gordonProfileUpdated = $this->withHeader('Authorization', 'Bearer '.$gordonToken)
        ->getJson('/api/profile/');
    $gordonProfileUpdated->assertStatus(200)
        ->assertJsonStructure([
            'user_id',
            'name',
            'email',
            'receipts' => [
                '*' => [
                    'receipt_id',
                    'name',
                    'caption',
                    'category',
                    'estimated_time',
                    'Calories',
                    'Fats',
                    'Carbs',
                    'Protein',
                    'timestamp',
                ],
            ],
        ]);
    expect($gordonProfileUpdated->json('receipts.0.receipt_id'))->toBe($recipe->receipt_id);
    expect($gordonProfileUpdated->json('receipts.0.name'))->toBe('Classic Carbonara');

    // =========================================================================
    // STEP 3: COMMENTS LIFECYCLE (VIEW, ADD, LIKE, UNLIKE, IDEMPOTENCY)
    // =========================================================================

    // 3.1 Nonexistent Receipt ID on comments
    resetSanctumState();
    $nonexistentComment = $this->withHeader('Authorization', 'Bearer '.$alexToken)
        ->getJson('/api/comments/?receipt_id=999999');
    $nonexistentComment->assertStatus(404)
        ->assertJson(['message' => 'Receipt not found.']);

    // 3.2 Alex Adds a Comment to Gordon's Recipe
    resetSanctumState();
    $addComment = $this->withHeader('Authorization', 'Bearer '.$alexToken)
        ->postJson('/api/comment/', [
            'receipt_id' => $recipe->receipt_id,
            'text' => 'Make sure the pan is off the heat before adding the egg mixture to avoid scrambling!',
        ]);
    $addComment->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'comment' => ['id', 'user_id', 'receipt_id', 'text', 'timestamp'],
        ]);
    $commentId = $addComment->json('comment.id');
    expect($addComment->json('comment.user_id'))->toBe($alexId);

    // 3.3 Text Length Validation (Max 1000 characters)
    resetSanctumState();
    $overlongComment = $this->withHeader('Authorization', 'Bearer '.$alexToken)
        ->postJson('/api/comment/', [
            'receipt_id' => $recipe->receipt_id,
            'text' => str_repeat('A', 1001),
        ]);
    $overlongComment->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['text']]);

    // 3.4 Gordon Views Comments on their Recipe (Pagination & User Eager Loading)
    resetSanctumState();
    $viewComments = $this->withHeader('Authorization', 'Bearer '.$gordonToken)
        ->getJson('/api/comments/?receipt_id='.$recipe->receipt_id);
    $viewComments->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'text', 'timestamp', 'user' => ['user_id', 'name'], 'likes_count', 'is_liked'],
            ],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
    expect($viewComments->json('data.0.id'))->toBe($commentId);
    expect($viewComments->json('data.0.user.name'))->toBe('Alex');
    expect($viewComments->json('data.0.likes_count'))->toBe(0);
    expect($viewComments->json('data.0.is_liked'))->toBeFalse();

    // 3.5 Bob Likes the Comment
    resetSanctumState();
    $bobLikeComment = $this->withHeader('Authorization', 'Bearer '.$bobToken)
        ->postJson('/api/like-comment/', ['comment_id' => $commentId]);
    $bobLikeComment->assertStatus(201)
        ->assertJson([
            'message' => 'Comment liked successfully',
            'comment_id' => $commentId,
            'is_liked' => true,
            'likes_count' => 1,
        ]);

    // 3.6 Bob Likes the Comment Again (Idempotent: Count Stays 1)
    resetSanctumState();
    $bobLikeAgain = $this->withHeader('Authorization', 'Bearer '.$bobToken)
        ->postJson('/api/like-comment/', ['comment_id' => $commentId]);
    $bobLikeAgain->assertStatus(201)
        ->assertJson([
            'is_liked' => true,
            'likes_count' => 1,
        ]);

    // 3.7 Gordon Also Likes the Comment (Count Increases to 2)
    resetSanctumState();
    $gordonLikeComment = $this->withHeader('Authorization', 'Bearer '.$gordonToken)
        ->postJson('/api/like-comment/', ['comment_id' => $commentId]);
    $gordonLikeComment->assertStatus(201)
        ->assertJson([
            'is_liked' => true,
            'likes_count' => 2,
        ]);

    // 3.8 Bob Unlikes the Comment (Count Decreases to 1)
    resetSanctumState();
    $bobUnlike = $this->withHeader('Authorization', 'Bearer '.$bobToken)
        ->deleteJson('/api/like-comment/', ['comment_id' => $commentId]);
    $bobUnlike->assertStatus(200)
        ->assertJson([
            'message' => 'Comment unliked successfully',
            'comment_id' => $commentId,
            'is_liked' => false,
            'likes_count' => 1,
        ]);

    // =========================================================================
    // STEP 4: SUGGESTIONS SNAPSHOT CREATION & VIEWING
    // =========================================================================

    // 4.1 Alex Creates a Suggestion for Gordon's Recipe
    resetSanctumState();
    $addSuggestion = $this->withHeader('Authorization', 'Bearer '.$alexToken)
        ->postJson('/api/suggestion/', [
            'receipt_id' => $recipe->receipt_id,
            'text' => 'Upgrade to artisan rigatoni and add reserved pasta water to emulsify the pecorino.',
        ]);
    $addSuggestion->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'suggestion' => [
                'id',
                'user_id',
                'receipt_id',
                'text',
                'timestamp',
                'isApproved',
                'ingredients' => ['*' => ['id', 'name', 'quantity', 'unit', 'isAssigned']],
                'instructions' => ['*' => ['id', 'step_number', 'instruction']],
            ],
        ]);
    $suggestionId = $addSuggestion->json('suggestion.id');
    expect($addSuggestion->json('suggestion.isApproved'))->toBeFalse();
    expect($addSuggestion->json('suggestion.ingredients'))->toHaveCount(3);
    expect($addSuggestion->json('suggestion.instructions'))->toHaveCount(3);

    // Verify DB Integrity: Original recipe items unchanged, snapshot items cloned with suggestion_id
    expect(Ingredient::where('receipt_id', $recipe->receipt_id)->count())->toBe(3);
    expect(Instruction::where('receipt_id', $recipe->receipt_id)->count())->toBe(3);
    expect(Ingredient::where('suggestion_id', $suggestionId)->count())->toBe(3);
    expect(Instruction::where('suggestion_id', $suggestionId)->count())->toBe(3);

    // 4.2 Gordon Views Suggestions on their Recipe (Ordered by step_number)
    resetSanctumState();
    $viewSuggestions = $this->withHeader('Authorization', 'Bearer '.$gordonToken)
        ->getJson('/api/suggestions/?receipt_id='.$recipe->receipt_id);
    $viewSuggestions->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'receipt_id',
                    'text',
                    'timestamp',
                    'isApproved',
                    'user' => ['user_id', 'name'],
                    'ingredients' => ['*' => ['id', 'name', 'quantity', 'unit', 'isAssigned']],
                    'instructions' => ['*' => ['id', 'step_number', 'instruction']],
                    'likes_count',
                    'is_liked',
                ],
            ],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
    expect($viewSuggestions->json('data.0.instructions.0.step_number'))->toBe(1);
    expect($viewSuggestions->json('data.0.instructions.1.step_number'))->toBe(2);
    expect($viewSuggestions->json('data.0.instructions.2.step_number'))->toBe(3);

    // 4.3 Bob Likes and Unlikes the Suggestion
    resetSanctumState();
    $bobLikeSug = $this->withHeader('Authorization', 'Bearer '.$bobToken)
        ->postJson('/api/like-suggestion/', ['suggestion_id' => $suggestionId]);
    $bobLikeSug->assertStatus(201)
        ->assertJson(['is_liked' => true, 'likes_count' => 1]);

    resetSanctumState();
    $bobUnlikeSug = $this->withHeader('Authorization', 'Bearer '.$bobToken)
        ->deleteJson('/api/like-suggestion/', ['suggestion_id' => $suggestionId]);
    $bobUnlikeSug->assertStatus(200)
        ->assertJson(['is_liked' => false, 'likes_count' => 0]);

    // =========================================================================
    // STEP 5: UPDATING PENDING SUGGESTION SNAPSHOT (AUTHOR ONLY)
    // =========================================================================

    // 5.1 Bob (Unauthorized) Tries to Edit Alex's Pending Suggestion -> 403 Forbidden
    resetSanctumState();
    $bobUnauthorizedEdit = $this->withHeader('Authorization', 'Bearer '.$bobToken)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => $suggestionId,
            'text' => 'Hacked text!',
            'ingredients' => [
                ['name' => 'Hacked Ingredient', 'quantity' => 10, 'unit' => 'kg', 'isAssigned' => true],
            ],
            'instructions' => [
                ['step_number' => 1, 'instruction' => 'Hacked step.'],
            ],
        ]);
    $bobUnauthorizedEdit->assertStatus(403);

    // 5.2 Alex (Author) Updates the Pending Suggestion Snapshot
    resetSanctumState();
    $alexUpdatedSnapshot = $this->withHeader('Authorization', 'Bearer '.$alexToken)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => $suggestionId,
            'text' => 'Artisan Rigatoni Carbonara with Truffle Pecorino and Pasta Emulsion',
            'ingredients' => [
                ['name' => 'Artisan Rigatoni', 'quantity' => 250, 'unit' => 'g', 'isAssigned' => true],
                ['name' => 'Aged Guanciale', 'quantity' => 120, 'unit' => 'g', 'isAssigned' => true],
                ['name' => 'Truffle Pecorino Romano', 'quantity' => 60, 'unit' => 'g', 'isAssigned' => true],
                ['name' => 'Organic Egg Yolks', 'quantity' => 4, 'unit' => 'piece', 'isAssigned' => true],
            ],
            'instructions' => [
                ['step_number' => 1, 'instruction' => 'Boil salted water and cook artisan rigatoni for 11 mins.'],
                ['step_number' => 2, 'instruction' => 'Slowly render aged guanciale until crispy.'],
                ['step_number' => 3, 'instruction' => 'Whisk 4 egg yolks with truffle pecorino and 2 tbsp reserved pasta water.'],
                ['step_number' => 4, 'instruction' => 'Emulsify pasta, rendered fat, and egg mixture off heat until glossy.'],
            ],
        ]);
    $alexUpdatedSnapshot->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'suggestion' => [
                'id',
                'text',
                'ingredients' => ['*' => ['id', 'name', 'quantity', 'unit', 'isAssigned']],
                'instructions' => ['*' => ['id', 'step_number', 'instruction']],
            ],
        ]);
    expect($alexUpdatedSnapshot->json('suggestion.ingredients'))->toHaveCount(4);
    expect($alexUpdatedSnapshot->json('suggestion.instructions'))->toHaveCount(4);

    // Verify DB State: Suggestion snapshot updated, original recipe items untouched!
    expect(Ingredient::where('suggestion_id', $suggestionId)->count())->toBe(4);
    expect(Instruction::where('suggestion_id', $suggestionId)->count())->toBe(4);
    expect(Ingredient::where('receipt_id', $recipe->receipt_id)->count())->toBe(3);
    expect(Instruction::where('receipt_id', $recipe->receipt_id)->count())->toBe(3);

    // =========================================================================
    // STEP 6: SUGGESTION APPROVAL & ATOMIC RECIPE OVERWRITE (OWNER ONLY)
    // =========================================================================

    // 6.1 Hacker Bob Tries to Approve the Suggestion -> 403 Forbidden
    resetSanctumState();
    $bobApproveFail = $this->withHeader('Authorization', 'Bearer '.$bobToken)
        ->patchJson('/api/approve-suggestion/', ['suggestion_id' => $suggestionId]);
    $bobApproveFail->assertStatus(403)
        ->assertJson(['message' => 'You are not allowed to approve this suggestion.']);

    // 6.2 Suggestion Author (Alex) Tries to Approve -> 403 Forbidden (Only Recipe Owner Can Approve)
    resetSanctumState();
    $alexApproveFail = $this->withHeader('Authorization', 'Bearer '.$alexToken)
        ->patchJson('/api/approve-suggestion/', ['suggestion_id' => $suggestionId]);
    $alexApproveFail->assertStatus(403)
        ->assertJson(['message' => 'You are not allowed to approve this suggestion.']);

    // 6.3 Chef Gordon (Recipe Owner) Approves the Suggestion -> 200 OK
    resetSanctumState();
    $gordonApproveSuccess = $this->withHeader('Authorization', 'Bearer '.$gordonToken)
        ->patchJson('/api/approve-suggestion/', ['suggestion_id' => $suggestionId]);
    $gordonApproveSuccess->assertStatus(200)
        ->assertJson([
            'message' => 'Suggestion approved successfully',
            'suggestion' => [
                'id' => $suggestionId,
                'receipt_id' => $recipe->receipt_id,
                'isApproved' => true,
            ],
        ]);

    // 6.4 Verify Database Atomic Overwrite (DEC-020):
    // Recipe ingredients & instructions replaced with the 4 suggestion items
    $recipeIngredients = Ingredient::where('receipt_id', $recipe->receipt_id)->get();
    $recipeInstructions = Instruction::where('receipt_id', $recipe->receipt_id)->orderBy('step_number')->get();
    expect($recipeIngredients)->toHaveCount(4);
    expect($recipeIngredients->pluck('name')->all())->toContain('Artisan Rigatoni', 'Aged Guanciale', 'Truffle Pecorino Romano', 'Organic Egg Yolks');
    expect($recipeInstructions)->toHaveCount(4);
    expect($recipeInstructions[0]->instruction)->toBe('Boil salted water and cook artisan rigatoni for 11 mins.');
    expect($recipeInstructions[3]->instruction)->toBe('Emulsify pasta, rendered fat, and egg mixture off heat until glossy.');

    // Suggestion snapshot is preserved intact in DB for history / audit
    $suggestionIngredients = Ingredient::where('suggestion_id', $suggestionId)->get();
    $suggestionInstructions = Instruction::where('suggestion_id', $suggestionId)->get();
    expect($suggestionIngredients)->toHaveCount(4);
    expect($suggestionInstructions)->toHaveCount(4);

    // 6.5 Guard: Approved Suggestion Cannot Be Updated Anymore -> 403 Forbidden
    resetSanctumState();
    $alexPostApprovalEdit = $this->withHeader('Authorization', 'Bearer '.$alexToken)
        ->putJson('/api/suggestion/', [
            'suggestion_id' => $suggestionId,
            'text' => 'Trying to edit approved suggestion',
            'ingredients' => [
                ['name' => 'New Ing', 'quantity' => 10, 'unit' => 'g', 'isAssigned' => true],
            ],
            'instructions' => [
                ['step_number' => 1, 'instruction' => 'New Step'],
            ],
        ]);
    $alexPostApprovalEdit->assertStatus(403)
        ->assertJson(['message' => 'Approved suggestions cannot be updated.']);

    // =========================================================================
    // STEP 7: LOGOUT & TOKEN REVOCATION LIFECYCLE
    // =========================================================================

    // 7.1 Alex Logs Out -> 204 No Content
    resetSanctumState();
    $alexLogout = $this->withHeader('Authorization', 'Bearer '.$alexToken)
        ->deleteJson('/api/logout/');
    $alexLogout->assertStatus(204);

    // 7.2 Alex Attempts to Access Protected Profile with Revoked Token -> 401 Unauthenticated
    resetSanctumState();
    $alexPostLogoutAccess = $this->withHeader('Authorization', 'Bearer '.$alexToken)
        ->getJson('/api/profile/');
    $alexPostLogoutAccess->assertStatus(401);

    // 7.3 Token Isolation: Chef Gordon's Token Remains Fully Active and Valid -> 200 OK
    resetSanctumState();
    $gordonStillActive = $this->withHeader('Authorization', 'Bearer '.$gordonToken)
        ->getJson('/api/profile/');
    $gordonStillActive->assertStatus(200)
        ->assertJson([
            'user_id' => $gordonId,
            'name' => 'Chef Gordon',
        ]);
});
