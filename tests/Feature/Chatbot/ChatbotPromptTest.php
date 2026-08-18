<?php

use App\Models\ChatHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('chatbot prompt endpoint requires authentication', function () {
    $response = $this->getJson('/api/get-ai-response/?prompt=How%20do%20I%20make%20pasta%3F');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('chatbot prompt endpoint validates prompt input', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/get-ai-response/');

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => ['prompt'],
        ])
        ->assertJsonPath('message', 'The prompt field is required.')
        ->assertJsonPath('errors.prompt.0', 'The prompt field is required.');
});

test('chatbot prompt endpoint sends the last five history entries to the ai service', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    foreach (range(1, 6) as $index) {
        ChatHistory::create([
            'user_id' => $user->user_id,
            'user_prompt' => 'Prompt '.$index,
            'response' => 'Response '.$index,
            'timestamp' => now()->subMinutes(6 - $index),
        ]);
    }

    ChatHistory::create([
        'user_id' => $otherUser->user_id,
        'user_prompt' => 'Other user prompt',
        'response' => 'Other user response',
        'timestamp' => now()->subMinute(),
    ]);

    Http::fake([
        config('services.ai.url').'/send-prompt/' => Http::response([
            'answer' => 'Try a simple tomato pasta.',
        ], 200),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/get-ai-response/?prompt=How%20do%20I%20make%20pasta%3F');

    $response->assertStatus(200)
        ->assertJson([
            'answer' => 'Try a simple tomato pasta.',
        ]);

    Http::assertSent(function ($request) {
        $payload = $request->data();

        return $request->url() === config('services.ai.url').'/send-prompt/'
            && $payload['message'] === 'How do I make pasta?'
            && count($payload['history']) === 5
            && $payload['history'][0]['user_prompt'] === 'Prompt 2'
            && $payload['history'][4]['user_prompt'] === 'Prompt 6'
            && collect($payload['history'])->doesntContain(fn (array $entry) => $entry['user_prompt'] === 'Other user prompt');
    });
});