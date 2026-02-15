<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_for_groups(): void
    {
        $user = User::factory()->create();
        
        $group1 = Group::factory()->create([
            'name' => 'Laravel Developers',
            'privacy' => 'public',
        ]);
        
        $group2 = Group::factory()->create([
            'name' => 'PHP Community',
            'privacy' => 'public',
        ]);
        
        $group3 = Group::factory()->create([
            'name' => 'JavaScript Group',
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($user)->getJson('/api/groups/search/query?query=Laravel');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Laravel Developers']);
        $response->assertJsonMissing(['name' => 'JavaScript Group']);
    }

    public function test_search_finds_groups_by_description(): void
    {
        $user = User::factory()->create();
        
        $group1 = Group::factory()->create([
            'name' => 'Tech Group',
            'description' => 'A group for Laravel enthusiasts',
            'privacy' => 'public',
        ]);
        
        $group2 = Group::factory()->create([
            'name' => 'Another Group',
            'description' => 'Random description',
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($user)->getJson('/api/groups/search/query?query=Laravel');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Tech Group']);
    }

    public function test_search_only_shows_public_groups_by_default(): void
    {
        $user = User::factory()->create();
        
        $publicGroup = Group::factory()->create([
            'name' => 'Public Laravel Group',
            'privacy' => 'public',
        ]);
        
        $privateGroup = Group::factory()->create([
            'name' => 'Private Laravel Group',
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($user)->getJson('/api/groups/search/query?query=Laravel');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Public Laravel Group']);
        $response->assertJsonMissing(['name' => 'Private Laravel Group']);
    }

    public function test_search_shows_private_groups_user_is_member_of(): void
    {
        $user = User::factory()->create();
        
        $privateGroup = Group::factory()->create([
            'name' => 'Private Laravel Group',
            'privacy' => 'private',
        ]);

        $privateGroup->members()->attach($user->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->getJson('/api/groups/search/query?query=Laravel');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Private Laravel Group']);
    }

    public function test_user_can_get_group_suggestions(): void
    {
        $user = User::factory()->create();
        
        $group1 = Group::factory()->create([
            'name' => 'Suggested Group 1',
            'privacy' => 'public',
        ]);
        
        $group2 = Group::factory()->create([
            'name' => 'Suggested Group 2',
            'privacy' => 'public',
        ]);
        
        // User is already a member of this group, so it shouldn't be suggested
        $memberGroup = Group::factory()->create([
            'name' => 'Member Group',
            'privacy' => 'public',
        ]);
        $memberGroup->members()->attach($user->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->getJson('/api/groups/search/suggestions');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Suggested Group 1']);
        $response->assertJsonFragment(['name' => 'Suggested Group 2']);
        $response->assertJsonMissing(['name' => 'Member Group']);
    }

    public function test_suggestions_only_include_public_groups(): void
    {
        $user = User::factory()->create();
        
        $publicGroup = Group::factory()->create([
            'name' => 'Public Group',
            'privacy' => 'public',
        ]);
        
        $privateGroup = Group::factory()->create([
            'name' => 'Private Group',
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($user)->getJson('/api/groups/search/suggestions');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Public Group']);
        $response->assertJsonMissing(['name' => 'Private Group']);
    }

    public function test_user_can_get_popular_groups(): void
    {
        $user = User::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        $popularGroup = Group::factory()->create([
            'name' => 'Popular Group',
            'privacy' => 'public',
        ]);
        
        $lessPopularGroup = Group::factory()->create([
            'name' => 'Less Popular Group',
            'privacy' => 'public',
        ]);

        // Add members to make it popular
        $popularGroup->members()->attach($user1->id, ['role' => 'member', 'status' => 'approved']);
        $popularGroup->members()->attach($user2->id, ['role' => 'member', 'status' => 'approved']);
        $popularGroup->members()->attach($user3->id, ['role' => 'member', 'status' => 'approved']);

        $response = $this->actingAs($user)->getJson('/api/groups/search/popular');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Popular Group']);
    }

    public function test_popular_groups_only_include_public_groups(): void
    {
        $user = User::factory()->create();
        
        $publicGroup = Group::factory()->create([
            'name' => 'Public Popular Group',
            'privacy' => 'public',
        ]);
        
        $privateGroup = Group::factory()->create([
            'name' => 'Private Popular Group',
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($user)->getJson('/api/groups/search/popular');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Public Popular Group']);
        $response->assertJsonMissing(['name' => 'Private Popular Group']);
    }

    public function test_search_requires_query_parameter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/groups/search/query');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('query');
    }

    public function test_guest_cannot_search_groups(): void
    {
        Group::factory()->create([
            'name' => 'Test Group',
            'privacy' => 'public',
        ]);

        $response = $this->getJson('/api/groups/search/query?query=Test');

        $response->assertStatus(401);
    }
}
