<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comments\GetCommentsRequest;
use App\Http\Requests\Comments\LikeCommentRequest;
use App\Http\Requests\Comments\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\CommentStoreResource;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    protected CommentService $commentService;

    /**
     * Create a new controller instance.
     */
    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Display a listing of comments for a receipt.
     */
    public function index(GetCommentsRequest $request): JsonResponse
    {
        $receiptId = (int) $request->query('receipt_id');
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);

        $result = $this->commentService->getCommentsForReceipt($receiptId, $page, $perPage);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'data' => CommentResource::collection($result['comments']),
            'meta' => [
                'current_page' => $result['pagination']['current_page'],
                'per_page' => $result['pagination']['per_page'],
                'total' => $result['pagination']['total'],
                'last_page' => $result['pagination']['last_page'],
            ],
        ], 200);
    }

    /**
     * Store a newly created comment in storage.
     */
    public function store(StoreCommentRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $receiptId = (int) $request->input('receipt_id');
        $text = $request->input('text');

        $result = $this->commentService->storeComment($userId, $receiptId, $text);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => new CommentStoreResource($result['comment']),
        ], 201);
    }

    /**
     * Like a comment.
     */
    public function like(LikeCommentRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $commentId = (int) $request->input('comment_id');

        $result = $this->commentService->likeComment($userId, $commentId);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'message' => 'Comment liked successfully',
            'comment_id' => $result['comment_id'],
            'is_liked' => $result['is_liked'],
            'likes_count' => $result['likes_count'],
        ], 201);
    }

    /**
     * Remove like from a comment.
     */
    public function unlike(LikeCommentRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $commentId = (int) $request->input('comment_id');

        $result = $this->commentService->unlikeComment($userId, $commentId);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'message' => 'Comment unliked successfully',
            'comment_id' => $result['comment_id'],
            'is_liked' => $result['is_liked'],
            'likes_count' => $result['likes_count'],
        ], 200);
    }
}
