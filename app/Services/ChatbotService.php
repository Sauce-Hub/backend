<?php

namespace App\Services;

use App\Models\Receipt;
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
            config('services.ai.url') . '/chat',
            [
                'message' => $userInput,
                'history' => $history,
            ]
        );

        $aiResponse = $response->json();

        if ($response->status() === 200 && ($aiResponse['status'] ?? null) === 'success') {
            ChatHistory::create([
                'user_prompt' => $userInput,
                'response' => $aiResponse['response'] ?? '',
                'timestamp' => now(),
                'user_id' => $userId,
            ]);
        }

        return $aiResponse;
    }

    public function search(array $criteria): array
    {
        $receipt = Receipt::query()
            ->with(['user', 'ingredients'])
            ->orderByDesc('receipt_id')
            ->get()
            ->first(function (Receipt $receipt) use ($criteria) {
                return $this->matchesReceipt($receipt, $criteria);
            });

        return [
            'message' => 'success',
            'receipt' => $receipt ? $this->formatReceipt($receipt) : null,
        ];
    }

    private function matchesReceipt(Receipt $receipt, array $criteria): bool
    {
        if (array_key_exists('category', $criteria) && $criteria['category'] !== null) {
            $receiptCategory = $receipt->category instanceof \BackedEnum
                ? $receipt->category->value
                : (string) $receipt->category;

            if ($receiptCategory !== $criteria['category']) {
                return false;
            }
        }

        $estimatedTimeMinutes = $receipt->estimated_time === null
            ? null
            : (int) $receipt->estimated_time;

        if (array_key_exists('max_estimated_time_min', $criteria) && $criteria['max_estimated_time_min'] !== null) {
            if ($estimatedTimeMinutes === null || $estimatedTimeMinutes > (int) $criteria['max_estimated_time_min']) {
                return false;
            }
        }

        if (array_key_exists('max_calories', $criteria) && $criteria['max_calories'] !== null && (int) $receipt->Calories > (int) $criteria['max_calories']) {
            return false;
        }

        if (array_key_exists('min_calories', $criteria) && $criteria['min_calories'] !== null && (int) $receipt->Calories < (int) $criteria['min_calories']) {
            return false;
        }

        if (array_key_exists('min_protein', $criteria) && $criteria['min_protein'] !== null && (int) $receipt->Protein < (int) $criteria['min_protein']) {
            return false;
        }

        if (array_key_exists('max_protein', $criteria) && $criteria['max_protein'] !== null && (int) $receipt->Protein > (int) $criteria['max_protein']) {
            return false;
        }

        if (array_key_exists('min_carbs', $criteria) && $criteria['min_carbs'] !== null && (int) $receipt->Carbs < (int) $criteria['min_carbs']) {
            return false;
        }

        if (array_key_exists('max_carbs', $criteria) && $criteria['max_carbs'] !== null && (int) $receipt->Carbs > (int) $criteria['max_carbs']) {
            return false;
        }

        if (array_key_exists('min_fats', $criteria) && $criteria['min_fats'] !== null && (int) $receipt->Fats < (int) $criteria['min_fats']) {
            return false;
        }

        if (array_key_exists('max_fats', $criteria) && $criteria['max_fats'] !== null && (int) $receipt->Fats > (int) $criteria['max_fats']) {
            return false;
        }

        $ingredientNames = $receipt->ingredients
            ->pluck('name')
            ->map(fn ($name) => mb_strtolower((string) $name))
            ->values()
            ->all();

        $includeIngredients = $this->normalizeNames($criteria['include_ingredients'] ?? []);
        foreach ($includeIngredients as $ingredientName) {
            if (! in_array($ingredientName, $ingredientNames, true)) {
                return false;
            }
        }

        $excludeIngredients = $this->normalizeNames($criteria['exclude_ingredients'] ?? []);
        foreach ($excludeIngredients as $ingredientName) {
            if (in_array($ingredientName, $ingredientNames, true)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeNames(array $names): array
    {
        return collect($names)
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->map(fn (string $name) => mb_strtolower(trim($name)))
            ->values()
            ->all();
    }

    private function formatReceipt(Receipt $receipt): array
    {
        return [
            'receipt_id' => $receipt->receipt_id,
            'name' => $receipt->name,
            'caption' => $receipt->caption,
            'category' => $receipt->category instanceof \BackedEnum ? $receipt->category->value : $receipt->category,
            'estimated_time_min' => $receipt->estimated_time === null ? null : (int) $receipt->estimated_time,
            'calories' => (int) $receipt->Calories,
            'fats' => (int) $receipt->Fats,
            'carbs' => (int) $receipt->Carbs,
            'protein' => (int) $receipt->Protein,
            'timestamp' => $receipt->timestamp ? $receipt->timestamp->toIso8601ZuluString() : null,
            'user' => [
                'user_id' => $receipt->user->user_id,
                'name' => $receipt->user->name,
            ],
            'ingredients' => $receipt->ingredients->map(function ($ingredient) {
                return [
                    'id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'quantity' => (float) $ingredient->quantity,
                    'unit' => $ingredient->unit instanceof \BackedEnum ? $ingredient->unit->value : $ingredient->unit,
                    'isAssigned' => (bool) $ingredient->isAssigned,
                ];
            })->values()->all(),
        ];
    }
}