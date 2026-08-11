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
        return Auth::user();
    }
}
