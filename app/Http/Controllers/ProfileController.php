<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    protected ProfileService $profileService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Get the authenticated user's profile details.
     */
    public function show(): JsonResponse
    {
        $user = $this->profileService->getProfile();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json((new UserResource($user))->resolve());
    }
}
