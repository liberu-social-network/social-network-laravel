<?php

namespace App\Events;

use App\Models\MessageReaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reaction;

    public function __construct(MessageReaction $reaction)
    {
        $this->reaction = $reaction->load(['user', 'message']);
    }

    public function broadcastOn(): array
    {
        $message = $this->reaction->message;
        
        if ($message->conversation_id) {
            return [new PresenceChannel('conversation.' . $message->conversation_id)];
        }

        return [
            new PrivateChannel('user.' . $message->sender_id),
            new PrivateChannel('user.' . $message->receiver_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->reaction->id,
            'message_id' => $this->reaction->message_id,
            'user_id' => $this->reaction->user_id,
            'emoji' => $this->reaction->emoji,
            'user' => [
                'id' => $this->reaction->user->id,
                'name' => $this->reaction->user->name,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'reaction.added';
    }
}
