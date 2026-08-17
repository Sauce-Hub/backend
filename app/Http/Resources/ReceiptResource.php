<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'receipt_id' => $this->receipt_id,
            'name' => $this->name,
            'caption' => $this->caption,
            'category' => $this->category instanceof \BackedEnum ? $this->category->value : $this->category,
            'estimated_time' => $this->estimated_time,
            'Calories' => (int) $this->Calories,
            'Fats' => (int) $this->Fats,
            'Carbs' => (int) $this->Carbs,
            'Protein' => (int) $this->Protein,
            'timestamp' => $this->timestamp ? $this->timestamp->toIso8601ZuluString() : null,
        ];
    }
}
