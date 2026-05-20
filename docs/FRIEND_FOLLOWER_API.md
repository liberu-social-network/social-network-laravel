# Friend Request and Follower System API Documentation

This document describes the API endpoints for the friend request and follower system in the Social Network Laravel application.

## Authentication

All API endpoints require authentication using Laravel Sanctum. Include the authentication token in the `Authorization` header:

```
Authorization: Bearer {your-token}
```

## Friend Request Endpoints

### Get Friend Requests and Friends List

Retrieves the authenticated user's friends, sent friend requests, and received friend requests.

**Endpoint:** `GET /api/friendships`

**Response:**
```json
{
  "friends": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "profile_photo_url": "https://..."
    }
  ],
  "sent_requests": [
    {
      "id": 2,
      "requester_id": 1,
      "addressee_id": 3,
      "addressee": {
        "id": 3,
        "name": "Jane Smith",
        "email": "jane@example.com"
      },
      "status": "pending",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "received_requests": [
    {
      "id": 3,
      "requester_id": 4,
      "addressee_id": 1,
      "requester": {
        "id": 4,
        "name": "Bob Johnson",
        "email": "bob@example.com"
      },
      "status": "pending",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

### Send Friend Request

Sends a friend request to another user.

**Endpoint:** `POST /api/friendships/send`

**Request Body:**
```json
{
  "user_id": 3
}
```

**Response (201 Created):**
```json
{
  "message": "Friend request sent successfully.",
  "friendship": {
    "id": 5,
    "requester_id": 1,
    "addressee_id": 3,
    "status": "pending",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "message": "Unable to send friend request."
}
```

### Accept Friend Request

Accepts a pending friend request from another user.

**Endpoint:** `POST /api/friendships/accept`

**Request Body:**
```json
{
  "user_id": 4
}
```

**Response (200 OK):**
```json
{
  "message": "Friend request accepted successfully.",
  "friendship": {
    "id": 3,
    "requester_id": 4,
    "addressee_id": 1,
    "status": "accepted",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "message": "Unable to accept friend request."
}
```

### Reject Friend Request

Rejects a pending friend request from another user.

**Endpoint:** `POST /api/friendships/reject`

**Request Body:**
```json
{
  "user_id": 4
}
```

**Response (200 OK):**
```json
{
  "message": "Friend request rejected successfully.",
  "friendship": {
    "id": 3,
    "requester_id": 4,
    "addressee_id": 1,
    "status": "declined",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "message": "Unable to reject friend request."
}
```

## Follower Endpoints

### Get Followers and Following

Retrieves the authenticated user's followers and users they are following.

**Endpoint:** `GET /api/followers`

**Response:**
```json
{
  "followers": [
    {
      "id": 2,
      "name": "Jane Smith",
      "email": "jane@example.com",
      "profile_photo_url": "https://..."
    }
  ],
  "following": [
    {
      "id": 3,
      "name": "Bob Johnson",
      "email": "bob@example.com",
      "profile_photo_url": "https://..."
    }
  ],
  "followers_count": 1,
  "following_count": 1
}
```

### Follow User

Follows another user.

**Endpoint:** `POST /api/followers/follow`

**Request Body:**
```json
{
  "user_id": 3
}
```

**Response (201 Created):**
```json
{
  "message": "Successfully followed user.",
  "follower": {
    "id": 1,
    "follower_id": 1,
    "following_id": 3,
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "message": "Unable to follow user."
}
```

### Unfollow User

Unfollows a user that is currently being followed.

**Endpoint:** `POST /api/followers/unfollow`

**Request Body:**
```json
{
  "user_id": 3
}
```

**Response (200 OK):**
```json
{
  "message": "Successfully unfollowed user."
}
```

**Error Response (400 Bad Request):**
```json
{
  "message": "Unable to unfollow user."
}
```

## User Search Endpoint

### Search Users

Searches for users by name or email address.

**Endpoint:** `GET /api/users/search`

**Query Parameters:**
- `query` (required): The search term (minimum 1 character)

**Example:** `/api/users/search?query=John`

**Response:**
```json
{
  "users": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "profile_photo_url": "https://...",
      "friends_count": 5,
      "followers_count": 10,
      "following_count": 8
    },
    {
      "id": 3,
      "name": "John Smith",
      "email": "john.smith@example.com",
      "profile_photo_url": "https://...",
      "friends_count": 3,
      "followers_count": 7,
      "following_count": 5
    }
  ]
}
```

**Note:** Results are limited to 20 users.

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "The query field is required.",
  "errors": {
    "query": ["The query field is required."]
  }
}
```

## User Model Helper Methods

The `User` model includes the following helper methods for friend and follower functionality:

### Friend Request Methods

- `sendFriendRequest(User $user)`: Sends a friend request to another user
- `acceptFriendRequest(User $user)`: Accepts a friend request from another user
- `rejectFriendRequest(User $user)`: Rejects a friend request from another user
- `hasFriendRequestPending(User $user)`: Checks if there's a pending friend request between users
- `isFriendWith(User $user)`: Checks if the user is friends with another user

### Follower Methods

- `follow(User $user)`: Follows another user
- `unfollow(User $user)`: Unfollows another user
- `isFollowing(User $user)`: Checks if the user is following another user
- `isFollowedBy(User $user)`: Checks if the user is being followed by another user

### Count Attributes

- `friends_count`: Returns the total number of friends
- `followers_count`: Returns the total number of followers
- `following_count`: Returns the total number of users being followed

## Error Handling

All endpoints may return the following error responses:

- **401 Unauthorized**: Missing or invalid authentication token
- **404 Not Found**: User not found
- **422 Unprocessable Entity**: Validation error
- **500 Internal Server Error**: Server error

## Rate Limiting

API endpoints are subject to Laravel's default rate limiting. Please ensure your application handles rate limit responses appropriately.
