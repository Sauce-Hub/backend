<?php

namespace App\Policies;

use App\Models\Suggestion;
use App\Models\User;

class SuggestionPolicy
{
    /**
     * Determine whether the user can approve the suggestion.
     *
     * @param User $user
     * @param Suggestion $suggestion
     * @return bool
     */
    public function approve(User $user, Suggestion $suggestion): bool
    {
        return $suggestion->receipt && $suggestion->receipt->user_id === $user->user_id;
    }
}
