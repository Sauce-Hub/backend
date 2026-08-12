<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuggestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = auth()->id();

        // Calculate if the suggestion is liked by the currently authenticated user
        $isLiked = false;
        if ($userId) {
            $isLiked = $this->relationLoaded('likes')
                ? $this->likes->contains('user_id', $userId)
                : $this->likes()->where('users.user_id', $userId)->exists();
        }

        // Count of likes
        $likesCount = $this->relationLoaded('likes')
            ? $this->likes->count()
            : $this->likes()->count();

        return [
            'id' => $this->id,
            'receipt_id' => $this->receipt_id,
            'text' => $this->text,
            'isApproved' => (bool) $this->isApproved,
            'timestamp' => $this->timestamp ? $this->timestamp->toIso8601ZuluString() : null,
            'user' => [
                'user_id' => $this->user->user_id,
                'name' => $this->user->name,
            ],
            'likes_count' => $likesCount,
            'is_liked' => (bool) $isLiked,
            'ingredients' => IngredientResource::collection($this->ingredients),
        ];
    }
}
