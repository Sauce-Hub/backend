<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SuggestionService
{
    /**
     * Get paginated suggestions for a receipt, with user, ingredients, and likes eager-loaded.
     */
    public function getSuggestionsForReceipt(int $receiptId, int $page = 1, int $perPage = 20): array
    {
        // 1. Verify receipt existence
        $receipt = Receipt::find($receiptId);

        if (! $receipt) {
            return [
                'success' => false,
                'message' => 'Receipt not found.',
            ];
        }

        // 2. Fetch paginated suggestions for this receipt with relationships eager-loaded
        $suggestions = $receipt->suggestions()
            ->with(['user', 'ingredients', 'instructions', 'likes'])
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
     * Store a suggestion for a receipt by creating a snapshot of the current recipe ingredients and instructions.
     */
    public function storeSuggestion(int $userId, int $receiptId, string $text): array
    {
        return DB::transaction(function () use ($userId, $receiptId, $text) {
            // 1. Verify receipt existence and load current ingredients and instructions
            $receipt = Receipt::with(['ingredients', 'instructions'])->find($receiptId);

            if (! $receipt) {
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

            // 3. Clone current recipe ingredients into the new suggestion
            foreach ($receipt->ingredients as $ingredient) {
                $suggestion->ingredients()->create([
                    'name' => $ingredient->name,
                    'quantity' => $ingredient->quantity,
                    'unit' => $ingredient->unit,
                    'isAssigned' => (bool) $ingredient->isAssigned,
                    'receipt_id' => null, // Enforces PostgreSQL CHECK constraint
                ]);
            }

            // 4. Clone current recipe instructions into the new suggestion
            foreach ($receipt->instructions as $instruction) {
                $suggestion->instructions()->create([
                    'step_number' => $instruction->step_number,
                    'instruction' => $instruction->instruction,
                    'receipt_id' => null, // Enforces PostgreSQL CHECK constraint
                ]);
            }

            // 5. Load cloned relationships
            $suggestion->load(['ingredients', 'instructions']);

            return [
                'success' => true,
                'suggestion' => $suggestion,
            ];
        });
    }

    /**
     * Like a suggestion.
     */
    public function likeSuggestion(int $userId, int $suggestionId): array
    {
        // 1. Verify suggestion existence
        $suggestion = Suggestion::find($suggestionId);

        if (! $suggestion) {
            return [
                'success' => false,
                'message' => 'Suggestion not found.',
            ];
        }

        $user = User::find($userId);

        // 2. Perform idempotent check: attach only if not already liked
        $hasLiked = $user->likedSuggestions()->where('suggestions.id', $suggestionId)->exists();
        if (! $hasLiked) {
            $user->likedSuggestions()->attach($suggestionId);
        }

        // 3. Get updated likes count
        $likesCount = $suggestion->likes()->count();

        return [
            'success' => true,
            'suggestion_id' => $suggestionId,
            'is_liked' => true,
            'likes_count' => $likesCount,
        ];
    }

    /**
     * Unlike a suggestion.
     */
    public function unlikeSuggestion(int $userId, int $suggestionId): array
    {
        // 1. Verify suggestion existence
        $suggestion = Suggestion::find($suggestionId);

        if (! $suggestion) {
            return [
                'success' => false,
                'message' => 'Suggestion not found.',
            ];
        }

        $user = User::find($userId);

        // 2. Verify that the user has actually liked the suggestion
        $hasLiked = $user->likedSuggestions()->where('suggestions.id', $suggestionId)->exists();
        if (! $hasLiked) {
            return [
                'success' => false,
                'message' => 'Suggestion like not found.',
            ];
        }

        // 3. Detach the user's like
        $user->likedSuggestions()->detach($suggestionId);

        // 4. Get updated likes count
        $likesCount = $suggestion->likes()->count();

        return [
            'success' => true,
            'suggestion_id' => $suggestionId,
            'is_liked' => false,
            'likes_count' => $likesCount,
        ];
    }

    /**
     * Approve a suggestion and atomically replace the target recipe's ingredients and instructions with the suggestion snapshot.
     */
    public function approveSuggestion(int $suggestionId): array
    {
        return DB::transaction(function () use ($suggestionId) {
            $suggestion = Suggestion::with(['receipt', 'ingredients', 'instructions'])->find($suggestionId);

            if (! $suggestion) {
                return [
                    'success' => false,
                    'message' => 'Suggestion not found.',
                ];
            }

            $receipt = $suggestion->receipt;

            if (! $receipt) {
                return [
                    'success' => false,
                    'message' => 'Receipt not found.',
                ];
            }

            // 1. Replace current recipe ingredients with suggestion ingredients snapshot
            $receipt->ingredients()->delete();
            foreach ($suggestion->ingredients as $ingredient) {
                $receipt->ingredients()->create([
                    'name' => $ingredient->name,
                    'quantity' => $ingredient->quantity,
                    'unit' => $ingredient->unit,
                    'isAssigned' => (bool) $ingredient->isAssigned,
                    'suggestion_id' => null, // Enforces CHECK constraint
                ]);
            }

            // 2. Replace current recipe instructions with suggestion instructions snapshot
            $receipt->instructions()->delete();
            foreach ($suggestion->instructions as $instruction) {
                $receipt->instructions()->create([
                    'step_number' => $instruction->step_number,
                    'instruction' => $instruction->instruction,
                    'suggestion_id' => null, // Enforces CHECK constraint
                ]);
            }

            // 3. Mark suggestion as approved
            $suggestion->isApproved = true;
            $suggestion->save();

            return [
                'success' => true,
                'suggestion' => $suggestion,
            ];
        });
    }

    /**
     * Update a pending suggestion's text, ingredients snapshot, and instructions snapshot atomically.
     */
    public function updateSuggestion(Suggestion $suggestion, string $text, array $ingredientsData, array $instructionsData): array
    {
        return DB::transaction(function () use ($suggestion, $text, $ingredientsData, $instructionsData) {
            // 1. Update suggestion text (preserving isApproved = false)
            $suggestion->update([
                'text' => $text,
            ]);

            // 2. Delete existing suggestion ingredients
            $suggestion->ingredients()->delete();

            // 3. Create submitted suggestion ingredients
            foreach ($ingredientsData as $ingredient) {
                $suggestion->ingredients()->create([
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity'],
                    'unit' => $ingredient['unit'],
                    'isAssigned' => $ingredient['isAssigned'] ?? false,
                    'receipt_id' => null, // Enforces PostgreSQL CHECK constraint
                ]);
            }

            // 4. Delete existing suggestion instructions
            $suggestion->instructions()->delete();

            // 5. Create submitted suggestion instructions
            foreach ($instructionsData as $instruction) {
                $suggestion->instructions()->create([
                    'step_number' => $instruction['step_number'],
                    'instruction' => $instruction['instruction'],
                    'receipt_id' => null, // Enforces PostgreSQL CHECK constraint
                ]);
            }

            // 6. Reload updated relationships
            $suggestion->load(['ingredients', 'instructions']);

            return [
                'success' => true,
                'suggestion' => $suggestion,
            ];
        });
    }
}
