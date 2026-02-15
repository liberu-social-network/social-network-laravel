<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_export_event_to_icalendar(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Event',
            'description' => 'Test Description',
            'location' => 'Test Location',
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->get("/api/events/{$event->id}/export");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        
        $content = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('BEGIN:VEVENT', $content);
        $this->assertStringContainsString('SUMMARY:Test Event', $content);
        $this->assertStringContainsString('DESCRIPTION:Test Description', $content);
        $this->assertStringContainsString('LOCATION:Test Location', $content);
        $this->assertStringContainsString('END:VEVENT', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);
    }

    public function test_can_export_multiple_events_to_icalendar(): void
    {
        $user = User::factory()->create();
        
        // Create user's own events
        Event::factory()->count(2)->create([
            'user_id' => $user->id,
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        // Create event user is attending
        $event = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
        ]);

        EventAttendee::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'going',
        ]);

        $response = $this->actingAs($user)->get('/api/calendar/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        
        $content = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        // Should contain 3 events
        $this->assertEquals(3, substr_count($content, 'BEGIN:VEVENT'));
        $this->assertEquals(3, substr_count($content, 'END:VEVENT'));
        $this->assertStringContainsString('END:VCALENDAR', $content);
    }

    public function test_cannot_export_private_event_from_other_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $event = Event::factory()->create([
            'user_id' => $otherUser->id,
            'is_public' => false,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->get("/api/events/{$event->id}/export");

        $response->assertStatus(403);
    }

    public function test_can_export_own_private_event(): void
    {
        $user = User::factory()->create();
        
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
            'title' => 'Private Event',
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->get("/api/events/{$event->id}/export");

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('SUMMARY:Private Event', $content);
    }

    public function test_only_exports_upcoming_events(): void
    {
        $user = User::factory()->create();
        
        // Create upcoming event
        Event::factory()->create([
            'user_id' => $user->id,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        // Create past event
        Event::factory()->create([
            'user_id' => $user->id,
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHours(2),
        ]);

        $response = $this->actingAs($user)->get('/api/calendar/export');

        $response->assertStatus(200);
        $content = $response->getContent();
        // Should only contain 1 upcoming event
        $this->assertEquals(1, substr_count($content, 'BEGIN:VEVENT'));
    }

    public function test_guest_cannot_export_events(): void
    {
        $event = Event::factory()->create([
            'is_public' => true,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->get("/api/events/{$event->id}/export");

        $response->assertStatus(401);
    }
}
