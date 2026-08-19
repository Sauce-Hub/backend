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

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);

        $recommendations = Recommendation::query()
            ->where('user_id', $userId)
            ->where('seen', false)
            ->with([
                'receipt' => function ($query) use ($userId) {
                    $query->withCount(['likedBy', 'comments', 'favoritedBy'])
                        ->withExists([
                            'likedBy as is_liked' => fn ($q) => $q->where('user_id', $userId),
                            'favoritedBy as is_favorited' => fn ($q) => $q->where('user_id', $userId),
                        ]);
                },
                'receipt.user',
            ])
            ->orderByDesc('receipt_id')
            ->paginate($perPage, ['*'], 'page', $page);

        if ($recommendations->isNotEmpty()) {
            Recommendation::query()
                ->whereIn('id', $recommendations->pluck('id'))
                ->update(['seen' => true]);
        }

        $data = $recommendations->getCollection()->map(function (Recommendation $recommendation) {
            $receipt = $recommendation->receipt;

            if (! $receipt) {
                return null;
            }

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
        })->filter()->values()->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $recommendations->currentPage(),
                'last_page' => $recommendations->lastPage(),
                'per_page' => $recommendations->perPage(),
                'total' => $recommendations->total(),
            ],
        ], 200);
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

        $response = Http::post(
            config('services.ai.url') . '/calculate-nutrition/',
            [
                'ingredients' => $ingredientsData,
                'instructions' => $instructionsConcatenated,
            ]
        );

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Failed to calculate nutrition.',
            ], 502);
        }

        $receipt = Receipt::create([
            'name' => $receiptData['name'],
            'caption' => $receiptData['caption'] ?? null,
            'category' => $receiptData['category'],
            'image_url' => $imageUrl,
            'estimated_time' => $response['estimated_time'] ?? 0,
            'Calories' => $response['Calories'] ?? 0,
            'Fats' => $response['Fats'] ?? 0,
            'Carbs' => $response['Carbs'] ?? 0,
            'Protein' => $response['Protein'] ?? 0,
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

        Ingredient::insert(array_map(function ($ingredient) use ($receipt) {
            return [
                'name' => $ingredient['name'],
                'quantity' => $ingredient['quantity'],
                'unit' => $ingredient['unit'],
                'isAssigned' => true,
                'receipt_id' => $receipt->receipt_id,
            ];
        }, $ingredientsData));

        return response()->json([
            'message' => 'Receipt created successfully',
            'receipt' => $receipt,
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
}
