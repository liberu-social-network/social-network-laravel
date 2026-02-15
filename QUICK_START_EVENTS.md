# Event Management - Quick Start Guide

## 🎯 What Was Implemented

A complete Event Management system for the social network with:
- ✅ Event creation and management
- ✅ RSVP system (going/maybe/not_going)
- ✅ Event discovery and search
- ✅ Calendar integration (.ics export)
- ✅ Admin panel for moderation
- ✅ 36 automated tests

## 🚀 Quick Start

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Test the API

#### Create an Event
```bash
curl -X POST http://localhost/api/events \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Community Meetup",
    "description": "Monthly tech gathering",
    "location": "Downtown Coffee Shop",
    "start_time": "2026-03-15 18:00:00",
    "end_time": "2026-03-15 21:00:00",
    "is_public": true
  }'
```

#### RSVP to an Event
```bash
curl -X POST http://localhost/api/events/1/rsvp \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "going"}'
```

#### Discover Events
```bash
curl http://localhost/api/events/discover?location=Downtown \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Export to Calendar
```bash
curl http://localhost/api/events/1/export \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o event.ics
```

### 3. Run Tests
```bash
# Run all event tests
php artisan test --filter Event

# Run specific test file
php artisan test tests/Feature/EventTest.php
```

### 4. Access Admin Panel
1. Navigate to: `http://localhost/admin`
2. Login with admin credentials
3. Go to "Events" or "Event Attendees" in the Content menu

## 📚 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/events | List events with filters |
| POST | /api/events | Create new event |
| GET | /api/events/{id} | View event details |
| PUT | /api/events/{id} | Update event |
| DELETE | /api/events/{id} | Delete event |
| GET | /api/events/discover | Discover public events |
| GET | /api/events/my-events | Your created events |
| GET | /api/events/attending | Events you're attending |
| POST | /api/events/{id}/rsvp | RSVP to event |
| DELETE | /api/events/{id}/rsvp | Cancel RSVP |
| GET | /api/events/{id}/attendees | List attendees |
| GET | /api/events/{id}/export | Export to .ics |
| GET | /api/calendar/export | Export all events |

## 🔍 Key Features

### Event Creation
- Title, description, location
- Start/end times with validation
- Image upload support
- Capacity management (max attendees)
- Public/private visibility

### RSVP System
- Three statuses: going, maybe, not_going
- Update anytime
- Capacity enforcement
- Attendee lists

### Discovery
- Filter by location, date, status
- Search by title/description
- View created events
- View attending events

### Calendar Integration
- iCalendar (.ics) format
- Compatible with Google, Outlook, Apple
- Single or bulk export

### Admin Panel
- Full event management
- RSVP moderation
- Bulk operations
- Advanced filters

## 📖 Documentation

- **Complete Guide**: `docs/EVENT_MANAGEMENT.md`
- **Implementation Summary**: `EVENT_IMPLEMENTATION_SUMMARY.md`

## 🧪 Test Coverage

- **EventTest**: 16 tests for CRUD operations
- **EventAttendeeTest**: 14 tests for RSVP functionality
- **EventCalendarTest**: 6 tests for calendar export

**Total: 36 tests** - All passing ✅

## 🔒 Security

- ✅ Input validation on all endpoints
- ✅ Authorization checks (creator-only for private events)
- ✅ SQL injection protection
- ✅ XSS protection
- ✅ File upload restrictions
- ✅ CodeQL scan passed

## 💡 Common Use Cases

### Scenario 1: Create a Public Event
```php
$event = Event::create([
    'user_id' => auth()->id(),
    'title' => 'Tech Talk',
    'description' => 'Learn about AI',
    'location' => 'Conference Center',
    'start_time' => '2026-04-01 14:00:00',
    'end_time' => '2026-04-01 16:00:00',
    'is_public' => true,
    'max_attendees' => 100
]);
```

### Scenario 2: RSVP to Event
```php
EventAttendee::create([
    'event_id' => $eventId,
    'user_id' => auth()->id(),
    'status' => 'going'
]);
```

### Scenario 3: Find Nearby Events
```php
$events = Event::public()
    ->upcoming()
    ->where('location', 'like', '%' . $city . '%')
    ->paginate(20);
```

## 🎓 Next Steps

1. ✅ Feature is complete and tested
2. ✅ Documentation is ready
3. ✅ Security scan passed
4. 🚀 Ready for production deployment

For detailed information, see `docs/EVENT_MANAGEMENT.md`
