# Enhanced Private Messaging System Documentation

## Overview
The enhanced private messaging system provides comprehensive messaging capabilities including direct messages, group conversations, file attachments, reactions, typing indicators, real-time broadcasting, and optional message encryption.

## Features

### Core Features
- **Direct Messaging**: One-on-one private conversations
- **Group Conversations**: Multi-participant chat rooms
- **File Attachments**: Send images, documents, and other files (up to 5 files, 10MB each)
- **Message Reactions**: React to messages with emojis
- **Typing Indicators**: See when users are typing in real-time
- **Real-time Broadcasting**: Instant message delivery via WebSockets
- **Message Encryption**: Optional end-to-end encryption for sensitive messages
- **Read Receipts**: Track when messages are read
- **Soft Delete**: Privacy-preserving message deletion

### User Features
- Send messages in direct or group conversations
- Upload and share files with messages
- React to messages with emoji reactions
- See typing indicators from other users
- View conversation history
- Search conversations by user name
- Automatic read marking
- Delete messages securely

### Admin Features
- View all messages and conversations
- Filter by read/unread status
- Manage soft-deleted messages
- Full CRUD operations
- Search functionality

## Architecture

### Database Schema

#### Conversations Table
```sql
- id: Primary key
- name: Conversation name (required for groups, nullable for direct)
- type: enum('direct', 'group')
- created_by: Foreign key to users (conversation creator)
- created_at, updated_at, deleted_at
```

#### Conversation Participants Table
```sql
- id: Primary key
- conversation_id: Foreign key to conversations
- user_id: Foreign key to users
- joined_at: When user joined
- left_at: When user left (nullable)
- last_read_at: Last message read timestamp
- Unique constraint on (conversation_id, user_id)
```

#### Messages Table
```sql
- id: Primary key
- conversation_id: Foreign key to conversations (nullable for direct messages)
- sender_id: Foreign key to users
- receiver_id: Foreign key to users (nullable for group messages)
- content: Plain text message content
- encrypted_content: Encrypted message content (nullable)
- encryption_key_id: Key used for encryption (nullable)
- read_at: When message was read (nullable)
- created_at, updated_at, deleted_at
```

#### Message Attachments Table
```sql
- id: Primary key
- message_id: Foreign key to messages
- filename: Stored filename
- original_filename: Original upload filename
- mime_type: File MIME type
- size: File size in bytes
- path: Storage path
- created_at, updated_at
```

#### Message Reactions Table
```sql
- id: Primary key
- message_id: Foreign key to messages
- user_id: Foreign key to users
- emoji: Reaction emoji (max 10 chars)
- Unique constraint on (message_id, user_id, emoji)
- created_at, updated_at
```

### Models

#### Conversation Model
**Relationships:**
- `participants()`: BelongsToMany User
- `activeParticipants()`: Participants with left_at = null
- `messages()`: HasMany Message
- `creator()`: BelongsTo User
- `latestMessage()`: Latest message in conversation

**Methods:**
- `isGroupConversation()`: Check if group type
- `isDirectConversation()`: Check if direct type
- `addParticipant(User $user)`: Add user to conversation
- `removeParticipant(User $user)`: Mark user as left

#### Message Model
**Relationships:**
- `sender()`: BelongsTo User
- `receiver()`: BelongsTo User (nullable for group messages)
- `conversation()`: BelongsTo Conversation
- `attachments()`: HasMany MessageAttachment
- `reactions()`: HasMany MessageReaction

**Methods:**
- `markAsRead()`: Mark message as read
- `isRead()`: Check if message is read
- `encrypt(string $content)`: Encrypt message content
- `getDecryptedContentAttribute()`: Get decrypted content
- `addReaction(User $user, string $emoji)`: Add/update reaction
- `removeReaction(User $user, string $emoji)`: Remove reaction
- `hasAttachments()`: Check if message has attachments

**Scopes:**
- `unread()`: Filter unread messages
- `betweenUsers($userId1, $userId2)`: Messages between two users

#### MessageAttachment Model
**Methods:**
- `isImage()`: Check if image type
- `isVideo()`: Check if video type
- `isAudio()`: Check if audio type
- `getHumanReadableSizeAttribute()`: Format size (e.g., "2.5 MB")
- `getUrlAttribute()`: Get storage URL

