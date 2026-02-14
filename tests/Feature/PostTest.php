<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_text_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'content' => 'This is a test post',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'This is a test post',
            'media_type' => 'text',
        ]);
    }

    public function test_user_can_create_post_with_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'content' => 'Post with image',
            'image' => UploadedFile::fake()->image('post.jpg'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'Post with image',
            'media_type' => 'image',
        ]);
    }

    public function test_user_can_view_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $post->id,
            'content' => $post->content,
        ]);
    }

    public function test_user_can_update_own_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson("/api/posts/{$post->id}", [
            'content' => 'Updated content',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_user_cannot_update_others_post(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->putJson("/api/posts/{$post->id}", [
            'content' => 'Updated content',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_user_cannot_delete_others_post(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_content_is_required_for_text_only_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/posts', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('content');
    }

    public function test_guest_cannot_create_post(): void
    {
        $response = $this->postJson('/api/posts', [
            'content' => 'Test content',
        ]);

        $response->assertStatus(401);
    }
}
