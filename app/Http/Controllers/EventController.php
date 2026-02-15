<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Event::with(['user', 'attendees.user'])
            ->where(function ($q) use ($request) {
                $q->where('is_public', true)
                    ->orWhere('user_id', auth()->id());
            });

        // Filter by status (upcoming, past, ongoing)
        if ($request->has('status')) {
            switch ($request->status) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'past':
                    $query->past();
                    break;
                case 'ongoing':
                    $query->ongoing();
                    break;
            }
        } else {
            // Default to upcoming events
            $query->upcoming();
        }

        // Filter by location
        if ($request->has('location') && $request->location) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('start_time', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('end_time', '<=', $request->end_date);
        }

        // Search by title or description
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $events = $query->orderBy('start_time', 'asc')->paginate(20);

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'location' => 'nullable|string|max:255',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'max_attendees' => 'nullable|integer|min:1',
            'is_public' => 'boolean',
            'image' => 'nullable|image|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imageUrl = null;

        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('events/images', 'public');
        }

        $event = Event::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'max_attendees' => $request->max_attendees,
            'is_public' => $request->is_public ?? true,
            'image_url' => $imageUrl,
        ]);

        $event->load(['user', 'attendees.user']);

        return response()->json($event, 201);
    }

    public function show($id)
    {
        $event = Event::with(['user', 'attendees.user'])
            ->findOrFail($id);

        // Check if user can view this event
        if (!$event->is_public && $event->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($event);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if ($event->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'description' => 'nullable|string|max:5000',
            'location' => 'nullable|string|max:255',
            'start_time' => 'date|after:now',
            'end_time' => 'date|after:start_time',
            'max_attendees' => 'nullable|integer|min:1',
            'is_public' => 'boolean',
            'image' => 'nullable|image|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'title',
            'description',
            'location',
            'start_time',
            'end_time',
            'max_attendees',
            'is_public',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($event->image_url) {
                Storage::disk('public')->delete($event->image_url);
            }
            $data['image_url'] = $request->file('image')->store('events/images', 'public');
        }

        $event->update($data);

        $event->load(['user', 'attendees.user']);

        return response()->json($event);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        if ($event->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete associated image file
        if ($event->image_url) {
            Storage::disk('public')->delete($event->image_url);
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }

    public function discover(Request $request)
    {
        $query = Event::with(['user', 'attendees.user'])
            ->public()
            ->upcoming();

        // Location-based discovery
        if ($request->has('location') && $request->location) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Date range filter
        if ($request->has('start_date')) {
            $query->where('start_time', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('start_time', '<=', $request->end_date);
        }

        $events = $query->orderBy('start_time', 'asc')->paginate(20);

        return response()->json($events);
    }

    public function myEvents(Request $request)
    {
        $query = Event::with(['user', 'attendees.user'])
            ->where('user_id', auth()->id());

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'past':
                    $query->past();
                    break;
                case 'ongoing':
                    $query->ongoing();
                    break;
            }
        }

        $events = $query->orderBy('start_time', 'desc')->paginate(20);

        return response()->json($events);
    }

    public function attending(Request $request)
    {
        $userId = auth()->id();

        $query = Event::with(['user', 'attendees.user'])
            ->whereHas('attendees', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->where('status', 'going');
            });

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'past':
                    $query->past();
                    break;
                case 'ongoing':
                    $query->ongoing();
                    break;
            }
        } else {
            $query->upcoming();
        }

        $events = $query->orderBy('start_time', 'asc')->paginate(20);

        return response()->json($events);
    }
}
