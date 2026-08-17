<?php

use App\Models\Receipt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('authenticated user can retrieve profile details with own receipts', function () {
    $user = User::factory()->create([
        'name' => 'Ahmed',
        'email' => 'ahmed@example.com',
    ]);

    $receipt = Receipt::factory()->create([
        'user_id' => $user->user_id,
        'name' => 'Pasta',
        'caption' => 'Quick pasta',
        'category' => 'DINNER',
        'estimated_time' => '20 min',
        'Calories' => 500,
        'Fats' => 15,
        'Carbs' => 70,
        'Protein' => 20,
        'timestamp' => Carbon::parse('2026-08-10T18:00:00Z'),
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/profile/');

    $response->assertStatus(200)
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
        ])
        ->assertJson([
            'user_id' => $user->user_id,
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'receipts' => [
                [
                    'receipt_id' => $receipt->receipt_id,
                    'name' => 'Pasta',
                    'caption' => 'Quick pasta',
                    'category' => 'DINNER',
                    'estimated_time' => '20 min',
                    'Calories' => 500,
                    'Fats' => 15,
                    'Carbs' => 70,
                    'Protein' => 20,
                    'timestamp' => '2026-08-10T18:00:00Z',
                ],
            ],
        ]);

    // Ensure it's a flat structure at the root and not wrapped in a 'data' key
    $this->assertArrayHasKey('user_id', $response->json());
    $this->assertArrayHasKey('receipts', $response->json());
    $response->assertJsonMissingPath('data');
    $response->assertJsonMissingPath('user');
});

test('user only receives their own receipts in profile response', function () {
    $userA = User::factory()->create(['name' => 'User A', 'email' => 'usera@example.com']);
    $userB = User::factory()->create(['name' => 'User B', 'email' => 'userb@example.com']);

    $receiptA1 = Receipt::factory()->create(['user_id' => $userA->user_id, 'name' => 'Receipt A1']);
    $receiptA2 = Receipt::factory()->create(['user_id' => $userA->user_id, 'name' => 'Receipt A2']);
    $receiptB1 = Receipt::factory()->create(['user_id' => $userB->user_id, 'name' => 'Receipt B1']);

    $tokenA = $userA->createToken('token-a')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->getJson('/api/profile/');

    $response->assertStatus(200);

    $receipts = $response->json('receipts');
    $this->assertCount(2, $receipts);

    $receiptIds = collect($receipts)->pluck('receipt_id')->all();
    $this->assertContains($receiptA1->receipt_id, $receiptIds);
    $this->assertContains($receiptA2->receipt_id, $receiptIds);
    $this->assertNotContains($receiptB1->receipt_id, $receiptIds);
});

test('user with zero receipts receives empty array', function () {
    $user = User::factory()->create([
        'name' => 'Empty User',
        'email' => 'empty@example.com',
    ]);

    // Create receipts for another user to ensure no bleed-through
    $otherUser = User::factory()->create();
    Receipt::factory()->count(3)->create(['user_id' => $otherUser->user_id]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/profile/');

    $response->assertStatus(200)
        ->assertJson([
            'user_id' => $user->user_id,
            'name' => 'Empty User',
            'email' => 'empty@example.com',
            'receipts' => [],
        ]);

    $this->assertSame([], $response->json('receipts'));
});

test('response field names and casing strictly match approved contract', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->create([
        'user_id' => $user->user_id,
        'Calories' => 600,
        'Fats' => 20,
        'Carbs' => 80,
        'Protein' => 30,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/profile/');

    $response->assertStatus(200);

    $data = $response->json();
    $this->assertArrayHasKey('user_id', $data);
    $this->assertArrayHasKey('name', $data);
    $this->assertArrayHasKey('email', $data);
    $this->assertArrayHasKey('receipts', $data);

    $receiptData = $data['receipts'][0];
    $expectedKeys = [
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
    ];

    foreach ($expectedKeys as $key) {
        $this->assertArrayHasKey($key, $receiptData, "Missing key [{$key}] in receipt response");
    }

    // Ensure lowercase variants of capitalized nutritional fields do NOT exist
    $this->assertArrayNotHasKey('calories', $receiptData);
    $this->assertArrayNotHasKey('fats', $receiptData);
    $this->assertArrayNotHasKey('carbs', $receiptData);
    $this->assertArrayNotHasKey('protein', $receiptData);
});

test('unauthenticated user cannot retrieve profile details', function () {
    $response = $this->getJson('/api/profile/');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('profile request with invalid token returns unauthenticated', function () {
    $response = $this->withHeader('Authorization', 'Bearer invalid-token')
        ->getJson('/api/profile/');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('profile response never includes user password hash', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/profile/');

    $response->assertStatus(200);

    // Explicitly assert password is not returned
    $response->assertJsonMissingPath('password');
    $response->assertJsonMissingPath('remember_token');
});

test('profile request returns 401 when user account has been deleted', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Delete user from database
    $user->delete();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/profile/');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('profile route is registered with correct url and middleware', function () {
    $route = collect(Route::getRoutes())->first(function ($route) {
        return $route->uri() === 'api/profile' && in_array('GET', $route->methods());
    });

    $this->assertNotNull($route, 'Route GET /api/profile/ was not found.');

    $middleware = $route->gatherMiddleware();

    $this->assertTrue(
        in_array('auth:sanctum', $middleware),
        'Sanctum authentication middleware not applied to profile route.'
    );
});