#### MessageReaction Model
**Relationships:**
- `message()`: BelongsTo Message
- `user()`: BelongsTo User

### Controllers

#### MessageController
**Endpoints:**
- `GET /api/messages` - List user's messages
- `POST /api/messages` - Send new message (with optional attachments & encryption)
- `GET /api/messages/{message}` - View message details
- `DELETE /api/messages/{message}` - Delete message
- `GET /api/messages/conversation/{user}` - Get direct conversation
- `GET /api/messages/unread-count` - Get unread count
- `GET /api/conversations/{conversation}/messages` - Get conversation messages
- `POST /api/messages/{message}/reactions` - Add reaction
- `DELETE /api/messages/{message}/reactions/{emoji}` - Remove reaction
- `POST /api/messages/typing` - Broadcast typing indicator

#### ConversationController
**Endpoints:**
- `GET /api/conversations` - List user's conversations
- `POST /api/conversations` - Create new conversation
- `GET /api/conversations/{conversation}` - View conversation
- `POST /api/conversations/{conversation}/participants` - Add participants (group only)
- `DELETE /api/conversations/{conversation}/participants/{user}` - Remove participant

### Broadcasting Events

#### MessageSent Event
Broadcasts when a message is sent.
- **Channels**: `user.{userId}` (direct) or `conversation.{conversationId}` (group)
- **Data**: Message with sender, attachments, and reactions

#### UserTyping Event
Broadcasts when a user starts typing.
- **Channels**: `user.{receiverId}` (direct) or `conversation.{conversationId}` (group)
- **Data**: User info and conversation/receiver ID

#### ReactionAdded Event
Broadcasts when a reaction is added to a message.
- **Channels**: Same as message channels
- **Data**: Reaction with user info

### Authorization Channels

#### user.{userId}
Private channel for direct messages. Authorized if:
- Authenticated user ID matches channel user ID

#### conversation.{conversationId}
Presence channel for group conversations. Authorized if:
- User is an active participant (left_at is null)
- Returns user profile data for presence

## API Reference

### Send Message
```http
POST /api/messages
Content-Type: multipart/form-data
Authorization: Bearer {token}

{
  "receiver_id": 2,  // For direct messages (optional if conversation_id provided)
  "conversation_id": 1,  // For group messages (optional if receiver_id provided)
  "content": "Hello! Check out these files.",
  "encrypted": false,  // Optional, default false
  "attachments[]": [file1, file2]  // Optional, max 5 files, 10MB each
}
```

**Response (201):**
```json
{
  "id": 1,
  "sender_id": 1,
  "receiver_id": 2,
  "conversation_id": null,
  "content": "Hello! Check out these files.",
  "encrypted_content": null,
  "read_at": null,
  "created_at": "2024-06-16T10:30:00.000000Z",
  "sender": {
    "id": 1,
    "name": "John Doe",
    "profile_photo_url": "..."
  },
  "attachments": [
    {
      "id": 1,
      "filename": "uuid.jpg",
      "original_filename": "photo.jpg",
      "mime_type": "image/jpeg",
      "size": 102400,
      "path": "message-attachments/uuid.jpg"
    }
  ],
  "reactions": []
}
```

### Create Conversation
```http
POST /api/conversations
Content-Type: application/json
Authorization: Bearer {token}

{
  "type": "group",
  "name": "Team Chat",
  "participant_ids": [2, 3, 4]
}
```

**Response (201):**
```json
{
  "id": 1,
  "name": "Team Chat",
  "type": "group",
  "created_by": 1,
  "participants": [
    {"id": 1, "name": "John Doe", "pivot": {"joined_at": "..."}},
    {"id": 2, "name": "Jane Smith", "pivot": {"joined_at": "..."}},
    ...
  ]
}
```

### Add Reaction
```http
POST /api/messages/{messageId}/reactions
Content-Type: application/json
Authorization: Bearer {token}

{
  "emoji": "👍"
}
```

**Response (201):**
```json
{
  "id": 1,
  "message_id": 123,
  "user_id": 1,
  "emoji": "👍",
  "user": {
    "id": 1,
    "name": "John Doe"
  }
}
```

