<?php

namespace App\Http\Requests\Suggestions;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSuggestionRequest extends FormRequest
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
            'suggestion_id' => ['required', 'integer'],
            'text' => ['required', 'string', 'max:2000'],
            'ingredients' => ['present', 'array'],
            'ingredients.*.name' => ['required', 'string'],
            'ingredients.*.quantity' => ['required', 'numeric', 'gt:0'],
            'ingredients.*.unit' => ['required', 'string'],
            'ingredients.*.isAssigned' => ['nullable', 'boolean'],
            'instructions' => ['present', 'array'],
            'instructions.*.step_number' => ['required', 'integer', 'min:1'],
            'instructions.*.instruction' => ['required', 'string'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
