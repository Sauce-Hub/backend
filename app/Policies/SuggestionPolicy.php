<?php

namespace App\Policies;

use App\Models\Suggestion;
use App\Models\User;

class SuggestionPolicy
{
    /**
     * Determine whether the user can approve the suggestion.
     */
    public function approve(User $user, Suggestion $suggestion): bool
    {
        return $suggestion->receipt && $suggestion->receipt->user_id === $user->user_id;
    }

    /**
     * Determine whether the user can update the suggestion.
     */
    public function update(User $user, Suggestion $suggestion): bool
    {
        return $suggestion->user_id === $user->user_id;
    }
}
