<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Share;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_share_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/posts/{$post->id}/share");

        $response->assertStatus(200);
        $response->assertJson([
            'shared' => true,
            'shares_count' => 1,
        ]);
        
        $this->assertDatabaseHas('shares', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_user_can_unshare_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        Share::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/posts/{$post->id}/share");

        $response->assertStatus(200);
        $response->assertJson([
            'shared' => false,
            'shares_count' => 0,
        ]);
        
        $this->assertDatabaseMissing('shares', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_user_can_view_post_shares(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        Share::factory(3)->create(['post_id' => $post->id]);

        $response = $this->actingAs($user)->getJson("/api/posts/{$post->id}/shares");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_toggling_share_twice_results_in_no_share(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)->postJson("/api/posts/{$post->id}/share");
        $this->actingAs($user)->postJson("/api/posts/{$post->id}/share");

        $this->assertEquals(0, Share::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->count());
    }

    public function test_guest_cannot_share_post(): void
    {
        $post = Post::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/share");

        $response->assertStatus(401);
    }
}
