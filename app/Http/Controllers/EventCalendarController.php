<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventCalendarController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function exportEvent($eventId)
    {
        $event = Event::findOrFail($eventId);

        // Check if user can view this event
        if (!$event->is_public && $event->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $icsContent = $this->generateICS($event);

        return response($icsContent)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="event-' . $event->id . '.ics"');
    }

    public function exportMyEvents()
    {
        $userId = auth()->id();

        $events = Event::where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhereHas('attendees', function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                        ->where('status', 'going');
                });
        })
            ->upcoming()
            ->get();

        $icsContent = $this->generateMultipleICS($events);

        return response($icsContent)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="my-events.ics"');
    }

    private function generateICS(Event $event)
    {
        $now = now()->format('Ymd\THis\Z');
        $start = $event->start_time->format('Ymd\THis\Z');
        $end = $event->end_time->format('Ymd\THis\Z');
        $uid = 'event-' . $event->id . '@' . config('app.url');
        $url = config('app.url') . '/api/events/' . $event->id;

        $description = $this->escapeString($event->description ?? '');
        $title = $this->escapeString($event->title);
        $location = $this->escapeString($event->location ?? '');
        $organizer = $this->escapeString($event->user->name ?? 'Unknown');

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Social Network//Event//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTAMP:{$now}\r\n";
        $ics .= "DTSTART:{$start}\r\n";
        $ics .= "DTEND:{$end}\r\n";
        $ics .= "SUMMARY:{$title}\r\n";
        
        if ($event->description) {
            $ics .= "DESCRIPTION:{$description}\r\n";
        }
        
        if ($event->location) {
            $ics .= "LOCATION:{$location}\r\n";
        }
        
        $ics .= "ORGANIZER:CN={$organizer}\r\n";
        $ics .= "URL:{$url}\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "SEQUENCE:0\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    private function generateMultipleICS($events)
    {
        $now = now()->format('Ymd\THis\Z');

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Social Network//Events//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";

        foreach ($events as $event) {
            $start = $event->start_time->format('Ymd\THis\Z');
            $end = $event->end_time->format('Ymd\THis\Z');
            $uid = 'event-' . $event->id . '@' . config('app.url');
            $url = config('app.url') . '/api/events/' . $event->id;

            $description = $this->escapeString($event->description ?? '');
            $title = $this->escapeString($event->title);
            $location = $this->escapeString($event->location ?? '');
            $organizer = $this->escapeString($event->user->name ?? 'Unknown');

            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:{$uid}\r\n";
            $ics .= "DTSTAMP:{$now}\r\n";
            $ics .= "DTSTART:{$start}\r\n";
            $ics .= "DTEND:{$end}\r\n";
            $ics .= "SUMMARY:{$title}\r\n";
            
            if ($event->description) {
                $ics .= "DESCRIPTION:{$description}\r\n";
            }
            
            if ($event->location) {
                $ics .= "LOCATION:{$location}\r\n";
            }
            
            $ics .= "ORGANIZER:CN={$organizer}\r\n";
            $ics .= "URL:{$url}\r\n";
            $ics .= "STATUS:CONFIRMED\r\n";
            $ics .= "SEQUENCE:0\r\n";
            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    private function escapeString($string)
    {
        return str_replace(["\r\n", "\n", "\r", ",", ";"], ['\n', '\n', '\n', '\,', '\;'], $string);
    }
}
