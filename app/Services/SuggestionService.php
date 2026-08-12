<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Suggestion;

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
}
