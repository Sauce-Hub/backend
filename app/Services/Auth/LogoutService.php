<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;

class LogoutService
{
    /**
     * Revoke the current authenticated user's access token.
     *
     * @param Request $request
     * @return void
     */
    public function logout(Request $request): void
    {
        $request->user()->currentAccessToken()->delete();
    }
}
