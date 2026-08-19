<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\Receipt\CreateReceiptRequest;
use Illuminate\Http\Request;
use App\Models\Ingredient;
use App\Models\Instruction;

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
        $image_url = $request->input('receipt.image')->store('receipts', 'public');

        $response = Http::post(
            config('services.ai.url') . '/calculate-nutrition/',
            [
                'ingredients' => $ingredientsData,
                'instructions' => $instructionsConcatenated
            ]
        );

        if ($response) {
            Receipt::create([
                'name' => $receiptData['name'],
                'caption' => $receiptData['caption'] ?? null,
                'category' => $receiptData['category'],
                'image_url' => $image_url,
                'estimated_time' => $response['estimated_time'] ?? 0,
                'Calories' => $response['Calories'] ?? 0,
                'Fats' => $response['Fats'] ?? 0,
                'Carbs' => $response['Carbs'] ?? 0,
                'Protein' => $response['Protein'] ?? 0,
                'timestamp' => now(),
                'user_id' => auth()->id(),
            ]);

            Instruction::insert(array_map(function ($instruction, $index) {
                return [
                    'step_number' => $index + 1,
                    'instruction' => $instruction,
                    'receipt_id' => Receipt::latest('receipt_id')->first()->receipt_id,
                ];
            }, $instructionsData, array_keys($instructionsData)));

            Ingredient::insert(array_map(function ($ingredient) {
                return [
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity'],
                    'unit' => $ingredient['unit'],
                    'receipt_id' => Receipt::latest('receipt_id')->first()->receipt_id,
                ];
            }, $ingredientsData));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $receiptId = $request->input('receipt');
    }
}
