<?php

namespace Tests\Feature;

use App\Models\Follower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_another_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1);

        $response = $this->postJson('/api/followers/follow', [
            'user_id' => $user2->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('followers', [
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
        ]);
    }

    public function test_user_can_unfollow_another_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Follower::create([
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
        ]);

        $this->actingAs($user1);

        $response = $this->postJson('/api/followers/unfollow', [
            'user_id' => $user2->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('followers', [
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
        ]);
    }

    public function test_user_cannot_follow_self(): void
    {
        $user = User::factory()->create();

        $result = $user->follow($user);

        $this->assertFalse($result);
    }

    public function test_user_can_get_followers_list(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Follower::create([
            'follower_id' => $user2->id,
            'following_id' => $user1->id,
        ]);

        Follower::create([
            'follower_id' => $user3->id,
            'following_id' => $user1->id,
        ]);

        $this->actingAs($user1);

        $response = $this->getJson('/api/followers');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'followers',
            'following',
            'followers_count',
            'following_count',
        ]);
    }

    public function test_followers_count_is_accurate(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Follower::create([
            'follower_id' => $user2->id,
            'following_id' => $user1->id,
        ]);

        Follower::create([
            'follower_id' => $user3->id,
            'following_id' => $user1->id,
        ]);

        $this->assertEquals(2, $user1->fresh()->followers_count);
    }

    public function test_following_count_is_accurate(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Follower::create([
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
        ]);

        Follower::create([
            'follower_id' => $user1->id,
            'following_id' => $user3->id,
        ]);

        $this->assertEquals(2, $user1->fresh()->following_count);
    }

    public function test_is_following_works_correctly(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertFalse($user1->isFollowing($user2));

        Follower::create([
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
        ]);

        $this->assertTrue($user1->isFollowing($user2));
    }

    public function test_is_followed_by_works_correctly(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertFalse($user1->isFollowedBy($user2));

        Follower::create([
            'follower_id' => $user2->id,
            'following_id' => $user1->id,
        ]);

        $this->assertTrue($user1->isFollowedBy($user2));
    }
}
