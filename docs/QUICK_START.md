# Activity Feed - Quick Start Guide

## For Users

### Accessing the Activity Feed
1. Log in to your account
2. Navigate to `/activity-feed`
3. View recent activities from your network
4. Feed auto-updates every 10 seconds
5. Click "Load More" to see older activities

## For Developers

### Adding a New Activity Type

**Step 1: Create the Activity**
```php
use App\Services\ActivityService;

$activityService = app(ActivityService::class);

$activityService->createActivity(
    actorId: $userId,
    type: 'new_activity_type',
    subject: $model,
    data: ['custom_key' => 'custom_value']
);
```

**Step 2: Add Display Logic**
Edit `resources/views/livewire/activity-feed.blade.php`:
```blade
@elseif($activity->type === 'new_activity_type')
    <span class="text-gray-600">performed a new action</span>
@endif
```

### Using the Activity Feed Component

**In a Blade View:**
```blade
@livewire('activity-feed')
```

**As a Standalone Page:**
```blade
@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        @livewire('activity-feed')
    </div>
@endsection
```

### Retrieving Activities Programmatically

```php
use App\Services\ActivityService;

$activityService = app(ActivityService::class);

// Get activities for a specific user
$activities = $activityService->getActivitiesForUser($userId, $limit = 20);

// Each activity has:
// - actor (User who performed the action)
// - user (User who sees this in their feed)
// - type (Activity type string)
// - subject (The related model - Post, Like, Comment)
// - data (Additional JSON data)
```

### Creating Custom Observers

```php
namespace App\Observers;

use App\Models\YourModel;
use App\Services\ActivityService;

class YourModelObserver
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function created(YourModel $model): void
    {
        $this->activityService->createActivity(
            actorId: $model->user_id,
            type: 'your_model_created',
            subject: $model
        );
    }

    public function deleted(YourModel $model): void
    {
        $this->activityService->deleteActivitiesForSubject($model);
    }
}
```

**Register in AppServiceProvider:**
```php
use App\Models\YourModel;
use App\Observers\YourModelObserver;

public function boot(): void
{
    YourModel::observe(YourModelObserver::class);
}
```

### API Usage

**Get Activities as JSON:**
```bash
curl -X GET "https://yourapp.com/api/activities?limit=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "activities": [
    {
      "id": 1,
      "user_id": 1,
      "actor_id": 2,
      "type": "post_created",
      "subject_type": "App\\Models\\Post",
      "subject_id": 5,
      "data": {
        "content_preview": "Hello world..."
      },
      "created_at": "2024-02-14T10:30:00.000000Z",
      "actor": {
        "id": 2,
        "name": "John Doe"
      }
    }
  ]
}
```

### Testing

**Run Activity Tests:**
```bash
# All activity tests
php artisan test --filter=Activity

# Feature tests only
php artisan test tests/Feature/ActivityFeedTest.php

# Unit tests only
php artisan test tests/Unit/ActivityTest.php
```

### Database Queries

**Get all activities for a user:**
```php
$activities = Activity::forUser($userId)->recent(20)->get();
```

**Get activities by type:**
```php
$postActivities = Activity::where('type', 'post_created')->get();
```

**Get activities with relationships:**
```php
$activities = Activity::with(['actor', 'subject', 'user'])
    ->forUser($userId)
    ->recent(20)
    ->get();
```

### Configuration

**Change Polling Interval:**
Edit `resources/views/livewire/activity-feed.blade.php`:
```blade
<!-- Change from 10s to 5s -->
<div wire:poll.5s="loadActivities">
```

**Change Default Limit:**
Edit `app/Http/Livewire/ActivityFeed.php`:
```php
public $limit = 30; // Changed from 20
```

### Performance Tips

1. **Add Indexes** - Activities table already has indexes on commonly queried columns
2. **Eager Loading** - Always use `with()` to avoid N+1 queries
3. **Pagination** - Use the load more feature instead of loading all activities
4. **Cleanup** - Consider adding a scheduled task to delete old activities

### Troubleshooting

**Activities not showing:**
- Check if users are friends (activities only show for network)
- Verify observers are registered in AppServiceProvider
- Check database for activity records

**Real-time updates not working:**
- Ensure Livewire scripts are included in layout
- Check browser console for JavaScript errors
- Verify wire:poll directive is present

**Performance issues:**
- Add indexes to activities table
- Reduce polling frequency
- Implement caching for friend lists
- Add pagination/lazy loading

## Architecture Overview

```
User Action (Post/Like/Comment)
    ↓
Model Observer Triggered
    ↓
ActivityService::createActivity()
    ↓
Get User's Friends
    ↓
Create Activity Records for Each Friend
    ↓
Store in activities Table
    ↓
Livewire Component Polls (10s)
    ↓
ActivityService::getActivitiesForUser()
    ↓
Display in Feed with Real-time Updates
```

## Related Files
- Model: `app/Models/Activity.php`
- Service: `app/Services/ActivityService.php`
- Component: `app/Http/Livewire/ActivityFeed.php`
- View: `resources/views/livewire/activity-feed.blade.php`
- Observers: `app/Observers/*Observer.php`
- Tests: `tests/Feature/ActivityFeedTest.php`, `tests/Unit/ActivityTest.php`
