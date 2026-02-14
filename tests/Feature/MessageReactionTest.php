<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageReactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_add_reaction_to_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        Sanctum::actingAs($receiver);

        $response = $this->postJson("/api/messages/{$message->id}/reactions", [
            'emoji' => '👍',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message_id' => $message->id,
            'user_id' => $receiver->id,
            'emoji' => '👍',
        ]);

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $receiver->id,
            'emoji' => '👍',
        ]);
    }

    public function test_user_can_add_multiple_different_reactions()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        Sanctum::actingAs($receiver);

        $this->postJson("/api/messages/{$message->id}/reactions", ['emoji' => '👍']);
        $this->postJson("/api/messages/{$message->id}/reactions", ['emoji' => '❤️']);

        $message->refresh();
        $this->assertCount(2, $message->reactions);
    }

    public function test_adding_same_reaction_twice_does_not_duplicate()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        Sanctum::actingAs($receiver);

        $this->postJson("/api/messages/{$message->id}/reactions", ['emoji' => '👍']);
        $this->postJson("/api/messages/{$message->id}/reactions", ['emoji' => '👍']);

        $message->refresh();
        $this->assertCount(1, $message->reactions);
    }

    public function test_user_can_remove_reaction()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        Sanctum::actingAs($receiver);

        // Add reaction
        $this->postJson("/api/messages/{$message->id}/reactions", ['emoji' => '👍']);

        // Remove reaction
        $response = $this->deleteJson("/api/messages/{$message->id}/reactions/👍");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $receiver->id,
            'emoji' => '👍',
        ]);
    }

    public function test_multiple_users_can_react_to_same_message()
    {
        $sender = User::factory()->create();
        $receiver1 = User::factory()->create();
        $receiver2 = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver1->id,
        ]);

        Sanctum::actingAs($receiver1);
        $this->postJson("/api/messages/{$message->id}/reactions", ['emoji' => '👍']);

        Sanctum::actingAs($receiver2);
        $this->postJson("/api/messages/{$message->id}/reactions", ['emoji' => '👍']);

        $message->refresh();
        $this->assertCount(2, $message->reactions);
    }

    public function test_user_cannot_react_to_message_they_cannot_view()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $otherUser = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/messages/{$message->id}/reactions", [
            'emoji' => '👍',
        ]);

        $response->assertStatus(403);
    }

    public function test_emoji_field_is_required()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        Sanctum::actingAs($receiver);

        $response = $this->postJson("/api/messages/{$message->id}/reactions", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['emoji']);
    }

    public function test_sender_can_react_to_their_own_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        Sanctum::actingAs($sender);

        $response = $this->postJson("/api/messages/{$message->id}/reactions", [
            'emoji' => '👍',
        ]);

        $response->assertStatus(201);
    }
}
