<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityService $activityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityService = app(ActivityService::class);
    }

    public function test_activity_feed_page_requires_authentication()
    {
        $response = $this->get('/activity-feed');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_activity_feed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/activity-feed');
        $response->assertStatus(200);
        $response->assertSeeLivewire('activity-feed');
    }

    public function test_post_creation_generates_activity()
    {
        $user = User::factory()->create();
        
        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Test post content',
        ]);

        $this->assertDatabaseHas('activities', [
            'actor_id' => $user->id,
            'type' => 'post_created',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
        ]);
    }

    public function test_like_creation_generates_activity()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        
        $like = Like::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->assertDatabaseHas('activities', [
            'actor_id' => $user->id,
            'type' => 'post_liked',
            'subject_type' => Like::class,
            'subject_id' => $like->id,
        ]);
    }

    public function test_comment_creation_generates_activity()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        
        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Test comment',
        ]);

        $this->assertDatabaseHas('activities', [
            'actor_id' => $user->id,
            'type' => 'comment_added',
            'subject_type' => Comment::class,
            'subject_id' => $comment->id,
        ]);
    }

    public function test_activity_deletion_when_post_is_deleted()
    {
        $user = User::factory()->create();
        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Test post to be deleted',
        ]);

        $activityCount = Activity::where('subject_type', Post::class)
            ->where('subject_id', $post->id)
            ->count();
        
        $this->assertGreaterThan(0, $activityCount);

        $post->delete();

        $activityCount = Activity::where('subject_type', Post::class)
            ->where('subject_id', $post->id)
            ->count();
        
        $this->assertEquals(0, $activityCount);
    }

    public function test_activity_service_retrieves_user_activities()
    {
        $user = User::factory()->create();
        
        // Create some activities
        Post::create(['user_id' => $user->id, 'content' => 'Post 1']);
        Post::create(['user_id' => $user->id, 'content' => 'Post 2']);

        $activities = $this->activityService->getActivitiesForUser($user->id);
        
        $this->assertGreaterThan(0, $activities->count());
        $this->assertEquals($user->id, $activities->first()->user_id);
    }
}
