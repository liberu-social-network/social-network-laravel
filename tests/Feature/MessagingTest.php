<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_send_message_to_another_user()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender);

        $response = $this->postJson('/api/messages', [
            'receiver_id' => $receiver->id,
            'content' => 'Hello, this is a test message!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'sender_id',
            'receiver_id',
            'content',
            'read_at',
            'created_at',
            'updated_at',
        ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => 'Hello, this is a test message!',
        ]);
    }

    public function test_user_cannot_send_message_to_themselves()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/messages', [
            'receiver_id' => $user->id,
            'content' => 'Trying to message myself',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'You cannot send a message to yourself'
        ]);
    }

    public function test_message_content_is_required()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender);

        $response = $this->postJson('/api/messages', [
            'receiver_id' => $receiver->id,
            'content' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_receiver_must_exist()
    {
        $sender = User::factory()->create();

        Sanctum::actingAs($sender);

        $response = $this->postJson('/api/messages', [
            'receiver_id' => 99999,
            'content' => 'Test message',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['receiver_id']);
    }

    public function test_user_can_view_their_messages()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Message::factory()->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
        ]);

        Message::factory()->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
        ]);

        Sanctum::actingAs($user1);

        $response = $this->getJson('/api/messages');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'sender_id',
                    'receiver_id',
                    'content',
                    'read_at',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
    }

    public function test_user_can_view_conversation_with_another_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Message::factory()->count(3)->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
        ]);

        Message::factory()->count(2)->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
        ]);

        Sanctum::actingAs($user1);

        $response = $this->getJson("/api/messages/conversation/{$user2->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(5);
    }

    public function test_message_is_marked_as_read_when_viewed_by_receiver()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'read_at' => null,
        ]);

        Sanctum::actingAs($receiver);

        $response = $this->getJson("/api/messages/{$message->id}");

        $response->assertStatus(200);
        
        $message->refresh();
        $this->assertNotNull($message->read_at);
    }

    public function test_message_is_not_marked_as_read_when_viewed_by_sender()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'read_at' => null,
        ]);

        Sanctum::actingAs($sender);

        $response = $this->getJson("/api/messages/{$message->id}");

        $response->assertStatus(200);
        
        $message->refresh();
        $this->assertNull($message->read_at);
    }

    public function test_user_cannot_view_messages_they_are_not_part_of()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
        ]);

        Sanctum::actingAs($user3);

        $response = $this->getJson("/api/messages/{$message->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_sent_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        Sanctum::actingAs($sender);

        $response = $this->deleteJson("/api/messages/{$message->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    public function test_user_can_delete_their_received_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        Sanctum::actingAs($receiver);

        $response = $this->deleteJson("/api/messages/{$message->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    public function test_user_cannot_delete_message_they_are_not_part_of()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
        ]);

        Sanctum::actingAs($user3);

        $response = $this->deleteJson("/api/messages/{$message->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_get_unread_message_count()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Message::factory()->count(3)->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
            'read_at' => null,
        ]);

        Message::factory()->count(2)->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
            'read_at' => now(),
        ]);

        Sanctum::actingAs($user1);

        $response = $this->getJson('/api/messages/unread-count');

        $response->assertStatus(200);
        $response->assertJson([
            'unread_count' => 3
        ]);
    }

    public function test_unauthenticated_user_cannot_access_messages()
    {
        $response = $this->getJson('/api/messages');
        $response->assertStatus(401);
    }

    public function test_message_model_has_sender_and_receiver_relationships()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertInstanceOf(User::class, $message->receiver);
        $this->assertEquals($sender->id, $message->sender->id);
        $this->assertEquals($receiver->id, $message->receiver->id);
    }

    public function test_user_model_has_message_relationships()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Message::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
        ]);

        Message::factory()->create([
            'sender_id' => $otherUser->id,
            'receiver_id' => $user->id,
        ]);

        $this->assertCount(1, $user->sentMessages);
        $this->assertCount(1, $user->receivedMessages);
    }
}
