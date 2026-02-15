# Event Management Feature - Implementation Guide

## Overview
This document provides comprehensive documentation for the Event Management feature implemented in the Liberu Social Network Laravel application.

## Features Implemented

### 1. Event Creation and Management
Users can create, view, update, and delete events with the following attributes:
- **Title**: Event name (required, max 255 characters)
- **Description**: Detailed event information (optional, max 5000 characters)
- **Location**: Event venue or address (optional, max 255 characters)
- **Start Time**: When the event begins (required, must be in future)
- **End Time**: When the event ends (required, must be after start time)
- **Image**: Event banner/image (optional, max 10MB)
- **Max Attendees**: Capacity limit (optional)
- **Visibility**: Public or private events (default: public)

### 2. RSVP and Attendance Tracking
Users can RSVP to events with three status options:
- **Going**: User plans to attend
- **Maybe**: User is interested but not confirmed
- **Not Going**: User explicitly declined

**Key Features:**
- Update RSVP status at any time
- Cancel RSVP completely
- View attendee lists with status filters
- Automatic capacity management (prevents overbooking when max_attendees is set)
- Existing attendees can update their RSVP even when event is full

### 3. Event Discovery System
Multiple ways to discover events:
- **Browse All Events**: View upcoming public events
- **Filter by Location**: Find events in specific areas
- **Date Range Filters**: Search events within date ranges
- **Search**: Find events by title or description keywords
- **My Events**: View events you created
- **Attending Events**: See events you're attending

**Available Filters:**
- Status: upcoming, past, ongoing
- Location: partial text matching
- Date range: start_date and end_date parameters
- Search: title and description search

### 4. Calendar Integration
Export events to calendar applications:
- **Single Event Export**: Download .ics file for individual events
- **Bulk Export**: Export all your events (created + attending) at once
- **iCalendar Format**: Compatible with Google Calendar, Outlook, Apple Calendar, etc.

**Exported Information:**
- Event title, description, location
- Start and end times (UTC format)
- Organizer information
- Event URL for easy access

### 5. Admin Panel Integration
Comprehensive admin management via Filament:
- **Event Resource**: Full CRUD operations for events
- **Event Attendee Resource**: Manage RSVPs
- **Filtering**: Status, user, visibility filters
- **Bulk Operations**: Delete multiple events/RSVPs at once
- **Statistics**: View attendee counts per event

## API Endpoints

### Event Endpoints

#### List Events
```
GET /api/events
```
**Query Parameters:**
- `status`: upcoming|past|ongoing (default: upcoming)
- `location`: string (partial match)
- `start_date`: Y-m-d H:i:s format
- `end_date`: Y-m-d H:i:s format
- `search`: string (searches title and description)

**Response:** Paginated list of events with user and attendee information

#### Create Event
```
POST /api/events
```
**Request Body:**
```json
{
  "title": "Team Building Event",
  "description": "Annual company team building",
  "location": "Central Park, New York",
  "start_time": "2026-03-15 10:00:00",
  "end_time": "2026-03-15 16:00:00",
  "max_attendees": 50,
  "is_public": true,
  "image": "<file upload>"
}
```

#### View Event
```
GET /api/events/{id}
```
**Response:** Event details with attendees

#### Update Event
```
PUT /api/events/{id}
```
**Authorization:** Only event creator can update
**Request Body:** Same as create (all fields optional)

#### Delete Event
```
DELETE /api/events/{id}
```
**Authorization:** Only event creator can delete

#### Discover Events
```
GET /api/events/discover
```
**Query Parameters:** Same as list events
**Response:** Only public upcoming events

#### My Events
```
GET /api/events/my-events
```
**Query Parameters:**
- `status`: upcoming|past|ongoing

**Response:** Events created by authenticated user

#### Attending Events
```
GET /api/events/attending
```
**Query Parameters:**
- `status`: upcoming|past|ongoing (default: upcoming)

**Response:** Events user is attending (status = going)

### RSVP Endpoints

