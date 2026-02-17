<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PostSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_schedule_post_for_future(): void
    {
        $user = User::factory()->create();

        $scheduledAt = now()->addHour()->format('Y-m-d H:i:s');

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'content' => 'This is a scheduled post',
            'scheduled_at' => $scheduledAt,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Post scheduled successfully for ' . $scheduledAt,
        ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'This is a scheduled post',
            'is_published' => false,
        ]);
    }

    public function test_scheduled_post_is_not_visible_in_feed(): void
    {
        $user = User::factory()->create();

        // Create a scheduled post
        $scheduledPost = Post::factory()
            ->scheduled()
            ->create(['user_id' => $user->id]);

        // Create a published post
        $publishedPost = Post::factory()
            ->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/posts');

        $response->assertStatus(200);
        $response->assertJsonMissing(['id' => $scheduledPost->id]);
        $response->assertJsonFragment(['id' => $publishedPost->id]);
    }

    public function test_scheduled_posts_are_published_by_command(): void
    {
        $user = User::factory()->create();

        // Create posts scheduled for publishing (in the past)
        $post1 = Post::factory()
            ->scheduledForPublishing()
            ->create(['user_id' => $user->id]);

        $post2 = Post::factory()
            ->scheduledForPublishing()
            ->create(['user_id' => $user->id]);

        // Create a post scheduled for future
        $futurePost = Post::factory()
            ->scheduled(60)
            ->create(['user_id' => $user->id]);

        $this->assertFalse($post1->fresh()->is_published);
        $this->assertFalse($post2->fresh()->is_published);
        $this->assertFalse($futurePost->fresh()->is_published);

        // Run the publish command
        Artisan::call('posts:publish-scheduled');

        // Check that past scheduled posts are published
        $this->assertTrue($post1->fresh()->is_published);
        $this->assertTrue($post2->fresh()->is_published);
        
        // Future post should still be unpublished
        $this->assertFalse($futurePost->fresh()->is_published);
    }

    public function test_user_cannot_view_others_scheduled_posts(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $scheduledPost = Post::factory()
            ->scheduled()
            ->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->getJson("/api/posts/{$scheduledPost->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_view_own_scheduled_posts(): void
    {
        $user = User::factory()->create();

        $scheduledPost = Post::factory()
            ->scheduled()
            ->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/posts/{$scheduledPost->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $scheduledPost->id,
            'is_published' => false,
        ]);
    }

    public function test_post_with_past_scheduled_date_is_published_immediately(): void
    {
        $user = User::factory()->create();

        // Try to schedule a post in the past
        $scheduledAt = now()->subHour()->format('Y-m-d H:i:s');

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'content' => 'This post has past scheduled date',
            'scheduled_at' => $scheduledAt,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('scheduled_at');
    }

    public function test_scheduled_at_validation_rejects_invalid_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'content' => 'Test content',
            'scheduled_at' => 'invalid-date',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('scheduled_at');
    }

    public function test_post_without_scheduled_at_is_published_immediately(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'content' => 'Immediate post',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'Immediate post',
            'is_published' => true,
            'scheduled_at' => null,
        ]);
    }
}
