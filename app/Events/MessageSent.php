<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load(['sender', 'receiver', 'attachments', 'reactions']);
    }

    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->message->conversation_id) {
            $channels[] = new PresenceChannel('conversation.' . $this->message->conversation_id);
        } else {
            $channels[] = new PrivateChannel('user.' . $this->message->sender_id);
            $channels[] = new PrivateChannel('user.' . $this->message->receiver_id);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'content' => $this->message->decrypted_content,
            'created_at' => $this->message->created_at->toISOString(),
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'profile_photo_url' => $this->message->sender->profile_photo_url,
            ],
            'attachments' => $this->message->attachments,
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
