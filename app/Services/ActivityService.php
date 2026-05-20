<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Support\Collection;

class ActivityService
{
    /**
     * Create an activity for the user's network
     */
    public function createActivity(
        int $actorId,
        string $type,
        $subject,
        ?array $data = null
    ): void {
        // Get all friends of the actor
        $friendIds = $this->getFriendIds($actorId);
        
        // Add the actor themselves to see their own activity
        $friendIds->push($actorId);
        
        // Create activity records for each user in the network
        foreach ($friendIds as $userId) {
            Activity::create([
                'user_id' => $userId,
                'actor_id' => $actorId,
                'type' => $type,
                'subject_type' => get_class($subject),
                'subject_id' => $subject->id,
                'data' => $data,
            ]);
        }
    }

    /**
     * Get activities for a user's feed
     */
    public function getActivitiesForUser(int $userId, int $limit = 20): Collection
    {
        return Activity::with(['actor', 'subject'])
            ->forUser($userId)
            ->recent($limit)
            ->get();
    }

    /**
     * Get friend IDs for a user
     */
    protected function getFriendIds(int $userId): Collection
    {
        $friends = Friendship::where(function ($query) use ($userId) {
            $query->where('requester_id', $userId)
                ->orWhere('addressee_id', $userId);
        })
        ->where('status', 'accepted')
        ->get();

        return $friends->map(function ($friendship) use ($userId) {
            return $friendship->requester_id === $userId 
                ? $friendship->addressee_id 
                : $friendship->requester_id;
        });
    }

    /**
     * Delete activities related to a subject
     */
    public function deleteActivitiesForSubject($subject): void
    {
        Activity::where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id)
            ->delete();
    }
}
