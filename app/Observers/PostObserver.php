<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\ActivityService;

class PostObserver
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->activityService->createActivity(
            actorId: $post->user_id,
            type: 'post_created',
            subject: $post,
            data: ['content_preview' => substr($post->content, 0, 100)]
        );
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        $this->activityService->deleteActivitiesForSubject($post);
    }
}
