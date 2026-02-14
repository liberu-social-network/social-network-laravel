# Private Messaging System Documentation

## Overview
The private messaging system allows users to send direct messages to each other securely within the application. Messages are stored with read tracking and soft delete capabilities for privacy and security.

## Features

### User Features
- Send private messages to other users
- View conversation history with any user
- Real-time unread message badges
- Search conversations by user name
- Mark messages as read automatically when viewed
- Delete messages (soft delete for privacy)
- Read receipts (double checkmark when message is read)

### Admin Features
- View all messages in the system
- Filter messages by read/unread status
- Filter soft-deleted messages
- Full CRUD operations on messages
- Search messages by sender, receiver, or content

## Architecture

### Models

#### Message Model (`app/Models/Message.php`)
- **Attributes:**
  - `id`: Primary key
  - `sender_id`: Foreign key to users table
  - `receiver_id`: Foreign key to users table
  - `content`: Message text content (max 5000 characters)
  - `read_at`: Timestamp when message was read (nullable)
  - `created_at`: Message creation timestamp
  - `updated_at`: Message update timestamp
  - `deleted_at`: Soft delete timestamp (nullable)

- **Relationships:**
  - `sender()`: BelongsTo User
  - `receiver()`: BelongsTo User

- **Scopes:**
  - `unread()`: Filters unread messages
  - `betweenUsers($userId1, $userId2)`: Gets messages between two users

- **Methods:**
  - `markAsRead()`: Marks message as read
  - `isRead()`: Checks if message has been read

### Controllers

#### MessageController (`app/Http/Controllers/MessageController.php`)
API endpoints for messaging functionality:

- `GET /api/messages`: List all messages for authenticated user
- `POST /api/messages`: Send a new message
- `GET /api/messages/{message}`: View a specific message
- `DELETE /api/messages/{message}`: Delete a message (soft delete)
- `GET /api/messages/conversation/{user}`: View conversation with a specific user
- `GET /api/messages/unread-count`: Get count of unread messages

### Policies

#### MessagePolicy (`app/Policies/MessagePolicy.php`)
Authorization rules:
- Users can view messages they sent or received
- Only senders can update their messages
- Both senders and receivers can delete messages
- Users cannot view/modify messages they're not part of

### Livewire Components

#### Messages Component (`app/Http/Livewire/Messages.php`)
Interactive messaging interface with:
- Conversation list sidebar
- Message thread view
- Real-time message sending
- Automatic read marking
- Search functionality

### Filament Resources

#### MessageResource (`app/Filament/Admin/Resources/MessageResource.php`)
Admin panel resource for message management:
- List view with filters
- Create new messages
- Edit existing messages
- View message details
- Soft delete support

#### MessagesPage (`app/Filament/App/Pages/MessagesPage.php`)
User-facing Filament page:
- Navigation menu entry with unread badge
- Embeds Livewire Messages component

## API Endpoints

### Authentication
All API endpoints require authentication via Laravel Sanctum.
Include the bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

### Send Message
```http
POST /api/messages
Content-Type: application/json

{
  "receiver_id": 2,
  "content": "Hello! This is a test message."
}
```

**Response (201):**
```json
{
  "id": 1,
  "sender_id": 1,
  "receiver_id": 2,
  "content": "Hello! This is a test message.",
  "read_at": null,
  "created_at": "2024-06-16T10:30:00.000000Z",
  "updated_at": "2024-06-16T10:30:00.000000Z",
  "sender": {
    "id": 1,
    "name": "John Doe"
  },
  "receiver": {
    "id": 2,
    "name": "Jane Smith"
  }
}
```

### List Messages
```http
GET /api/messages
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "sender_id": 1,
      "receiver_id": 2,
      "content": "Hello!",
      "read_at": "2024-06-16T10:35:00.000000Z",
      "created_at": "2024-06-16T10:30:00.000000Z"
    }
  ],
  "links": {},
  "meta": {}
}
```

### Get Conversation
```http
GET /api/messages/conversation/{userId}
```

**Response (200):** Array of messages between authenticated user and specified user

### View Message
```http
GET /api/messages/{messageId}
```

**Response (200):** Single message object with sender and receiver details

### Delete Message
```http
DELETE /api/messages/{messageId}
```

**Response (200):**
```json
{
  "message": "Message deleted successfully"
}
```

### Get Unread Count
```http
GET /api/messages/unread-count
```

**Response (200):**
```json
{
  "unread_count": 5
}
```

## Security Features

### Authorization
- MessagePolicy ensures users can only access their own messages
- API routes protected by Sanctum authentication
- Controller methods use `authorize()` for policy enforcement

### Validation
- Message content is required and limited to 5000 characters
- Receiver must exist in users table
- Users cannot send messages to themselves

### Privacy
- Soft deletes allow users to delete messages without permanently removing them
- Messages are only visible to sender and receiver
- Read receipts only visible to message sender

### Data Integrity
- Foreign key constraints on sender_id and receiver_id
- Cascade delete when user is deleted
- Timestamps track message history

## Database Schema

### Messages Table
```sql
CREATE TABLE messages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sender_id BIGINT UNSIGNED NOT NULL,
    receiver_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Testing

### Feature Tests (`tests/Feature/MessagingTest.php`)
Comprehensive test suite covering:
- Message sending and receiving
- Authorization checks
- Validation rules
- Read tracking
- Message deletion
- Conversation retrieval
- Unread count

### Policy Tests (`tests/Feature/MessagePolicyTest.php`)
Authorization test suite covering:
- View permissions
- Update permissions
- Delete permissions
- Unauthorized access prevention

Run tests:
```bash
php artisan test --filter=Messaging
php artisan test --filter=MessagePolicy
```

## Usage Examples

### Accessing Messages in Filament
1. Login to the application
2. Navigate to "Messages" in the sidebar
3. The unread count badge will show pending messages
4. Click on a user to view the conversation
5. Type and send messages in the message input area

### Using the API
```javascript
// Send a message
fetch('/api/messages', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
        receiver_id: 2,
        content: 'Hello!'
    })
});

// Get unread count
fetch('/api/messages/unread-count', {
    headers: {
        'Authorization': 'Bearer ' + token
    }
})
.then(res => res.json())
.then(data => console.log('Unread:', data.unread_count));
```

## Troubleshooting

### Messages not appearing
- Ensure migrations have been run: `php artisan migrate`
- Check that users exist in the database
- Verify API authentication token is valid

### Read receipts not working
- Ensure the receiver has viewed the message
- Check that the `read_at` column exists in messages table
- Verify message is loaded with sender/receiver relationships

### Permission errors
- Verify MessagePolicy is registered
- Check that user is either sender or receiver of message
- Ensure proper authentication middleware is applied

## Future Enhancements

Potential improvements:
- Real-time messaging with WebSockets/Pusher
- Message attachments (images, files)
- Group messaging
- Message reactions/emojis
- Typing indicators
- Message search
- Message threading/replies
- Notification system integration
- Block/unblock users
- Message encryption
