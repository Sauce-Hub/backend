<?php

namespace App\Http\Requests\Chatbot;

use App\Enums\ReceiptCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchEngineRequest extends FormRequest
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
            'include_ingredients' => ['nullable', 'array'],
            'include_ingredients.*' => ['string'],
            'exclude_ingredients' => ['nullable', 'array'],
            'exclude_ingredients.*' => ['string'],
            'category' => ['nullable', Rule::enum(ReceiptCategory::class)],
            'max_estimated_time_min' => ['nullable', 'integer', 'min:0'],
            'max_calories' => ['nullable', 'integer', 'min:0'],
            'min_protein' => ['nullable', 'integer', 'min:0'],
            'min_carbs' => ['nullable', 'integer', 'min:0'],
            'max_carbs' => ['nullable', 'integer', 'min:0'],
            'min_fats' => ['nullable', 'integer', 'min:0'],
            'max_fats' => ['nullable', 'integer', 'min:0'],
            'min_calories' => ['nullable', 'integer', 'min:0'],
            'max_protein' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
