<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chatbot\GetResponseRequest;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Chatbot\SearchEngineRequest;

class ChatbotController extends Controller
{
    protected ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    public function getResponse(GetResponseRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->user_id;
        $userInput = $request->validated('prompt');

        $response = $this->chatbotService->getAIResponse($userInput, $userId);

        if (! $response['success']) {
            return response()->json([
                'response' => 'You are out of tokens',
            ], 402);
        }

        return response()->json([
            'response' => $response['response']
        ], 200);
    }

    public function searchEngine(SearchEngineRequest $request)
    {
        
    }
}