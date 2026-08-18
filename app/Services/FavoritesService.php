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
}
