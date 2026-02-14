<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_friend_request(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1);

        $response = $this->postJson('/api/friendships/send', [
            'user_id' => $user2->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('friendships', [
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_accept_friend_request(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Friendship::create([
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user2);

        $response = $this->postJson('/api/friendships/accept', [
            'user_id' => $user1->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('friendships', [
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_can_reject_friend_request(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Friendship::create([
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user2);

        $response = $this->postJson('/api/friendships/reject', [
            'user_id' => $user1->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('friendships', [
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
            'status' => 'declined',
        ]);
    }

    public function test_user_cannot_send_friend_request_to_self(): void
    {
        $user = User::factory()->create();

        $result = $user->sendFriendRequest($user);

        $this->assertFalse($result);
    }

    public function test_user_can_get_friends_list(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Friendship::create([
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
            'status' => 'accepted',
        ]);

        Friendship::create([
            'requester_id' => $user3->id,
            'addressee_id' => $user1->id,
            'status' => 'accepted',
        ]);

        $this->actingAs($user1);

        $response = $this->getJson('/api/friendships');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'friends',
            'sent_requests',
            'received_requests',
        ]);
    }

    public function test_friends_count_is_accurate(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Friendship::create([
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
            'status' => 'accepted',
        ]);

        Friendship::create([
            'requester_id' => $user3->id,
            'addressee_id' => $user1->id,
            'status' => 'accepted',
        ]);

        $this->assertEquals(2, $user1->fresh()->friends_count);
    }
}
