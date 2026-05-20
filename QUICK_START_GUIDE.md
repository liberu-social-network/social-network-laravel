# Media Sharing Feature - Quick Start Guide

## 🚀 Getting Started

This guide provides a quick overview of using the media sharing and gallery feature.

## Installation

1. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

2. **Create Storage Link:**
   ```bash
   php artisan storage:link
   ```

3. **Set Permissions:**
   ```bash
   chmod -R 775 storage
   ```

## API Usage Examples

### Upload an Image

```bash
curl -X POST http://your-app.com/api/media \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@photo.jpg" \
  -F "privacy=public" \
  -F "description=My photo" \
  -F "tags[]=nature" \
  -F "tags[]=sunset"
```

### Create an Album

```bash
curl -X POST http://your-app.com/api/albums \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Vacation",
    "description": "Summer 2026",
    "privacy": "friends_only"
  }'
```

### Add Media to Album

```bash
curl -X PUT http://your-app.com/api/media/123 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "album_id": 5
  }'
```

### View User Gallery

```bash
curl -X GET http://your-app.com/api/media/gallery/USER_ID \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Media Feed

```bash
curl -X GET http://your-app.com/api/media/feed \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Privacy Levels

| Level | Description | Visible To |
|-------|-------------|------------|
| `public` | Everyone can see | All users |
| `friends_only` | Friends only | Confirmed friends + owner |
| `private` | Owner only | Owner only |

## File Limits

- **Images:** JPEG, PNG, GIF
- **Videos:** MP4, MOV, AVI, WMV
- **Max Size:** 50MB per file

## Directory Structure

```
storage/app/public/
├── media/
│   ├── images/
│   │   └── {user_id}/
│   │       └── {random_filename}.jpg
│   └── videos/
│       └── {user_id}/
│           └── {random_filename}.mp4
```

## JavaScript Example

```javascript
// Upload media
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('privacy', 'public');
formData.append('description', 'My photo');
formData.append('tags[]', 'nature');

fetch('/api/media', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
  },
  body: formData
})
.then(response => response.json())
.then(data => console.log('Uploaded:', data));

// Create album
fetch('/api/albums', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'My Album',
    privacy: 'friends_only'
  })
})
.then(response => response.json())
.then(data => console.log('Album created:', data));
```

## Common Tasks

### List My Media
```
GET /api/media
```

### List My Albums
```
GET /api/albums
```

### Get Specific Media
```
GET /api/media/{id}
```

### Update Media Privacy
```
PUT /api/media/{id}
{
  "privacy": "private"
}
```

### Delete Media
```
DELETE /api/media/{id}
```

### Delete Album (keeps media)
```
DELETE /api/albums/{id}
```

## Testing

Run tests to verify everything works:

```bash
php artisan test --filter MediaTest
php artisan test --filter AlbumTest
php artisan test --filter PostPrivacyTest
```

## Documentation

For detailed documentation, see:
- `docs/MEDIA_SHARING_FEATURE.md` - Complete API reference
- `MEDIA_IMPLEMENTATION_SUMMARY.md` - Technical details
- `DEPLOYMENT_GUIDE.md` - Production deployment

## Support

For issues or questions, check:
1. The comprehensive documentation
2. Test files for usage examples
3. Laravel error logs

---

**Version:** 1.0.0  
**Status:** Production Ready ✅
