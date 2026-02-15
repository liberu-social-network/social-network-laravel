# Media Sharing and Gallery Feature

This document describes the media sharing and gallery functionality implemented for the Liberu Social Network.

## Overview

The media sharing feature allows users to:
- Upload images and videos to their profile
- Organize media into albums
- Tag media for easy discovery
- Set privacy levels for media and albums (public, friends-only, private)
- View media in a gallery format
- Share media in posts and news feed

## Database Schema

### Media Table
Stores individual media files (images and videos).

**Columns:**
- `id`: Primary key
- `user_id`: Foreign key to users table
- `post_id`: Optional foreign key to posts table (if media is attached to a post)
- `album_id`: Optional foreign key to albums table
- `file_path`: Path to the media file in storage
- `file_name`: Original filename
- `file_type`: Type of media (image/video)
- `mime_type`: MIME type of the file
- `file_size`: Size in bytes
- `thumbnail_path`: Path to thumbnail (mainly for videos)
- `description`: Optional description
- `privacy`: Privacy level (public, friends_only, private)
- `width`: Width in pixels (for images)
- `height`: Height in pixels (for images)
- `duration`: Duration in seconds (for videos)
- `created_at`, `updated_at`: Timestamps

### Albums Table
Stores user-created albums for organizing media.

**Columns:**
- `id`: Primary key
- `user_id`: Foreign key to users table
- `name`: Album name
- `description`: Optional description
- `cover_image`: Optional cover image path
- `privacy`: Privacy level (public, friends_only, private)
- `created_at`, `updated_at`: Timestamps

### Tags Table
Stores tags that can be applied to media.

**Columns:**
- `id`: Primary key
- `name`: Tag name
- `slug`: URL-friendly slug
- `created_at`, `updated_at`: Timestamps

### Media_Tag Table (Pivot)
Links media to tags.

**Columns:**
- `id`: Primary key
- `media_id`: Foreign key to media table
- `tag_id`: Foreign key to tags table
- `created_at`, `updated_at`: Timestamps

### Posts Table (Enhanced)
Added privacy column to existing posts table.

**New Column:**
- `privacy`: Privacy level (public, friends_only, private)

## API Endpoints

### Media Endpoints

#### Upload Media
```
POST /api/media
```

**Request Body:**
- `file`: File (required) - Image or video file
- `album_id`: Integer (optional) - Album ID to add media to
- `description`: String (optional) - Description of the media
- `privacy`: String (required) - Privacy level (public, friends_only, private)
- `tags`: Array (optional) - Array of tag names

**Response:** Created media object with relations

#### List User's Media
```
GET /api/media
```

**Query Parameters:**
- `album_id`: Filter by album
- `tag`: Filter by tag slug
- `type`: Filter by file type (image/video)

**Response:** Paginated list of user's media

#### Get Media Feed
```
GET /api/media/feed
```

**Response:** Paginated list of media visible to the current user

#### Get User Gallery
```
GET /api/media/gallery/{userId}
```

**Response:** User's albums and recent media (respecting privacy settings)

#### View Media
```
GET /api/media/{id}
```

**Response:** Media object with relations (if user has permission)

#### Update Media
```
PUT /api/media/{id}
```

**Request Body:**
- `album_id`: Integer (optional)
- `description`: String (optional)
- `privacy`: String (optional)
- `tags`: Array (optional)

**Response:** Updated media object

#### Delete Media
```
DELETE /api/media/{id}
```

**Response:** Success message

### Album Endpoints

#### Create Album
```
POST /api/albums
```

**Request Body:**
- `name`: String (required) - Album name
- `description`: String (optional) - Album description
- `privacy`: String (required) - Privacy level

**Response:** Created album object

#### List User's Albums
```
GET /api/albums
```

**Response:** Paginated list of user's albums with media count

#### List Public Albums
```
GET /api/albums/public
```

**Response:** Paginated list of albums visible to current user

#### View Album
```
GET /api/albums/{id}
```

**Response:** Album with its media (if user has permission)

#### Update Album
```
PUT /api/albums/{id}
```

**Request Body:**
- `name`: String (optional)
- `description`: String (optional)
- `privacy`: String (optional)

**Response:** Updated album object

#### Delete Album
```
DELETE /api/albums/{id}
```

**Response:** Success message
**Note:** Media in the album are not deleted, only their album association is removed.

## Privacy Settings

### Privacy Levels

1. **Public**: Visible to everyone
2. **Friends Only**: Visible only to confirmed friends
3. **Private**: Visible only to the owner

### Privacy Implementation

Both Media and Album models include:
- `isVisibleTo(User $viewer)`: Check if viewer can access the item
- `scopeVisibleTo($query, User $viewer)`: Query scope to filter visible items

Posts also include the same privacy functionality to ensure consistency.

