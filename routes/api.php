<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;

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

// Temporary Sanctum verification route (Not part of Cooktributors API contract)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

