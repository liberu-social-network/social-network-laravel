<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventAttendeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function rsvp(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);

        // Check if event is public or user is the organizer
        if (!$event->is_public && $event->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:going,maybe,not_going',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if event is full (only for "going" status)
        if ($request->status === 'going' && $event->isFull()) {
            $currentRsvp = $event->getUserRsvpStatus(auth()->id());
            // Allow if user is already going (updating their RSVP)
            if ($currentRsvp !== 'going') {
                return response()->json(['error' => 'Event is full'], 422);
            }
        }

        // Create or update RSVP
        $attendee = EventAttendee::updateOrCreate(
            [
                'event_id' => $eventId,
                'user_id' => auth()->id(),
            ],
            [
                'status' => $request->status,
            ]
        );

        $attendee->load('user');

        return response()->json($attendee, 201);
    }

    public function updateRsvp(Request $request, $eventId)
    {
        return $this->rsvp($request, $eventId);
    }

    public function cancelRsvp($eventId)
    {
        $event = Event::findOrFail($eventId);

        $attendee = EventAttendee::where('event_id', $eventId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$attendee) {
            return response()->json(['error' => 'RSVP not found'], 404);
        }

        $attendee->delete();

        return response()->json(['message' => 'RSVP cancelled successfully']);
    }

    public function getAttendees($eventId, Request $request)
    {
        $event = Event::findOrFail($eventId);

        // Check if user can view attendees
        if (!$event->is_public && $event->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = EventAttendee::with('user')
            ->where('event_id', $eventId);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $attendees = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($attendees);
    }

    public function getMyRsvp($eventId)
    {
        $event = Event::findOrFail($eventId);

        $attendee = EventAttendee::with('user')
            ->where('event_id', $eventId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$attendee) {
            return response()->json(['status' => null]);
        }

        return response()->json($attendee);
    }
}
