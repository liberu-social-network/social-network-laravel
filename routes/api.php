<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
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

Route::middleware('auth:sanctum')->group(function () {
    // Messages
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);
    Route::get('/messages/conversation/{user}', [MessageController::class, 'conversation']);
    Route::get('/messages/{message}', [MessageController::class, 'show']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);
    
    // Message Reactions
    Route::post('/messages/{message}/reactions', [MessageController::class, 'addReaction']);
    Route::delete('/messages/{message}/reactions/{emoji}', [MessageController::class, 'removeReaction']);
    
    // Typing Indicator
    Route::post('/messages/typing', [MessageController::class, 'typing']);
    
    // Conversations
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'conversationMessages']);
    Route::post('/conversations/{conversation}/participants', [ConversationController::class, 'addParticipants']);
    Route::delete('/conversations/{conversation}/participants/{user}', [ConversationController::class, 'removeParticipant']);
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

