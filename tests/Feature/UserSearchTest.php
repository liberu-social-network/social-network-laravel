<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_by_name(): void
    {
        $user1 = User::factory()->create(['name' => 'John Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);
        $user3 = User::factory()->create(['name' => 'John Smith']);

        $this->actingAs($user1);

        $response = $this->getJson('/api/users/search?query=John');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'users' => [
                '*' => ['id', 'name', 'email', 'profile_photo_url', 'friends_count', 'followers_count', 'following_count'],
            ],
        ]);

        $users = $response->json('users');
        $this->assertCount(2, $users);
    }

    public function test_user_can_search_by_email(): void
    {
        $user1 = User::factory()->create(['email' => 'john@example.com']);
        $user2 = User::factory()->create(['email' => 'jane@example.com']);
        $user3 = User::factory()->create(['email' => 'test@example.com']);

        $this->actingAs($user1);

        $response = $this->getJson('/api/users/search?query=john');

        $response->assertStatus(200);
        $users = $response->json('users');
        $this->assertGreaterThanOrEqual(1, count($users));
    }

    public function test_search_requires_query_parameter(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/users/search');

        $response->assertStatus(422);
    }

    public function test_search_limits_results_to_20(): void
    {
        User::factory()->count(25)->create(['name' => 'Test User']);
        $authUser = User::factory()->create();

        $this->actingAs($authUser);

        $response = $this->getJson('/api/users/search?query=Test');

        $response->assertStatus(200);
        $users = $response->json('users');
        $this->assertLessThanOrEqual(20, count($users));
    }
}
