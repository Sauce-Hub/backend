<?php

namespace App\Services;

use App\Models\ChatHistory;
use Illuminate\Support\Facades\Http;

class ChatbotService
{
    public function getAIResponse(string $userInput, int $userId)
    {
        $history = ChatHistory::query()
            ->where('user_id', $userId)
            ->orderByDesc('timestamp')
            ->limit(5)
            ->get(['user_prompt', 'response'])
            ->reverse()
            ->values()
            ->map(function (ChatHistory $chatHistory) {
                return [
                    'user_prompt' => $chatHistory->user_prompt,
                    'response' => $chatHistory->response,
                ];
            })
            ->all();

        $response = Http::post(
            config('services.ai.url') . '/send-prompt/',
            [
                'message' => $userInput,
                'history' => $history
            ]
        );

        if ($response['status'] === 'success') {
            $aiResponse = $response['response'];

            ChatHistory::create([
                'user_prompt' => $userInput,
                'response' => $aiResponse,
                'timestamp' => now(),
                'user_id' => $userId,
            ]);
        }

        return $response;
    }
}