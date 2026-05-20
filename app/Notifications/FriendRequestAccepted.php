<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class FriendRequestAccepted extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public User $accepter) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'friend_request_accepted',
            'accepter_id' => $this->accepter->id,
            'accepter_name' => $this->accepter->name,
            'accepter_photo' => $this->accepter->profile_photo_url,
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
