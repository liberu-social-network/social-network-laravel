<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageEncryptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_send_encrypted_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender);

        $response = $this->postJson('/api/messages', [
            'receiver_id' => $receiver->id,
            'content' => 'This is a secret message',
            'encrypted' => true,
        ]);

        $response->assertStatus(201);

        $message = Message::find($response->json('id'));

        // Content should be null
        $this->assertNull($message->content);
        
        // Encrypted content should be set
        $this->assertNotNull($message->encrypted_content);
        $this->assertNotNull($message->encryption_key_id);

        // Decrypted content should match original
        $this->assertEquals('This is a secret message', $message->decrypted_content);
    }

    public function test_encrypted_message_is_decrypted_when_accessed()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = new Message([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);
        $message->encrypt('Secret content');
        $message->save();

        Sanctum::actingAs($receiver);

        $response = $this->getJson("/api/messages/{$message->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $message->id,
        ]);

        // The decrypted_content should be accessible
        $fetchedMessage = Message::find($message->id);
        $this->assertEquals('Secret content', $fetchedMessage->decrypted_content);
    }

    public function test_unencrypted_message_returns_plain_content()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => 'Plain text message',
        ]);

        Sanctum::actingAs($receiver);

        $response = $this->getJson("/api/messages/{$message->id}");

        $response->assertStatus(200);

        // Decrypted content should return the plain content
        $this->assertEquals('Plain text message', $message->decrypted_content);
    }

    public function test_encryption_is_optional()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender);

        // Send without encryption flag
        $response = $this->postJson('/api/messages', [
            'receiver_id' => $receiver->id,
            'content' => 'Normal message',
        ]);

        $response->assertStatus(201);

        $message = Message::find($response->json('id'));

        // Should be stored as plain text
        $this->assertEquals('Normal message', $message->content);
        $this->assertNull($message->encrypted_content);
    }

    public function test_encrypted_message_model_methods()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = new Message([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        // Test encrypt method
        $message->encrypt('Test encryption');
        $message->save();

        $this->assertNull($message->content);
        $this->assertNotNull($message->encrypted_content);
        $this->assertEquals('Test encryption', $message->decrypted_content);

        // Test that decrypted_content is in appends
        $array = $message->toArray();
        $this->assertArrayHasKey('decrypted_content', $array);
    }
}
