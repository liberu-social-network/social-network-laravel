<?php

namespace App\Events;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostLiked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Post $post,
        public User $user,
        public int $likesCount
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('post.' . $this->post->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'post_id' => $this->post->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'likes_count' => $this->likesCount,
        ];
    }

    public function broadcastAs(): string
    {
        return 'post.liked';
    }
}
