<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GroupPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_post_in_group(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($member)->postJson("/api/groups/{$group->id}/posts", [
            'content' => 'Test post in group',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $member->id,
            'group_id' => $group->id,
            'content' => 'Test post in group',
        ]);
    }

    public function test_non_member_cannot_create_post_in_group(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($nonMember)->postJson("/api/groups/{$group->id}/posts", [
            'content' => 'Trying to post',
        ]);

        $response->assertStatus(403);
    }

    public function test_member_can_create_post_with_image_in_group(): void
    {
        Storage::fake('public');
        
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($member)->postJson("/api/groups/{$group->id}/posts", [
            'content' => 'Post with image',
            'image' => UploadedFile::fake()->image('post.jpg'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $member->id,
            'group_id' => $group->id,
            'media_type' => 'image',
        ]);
    }

    public function test_member_can_view_group_posts(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'group_id' => $group->id,
            'content' => 'Test group post',
        ]);

        $response = $this->actingAs($member)->getJson("/api/groups/{$group->id}/posts");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'content' => 'Test group post',
        ]);
    }

    public function test_non_member_cannot_view_private_group_posts(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        
        $group = Group::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'private',
        ]);

        Post::factory()->create([
            'user_id' => $owner->id,
            'group_id' => $group->id,
        ]);

        $response = $this->actingAs($nonMember)->getJson("/api/groups/{$group->id}/posts");

        $response->assertStatus(403);
    }

    public function test_anyone_can_view_public_group_posts(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        
        $group = Group::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'public',
        ]);

        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'group_id' => $group->id,
            'content' => 'Public post',
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/groups/{$group->id}/posts");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'content' => 'Public post',
        ]);
    }

    public function test_post_author_can_update_group_post(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $post = Post::factory()->create([
            'user_id' => $member->id,
            'group_id' => $group->id,
            'content' => 'Original content',
        ]);

        $response = $this->actingAs($member)->putJson("/api/groups/{$group->id}/posts/{$post->id}", [
            'content' => 'Updated content',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_group_admin_can_update_any_post(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($owner->id, [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $post = Post::factory()->create([
            'user_id' => $member->id,
            'group_id' => $group->id,
            'content' => 'Member post',
        ]);

        $response = $this->actingAs($owner)->putJson("/api/groups/{$group->id}/posts/{$post->id}", [
            'content' => 'Updated by admin',
        ]);

        $response->assertStatus(200);
    }

    public function test_regular_member_cannot_update_others_post(): void
    {
        $owner = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($member1->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $group->members()->attach($member2->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $post = Post::factory()->create([
            'user_id' => $member1->id,
            'group_id' => $group->id,
        ]);

        $response = $this->actingAs($member2)->putJson("/api/groups/{$group->id}/posts/{$post->id}", [
            'content' => 'Trying to update',
        ]);

        $response->assertStatus(403);
    }

    public function test_post_author_can_delete_group_post(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $post = Post::factory()->create([
            'user_id' => $member->id,
            'group_id' => $group->id,
        ]);

        $response = $this->actingAs($member)->deleteJson("/api/groups/{$group->id}/posts/{$post->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_group_admin_can_delete_any_post(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($owner->id, [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $post = Post::factory()->create([
            'user_id' => $member->id,
            'group_id' => $group->id,
        ]);

        $response = $this->actingAs($owner)->deleteJson("/api/groups/{$group->id}/posts/{$post->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_content_is_required_for_text_only_post(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($member)->postJson("/api/groups/{$group->id}/posts", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('content');
    }
}
