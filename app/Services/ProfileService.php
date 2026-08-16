<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ProfileService
{
    /**
     * Get the profile of the authenticated user.
     */
    public function getProfile(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            $user->load('receipts');
        }

        return $user;
    }
}
