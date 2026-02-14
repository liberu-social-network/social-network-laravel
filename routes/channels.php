<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private user channel for direct messages
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Presence channel for conversations (group messaging)
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    
    if (!$conversation) {
        return false;
    }
    
    // Check if user is a participant
    $isParticipant = $conversation->participants()
        ->where('user_id', $user->id)
        ->whereNull('left_at')
        ->exists();
    
    if ($isParticipant) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'profile_photo_url' => $user->profile_photo_url,
        ];
    }
    
    return false;
});