### Broadcast Typing Indicator
```http
POST /api/messages/typing
Content-Type: application/json
Authorization: Bearer {token}

{
  "receiver_id": 2  // For direct messages
  // OR
  "conversation_id": 1  // For group conversations
}
```

**Response (200):**
```json
{
  "message": "Typing event broadcasted"
}
```

## Security Features

### Authorization
- **MessagePolicy**: Ensures users can only access their own messages
- **ConversationPolicy**: Users must be participants to access conversations
- All API routes protected by Sanctum authentication

### Validation
- Message content: max 5000 characters
- Attachments: max 5 files, 10MB each
- Participant IDs must exist in users table
- Users cannot send messages to themselves

### Privacy
- Soft deletes preserve privacy
- Messages only visible to participants
- Attachments deleted from storage when message is deleted
- Optional encryption for sensitive content

### Encryption
Messages can be encrypted using Laravel's Crypt facade:
```php
$message->encrypt('Secret content');
// Automatically decrypted via decrypted_content accessor
```

## Frontend Integration

### Livewire Event Listeners
```javascript
// Listen for new messages
Echo.private(`user.${userId}`)
    .listen('.message.sent', (e) => {
        // Handle new message
        console.log('New message:', e);
    });

// Listen for typing indicators
Echo.private(`user.${userId}`)
    .listen('.user.typing', (e) => {
        // Show typing indicator
        console.log(`${e.user_name} is typing...`);
    });

// Listen for reactions
Echo.private(`user.${userId}`)
    .listen('.reaction.added', (e) => {
        // Update reaction UI
        console.log('Reaction added:', e.emoji);
    });
```

### Group Conversation Presence
```javascript
Echo.join(`conversation.${conversationId}`)
    .here((users) => {
        // Users currently in conversation
        console.log('Online users:', users);
    })
    .joining((user) => {
        // User joined
        console.log('User joined:', user.name);
    })
    .leaving((user) => {
        // User left
        console.log('User left:', user.name);
    })
    .listen('.message.sent', (e) => {
        // New message in conversation
    });
```

## Testing

### Running Tests
```bash
# Run all messaging tests
php artisan test --filter=Message

# Run specific test suites
php artisan test --filter=ConversationTest
php artisan test --filter=MessageAttachmentTest
php artisan test --filter=MessageReactionTest
php artisan test --filter=MessageEncryptionTest
```

### Test Coverage
- **36 comprehensive tests** covering:
  - Direct and group conversations
  - File attachments
  - Emoji reactions
  - Message encryption
  - Authorization
  - Validation

## Usage Examples

### Creating a Group Chat
```php
$conversation = Conversation::create([
    'type' => 'group',
    'name' => 'Project Team',
    'created_by' => auth()->id(),
]);

$conversation->addParticipant(auth()->user());
$conversation->addParticipant($user1);
$conversation->addParticipant($user2);
```

### Sending a Message with Attachments
```javascript
const formData = new FormData();
formData.append('conversation_id', conversationId);
formData.append('content', 'Check out these files!');
formData.append('attachments[]', file1);
formData.append('attachments[]', file2);

fetch('/api/messages', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
    },
    body: formData
});
```

### Adding a Reaction
```javascript
fetch(`/api/messages/${messageId}/reactions`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify({ emoji: '👍' })
});
```

## Troubleshooting

### File Uploads Not Working
- Ensure `storage/app/public` is linked: `php artisan storage:link`
- Check file permissions on storage directory
- Verify max upload size in `php.ini`

### Broadcasting Not Working
- Configure broadcasting driver in `.env`
- Run `php artisan queue:work` for queue-based broadcasting
- Check WebSocket connection in browser console

### Encryption Errors
- Ensure `APP_KEY` is set in `.env`
- Run `php artisan key:generate` if needed
- Verify encryption is working: test with a simple encrypted message

## Future Enhancements

Potential improvements:
- Message search functionality
- Voice/video messages
- Message forwarding
- Pin important messages
- Message scheduling
- Disappearing messages
- Broadcast channels (public conversations)
- Message templates
- Advanced file preview
- Integration with third-party storage (S3, etc.)
