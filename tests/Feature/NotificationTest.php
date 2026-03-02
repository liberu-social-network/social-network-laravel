<?php

namespace Tests\Feature;

use App\Models\Follower;
use App\Models\Friendship;
use App\Models\User;
use App\Notifications\FriendRequestAccepted;
use App\Notifications\FriendRequestReceived;
use App\Notifications\NewFollower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_friend_request_notifies_addressee(): void
    {
        Notification::fake();

        $requester = User::factory()->create();
        $addressee = User::factory()->create();

        $this->actingAs($requester);

        $this->postJson('/api/friendships/send', ['user_id' => $addressee->id])
            ->assertStatus(201);

        Notification::assertSentTo($addressee, FriendRequestReceived::class);
        Notification::assertNotSentTo($requester, FriendRequestReceived::class);
    }

    public function test_accepting_friend_request_notifies_requester(): void
    {
        Notification::fake();

        $requester = User::factory()->create();
        $addressee = User::factory()->create();

        Friendship::create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($addressee);

        $this->postJson('/api/friendships/accept', ['user_id' => $requester->id])
            ->assertStatus(200);

        Notification::assertSentTo($requester, FriendRequestAccepted::class);
        Notification::assertNotSentTo($addressee, FriendRequestAccepted::class);
    }

    public function test_following_a_user_notifies_them(): void
    {
        Notification::fake();

        $follower = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($follower);

        $this->postJson('/api/followers/follow', ['user_id' => $target->id])
            ->assertStatus(201);

        Notification::assertSentTo($target, NewFollower::class);
        Notification::assertNotSentTo($follower, NewFollower::class);
    }

    public function test_user_can_get_their_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $user->notify(new FriendRequestReceived($other));
        $user->notify(new NewFollower($other));

        $this->actingAs($user);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'type', 'data', 'read_at', 'created_at'],
            ],
        ]);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_user_can_get_unread_notification_count(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $user->notify(new FriendRequestReceived($other));
        $user->notify(new NewFollower($other));

        $this->actingAs($user);

        $response = $this->getJson('/api/notifications/unread-count');

        $response->assertStatus(200);
        $response->assertJson(['unread_count' => 2]);
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $user->notify(new FriendRequestReceived($other));

        $notification = $user->notifications()->first();

        $this->actingAs($user);

        $this->postJson("/api/notifications/{$notification->id}/read")
            ->assertStatus(200)
            ->assertJson(['message' => 'Notification marked as read.']);

        $this->assertNotNull($user->notifications()->find($notification->id)->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $user->notify(new FriendRequestReceived($other));
        $user->notify(new NewFollower($other));

        $this->actingAs($user);

        $this->postJson('/api/notifications/read-all')
            ->assertStatus(200)
            ->assertJson(['message' => 'All notifications marked as read.']);

        $this->assertEquals(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
        $this->getJson('/api/notifications/unread-count')->assertStatus(401);
    }

    public function test_friend_request_received_notification_has_correct_data(): void
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();

        $user->notify(new FriendRequestReceived($requester));

        $notification = $user->notifications()->first();
        $data = $notification->data;

        $this->assertEquals('friend_request_received', $data['type']);
        $this->assertEquals($requester->id, $data['requester_id']);
        $this->assertEquals($requester->name, $data['requester_name']);
    }

    public function test_friend_request_accepted_notification_has_correct_data(): void
    {
        $user = User::factory()->create();
        $accepter = User::factory()->create();

        $user->notify(new FriendRequestAccepted($accepter));

        $notification = $user->notifications()->first();
        $data = $notification->data;

        $this->assertEquals('friend_request_accepted', $data['type']);
        $this->assertEquals($accepter->id, $data['accepter_id']);
        $this->assertEquals($accepter->name, $data['accepter_name']);
    }

    public function test_new_follower_notification_has_correct_data(): void
    {
        $user = User::factory()->create();
        $follower = User::factory()->create();

        $user->notify(new NewFollower($follower));

        $notification = $user->notifications()->first();
        $data = $notification->data;

        $this->assertEquals('new_follower', $data['type']);
        $this->assertEquals($follower->id, $data['follower_id']);
        $this->assertEquals($follower->name, $data['follower_name']);
    }

    public function test_rejecting_friend_request_does_not_notify(): void
    {
        Notification::fake();

        $requester = User::factory()->create();
        $addressee = User::factory()->create();

        Friendship::create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($addressee);

        $this->postJson('/api/friendships/reject', ['user_id' => $requester->id])
            ->assertStatus(200);

        Notification::assertNothingSent();
    }
}
