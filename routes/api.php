<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Friend request routes
Route::middleware('auth:sanctum')->prefix('friendships')->group(function () {
    Route::get('/', [\App\Http\Controllers\FriendshipController::class, 'index']);
    Route::post('/send', [\App\Http\Controllers\FriendshipController::class, 'send']);
    Route::post('/accept', [\App\Http\Controllers\FriendshipController::class, 'accept']);
    Route::post('/reject', [\App\Http\Controllers\FriendshipController::class, 'reject']);
});

// Follower routes
Route::middleware('auth:sanctum')->prefix('followers')->group(function () {
    Route::get('/', [\App\Http\Controllers\FollowerController::class, 'index']);
    Route::post('/follow', [\App\Http\Controllers\FollowerController::class, 'follow']);
    Route::post('/unfollow', [\App\Http\Controllers\FollowerController::class, 'unfollow']);
});

// User search routes
Route::middleware('auth:sanctum')->get('/users/search', [\App\Http\Controllers\UserSearchController::class, 'search']);

