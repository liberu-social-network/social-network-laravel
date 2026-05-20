<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Friendship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_post_with_privacy_setting(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'content' => 'This is a private post',
            'privacy' => 'private',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'This is a private post',
            'privacy' => 'private',
        ]);
    }

    public function test_user_can_view_own_private_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($user)->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_others_private_post(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/posts/{$post->id}");

        $response->assertStatus(403);
    }

    public function test_anyone_can_view_public_post(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
    }

    public function test_friends_can_view_friends_only_post(): void
    {
        $owner = User::factory()->create();
        $friend = User::factory()->create();
        
        // Create friendship
        Friendship::create([
            'requester_id' => $owner->id,
            'addressee_id' => $friend->id,
            'status' => 'accepted',
        ]);
        
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'friends_only',
        ]);

        $response = $this->actingAs($friend)->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
    }

    public function test_non_friends_cannot_view_friends_only_post(): void
    {
        $owner = User::factory()->create();
        $nonFriend = User::factory()->create();
        
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'friends_only',
        ]);

        $response = $this->actingAs($nonFriend)->getJson("/api/posts/{$post->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_post_privacy(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($user)->putJson("/api/posts/{$post->id}", [
            'privacy' => 'private',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'privacy' => 'private',
        ]);
    }

    public function test_privacy_defaults_to_public_when_not_specified(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'content' => 'This is a test post',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'privacy' => 'public',
        ]);
    }
}
