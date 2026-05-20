<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $conversationId;
    public $receiverId;

    public function __construct(User $user, ?int $conversationId = null, ?int $receiverId = null)
    {
        $this->user = $user;
        $this->conversationId = $conversationId;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn(): array
    {
        if ($this->conversationId) {
            return [new PresenceChannel('conversation.' . $this->conversationId)];
        }

        if ($this->receiverId) {
            return [new PrivateChannel('user.' . $this->receiverId)];
        }

        return [];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'conversation_id' => $this->conversationId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }
}
