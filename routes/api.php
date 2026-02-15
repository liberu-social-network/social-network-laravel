<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\FeedController;

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

    // Feed routes
    Route::get('/feed', [FeedController::class, 'index']);
    Route::get('/timeline/{userId}', [FeedController::class, 'timeline']);

    // Post routes
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);

    // Comment routes
    Route::get('/posts/{postId}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{postId}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{commentId}', [CommentController::class, 'update']);
    Route::delete('/comments/{commentId}', [CommentController::class, 'destroy']);

    // Like routes
    Route::post('/posts/{postId}/like', [LikeController::class, 'toggle']);
    Route::get('/posts/{postId}/likes', [LikeController::class, 'index']);

    // Share routes
    Route::post('/posts/{postId}/share', [ShareController::class, 'toggle']);
    Route::get('/posts/{postId}/shares', [ShareController::class, 'index']);
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

// Group routes
Route::middleware('auth:sanctum')->prefix('groups')->group(function () {
    // Group CRUD
    Route::get('/', [\App\Http\Controllers\GroupController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\GroupController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\GroupController::class, 'show']);
    Route::put('/{id}', [\App\Http\Controllers\GroupController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\GroupController::class, 'destroy']);
    
    // Group members
    Route::get('/{id}/members', [\App\Http\Controllers\GroupController::class, 'members']);
    Route::get('/{id}/pending-members', [\App\Http\Controllers\GroupController::class, 'pendingMembers']);
    
    // Group membership management
    Route::post('/{groupId}/join', [\App\Http\Controllers\GroupMemberController::class, 'join']);
    Route::post('/{groupId}/leave', [\App\Http\Controllers\GroupMemberController::class, 'leave']);
    Route::post('/{groupId}/members/{userId}/approve', [\App\Http\Controllers\GroupMemberController::class, 'approve']);
    Route::post('/{groupId}/members/{userId}/reject', [\App\Http\Controllers\GroupMemberController::class, 'reject']);
    Route::delete('/{groupId}/members/{userId}', [\App\Http\Controllers\GroupMemberController::class, 'removeMember']);
    Route::put('/{groupId}/members/{userId}/role', [\App\Http\Controllers\GroupMemberController::class, 'updateRole']);
    
    // Group posts
    Route::get('/{groupId}/posts', [\App\Http\Controllers\GroupPostController::class, 'index']);
    Route::post('/{groupId}/posts', [\App\Http\Controllers\GroupPostController::class, 'store']);
    Route::put('/{groupId}/posts/{postId}', [\App\Http\Controllers\GroupPostController::class, 'update']);
    Route::delete('/{groupId}/posts/{postId}', [\App\Http\Controllers\GroupPostController::class, 'destroy']);
    
    // Group search and discovery
    Route::get('/search/query', [\App\Http\Controllers\GroupSearchController::class, 'search']);
    Route::get('/search/suggestions', [\App\Http\Controllers\GroupSearchController::class, 'suggestions']);
    Route::get('/search/popular', [\App\Http\Controllers\GroupSearchController::class, 'popular']);
});
