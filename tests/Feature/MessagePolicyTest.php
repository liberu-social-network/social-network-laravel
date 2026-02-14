<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_view_message_they_sent()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->assertTrue($sender->can('view', $message));
    }

    public function test_user_can_view_message_they_received()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->assertTrue($receiver->can('view', $message));
    }

    public function test_user_cannot_view_message_they_are_not_part_of()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $otherUser = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->assertFalse($otherUser->can('view', $message));
    }

    public function test_user_can_delete_message_they_sent()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->assertTrue($sender->can('delete', $message));
    }

    public function test_user_can_delete_message_they_received()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->assertTrue($receiver->can('delete', $message));
    }

    public function test_user_cannot_delete_message_they_are_not_part_of()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $otherUser = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->assertFalse($otherUser->can('delete', $message));
    }

    public function test_only_sender_can_update_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->assertTrue($sender->can('update', $message));
        $this->assertFalse($receiver->can('update', $message));
    }
}
