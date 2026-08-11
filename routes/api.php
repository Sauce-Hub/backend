<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;

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

    // Temporary Sanctum verification route (Not part of Cooktributors API contract)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
