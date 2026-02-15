<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_join_public_group(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        
        $group = Group::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/join");

        $response->assertStatus(201);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
    }

    public function test_join_private_group_creates_pending_request(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        
        $group = Group::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/join");

        $response->assertStatus(201);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_join_group_twice(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        
        $group = Group::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'public',
        ]);

        $group->members()->attach($user->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/join");

        $response->assertStatus(400);
    }

    public function test_member_can_leave_group(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($member)->postJson("/api/groups/{$group->id}/leave");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_owner_cannot_leave_own_group(): void
    {
        $owner = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);
        
        $group->members()->attach($owner->id, [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($owner)->postJson("/api/groups/{$group->id}/leave");

        $response->assertStatus(400);
    }

    public function test_admin_can_approve_pending_member(): void
    {
        $owner = User::factory()->create();
        $pendingUser = User::factory()->create();
        
        $group = Group::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'private',
        ]);

        $group->members()->attach($owner->id, [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $pendingUser->id,
            'role' => 'member',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->postJson("/api/groups/{$group->id}/members/{$pendingUser->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $pendingUser->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_reject_pending_member(): void
    {
        $owner = User::factory()->create();
        $pendingUser = User::factory()->create();
        
        $group = Group::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'private',
        ]);

        $group->members()->attach($owner->id, [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $pendingUser->id,
            'role' => 'member',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->postJson("/api/groups/{$group->id}/members/{$pendingUser->id}/reject");

        $response->assertStatus(200);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $pendingUser->id,
            'status' => 'rejected',
        ]);
    }

    public function test_regular_member_cannot_approve_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $pendingUser = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);

        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $pendingUser->id,
            'role' => 'member',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($member)->postJson("/api/groups/{$group->id}/members/{$pendingUser->id}/approve");

        $response->assertStatus(403);
    }

    public function test_admin_can_remove_member(): void
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

        $response = $this->actingAs($owner)->deleteJson("/api/groups/{$group->id}/members/{$member->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_admin_cannot_remove_owner(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);

        $group->members()->attach($owner->id, [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $group->members()->attach($admin->id, [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/groups/{$group->id}/members/{$owner->id}");

        $response->assertStatus(400);
    }

    public function test_owner_can_update_member_role(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);

        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($owner)->putJson("/api/groups/{$group->id}/members/{$member->id}/role", [
            'role' => 'admin',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => 'admin',
        ]);
    }

    public function test_non_owner_cannot_update_member_role(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        
        $group = Group::factory()->create(['user_id' => $owner->id]);

        $group->members()->attach($admin->id, [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $group->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->putJson("/api/groups/{$group->id}/members/{$member->id}/role", [
            'role' => 'moderator',
        ]);

        $response->assertStatus(403);
    }
}
