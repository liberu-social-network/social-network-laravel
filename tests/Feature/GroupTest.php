<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_public_group(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/groups', [
            'name' => 'Test Group',
            'description' => 'This is a test group',
            'privacy' => 'public',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('groups', [
            'name' => 'Test Group',
            'description' => 'This is a test group',
            'privacy' => 'public',
            'user_id' => $user->id,
        ]);

        // Verify creator is added as admin
        $this->assertDatabaseHas('group_members', [
            'group_id' => $response->json('group.id'),
            'user_id' => $user->id,
            'role' => 'admin',
            'status' => 'approved',
        ]);
    }

    public function test_user_can_create_private_group(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/groups', [
            'name' => 'Private Group',
            'description' => 'This is a private group',
            'privacy' => 'private',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('groups', [
            'name' => 'Private Group',
            'privacy' => 'private',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_create_group_with_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/groups', [
            'name' => 'Group with Image',
            'description' => 'Test description',
            'privacy' => 'public',
            'image' => UploadedFile::fake()->image('group.jpg'),
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('group.image_url'));
    }

    public function test_user_can_view_public_group(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        
        $group = Group::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/groups/{$group->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
        ]);
    }

    public function test_non_member_cannot_view_private_group(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        
        $group = Group::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/groups/{$group->id}");

        $response->assertStatus(403);
    }

    public function test_member_can_view_private_group(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'private',
        ]);

        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($member)->getJson("/api/groups/{$group->id}");

        $response->assertStatus(200);
    }

    public function test_owner_can_update_group(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['user_id' => $user->id]);
        
        $group->members()->attach($user->id, [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->putJson("/api/groups/{$group->id}", [
            'name' => 'Updated Group Name',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'Updated Group Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_admin_can_update_group(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($admin->id, [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->putJson("/api/groups/{$group->id}", [
            'name' => 'Updated by Admin',
        ]);

        $response->assertStatus(200);
    }

    public function test_regular_member_cannot_update_group(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($member)->putJson("/api/groups/{$group->id}", [
            'name' => 'Trying to Update',
        ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_delete_group(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/groups/{$group->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('groups', [
            'id' => $group->id,
        ]);
    }

    public function test_non_owner_cannot_delete_group(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($admin->id, [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/groups/{$group->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
        ]);
    }

    public function test_guest_cannot_create_group(): void
    {
        $response = $this->postJson('/api/groups', [
            'name' => 'Test Group',
            'privacy' => 'public',
        ]);

        $response->assertStatus(401);
    }

    public function test_group_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/groups', [
            'privacy' => 'public',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_privacy_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/groups', [
            'name' => 'Test Group',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('privacy');
    }
}