#### Create/Update RSVP
```
POST /api/events/{eventId}/rsvp
PUT /api/events/{eventId}/rsvp
```
**Request Body:**
```json
{
  "status": "going"
}
```
**Valid Status Values:** going, maybe, not_going

**Response:** RSVP record with user information

#### Cancel RSVP
```
DELETE /api/events/{eventId}/rsvp
```

#### Get My RSVP Status
```
GET /api/events/{eventId}/rsvp/me
```
**Response:**
```json
{
  "id": 1,
  "event_id": 10,
  "user_id": 5,
  "status": "going",
  "created_at": "2026-02-15T10:00:00.000000Z"
}
```

#### Get Event Attendees
```
GET /api/events/{eventId}/attendees
```
**Query Parameters:**
- `status`: going|maybe|not_going (optional filter)

**Response:** Paginated list of attendees with user information

### Calendar Export Endpoints

#### Export Single Event
```
GET /api/events/{eventId}/export
```
**Response:** .ics file download

#### Export All User Events
```
GET /api/calendar/export
```
**Includes:**
- Events created by user
- Events user is attending (status = going)
- Only upcoming events

**Response:** .ics file download with multiple events

## Database Schema

### Events Table
```sql
CREATE TABLE events (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    location VARCHAR(255) NULL,
    image_url VARCHAR(255) NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    max_attendees INT NULL,
    is_public BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_start (user_id, start_time),
    INDEX idx_public_start (is_public, start_time)
);
```

### Event Attendees Table
```sql
CREATE TABLE event_attendees (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    event_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('going', 'maybe', 'not_going') NOT NULL DEFAULT 'going',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_user (event_id, user_id),
    INDEX idx_user_status (user_id, status)
);
```

## Model Relationships

### Event Model
```php
// Relationships
user() // BelongsTo - Event creator
attendees() // HasMany - All RSVPs
goingAttendees() // HasMany - Going RSVPs only
maybeAttendees() // HasMany - Maybe RSVPs only
notGoingAttendees() // HasMany - Not going RSVPs only
attendeeUsers() // BelongsToMany - Users via pivot

// Helper Methods
goingCount() // Count of going attendees
maybeCount() // Count of maybe attendees
notGoingCount() // Count of not going attendees
isUserAttending($userId) // Check if user is attending
getUserRsvpStatus($userId) // Get user's RSVP status
isFull() // Check if event is at capacity
isPast() // Check if event has ended
isUpcoming() // Check if event hasn't started
isOngoing() // Check if event is currently happening

// Query Scopes
scopePublic() // Filter public events
scopeUpcoming() // Filter upcoming events
scopePast() // Filter past events
scopeOngoing() // Filter ongoing events
```

### EventAttendee Model
```php
// Relationships
event() // BelongsTo
user() // BelongsTo

// Query Scopes
scopeGoing() // Filter going RSVPs
scopeMaybe() // Filter maybe RSVPs
scopeNotGoing() // Filter not going RSVPs
```

## Authorization Rules

### Event Management
- **Create**: Any authenticated user
- **View**: Public events (anyone), Private events (creator only)
- **Update**: Creator only
- **Delete**: Creator only

### RSVP Management
- **Create/Update**: Any authenticated user for public events, creator can always RSVP
- **Cancel**: Own RSVP only
- **View Attendees**: Public events (anyone), Private events (creator only)

### Capacity Management
- Events with `max_attendees` set will prevent new "going" RSVPs when full
- Users who already have "going" status can update their RSVP even when full
- Users can always change from "going" to "maybe" or "not_going"

## Testing

### Test Coverage
The implementation includes comprehensive test coverage:

**EventTest.php** (16 tests):
- Event CRUD operations
- Authorization checks
- Validation rules
- Image upload
- Event filtering
- Event discovery
- My events listing

**EventAttendeeTest.php** (14 tests):
- RSVP creation and updates
- RSVP cancellation
- Authorization checks
- Capacity management
- Attendee listings
- Status filtering
- Attending events listing

