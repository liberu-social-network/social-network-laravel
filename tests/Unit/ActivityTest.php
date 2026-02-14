<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_belongs_to_user()
    {
        $user = User::factory()->create();
        $actor = User::factory()->create();
        $post = Post::factory()->create();
        
        $activity = Activity::create([
            'user_id' => $user->id,
            'actor_id' => $actor->id,
            'type' => 'post_created',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
        ]);

        $this->assertInstanceOf(User::class, $activity->user);
        $this->assertEquals($user->id, $activity->user->id);
    }

    public function test_activity_belongs_to_actor()
    {
        $user = User::factory()->create();
        $actor = User::factory()->create();
        $post = Post::factory()->create();
        
        $activity = Activity::create([
            'user_id' => $user->id,
            'actor_id' => $actor->id,
            'type' => 'post_created',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
        ]);

        $this->assertInstanceOf(User::class, $activity->actor);
        $this->assertEquals($actor->id, $activity->actor->id);
    }

    public function test_activity_has_subject()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        
        $activity = Activity::create([
            'user_id' => $user->id,
            'actor_id' => $user->id,
            'type' => 'post_created',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
        ]);

        $this->assertInstanceOf(Post::class, $activity->subject);
        $this->assertEquals($post->id, $activity->subject->id);
    }

    public function test_activity_data_is_casted_to_array()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        
        $data = ['key' => 'value', 'number' => 123];
        
        $activity = Activity::create([
            'user_id' => $user->id,
            'actor_id' => $user->id,
            'type' => 'post_created',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'data' => $data,
        ]);

        $this->assertIsArray($activity->data);
        $this->assertEquals($data, $activity->data);
    }

    public function test_for_user_scope_filters_by_user_id()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->create();
        
        Activity::create([
            'user_id' => $user1->id,
            'actor_id' => $user1->id,
            'type' => 'post_created',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
        ]);
        
        Activity::create([
            'user_id' => $user2->id,
            'actor_id' => $user2->id,
            'type' => 'post_created',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
        ]);

        $user1Activities = Activity::forUser($user1->id)->get();
        
        $this->assertEquals(1, $user1Activities->count());
        $this->assertEquals($user1->id, $user1Activities->first()->user_id);
    }
}
