<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        Storage::fake('public');
    }

    public function test_user_can_send_message_with_attachment()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender);

        $file = UploadedFile::fake()->image('photo.jpg', 600, 600)->size(100);

        $response = $this->postJson('/api/messages', [
            'receiver_id' => $receiver->id,
            'content' => 'Check out this photo!',
            'attachments' => [$file],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'content',
            'attachments' => [
                '*' => ['id', 'filename', 'original_filename', 'mime_type', 'size']
            ]
        ]);

        $message = Message::find($response->json('id'));
        $this->assertCount(1, $message->attachments);

        // Check file was stored
        $attachment = $message->attachments->first();
        Storage::disk('public')->assertExists($attachment->path);
    }

    public function test_user_can_send_message_with_multiple_attachments()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender);

        $files = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.jpg'),
            UploadedFile::fake()->create('document.pdf', 100),
        ];

        $response = $this->postJson('/api/messages', [
            'receiver_id' => $receiver->id,
            'content' => 'Multiple files',
            'attachments' => $files,
        ]);

        $response->assertStatus(201);

        $message = Message::find($response->json('id'));
        $this->assertCount(3, $message->attachments);
    }

    public function test_cannot_send_more_than_5_attachments()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender);

        $files = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.jpg'),
            UploadedFile::fake()->image('photo3.jpg'),
            UploadedFile::fake()->image('photo4.jpg'),
            UploadedFile::fake()->image('photo5.jpg'),
            UploadedFile::fake()->image('photo6.jpg'),
        ];

        $response = $this->postJson('/api/messages', [
            'receiver_id' => $receiver->id,
            'content' => 'Too many files',
            'attachments' => $files,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attachments']);
    }

    public function test_attachment_file_size_is_validated()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender);

        // Create file larger than 10MB
        $file = UploadedFile::fake()->create('large.pdf', 11000);

        $response = $this->postJson('/api/messages', [
            'receiver_id' => $receiver->id,
            'content' => 'Large file',
            'attachments' => [$file],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attachments.0']);
    }

    public function test_attachments_are_deleted_when_message_is_deleted()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $attachment = MessageAttachment::factory()->create([
            'message_id' => $message->id,
        ]);

        // Create a fake file
        Storage::disk('public')->put($attachment->path, 'fake content');

        Sanctum::actingAs($sender);

        $response = $this->deleteJson("/api/messages/{$message->id}");

        $response->assertStatus(200);

        // Check file was deleted
        Storage::disk('public')->assertMissing($attachment->path);
    }

    public function test_message_attachment_has_correct_attributes()
    {
        $attachment = MessageAttachment::factory()->create([
            'original_filename' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024000, // 1MB
        ]);

        $this->assertTrue($attachment->isImage());
        $this->assertFalse($attachment->isVideo());
        $this->assertFalse($attachment->isAudio());
        $this->assertEquals('1000 KB', $attachment->human_readable_size);
    }

    public function test_can_send_message_with_only_attachments()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender);

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->postJson('/api/messages', [
            'receiver_id' => $receiver->id,
            'attachments' => [$file],
        ]);

        $response->assertStatus(201);
    }
}