### Privacy Rules

- Public content is visible to all authenticated users
- Private content is only visible to the owner
- Friends-only content is visible to:
  - The owner
  - Users with accepted friendship status

## File Storage

Media files are stored using Laravel's Storage facade:
- **Location**: `storage/app/public/media/`
- **Structure**:
  - `media/images/{userId}/` - User's images
  - `media/videos/{userId}/` - User's videos
  - `media/thumbnails/` - Video thumbnails

Files are stored with random 40-character names to prevent collisions and enhance security.

## File Validation

### Image Files
- **Allowed types**: jpeg, jpg, png, gif
- **Maximum size**: 50MB
- **MIME type validation**: Enforced

### Video Files
- **Allowed types**: mp4, mov, avi, wmv
- **Maximum size**: 50MB
- **MIME type validation**: Enforced

## Models

### Media Model
**Key Methods:**
- `isVisibleTo(User $viewer)`: Check visibility
- `scopeVisibleTo($query, User $viewer)`: Query scope
- `getUrlAttribute()`: Get full media URL
- `getThumbnailUrlAttribute()`: Get thumbnail URL

**Relationships:**
- `user()`: Belongs to User
- `post()`: Belongs to Post (optional)
- `album()`: Belongs to Album (optional)
- `tags()`: Many-to-many with Tag

### Album Model
**Key Methods:**
- `isVisibleTo(User $viewer)`: Check visibility
- `scopeVisibleTo($query, User $viewer)`: Query scope
- `getMediaCountAttribute()`: Get media count
- `getCoverImageUrlAttribute()`: Get cover image URL

**Relationships:**
- `user()`: Belongs to User
- `media()`: Has many Media

### Tag Model
**Key Methods:**
- `setNameAttribute($value)`: Auto-generate slug
- `getMediaCountAttribute()`: Get media count

**Relationships:**
- `media()`: Many-to-many with Media

## Integration with Posts

Posts can now have:
- Privacy settings (same as media)
- Media attachments via the `media()` relationship
- Legacy image/video URLs (maintained for backward compatibility)

When creating a post with the existing image/video upload, privacy can be set:
```json
{
  "content": "Check out this photo!",
  "image": "<file>",
  "privacy": "friends_only"
}
```

## Usage Examples

### Upload and Tag an Image
```javascript
const formData = new FormData();
formData.append('file', imageFile);
formData.append('description', 'Beautiful sunset');
formData.append('privacy', 'public');
formData.append('tags', JSON.stringify(['nature', 'sunset', 'photography']));

fetch('/api/media', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
  },
  body: formData
});
```

### Create an Album
```javascript
fetch('/api/albums', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Summer Vacation 2026',
    description: 'Photos from our trip to Hawaii',
    privacy: 'friends_only'
  })
});
```

### Add Media to Album
```javascript
// First upload media with album_id
const formData = new FormData();
formData.append('file', imageFile);
formData.append('album_id', albumId);
formData.append('privacy', 'friends_only');

// Or update existing media
fetch(`/api/media/${mediaId}`, {
  method: 'PUT',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    album_id: albumId
  })
});
```

## Testing

### Running Tests
```bash
php artisan test --filter MediaTest
php artisan test --filter AlbumTest
php artisan test --filter PostPrivacyTest
```

### Test Coverage
- Media upload (images and videos)
- Media privacy settings
- Album creation and management
- Tag functionality
- Privacy enforcement
- Authorization checks
- Guest access restrictions

## Security Considerations

1. **File Validation**: All uploads are validated for type and size
2. **Authorization**: All endpoints check user ownership before allowing modifications
3. **Privacy Enforcement**: Visibility checks are enforced at the model level
4. **Secure Filenames**: Random 40-character filenames prevent enumeration attacks
5. **Storage Security**: Files are stored outside the public web root
6. **XSS Prevention**: Descriptions and tags are properly escaped in views

## Future Enhancements

Potential improvements for future iterations:
- Image processing and optimization
- Video thumbnail generation
- Multiple file upload in single request
- Drag-and-drop reordering in albums
- Advanced search and filtering
- Media sharing to specific users
- Comments on media
- Likes/reactions on media
- Download statistics
- Automatic tagging using image recognition
- GIF and WebP support
- Cloud storage integration (S3, etc.)

## Troubleshooting

### Storage Link Not Working
Make sure the storage link is created:
```bash
php artisan storage:link
```

### File Upload Errors
Check PHP upload limits in `php.ini`:
```ini
upload_max_filesize = 50M
post_max_size = 50M
```

### Permission Issues
Ensure storage directory is writable:
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## Contributing

When contributing to this feature:
1. Maintain backward compatibility with existing post media
2. Follow the existing privacy pattern
3. Add tests for new functionality
4. Update this documentation
5. Run security checks before submitting PR
