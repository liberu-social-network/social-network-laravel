<?php

namespace App\Observers;

use App\Models\Comment;
use App\Services\ActivityService;

class CommentObserver
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Handle the Comment "created" event.
     */
    public function created(Comment $comment): void
    {
        $comment->load('post');
        
        $this->activityService->createActivity(
            actorId: $comment->user_id,
            type: 'comment_added',
            subject: $comment,
            data: [
                'post_id' => $comment->post_id,
                'comment_preview' => substr($comment->content, 0, 100),
                'post_content_preview' => $comment->post ? substr($comment->post->content, 0, 100) : null
            ]
        );
    }

    /**
     * Handle the Comment "deleted" event.
     */
    public function deleted(Comment $comment): void
    {
        $this->activityService->deleteActivitiesForSubject($comment);
    }
}
