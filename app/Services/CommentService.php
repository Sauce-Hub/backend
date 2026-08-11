<?php

namespace App\Services;

use App\Models\Receipt;

class CommentService
{
    /**
     * Get paginated comments for a receipt, with user and likes eager-loaded.
     *
     * @param int $receiptId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getCommentsForReceipt(int $receiptId, int $page = 1, int $perPage = 20): array
    {
        // 1. Verify receipt existence
        $receipt = Receipt::find($receiptId);

        if (!$receipt) {
            return [
                'success' => false,
                'message' => 'Receipt not found.',
            ];
        }

        // 2. Fetch paginated comments for this receipt with user and likes relationship eager-loaded
        $comments = $receipt->comments()
            ->with(['user', 'likes'])
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'comments' => $comments,
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'last_page' => $comments->lastPage(),
            ],
        ];
    }
}
