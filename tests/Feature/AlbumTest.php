<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Album;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_album(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/albums', [
            'name' => 'My Vacation',
            'description' => 'Photos from summer vacation',
            'privacy' => 'public',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('albums', [
            'user_id' => $user->id,
            'name' => 'My Vacation',
            'privacy' => 'public',
        ]);
    }

    public function test_user_can_view_own_album(): void
    {
        $user = User::factory()->create();
        $album = Album::factory()->create([
            'user_id' => $user->id,
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($user)->getJson("/api/albums/{$album->id}");

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_others_private_album(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        
        $album = Album::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/albums/{$album->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_view_public_album(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        
        $album = Album::factory()->create([
            'user_id' => $owner->id,
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/albums/{$album->id}");

        $response->assertStatus(200);
    }

    public function test_user_can_update_own_album(): void
    {
        $user = User::factory()->create();
        $album = Album::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->putJson("/api/albums/{$album->id}", [
            'name' => 'Updated Album Name',
            'privacy' => 'friends_only',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('albums', [
            'id' => $album->id,
            'name' => 'Updated Album Name',
            'privacy' => 'friends_only',
        ]);
    }

    public function test_user_cannot_update_others_album(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        
        $album = Album::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($other)->putJson("/api/albums/{$album->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_album(): void
    {
        $user = User::factory()->create();
        $album = Album::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/albums/{$album->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('albums', [
            'id' => $album->id,
        ]);
    }

    public function test_deleting_album_removes_media_association(): void
    {
        $user = User::factory()->create();
        $album = Album::factory()->create([
            'user_id' => $user->id,
        ]);
        
        $media = Media::factory()->create([
            'user_id' => $user->id,
            'album_id' => $album->id,
        ]);

        $this->actingAs($user)->deleteJson("/api/albums/{$album->id}");

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'album_id' => null,
        ]);
    }

    public function test_album_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/albums', [
            'privacy' => 'public',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_guest_cannot_create_album(): void
    {
        $response = $this->postJson('/api/albums', [
            'name' => 'Test Album',
            'privacy' => 'public',
        ]);

        $response->assertStatus(401);
    }
}
