<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_like_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/posts/{$post->id}/like");

        $response->assertStatus(200);
        $response->assertJson([
            'liked' => true,
            'likes_count' => 1,
        ]);
        
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_user_can_unlike_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        Like::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/posts/{$post->id}/like");

        $response->assertStatus(200);
        $response->assertJson([
            'liked' => false,
            'likes_count' => 0,
        ]);
        
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_user_can_view_post_likes(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        Like::factory(3)->create(['post_id' => $post->id]);

        $response = $this->actingAs($user)->getJson("/api/posts/{$post->id}/likes");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_toggling_like_twice_results_in_no_like(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)->postJson("/api/posts/{$post->id}/like");
        $this->actingAs($user)->postJson("/api/posts/{$post->id}/like");

        $this->assertEquals(0, Like::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->count());
    }

    public function test_guest_cannot_like_post(): void
    {
        $post = Post::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/like");

        $response->assertStatus(401);
    }
}
