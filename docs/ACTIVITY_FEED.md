# User Activity Feed Feature

## Overview
The user activity feed displays recent activities from a user's network, including new posts, likes, and comments. The feed updates in real-time to provide users with the latest updates.

## Features
- **Real-time Updates**: The feed automatically refreshes every 10 seconds using Livewire polling
- **Activity Types**:
  - Post creation
  - Post likes
  - Comment additions
- **Network-based**: Activities are shown from the user and their friends
- **Pagination**: Load more functionality to view older activities

## Components

### Database
- **Migration**: `2024_12_01_100000_create_activities_table.php`
  - Stores all user activities
  - Uses polymorphic relationships for flexible subject types
  - Indexed for performance

### Models
- **Activity** (`app/Models/Activity.php`)
  - Relationships: user, actor, subject (polymorphic)
  - Scopes: forUser, recent

### Services
- **ActivityService** (`app/Services/ActivityService.php`)
  - `createActivity()`: Creates activities for user's network
  - `getActivitiesForUser()`: Retrieves activities for feed
  - `getFriendIds()`: Gets user's friend list
  - `deleteActivitiesForSubject()`: Cleans up deleted content

### Observers
Automatically create activities when events occur:
- **PostObserver**: Tracks post creation and deletion
- **LikeObserver**: Tracks likes
- **CommentObserver**: Tracks comments

### Livewire Component
- **ActivityFeed** (`app/Http/Livewire/ActivityFeed.php`)
  - Real-time feed updates
  - Load more functionality
  - Wire:poll for automatic updates

### Views
- **Main Page**: `resources/views/activity-feed.blade.php`
- **Livewire Component**: `resources/views/livewire/activity-feed.blade.php`

## Routes
```php
GET /activity-feed - View the activity feed (requires authentication)
```

## Usage

### Viewing the Activity Feed
Navigate to `/activity-feed` when authenticated to see your personalized activity feed.

### Adding Custom Activity Types
1. Create the activity in your observer or controller:
```php
$activityService->createActivity(
    actorId: $userId,
    type: 'custom_activity_type',
    subject: $model,
    data: ['additional' => 'information']
);
```

2. Add the display logic in `resources/views/livewire/activity-feed.blade.php`:
```blade
@elseif($activity->type === 'custom_activity_type')
    <span class="text-gray-600">performed a custom action</span>
@endif
```

## Testing
Run the test suite:
```bash
php artisan test --filter=Activity
```

Tests cover:
- Activity creation on Post, Like, Comment events
- Activity retrieval for users
- Activity deletion on subject deletion
- Model relationships and scopes
- Authentication requirements

## Performance Considerations
- Activities table has indexes on user_id, actor_id, and type
- Feed is limited to 20 items by default
- Pagination available for older activities
- Consider adding a cleanup job for old activities

## Future Enhancements
- Push notifications for new activities
- Activity filtering by type
- Mark activities as read/unread
- Aggregate similar activities (e.g., "User A and 5 others liked your post")
- Configurable polling interval
- Export activity data
