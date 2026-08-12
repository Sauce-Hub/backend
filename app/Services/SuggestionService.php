<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Suggestion;
use Illuminate\Support\Facades\DB;

class SuggestionService
{
    /**
     * Get paginated suggestions for a receipt, with user, ingredients, and likes eager-loaded.
     *
     * @param int $receiptId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getSuggestionsForReceipt(int $receiptId, int $page = 1, int $perPage = 20): array
    {
        // 1. Verify receipt existence
        $receipt = Receipt::find($receiptId);

        if (!$receipt) {
            return [
                'success' => false,
                'message' => 'Receipt not found.',
            ];
        }

        // 2. Fetch paginated suggestions for this receipt with relationships eager-loaded
        $suggestions = $receipt->suggestions()
            ->with(['user', 'ingredients', 'likes'])
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'suggestions' => $suggestions,
            'pagination' => [
                'current_page' => $suggestions->currentPage(),
                'per_page' => $suggestions->perPage(),
                'total' => $suggestions->total(),
                'last_page' => $suggestions->lastPage(),
            ],
        ];
    }

    /**
     * Store a suggestion for a receipt with ingredients.
     *
     * @param int $userId
     * @param int $receiptId
     * @param string $text
     * @param array $ingredientsData
     * @return array
     */
    public function storeSuggestion(int $userId, int $receiptId, string $text, array $ingredientsData = []): array
    {
        return DB::transaction(function () use ($userId, $receiptId, $text, $ingredientsData) {
            // 1. Verify receipt existence
            $receipt = Receipt::find($receiptId);

            if (!$receipt) {
                return [
                    'success' => false,
                    'message' => 'Receipt not found.',
                ];
            }

            // 2. Create the suggestion
            $suggestion = Suggestion::create([
                'user_id' => $userId,
                'receipt_id' => $receiptId,
                'text' => $text,
                'isApproved' => false,
                'timestamp' => now(),
            ]);

            // 3. Create ingredients (if any)
            foreach ($ingredientsData as $ingredient) {
                $suggestion->ingredients()->create([
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity'],
                    'unit' => $ingredient['unit'],
                    'isAssigned' => $ingredient['isAssigned'] ?? false,
                    'receipt_id' => null, // Enforces PostgreSQL CHECK constraint
                ]);
            }

            // 4. Load ingredients relationship
            $suggestion->load('ingredients');

            return [
                'success' => true,
                'suggestion' => $suggestion,
            ];
        });
    }
}
