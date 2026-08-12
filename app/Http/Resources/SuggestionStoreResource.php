<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuggestionStoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'receipt_id' => $this->receipt_id,
            'text' => $this->text,
            'isApproved' => (bool) $this->isApproved,
            'timestamp' => $this->timestamp ? $this->timestamp->toIso8601ZuluString() : null,
            'ingredients' => IngredientResource::collection($this->ingredients),
        ];
    }
}
