<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostShared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Post $post,
        public User $user,
        public int $sharesCount
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
            'shares_count' => $this->sharesCount,
        ];
    }

    public function broadcastAs(): string
    {
        return 'post.shared';
    }
}
