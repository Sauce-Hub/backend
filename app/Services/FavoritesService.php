<?php

namespace App\Services;

use App\Models\Receipt;

class FavoritesService
{
    public function getFavoritesForUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $receipts = Receipt::whereHas('favoritedBy', function ($query) use ($userId) {
            $query->where('users.user_id', $userId);
        })
            ->with([
                'user',
                'likedBy' => function ($query) use ($userId) {
                    $query->where('users.user_id', $userId);
                },
            ])
            ->withCount([
                'likedBy as likes_count',
                'comments as comments_count',
                'favoritedBy as favorites_count',
            ])
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'receipts' => $receipts,
            'pagination' => [
                'current_page' => $receipts->currentPage(),
                'per_page' => $receipts->perPage(),
                'total' => $receipts->total(),
                'last_page' => $receipts->lastPage(),
            ],
        ];
    }

    public function addFavorite(int $userId, int $receiptId): array
    {
        $receipt = Receipt::find($receiptId);

        if (! $receipt) {
            return [
                'success' => false,
                'message' => 'Receipt not found.',
            ];
        }

        if ($receipt->favoritedBy()->where('users.user_id', $userId)->exists()) {
            return [
                'success' => false,
                'message' => 'Receipt already in favorites.',
            ];
        }

        $receipt->favoritedBy()->attach($userId);

        return [
            'success' => true,
            'message' => 'success',
        ];
    }

    public function removeFavorite(int $userId, int $receiptId): array
    {
        $receipt = Receipt::find($receiptId);

        if (! $receipt) {
            return [
                'success' => false,
                'message' => 'Receipt not found.',
            ];
        }

        if (! $receipt->favoritedBy()->where('users.user_id', $userId)->exists()) {
            return [
                'success' => false,
                'message' => 'Favorite not found.',
            ];
        }

        $receipt->favoritedBy()->detach($userId);

        return [
            'success' => true,
            'message' => 'success',
        ];
    }
}
