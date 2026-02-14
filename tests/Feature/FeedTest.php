<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Friendship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_feed_with_own_posts(): void
    {
        $user = User::factory()->create();
        Post::factory(3)->create(['user_id' => $user->id]);
        Post::factory(2)->create(); // Other users' posts

        $response = $this->actingAs($user)->getJson('/api/feed');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_feed_includes_friends_posts(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        
        // Create friendship
        Friendship::factory()->create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);

        Post::factory(2)->create(['user_id' => $user->id]);
        Post::factory(3)->create(['user_id' => $friend->id]);
        Post::factory(2)->create(); // Other users' posts

        $response = $this->actingAs($user)->getJson('/api/feed');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data'); // 2 own + 3 friend
    }

    public function test_user_can_view_timeline_of_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        Post::factory(5)->create(['user_id' => $otherUser->id]);
        Post::factory(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/timeline/{$otherUser->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_feed_includes_interaction_flags(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        
        // Like the post
        $this->actingAs($user)->postJson("/api/posts/{$post->id}/like");

        $response = $this->actingAs($user)->getJson('/api/feed');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'is_liked' => true,
        ]);
    }

    public function test_guest_cannot_view_feed(): void
    {
        $response = $this->getJson('/api/feed');

        $response->assertStatus(401);
    }
}
