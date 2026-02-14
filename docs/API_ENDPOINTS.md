# Social Network API Endpoints

This document describes the API endpoints available for post creation and interactions in the Liberu Social Network.

## Authentication

All endpoints require authentication using Laravel Sanctum. Include the bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

## Posts

### Create Post
```
POST /api/posts
```

**Request Body:**
```json
{
  "content": "Your post content",
  "image": "file (optional)",
  "video": "file (optional)"
}
```

**Response:** `201 Created`
```json
{
  "id": 1,
  "user_id": 1,
  "content": "Your post content",
  "image_url": "posts/images/example.jpg",
  "video_url": null,
  "media_type": "image",
  "created_at": "2024-01-01T00:00:00.000000Z",
  "user": {...}
}
```

### Get Post
```
GET /api/posts/{id}
```

**Response:** `200 OK`

### Update Post
```
PUT /api/posts/{id}
```

**Request Body:**
```json
{
  "content": "Updated content"
}
```

**Response:** `200 OK`

### Delete Post
```
DELETE /api/posts/{id}
```

**Response:** `200 OK`
```json
{
  "message": "Post deleted successfully"
}
```

### List All Posts
```
GET /api/posts
```

**Response:** `200 OK` (Paginated)

## Comments

### Add Comment to Post
```
POST /api/posts/{postId}/comments
```

**Request Body:**
```json
{
  "content": "Your comment"
}
```

**Response:** `201 Created`

### Get Comments on Post
```
GET /api/posts/{postId}/comments
```

**Response:** `200 OK` (Paginated)

### Update Comment
```
PUT /api/comments/{commentId}
```

**Request Body:**
```json
{
  "content": "Updated comment"
}
```

**Response:** `200 OK`

### Delete Comment
```
DELETE /api/comments/{commentId}
```

**Response:** `200 OK`

## Likes

### Toggle Like on Post
```
POST /api/posts/{postId}/like
```

**Response:** `200 OK`
```json
{
  "liked": true,
  "likes_count": 5
}
```

### Get Likes on Post
```
GET /api/posts/{postId}/likes
```

**Response:** `200 OK` (Paginated)

## Shares

### Toggle Share on Post
```
POST /api/posts/{postId}/share
```

**Response:** `200 OK`
```json
{
  "shared": true,
  "shares_count": 3
}
```

### Get Shares on Post
```
GET /api/posts/{postId}/shares
```

**Response:** `200 OK` (Paginated)

## Feed

### Get News Feed
```
GET /api/feed
```

Returns posts from the authenticated user and their friends, ordered by creation date.

**Response:** `200 OK` (Paginated)
```json
{
  "data": [
    {
      "id": 1,
      "content": "Post content",
      "user": {...},
      "likes_count": 5,
      "comments_count": 3,
      "shares_count": 2,
      "is_liked": true,
      "is_shared": false
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### Get User Timeline
```
GET /api/timeline/{userId}
```

Returns posts from a specific user.

**Response:** `200 OK` (Paginated)

## Real-time Broadcasting

The following events are broadcast in real-time using Laravel Broadcasting:

### Post Interactions

**Channel:** `post.{postId}`

**Events:**
- `post.liked` - When a user likes a post
- `post.unliked` - When a user unlikes a post
- `post.shared` - When a user shares a post
- `post.unshared` - When a user unshares a post
- `comment.created` - When a comment is added

**Event Data Example:**
```json
{
  "post_id": 1,
  "user": {
    "id": 1,
    "name": "John Doe"
  },
  "likes_count": 6
}
```

## Media Upload Limits

- **Images:** Max 10MB (JPEG, PNG, GIF)
- **Videos:** Max 50MB (MP4, MOV, AVI, WMV)

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "error": "Unauthorized"
}
```

### 404 Not Found
```json
{
  "message": "No query results for model..."
}
```

### 422 Validation Error
```json
{
  "errors": {
    "content": [
      "The content field is required."
    ]
  }
}
```
