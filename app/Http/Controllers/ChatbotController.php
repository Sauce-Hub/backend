<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Chatbot\GetResponseRequest;

class ChatbotController extends Controller
{
    public function getResponse(GetResponseRequest $request)
    {
        $userId = auth()->id();

        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        
    }
}
