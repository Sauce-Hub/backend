<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateAiServiceKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredApiKey = (string) config('services.ai.api_key', '');
        $incomingApiKey = (string) $request->header('X-API-KEY', '');

        if ($configuredApiKey === '' || $incomingApiKey === '' || ! hash_equals($configuredApiKey, $incomingApiKey)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}