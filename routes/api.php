<?php

use App\Http\Controllers\ProfileController;
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

Route::middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('/profile/', [ProfileController::class, 'show']);

    // Temporary Sanctum verification route (Not part of Cooktributors API contract)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
