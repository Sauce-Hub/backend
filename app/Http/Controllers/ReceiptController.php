<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\Recommendation;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\Receipt\CreateReceiptRequest;
use Illuminate\Http\Request;
use App\Models\Ingredient;
use App\Models\Instruction;
use App\Models\User;
use App\Models\Event;
use Illuminate\Support\Facades\Log;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $perPage = (int) $request->query('per_page', 10);

        $recommendations = Recommendation::query()
            ->where('user_id', $userId)
            ->with([
                'receipt' => fn ($q) => $this->applyReceiptMetrics($q, $userId),
                'receipt.user',
            ])
            ->orderByDesc('id')
            ->paginate($perPage);

        if ($recommendations->isEmpty()) {
            return $this->getFallbackFeed($userId, $perPage);
        }

        Recommendation::query()
            ->whereIn('id', $recommendations->pluck('id'))
            ->where('seen', false)
            ->update(['seen' => true]);

        $receipts = $recommendations->pluck('receipt')->filter();

        return response()->json([
            'data' => $this->formatReceipts($receipts),
            'meta' => $this->formatMeta($recommendations),
        ], 200);
    }

    public function getByCategory(Request $request)
    {
        $userId = auth()->id();

        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'category' => 'required|string',
        ]);

        $perPage = (int) $request->query('per_page', 10);

        $receipts = Receipt::query()
            ->where('category', $request->input('category'))
            ->with(['user'])
            ->tap(fn ($q) => $this->applyReceiptMetrics($q, $userId))
            ->orderByDesc('receipt_id')
            ->paginate($perPage);

        return response()->json([
            'data' => $this->formatReceipts($receipts->items()),
            'meta' => $this->formatMeta($receipts),
        ], 200);
    }

    private function getFallbackFeed(int $userId, int $perPage)
    {
        $receipts = Receipt::query()
            ->with(['user'])
            ->tap(fn ($q) => $this->applyReceiptMetrics($q, $userId))
            ->orderByDesc('receipt_id')
            ->paginate($perPage);

        return response()->json([
            'data' => $this->formatReceipts($receipts->items()),
            'meta' => $this->formatMeta($receipts),
        ], 200);
    }

    private function applyReceiptMetrics($query, int $userId)
    {
        return $query->withCount(['likedBy', 'comments', 'favoritedBy'])
            ->withExists([
                'likedBy as is_liked' => fn ($q) => $q->where('user_id', $userId),
                'favoritedBy as is_favorited' => fn ($q) => $q->where('user_id', $userId),
            ]);
    }

    private function formatReceipts(iterable $receipts): array
    {
        return collect($receipts)->map(function (Receipt $receipt) {
            return [
                'receipt_id' => $receipt->receipt_id,
                'name' => $receipt->name,
                'caption' => $receipt->caption,
                'category' => $receipt->category,
                'image_url' => $receipt->image_url,
                'timestamp' => $receipt->timestamp ? $receipt->timestamp->toIso8601String() : null,
                'user' => [
                    'user_id' => $receipt->user?->user_id,
                    'name' => $receipt->user?->name,
                ],
                'likes_count' => $receipt->liked_by_count ?? 0,
                'comments_count' => $receipt->comments_count ?? 0,
                'favorites_count' => $receipt->favorited_by_count ?? 0,
                'is_favorited' => (bool) $receipt->is_favorited,
                'is_liked' => (bool) $receipt->is_liked,
            ];
        })->values()->all();
    }

    private function formatMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    public function addEvent(Request $request)
    {
        $userId = auth()->id();

        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Event::create([
            'user_id' => $userId,
            'timestamp' => now(),
        ]);

        return response()->json(['message' => 'done'], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateReceiptRequest $request)
    {
        $receiptData = $request->input('receipt');
        $ingredientsData = $request->input('ingredients');
        $instructionsData = $request->input('instructions');
        $instructionsConcatenated = implode("\n", $instructionsData);
        $imageUrl = $request->file('receipt.image')->store('receipts', 'public');

        $aiData = [
            'estimated_time' => 0,
            'Calories' => 0,
            'Fats' => 0,
            'Carbs' => 0,
            'Protein' => 0
        ];

        try {
            $response = Http::withHeaders([
                'X-API-Key' => config('services.ai.api_key'),
            ])->post(
                config('services.ai.url') . '/calculate-nutrition/',
                [
                    'ingredients' => $ingredientsData,
                    'instructions' => $instructionsConcatenated,
                ]
            );

            if ($response->successful()) {
                $aiData = [
                    'estimated_time' => $response['estimated_time'] ?? 0,
                    'Calories' => $response['Calories'] ?? 0,
                    'Fats' => $response['Fats'] ?? 0,
                    'Carbs' => $response['Carbs'] ?? 0,
                    'Protein' => $response['Protein'] ?? 0,
                ];
            } else {
                Log::warning('AI Service returned an error, using default values.');
            }
        } catch (\Exception $e) {
            Log::error('AI Service connection failed: ' . $e->getMessage());
        }

        $receipt = Receipt::create([
            'name' => $receiptData['name'],
            'caption' => $receiptData['caption'] ?? null,
            'category' => $receiptData['category'],
            'image_url' => $imageUrl,
            'estimated_time' => $aiData['estimated_time'] ?? 0,
            'Calories' => $aiData['Calories'] ?? 0,
            'Fats' => $aiData['Fats'] ?? 0,
            'Carbs' => $aiData['Carbs'] ?? 0,
            'Protein' => $aiData['Protein'] ?? 0,
            'timestamp' => now(),
            'user_id' => auth()->id(),
        ]);

        Instruction::insert(array_map(function ($instruction, $index) use ($receipt) {
            return [
                'step_number' => $index + 1,
                'instruction' => $instruction,
                'receipt_id' => $receipt->receipt_id,
            ];
        }, $instructionsData, array_keys($instructionsData)));

        // Storing the ingredients object to return it
        $ingredients = collect($ingredientsData)->map(function ($ingredient) use ($receipt) {
        return Ingredient::create([
            'name' => $ingredient['name'],
            'quantity' => $ingredient['quantity'],
            'unit' => $ingredient['unit'],
            'isAssigned' => true,
            'receipt_id' => $receipt->receipt_id,
        ]);
    });

        return response()->json([
            'message' => 'Post created successfully',
            'receipt' => $receipt,
            'ingredients' => $ingredients,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $receiptId = $request->input('receipt_id');
        $receipt = Receipt::find($receiptId);

        if (! $receipt) {
            return response()->json([
                'message' => 'Not Found'
            ], 404);
        }

        $ingredients = Ingredient::where('receipt_id', $receiptId)->where('isAssigned', true)->get();
        $user = User::find($receipt->user_id);
        $instructions = Instruction::where('receipt_id', $receiptId)->where('suggestion_id', null)->orderBy('step_number')->get();
        $instructionsAsStrings = $instructions->pluck('instruction')->toArray();

        return response()->json([
            'message' => 'success',
            'receipt' => $receipt,
            'ingredients' => $ingredients,
            'instructions' => $instructionsAsStrings,
            'user' => [
                'user_id' => $receipt->user_id,
                'name' => $user->name ?? null,
            ],
        ]);
    }

    public function likeReceipt(Request $request)
    {
        $request->validate([
            'receipt_id' => 'required|integer|exists:receipts,receipt_id',
        ]);

        $receipt = Receipt::findOrFail($request->input('receipt_id'));
        $receipt->likedBy()->syncWithoutDetaching([auth()->id()]);

        return response()->json(['message' => 'success'], 200);
    }

    public function unlikeReceipt(Request $request)
    {
        $request->validate([
            'receipt_id' => 'required|integer|exists:receipts,receipt_id',
        ]);

        $receipt = Receipt::findOrFail($request->input('receipt_id'));
        $detached = $receipt->likedBy()->detach(auth()->id());

        if (! $detached) {
            return response()->json(['message' => 'Receipt was not liked before.'], 400);
        }

        return response()->json(['message' => 'success'], 200);
    }
}
