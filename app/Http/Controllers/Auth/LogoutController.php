<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\LogoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    protected LogoutService $logoutService;

    /**
     * Create a new controller instance.
     */
    public function __construct(LogoutService $logoutService)
    {
        $this->logoutService = $logoutService;
    }

    /**
     * Log out the authenticated user by revoking their current token.
     */
    public function logout(Request $request): Response
    {
        $this->logoutService->logout($request);

        return response()->noContent();
    }
}
