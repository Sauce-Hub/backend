<?php

namespace App\Http\Controllers;

use App\Http\Requests\Suggestions\ApproveSuggestionRequest;
use App\Http\Requests\Suggestions\GetSuggestionsRequest;
use App\Http\Requests\Suggestions\LikeSuggestionRequest;
use App\Http\Requests\Suggestions\StoreSuggestionRequest;
use App\Http\Resources\SuggestionApproveResource;
use App\Http\Resources\SuggestionResource;
use App\Http\Resources\SuggestionStoreResource;
use App\Models\Suggestion;
use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SuggestionController extends Controller
{
    protected SuggestionService $suggestionService;

    /**
     * Create a new controller instance.
     *
     * @param SuggestionService $suggestionService
     */
    public function __construct(SuggestionService $suggestionService)
    {
        $this->suggestionService = $suggestionService;
    }

    /**
     * Display a listing of suggestions for a receipt.
     *
     * @param GetSuggestionsRequest $request
     * @return JsonResponse
     */
    public function index(GetSuggestionsRequest $request): JsonResponse
    {
        $receiptId = (int) $request->query('receipt_id');
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);

        $result = $this->suggestionService->getSuggestionsForReceipt($receiptId, $page, $perPage);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'data' => SuggestionResource::collection($result['suggestions']),
            'meta' => [
                'current_page' => $result['pagination']['current_page'],
                'per_page' => $result['pagination']['per_page'],
                'total' => $result['pagination']['total'],
                'last_page' => $result['pagination']['last_page'],
            ],
        ], 200);
    }

    /**
     * Store a newly created suggestion in storage.
     *
     * @param StoreSuggestionRequest $request
     * @return JsonResponse
     */
    public function store(StoreSuggestionRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $receiptId = (int) $request->input('receipt_id');
        $text = $request->input('text');
        $ingredients = $request->input('ingredients', []);

        $result = $this->suggestionService->storeSuggestion($userId, $receiptId, $text, $ingredients);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'message' => 'Suggestion created successfully',
            'suggestion' => new SuggestionStoreResource($result['suggestion']),
        ], 201);
    }

    /**
     * Like a suggestion.
     *
     * @param LikeSuggestionRequest $request
     * @return JsonResponse
     */
    public function like(LikeSuggestionRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $suggestionId = (int) $request->input('suggestion_id');

        $result = $this->suggestionService->likeSuggestion($userId, $suggestionId);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'message' => 'Suggestion liked successfully',
            'suggestion_id' => $result['suggestion_id'],
            'is_liked' => $result['is_liked'],
            'likes_count' => $result['likes_count'],
        ], 201);
    }

    /**
     * Remove like from a suggestion.
     *
     * @param LikeSuggestionRequest $request
     * @return JsonResponse
     */
    public function unlike(LikeSuggestionRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $suggestionId = (int) $request->input('suggestion_id');

        $result = $this->suggestionService->unlikeSuggestion($userId, $suggestionId);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'message' => 'Suggestion unliked successfully',
            'suggestion_id' => $result['suggestion_id'],
            'is_liked' => $result['is_liked'],
            'likes_count' => $result['likes_count'],
        ], 200);
    }

    /**
     * Approve a suggestion.
     *
     * @param ApproveSuggestionRequest $request
     * @return JsonResponse
     */
    public function approve(ApproveSuggestionRequest $request): JsonResponse
    {
        $suggestionId = (int) $request->input('suggestion_id');
        $suggestion = Suggestion::find($suggestionId);

        if (!$suggestion) {
            return response()->json([
                'message' => 'Suggestion not found.',
            ], 404);
        }

        // Authorize using SuggestionPolicy
        if (Gate::denies('approve', $suggestion)) {
            return response()->json([
                'message' => 'You are not allowed to approve this suggestion.',
            ], 403);
        }

        $result = $this->suggestionService->approveSuggestion($suggestionId);

        return response()->json([
            'message' => 'Suggestion approved successfully',
            'suggestion' => new SuggestionApproveResource($result['suggestion']),
        ], 200);
    }
}
