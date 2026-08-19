<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\Receipt\CreateReceiptRequest;
use Illuminate\Http\Request;
use App\Models\Ingredient;
use App\Models\Instruction;
use App\Models\User;

class ReceiptController extends Controller
{
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
