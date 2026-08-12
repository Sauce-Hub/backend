<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Receipt;
use App\Models\User;

class CommentService
{
    /**
     * Get paginated comments for a receipt, with user and likes eager-loaded.
     */
    public function getCommentsForReceipt(int $receiptId, int $page = 1, int $perPage = 20): array
    {
        // 1. Verify receipt existence
        $receipt = Receipt::find($receiptId);

        if (! $receipt) {
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

    /**
     * Store a comment for a receipt.
     */
    public function storeComment(int $userId, int $receiptId, string $text): array
    {
        // 1. Verify receipt existence
        $receipt = Receipt::find($receiptId);

        if (! $receipt) {
            return [
                'success' => false,
                'message' => 'Receipt not found.',
            ];
        }

        // 2. Create the comment with manually assigned timestamp (since Laravel $timestamps = false)
        $comment = Comment::create([
            'user_id' => $userId,
            'receipt_id' => $receiptId,
            'text' => $text,
            'timestamp' => now(),
        ]);

        return [
            'success' => true,
            'comment' => $comment,
        ];
    }

    /**
     * Like a comment.
     */
    public function likeComment(int $userId, int $commentId): array
    {
        // 1. Verify comment existence
        $comment = Comment::find($commentId);

        if (! $comment) {
            return [
                'success' => false,
                'message' => 'Comment not found.',
            ];
        }

        $user = User::find($userId);

        // 2. Perform idempotent check: attach only if not already liked
        $hasLiked = $user->likedComments()->where('comments.id', $commentId)->exists();
        if (! $hasLiked) {
            $user->likedComments()->attach($commentId);
        }

        // 3. Get updated likes count
        $likesCount = $comment->likedBy()->count();

        return [
            'success' => true,
            'comment_id' => $commentId,
            'is_liked' => true,
            'likes_count' => $likesCount,
        ];
    }

    /**
     * Unlike a comment.
     */
    public function unlikeComment(int $userId, int $commentId): array
    {
        // 1. Verify comment existence
        $comment = Comment::find($commentId);

        if (! $comment) {
            return [
                'success' => false,
                'message' => 'Comment not found.',
            ];
        }

        $user = User::find($userId);

        // 2. Verify that the user has actually liked the comment
        $hasLiked = $user->likedComments()->where('comments.id', $commentId)->exists();
        if (! $hasLiked) {
            return [
                'success' => false,
                'message' => 'Comment like not found.',
            ];
        }

        // 3. Detach the user's like
        $user->likedComments()->detach($commentId);

        // 4. Get updated likes count
        $likesCount = $comment->likedBy()->count();

        return [
            'success' => true,
            'comment_id' => $commentId,
            'is_liked' => false,
            'likes_count' => $likesCount,
        ];
    }
}
