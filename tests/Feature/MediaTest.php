<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Media;
use App\Models\Album;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/media', [
            'file' => UploadedFile::fake()->image('test.jpg'),
            'description' => 'Test image',
            'privacy' => 'public',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
            'description' => 'Test image',
            'file_type' => 'image',
            'privacy' => 'public',
        ]);
    }

    public function test_user_can_upload_video(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/media', [
            'file' => UploadedFile::fake()->create('test.mp4', 1000, 'video/mp4'),
            'description' => 'Test video',
            'privacy' => 'public',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
            'description' => 'Test video',
            'file_type' => 'video',
            'privacy' => 'public',
        ]);
    }

    public function test_user_can_tag_media(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/media', [
            'file' => UploadedFile::fake()->image('test.jpg'),
            'description' => 'Tagged image',
            'privacy' => 'public',
            'tags' => ['nature', 'sunset'],
        ]);

        $response->assertStatus(201);
        
        $media = Media::where('user_id', $user->id)->first();
        $this->assertEquals(2, $media->tags()->count());
        
        $this->assertDatabaseHas('tags', ['name' => 'nature']);
        $this->assertDatabaseHas('tags', ['name' => 'sunset']);
    }

    public function test_user_can_view_own_private_media(): void
    {
        $user = User::factory()->create();
        $media = Media::factory()->create([
            'user_id' => $user->id,
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($user)->getJson("/api/media/{$media->id}");

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_others_private_media(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        
        $media = Media::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/media/{$media->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_view_public_media(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        
        $media = Media::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/media/{$media->id}");

        $response->assertStatus(200);
    }

    public function test_user_can_update_own_media(): void
    {
        $user = User::factory()->create();
        $media = Media::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->putJson("/api/media/{$media->id}", [
            'description' => 'Updated description',
            'privacy' => 'private',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'description' => 'Updated description',
            'privacy' => 'private',
        ]);
    }

    public function test_user_cannot_update_others_media(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        
        $media = Media::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($other)->putJson("/api/media/{$media->id}", [
            'description' => 'Hacked description',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_media(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        
        $media = Media::factory()->create([
            'user_id' => $user->id,
            'file_path' => 'test/path.jpg',
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/media/{$media->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('media', [
            'id' => $media->id,
        ]);
    }

    public function test_user_cannot_delete_others_media(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        
        $media = Media::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($other)->deleteJson("/api/media/{$media->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
        ]);
    }

    public function test_guest_cannot_upload_media(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/media', [
            'file' => UploadedFile::fake()->image('test.jpg'),
            'privacy' => 'public',
        ]);

        $response->assertStatus(401);
    }
}
