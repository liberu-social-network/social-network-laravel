# Post Scheduling Feature Documentation

## Overview

The post scheduling feature allows users to schedule posts for future publication. Posts can be created with a `scheduled_at` timestamp, and they will automatically be published when that time is reached.

## Database Schema Changes

The following fields were added to the `posts` table:

- **`scheduled_at`** (timestamp, nullable): The datetime when the post should be published
- **`is_published`** (boolean, default: true): Whether the post is currently published
- Indexes on `scheduled_at` and composite index on `(is_published, scheduled_at)` for query performance

## API Usage

### Creating a Scheduled Post

**Endpoint**: `POST /api/posts`

**Request Body**:
```json
{
  "content": "This post will be published later",
  "scheduled_at": "2026-03-01 15:30:00",
  "privacy": "public"
}
```

**Validation Rules**:
- `scheduled_at`: Must be a valid date in the future (validated with `after:now`)
- If `scheduled_at` is provided and is in the future, the post will be marked as unpublished

**Response** (201 Created):
```json
{
  "post": {
    "id": 123,
    "content": "This post will be published later",
    "scheduled_at": "2026-03-01 15:30:00",
    "is_published": false,
    ...
  },
  "message": "Post scheduled successfully for 2026-03-01 15:30:00"
}
```

### Creating an Immediate Post

Simply omit the `scheduled_at` field or set it to `null`:

```json
{
  "content": "This post will be published immediately",
  "privacy": "public"
}
```

The post will be published immediately with `is_published: true`.

## Visibility Rules

1. **Published Posts**: Visible to all users according to privacy settings
2. **Scheduled/Unpublished Posts**: 
   - Only visible to the post author
   - Return 404 when accessed by other users
   - Do not appear in public feeds

## Automated Publishing

### Laravel Scheduler Setup

The system uses Laravel's task scheduler to automatically publish scheduled posts.

1. **Command**: `php artisan posts:publish-scheduled`
   - Runs every minute via Laravel scheduler
   - Finds all posts where `is_published = false` and `scheduled_at <= now()`
   - Updates them to `is_published = true`

2. **Cron Configuration**:
   Add this to your server's crontab:
   ```bash
   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
   ```

### Manual Publishing

You can also manually trigger the publishing command:
```bash
php artisan posts:publish-scheduled
```

Output example:
```
Published post ID: 123 - "This is a scheduled post"
Published post ID: 124 - "Another scheduled post"
Successfully published 2 post(s).
```

## Admin Panel (Filament)

The Filament admin panel has been updated with scheduling features:

### Post Form Fields:
- **Schedule for**: DateTimePicker field to select publication date/time
  - Minimum date: Current datetime
  - Helper text: "Leave empty to publish immediately"
- **Published**: Toggle to manually control publication status
  - Default: checked (true)
  - Helper text: "Uncheck to keep as draft or scheduled"

### Post Table Columns:
- **Published**: Boolean icon column showing publication status
- **Scheduled For**: Datetime column showing when post is scheduled (or "—" if not scheduled)

### Filters:
- **Publication Status**: Filter by published or scheduled/draft posts

## Model Methods

### Post Model

```php
// Scopes
Post::published()->get();  // Get only published posts
Post::scheduledForPublishing()->get();  // Get posts ready to be published

// Instance methods
$post->isScheduled();  // Returns true if post is scheduled for future
```

## Testing

Comprehensive test coverage is provided in `tests/Feature/PostSchedulingTest.php`:

- Scheduling posts for future publication
- Scheduled posts not appearing in feeds
- Automated publishing via command
- Visibility restrictions for scheduled posts
- Validation of scheduled_at field

### Running Tests

```bash
php artisan test tests/Feature/PostSchedulingTest.php
```

## Factory Methods

For testing, the PostFactory includes scheduling methods:

```php
// Create a post scheduled 60 minutes in future
Post::factory()->scheduled(60)->create();

// Create a post scheduled for publishing (5 minutes in past)
Post::factory()->scheduledForPublishing()->create();
```

## Example Workflow

1. **User schedules a post**:
   ```php
   POST /api/posts
   {
       "content": "Happy New Year 2027!",
       "scheduled_at": "2027-01-01 00:00:00"
   }
   ```
   Response confirms scheduling.

2. **Post is stored as unpublished**:
   - `is_published = false`
   - `scheduled_at = "2027-01-01 00:00:00"`

3. **Laravel scheduler runs every minute**:
   - When 2027-01-01 00:00:00 arrives, the command finds this post
   - Updates `is_published = true`
   - Post now appears in feeds

4. **Users see the post**:
   - Post is now visible according to privacy settings
   - Includes all normal interaction features (likes, comments, shares)

## Best Practices

1. **Timezone Considerations**: Ensure your Laravel app's timezone is correctly configured in `config/app.php`
2. **Monitoring**: Log or monitor the scheduler to ensure it's running correctly
3. **Queue Jobs**: For high-volume applications, consider moving the publishing logic to a queued job
4. **Database Indexes**: The composite index on `(is_published, scheduled_at)` ensures efficient queries

## Troubleshooting

### Posts Not Publishing Automatically

1. Check if cron is running the scheduler:
   ```bash
   php artisan schedule:list
   ```

2. Manually run the command to test:
   ```bash
   php artisan posts:publish-scheduled
   ```

3. Check Laravel logs for errors

### Scheduled Posts Appearing Too Early/Late

- Verify server timezone matches Laravel app timezone
- Check that `scheduled_at` values are stored in correct timezone
- Ensure system clock is accurate

## Security Considerations

- Users can only schedule their own posts
- Scheduled posts are not visible to other users until published
- Validation ensures scheduled_at cannot be in the past
- No special permissions required - all authenticated users can schedule posts
