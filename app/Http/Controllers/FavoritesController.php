<?php

namespace App\Http\Controllers;

use App\Http\Requests\Favorites\AddOrRemoveRequest;
use App\Http\Requests\Favorites\GetFavoritesRequest;
use App\Models\Receipt;
use App\Services\FavoritesService;
use Illuminate\Http\JsonResponse;

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
    public function index(GetFavoritesRequest $request): JsonResponse
    {
        $userId = auth()->id();

        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);

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

    public function add(AddOrRemoveRequest $request): JsonResponse
    {
        $userId = auth()->id();

        if (! $userId) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $receiptId = (int) $request->input('receipt_id');
        $result = $this->favoritesService->addFavorite($userId, $receiptId);

        if (! $result['success']) {
            $status = match ($result['message']) {
                'Receipt not found.' => 404,
                'Receipt already in favorites.' => 409,
                default => 400,
            };

            return response()->json([
                'message' => $result['message'],
            ], $status);
        }

        return response()->json([
            'message' => $result['message'],
            'receipt_id' => $receiptId,
            'is_favorited' => true,
        ], 201);
    }

    public function remove(AddOrRemoveRequest $request): JsonResponse
    {
        $userId = auth()->id();

        if (! $userId) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $receiptId = (int) $request->input('receipt_id');
        $result = $this->favoritesService->removeFavorite($userId, $receiptId);

        if (! $result['success']) {
            $status = match ($result['message']) {
                'Receipt not found.' => 404,
                'Favorite not found.' => 404,
                default => 400,
            };

            return response()->json([
                'message' => $result['message'],
            ], $status);
        }

        return response()->json([
            'message' => $result['message'],
            'receipt_id' => $receiptId,
            'is_favorited' => false,
        ], 200);
    }
}
