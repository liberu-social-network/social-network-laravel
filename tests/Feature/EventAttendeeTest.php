<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventAttendeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_rsvp_to_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->postJson("/api/events/{$event->id}/rsvp", [
            'status' => 'going',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'going',
        ]);
    }

    public function test_user_can_update_rsvp(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        EventAttendee::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'going',
        ]);

        $response = $this->actingAs($user)->postJson("/api/events/{$event->id}/rsvp", [
            'status' => 'maybe',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'maybe',
        ]);
    }

    public function test_user_can_cancel_rsvp(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        EventAttendee::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'going',
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/events/{$event->id}/rsvp");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_rsvp_to_private_event(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $otherUser->id,
            'is_public' => false,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->postJson("/api/events/{$event->id}/rsvp", [
            'status' => 'going',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_rsvp_to_full_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'is_public' => true,
            'max_attendees' => 2,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        // Create 2 attendees (filling the event)
        EventAttendee::factory()->count(2)->create([
            'event_id' => $event->id,
            'status' => 'going',
        ]);

        $response = $this->actingAs($user)->postJson("/api/events/{$event->id}/rsvp", [
            'status' => 'going',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Event is full']);
    }

    public function test_user_can_update_rsvp_even_when_event_is_full(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'is_public' => true,
            'max_attendees' => 2,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        // User already RSVP'd as going
        EventAttendee::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'going',
        ]);

        // Another user fills the event
        EventAttendee::factory()->create([
            'event_id' => $event->id,
            'status' => 'going',
        ]);

        // User should be able to change from going to maybe
        $response = $this->actingAs($user)->postJson("/api/events/{$event->id}/rsvp", [
            'status' => 'maybe',
        ]);

        $response->assertStatus(201);
    }

    public function test_can_get_event_attendees(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        EventAttendee::factory()->count(3)->create([
            'event_id' => $event->id,
            'status' => 'going',
        ]);

        EventAttendee::factory()->count(2)->create([
            'event_id' => $event->id,
            'status' => 'maybe',
        ]);

        $response = $this->actingAs($user)->getJson("/api/events/{$event->id}/attendees");

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('total'));
    }

    public function test_can_filter_attendees_by_status(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        EventAttendee::factory()->count(3)->create([
            'event_id' => $event->id,
            'status' => 'going',
        ]);

        EventAttendee::factory()->count(2)->create([
            'event_id' => $event->id,
            'status' => 'maybe',
        ]);

        $response = $this->actingAs($user)->getJson("/api/events/{$event->id}/attendees?status=going");

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
    }

    public function test_can_get_my_rsvp_status(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        EventAttendee::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'going',
        ]);

        $response = $this->actingAs($user)->getJson("/api/events/{$event->id}/rsvp/me");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'going',
        ]);
    }

    public function test_can_get_events_im_attending(): void
    {
        $user = User::factory()->create();
        
        $event1 = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $event2 = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
        ]);

        $event3 = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHours(2),
        ]);

        EventAttendee::factory()->create([
            'event_id' => $event1->id,
            'user_id' => $user->id,
            'status' => 'going',
        ]);

        EventAttendee::factory()->create([
            'event_id' => $event2->id,
            'user_id' => $user->id,
            'status' => 'going',
        ]);

        // Maybe status should not be included
        EventAttendee::factory()->create([
            'event_id' => $event3->id,
            'user_id' => $user->id,
            'status' => 'maybe',
        ]);

        $response = $this->actingAs($user)->getJson('/api/events/attending');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_rsvp_status_must_be_valid(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->postJson("/api/events/{$event->id}/rsvp", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    public function test_guest_cannot_rsvp(): void
    {
        $event = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->postJson("/api/events/{$event->id}/rsvp", [
            'status' => 'going',
        ]);

        $response->assertStatus(401);
    }
}
