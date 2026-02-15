<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_event(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/events', [
            'title' => 'Test Event',
            'description' => 'This is a test event',
            'location' => 'Test Location',
            'start_time' => now()->addDays(1)->toDateTimeString(),
            'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
            'is_public' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('events', [
            'user_id' => $user->id,
            'title' => 'Test Event',
            'location' => 'Test Location',
            'is_public' => true,
        ]);
    }

    public function test_user_can_create_event_with_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/events', [
            'title' => 'Event with image',
            'description' => 'Test description',
            'start_time' => now()->addDays(1)->toDateTimeString(),
            'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
            'image' => UploadedFile::fake()->image('event.jpg'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('events', [
            'user_id' => $user->id,
            'title' => 'Event with image',
        ]);
    }

    public function test_user_can_view_public_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->getJson("/api/events/{$event->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $event->id,
            'title' => $event->title,
        ]);
    }

    public function test_user_cannot_view_private_event_from_other_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $otherUser->id,
            'is_public' => false,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->getJson("/api/events/{$event->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->putJson("/api/events/{$event->id}", [
            'title' => 'Updated Event Title',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Event Title',
        ]);
    }

    public function test_user_cannot_update_others_event(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $otherUser->id,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->putJson("/api/events/{$event->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    public function test_user_cannot_delete_others_event(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $otherUser->id,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
        ]);
    }

    public function test_start_time_must_be_in_future(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/events', [
            'title' => 'Test Event',
            'start_time' => now()->subDays(1)->toDateTimeString(),
            'end_time' => now()->addHours(2)->toDateTimeString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('start_time');
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/events', [
            'title' => 'Test Event',
            'start_time' => now()->addDays(1)->toDateTimeString(),
            'end_time' => now()->addDays(1)->subHours(1)->toDateTimeString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('end_time');
    }

    public function test_guest_cannot_create_event(): void
    {
        $response = $this->postJson('/api/events', [
            'title' => 'Test Event',
            'start_time' => now()->addDays(1)->toDateTimeString(),
            'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        ]);

        $response->assertStatus(401);
    }

    public function test_can_list_upcoming_events(): void
    {
        $user = User::factory()->create();
        
        // Create upcoming events
        Event::factory()->count(3)->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        // Create past event
        Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->subDays(1),
            'end_time' => now()->subDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->getJson('/api/events');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_discover_events_by_location(): void
    {
        $user = User::factory()->create();
        
        Event::factory()->create([
            'is_public' => true,
            'location' => 'New York',
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        Event::factory()->create([
            'is_public' => true,
            'location' => 'Los Angeles',
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->getJson('/api/events/discover?location=New York');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_get_my_events(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        Event::factory()->count(2)->create([
            'user_id' => $user->id,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        Event::factory()->create([
            'user_id' => $otherUser->id,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->getJson('/api/events/my-events');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }
}
