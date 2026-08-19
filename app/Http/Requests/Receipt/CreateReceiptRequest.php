<?php

namespace App\Http\Requests\Receipt;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateReceiptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receipt' => 'required|array',
            'receipt.name' => 'required|string|max:255',
            'receipt.caption' => 'nullable|string|max:255',
            'receipt.category' => 'required|in:BREAKFAST,LUNCH,DINNER,SWEETS,HOT DRINKS,ICED DRINKS',
            'receipt.image' => 'required|image|max:5120',
            'ingredients' => 'required|array',
            'ingredients.*.name' => 'required|string|max:255',
            'ingredients.*.quantity' => 'required|numeric|min:0',
            'ingredients.*.unit' => 'required|in:g,kg,ml,l,tsp,tbsp,cup,piece',
            'instructions' => 'required|array',
            'instructions.*' => 'required|string|max:1000',
        ];
    }
}
