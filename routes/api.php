<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Http\Request;
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
    Route::get('/profile/', function () {
        return response()->json(['message' => 'Profile stub']);
    });

    // Comments
    Route::get('/comments/', function () {
        return response()->json(['message' => 'Comments list stub']);
    });
    Route::post('/comment/', function () {
        return response()->json(['message' => 'Add comment stub']);
    });
    Route::post('/like-comment/', function () {
        return response()->json(['message' => 'Like comment stub']);
    });
    Route::delete('/like-comment/', function () {
        return response()->json(['message' => 'Remove comment like stub']);
    });

    // Suggestions
    Route::get('/suggestions/', function () {
        return response()->json(['message' => 'Suggestions list stub']);
    });
    Route::post('/suggestion/', function () {
        return response()->json(['message' => 'Add suggestion stub']);
    });
    Route::post('/like-suggestion/', function () {
        return response()->json(['message' => 'Like suggestion stub']);
    });
    Route::delete('/like-suggestion/', function () {
        return response()->json(['message' => 'Remove suggestion like stub']);
    });
    Route::patch('/approve-suggestion/', function () {
        return response()->json(['message' => 'Approve suggestion stub']);
    });

    // Temporary Sanctum verification route (Not part of Cooktributors API contract)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
