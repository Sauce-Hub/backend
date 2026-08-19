<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register/', [RegisterController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login/', [LoginController::class, 'login'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/logout/', [LogoutController::class, 'logout']);

    // Profile
    Route::get('/profile/', [ProfileController::class, 'show']);

    // Comments
    Route::get('/comments/', [CommentController::class, 'index']);
    Route::post('/comment/', [CommentController::class, 'store']);
    Route::post('/like-comment/', [CommentController::class, 'like']);
    Route::delete('/like-comment/', [CommentController::class, 'unlike']);

    // Suggestions
    Route::get('/suggestions/', [SuggestionController::class, 'index']);
    Route::post('/suggestion/', [SuggestionController::class, 'store']);
    Route::put('/suggestion/', [SuggestionController::class, 'update']);
    Route::post('/like-suggestion/', [SuggestionController::class, 'like']);
    Route::delete('/like-suggestion/', [SuggestionController::class, 'unlike']);
    Route::patch('/approve-suggestion/', [SuggestionController::class, 'approve']);

    Route::get('/favorites/', [FavoritesController::class, 'index']);
    Route::post('/add-favorite/', [FavoritesController::class, 'add']);
    Route::delete('/remove-favorite/', [FavoritesController::class, 'remove']);

    Route::get('/get-ai-response/', [ChatbotController::class, 'getResponse']);

    Route::get('/fyp/', [ReceiptController::class, 'index']);
    Route::get('/specific-content/', [ReceiptController::class, 'getByCategory']);
    Route::get('/receipt-details/', [ReceiptController::class, 'show']);
    Route::post('/new-post/', [ReceiptController::class, 'store']);
});

Route::post('/search-engine/', [ChatbotController::class, 'searchEngine'])
    ->middleware('ai.service.key');