**EventCalendarTest.php** (6 tests):
- Single event export
- Multiple event export
- iCalendar format validation
- Authorization for private events
- Upcoming events only filtering

### Running Tests
```bash
# Run all event tests
php artisan test --filter Event

# Run specific test file
php artisan test tests/Feature/EventTest.php
php artisan test tests/Feature/EventAttendeeTest.php
php artisan test tests/Feature/EventCalendarTest.php
```

## Admin Panel Usage

### Accessing Event Management
1. Navigate to `/admin` and login
2. Click on "Events" in the Content navigation group
3. Use filters to find specific events
4. Click "Create" to add new events manually

### Managing Event Attendees
1. Navigate to `/admin` and login
2. Click on "Event Attendees" in the Content navigation group
3. Filter by event, user, or status
4. View, edit, or delete RSVPs as needed

### Available Filters
**Events:**
- Public/Private events
- User (event creator)
- Upcoming/Past/Ongoing
- Status filters

**Event Attendees:**
- Status (going/maybe/not_going)
- Event
- User

## Usage Examples

### Creating an Event (API)
```javascript
const response = await fetch('/api/events', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    title: 'Community Meetup',
    description: 'Monthly tech community gathering',
    location: '123 Tech Street, San Francisco',
    start_time: '2026-03-20 18:00:00',
    end_time: '2026-03-20 21:00:00',
    max_attendees: 30,
    is_public: true
  })
});
```

### RSVPing to an Event
```javascript
const response = await fetch('/api/events/5/rsvp', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    status: 'going'
  })
});
```

### Discovering Events
```javascript
// Find events in New York happening next month
const response = await fetch('/api/events/discover?' + new URLSearchParams({
  location: 'New York',
  start_date: '2026-03-01 00:00:00',
  end_date: '2026-03-31 23:59:59'
}), {
  headers: {
    'Authorization': 'Bearer ' + token
  }
});
```

### Exporting to Calendar
```html
<!-- Single event export -->
<a href="/api/events/5/export" download>
  Add to Calendar
</a>

<!-- Export all user events -->
<a href="/api/calendar/export" download>
  Export My Events
</a>
```

## Security Considerations

### Input Validation
- All inputs are validated using Laravel's validation rules
- File uploads are restricted to images only (max 10MB)
- Date validations ensure start_time is in future and end_time is after start_time
- Max attendees must be positive integer if provided

### Authorization
- Private events can only be viewed/managed by creators
- Users can only RSVP to public events or their own events
- Users can only update/delete their own events
- Proper middleware authentication on all routes

### Data Protection
- User data is sanitized before storage
- File uploads are stored securely in designated directories
- SQL injection protection via Eloquent ORM
- XSS protection via proper output escaping

## Future Enhancements

Potential improvements for future versions:
- Email notifications for event reminders
- Event categories and tags
- Recurring events support
- Event comments and discussion
- Photo galleries for events
- Check-in functionality
- Event analytics and reports
- Integration with external calendar services (Google Calendar API)
- Event recommendations based on user interests
- Social sharing capabilities
- Waiting list when event is full
- Co-organizers/multiple hosts support

## Troubleshooting

### Common Issues

**Issue: Cannot create event**
- Ensure start_time is in the future
- Verify end_time is after start_time
- Check all required fields are provided

**Issue: Cannot RSVP to event**
- Verify event is public or you are the creator
- Check if event is at capacity
- Ensure you're authenticated

**Issue: Calendar export not working**
- Verify you have permission to view the event
- Check that event times are valid
- Ensure proper authentication

**Issue: Admin panel not showing events**
- Clear cache: `php artisan cache:clear`
- Verify Filament resources are registered
- Check user has appropriate permissions

## Conclusion

The Event Management feature provides a complete solution for creating, discovering, and managing events within the social network. With comprehensive RSVP tracking, calendar integration, and admin tools, it enables users to organize and participate in community events effectively.

For additional support or feature requests, please contact the development team or create an issue in the repository.
