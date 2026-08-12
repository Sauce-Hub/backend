<?php

namespace App\Http\Controllers;

use App\Http\Requests\Suggestions\GetSuggestionsRequest;
use App\Http\Resources\SuggestionResource;
use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;

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
}
