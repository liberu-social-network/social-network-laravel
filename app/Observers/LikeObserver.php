<?php

namespace App\Observers;

use App\Models\Like;
use App\Services\ActivityService;

class LikeObserver
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Handle the Like "created" event.
     */
    public function created(Like $like): void
    {
        $like->load('post');
        
        $this->activityService->createActivity(
            actorId: $like->user_id,
            type: 'post_liked',
            subject: $like,
            data: [
                'post_id' => $like->post_id,
                'post_content_preview' => $like->post ? substr($like->post->content, 0, 100) : null
            ]
        );
    }

    /**
     * Handle the Like "deleted" event.
     */
    public function deleted(Like $like): void
    {
        $this->activityService->deleteActivitiesForSubject($like);
    }
}
