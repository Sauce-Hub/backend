<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favorites;
use App\Models\Receipt;

class FavoritesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = auth()->id();

        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 20);

        $query = Receipt::whereHas('favoritedBy', function ($q) use ($userId) {
            $q->where('users.user_id', $userId);
        })
        ->with(['user', 'likedBy' => function ($q) use ($userId) {
            $q->where('users.user_id', $userId);
        }])
        ->withCount([
            'likedBy as likes_count',
            'comments as comments_count',
            'favoritedBy as favorites_count',
        ]);

        $receipts = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $receipts->getCollection()->map(function (Receipt $r) {
            return [
                'receipt_id' => $r->receipt_id,
                'name' => $r->name,
                'caption' => $r->caption,
                'category' => $r->category,
                'timestamp' => $r->timestamp ? $r->timestamp->toIso8601String() : null,
                'user' => [
                    'user_id' => $r->user?->user_id,
                    'name' => $r->user?->name,
                ],
                'likes_count' => (int) $r->likes_count,
                'comments_count' => (int) $r->comments_count,
                'favorites_count' => (int) $r->favorites_count,
                'is_liked' => $r->relationLoaded('likedBy') && $r->likedBy->isNotEmpty(),
                'is_favorited' => true,
            ];
        })->toArray();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $receipts->currentPage(),
                'per_page' => $receipts->perPage(),
                'total' => $receipts->total(),
                'last_page' => $receipts->lastPage(),
            ],
        ]);
    }

    public function add(Request $request)
    {
        
    }

    public function remove(Request $request)
    {
        
    }
}
