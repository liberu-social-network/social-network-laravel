<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Comment $comment,
        public int $commentsCount
    ) {
        $this->comment->load('user');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('post.' . $this->comment->post_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'comment' => [
                'id' => $this->comment->id,
                'content' => $this->comment->content,
                'user' => [
                    'id' => $this->comment->user->id,
                    'name' => $this->comment->user->name,
                ],
                'created_at' => $this->comment->created_at->toISOString(),
            ],
            'comments_count' => $this->commentsCount,
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }
}
