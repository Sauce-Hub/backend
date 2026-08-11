<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comments\GetCommentsRequest;
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
     *
     * @param CommentService $commentService
     */
    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Display a listing of comments for a receipt.
     *
     * @param GetCommentsRequest $request
     * @return JsonResponse
     */
    public function index(GetCommentsRequest $request): JsonResponse
    {
        $receiptId = (int) $request->query('receipt_id');
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);

        $result = $this->commentService->getCommentsForReceipt($receiptId, $page, $perPage);

        if (!$result['success']) {
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
     *
     * @param StoreCommentRequest $request
     * @return JsonResponse
     */
    public function store(StoreCommentRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $receiptId = (int) $request->input('receipt_id');
        $text = $request->input('text');

        $result = $this->commentService->storeComment($userId, $receiptId, $text);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => new CommentStoreResource($result['comment']),
        ], 201);
    }
}
