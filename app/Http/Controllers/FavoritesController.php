<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\FavoritesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    protected FavoritesService $favoritesService;

    public function __construct(FavoritesService $favoritesService)
    {
        $this->favoritesService = $favoritesService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();

        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 20);

        $result = $this->favoritesService->getFavoritesForUser($userId, $page, $perPage);

        $data = $result['receipts']->getCollection()->map(function (Receipt $receipt) {
            return [
                'receipt_id' => $receipt->receipt_id,
                'name' => $receipt->name,
                'caption' => $receipt->caption,
                'category' => $receipt->category,
                'timestamp' => $receipt->timestamp ? $receipt->timestamp->toIso8601String() : null,
                'user' => [
                    'user_id' => $receipt->user?->user_id,
                    'name' => $receipt->user?->name,
                ],
                'likes_count' => (int) $receipt->likes_count,
                'comments_count' => (int) $receipt->comments_count,
                'favorites_count' => (int) $receipt->favorites_count,
                'is_liked' => $receipt->likedBy->isNotEmpty(),
                'is_favorited' => true,
            ];
        })->values()->all();

        return response()->json([
            'data' => $data,
            'meta' => $result['pagination'],
        ], 200);
    }

    public function add(Request $request)
    {
        
    }

    public function remove(Request $request)
    {
        
    }
}
