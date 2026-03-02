<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class FriendRequestReceived extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public User $requester) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'friend_request_received',
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'requester_photo' => $this->requester->profile_photo_url,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }
}
