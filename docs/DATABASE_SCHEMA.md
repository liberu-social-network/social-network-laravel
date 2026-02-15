# Database Schema Documentation

## Overview

This document provides a comprehensive overview of the database schema for the Liberu Social Network Laravel application. The schema is designed to support a full-featured social networking platform with user management, posts, comments, likes, friendships, and messaging capabilities.

## Schema Design Principles

- **Normalization**: The schema follows database normalization best practices to minimize redundancy and ensure data integrity.
- **Referential Integrity**: All foreign keys are properly constrained with cascading rules to maintain data consistency.
- **Scalability**: The schema is designed to handle growth efficiently with proper indexing on foreign keys.
- **Laravel Conventions**: All tables follow Laravel naming conventions and are designed to work seamlessly with Eloquent ORM.

## Tables

### 1. Users Table

The core table for user authentication and basic profile information.

**Table Name**: `users`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| name | varchar(255) | NOT NULL | User's full name |
| email | varchar(255) | UNIQUE, NOT NULL | User's email address |
| email_verified_at | timestamp | NULLABLE | Email verification timestamp |
| password | varchar(255) | NULLABLE | Encrypted password (nullable for OAuth users) |
| remember_token | varchar(100) | NULLABLE | Token for "remember me" functionality |
| current_team_id | bigint unsigned | NULLABLE | Current team reference (Jetstream) |
| profile_photo_path | varchar(2048) | NULLABLE | Path to user's profile photo |
| bio | text | NULLABLE | User biography/description |
| two_factor_secret | text | NULLABLE | Two-factor authentication secret |
| two_factor_recovery_codes | text | NULLABLE | Two-factor recovery codes |
| created_at | timestamp | NOT NULL | Record creation timestamp |
| updated_at | timestamp | NOT NULL | Record update timestamp |

**Indexes**:
- Primary key on `id`
- Unique index on `email`

**Relationships**:
- Has one `Profile`
- Has many `Posts`
- Has many `Comments`
- Has many `Likes`
- Has many `Messages` (as sender)
- Has many `Messages` (as receiver)
- Has many `Friendships` (as requester)
- Has many `Friendships` (as addressee)

---

### 2. Profiles Table

Extended user profile information separated for normalization.

**Table Name**: `profiles`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT | Unique profile identifier |
| user_id | bigint unsigned | FOREIGN KEY, NOT NULL | Reference to users.id |
| gender | varchar(255) | NULLABLE | User's gender |
| birth_date | date | NULLABLE | User's date of birth |
| location | varchar(255) | NULLABLE | User's location/city |
| website | varchar(255) | NULLABLE | User's personal website URL |
| created_at | timestamp | NOT NULL | Record creation timestamp |
| updated_at | timestamp | NOT NULL | Record update timestamp |

**Indexes**:
- Primary key on `id`
- Foreign key index on `user_id`

**Foreign Keys**:
- `user_id` REFERENCES `users(id)` ON DELETE CASCADE

**Relationships**:
- Belongs to one `User`

---

### 3. Posts Table

User-generated content posts.

**Table Name**: `posts`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT | Unique post identifier |
| user_id | bigint unsigned | FOREIGN KEY, NOT NULL | Reference to users.id (post author) |
| content | text | NOT NULL | Post text content |
| image_url | varchar(255) | NULLABLE | URL to attached image |
| video_url | varchar(255) | NULLABLE | URL to attached video |
| created_at | timestamp | NOT NULL | Record creation timestamp |
| updated_at | timestamp | NOT NULL | Record update timestamp |

**Indexes**:
- Primary key on `id`
- Foreign key index on `user_id`
- Recommended: Index on `created_at` for chronological queries

**Foreign Keys**:
- `user_id` REFERENCES `users(id)` ON DELETE CASCADE

**Relationships**:
- Belongs to one `User`
- Has many `Comments`
- Has many `Likes`
- Has many `Shares`

---

### 4. Comments Table

Comments on posts.

**Table Name**: `comments`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT | Unique comment identifier |
| post_id | bigint unsigned | FOREIGN KEY, NOT NULL | Reference to posts.id |
| user_id | bigint unsigned | FOREIGN KEY, NOT NULL | Reference to users.id (commenter) |
| content | text | NOT NULL | Comment text content |
| created_at | timestamp | NOT NULL | Record creation timestamp |
| updated_at | timestamp | NOT NULL | Record update timestamp |

**Indexes**:
- Primary key on `id`
- Foreign key index on `post_id`
- Foreign key index on `user_id`

**Foreign Keys**:
- `post_id` REFERENCES `posts(id)` ON DELETE CASCADE
- `user_id` REFERENCES `users(id)` ON DELETE CASCADE

**Relationships**:
- Belongs to one `Post`
- Belongs to one `User`

---

### 5. Likes Table

User likes on posts.

**Table Name**: `likes`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT | Unique like identifier |
| post_id | bigint unsigned | FOREIGN KEY, NOT NULL | Reference to posts.id |
| user_id | bigint unsigned | FOREIGN KEY, NOT NULL | Reference to users.id |
| created_at | timestamp | NOT NULL | Record creation timestamp |
| updated_at | timestamp | NOT NULL | Record update timestamp |

**Indexes**:
- Primary key on `id`
- Foreign key index on `post_id`
- Foreign key index on `user_id`
- Recommended: Unique compound index on (`post_id`, `user_id`) to prevent duplicate likes

**Foreign Keys**:
- `post_id` REFERENCES `posts(id)` ON DELETE CASCADE
- `user_id` REFERENCES `users(id)` ON DELETE CASCADE

**Relationships**:
- Belongs to one `Post`
- Belongs to one `User`

---

### 6. Friendships Table

Friend connections between users.

**Table Name**: `friendships`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT | Unique friendship identifier |
| requester_id | bigint unsigned | FOREIGN KEY, NOT NULL | Reference to users.id (who sent request) |
| addressee_id | bigint unsigned | FOREIGN KEY, NOT NULL | Reference to users.id (who received request) |
| status | enum | NOT NULL, DEFAULT 'pending' | Status: pending, accepted, declined |
| created_at | timestamp | NOT NULL | Record creation timestamp |
| updated_at | timestamp | NOT NULL | Record update timestamp |

**Indexes**:
- Primary key on `id`
- Foreign key index on `requester_id`
- Foreign key index on `addressee_id`
- Recommended: Compound index on (`requester_id`, `addressee_id`, `status`)

**Foreign Keys**:
- `requester_id` REFERENCES `users(id)` ON DELETE CASCADE
- `addressee_id` REFERENCES `users(id)` ON DELETE CASCADE

**Relationships**:
- Belongs to one `User` (as requester)
- Belongs to one `User` (as addressee)

**Business Logic**:
- Status values: `pending`, `accepted`, `declined`
- Default status is `pending`
- Once a friendship is `accepted`, both users are considered friends

---

### 7. Messages Table

Direct messages between users.

**Table Name**: `messages`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT | Unique message identifier |
| sender_id | bigint unsigned | FOREIGN KEY, NOT NULL | Reference to users.id (sender) |
| receiver_id | bigint unsigned | FOREIGN KEY, NOT NULL | Reference to users.id (receiver) |
| conversation_id | bigint unsigned | FOREIGN KEY, NULLABLE | Reference to conversations.id |
| content | text | NOT NULL | Message text content |
| read_at | timestamp | NULLABLE | Timestamp when message was read |
| deleted_at | timestamp | NULLABLE | Soft delete timestamp |
| is_encrypted | boolean | DEFAULT false | Whether message is encrypted |
| created_at | timestamp | NOT NULL | Record creation timestamp |
| updated_at | timestamp | NOT NULL | Record update timestamp |

**Indexes**:
- Primary key on `id`
- Foreign key index on `sender_id`
- Foreign key index on `receiver_id`
- Foreign key index on `conversation_id`
- Recommended: Index on `created_at` for chronological queries

**Foreign Keys**:
- `sender_id` REFERENCES `users(id)` ON DELETE CASCADE
- `receiver_id` REFERENCES `users(id)` ON DELETE CASCADE
- `conversation_id` REFERENCES `conversations(id)` ON DELETE CASCADE

**Relationships**:
- Belongs to one `User` (as sender)
- Belongs to one `User` (as receiver)
- Belongs to one `Conversation`
- Has many `MessageReactions`
- Has many `MessageAttachments`

---

### Additional Tables

The schema also includes several supporting tables for enhanced functionality:

#### Conversations Table
Groups messages into conversation threads.

#### Message Reactions Table
Emoji reactions to messages.

#### Message Attachments Table
File attachments for messages.

#### Shares Table
Post sharing functionality.

#### Activities Table
Activity feed tracking user actions.

#### Teams Table
Jetstream team functionality.

#### Sessions Table
User session management.

#### Password Reset Tokens Table
Password reset functionality.

---

## Relationships Diagram

```
users
  |
  +-- profiles (1:1)
  |
  +-- posts (1:N)
  |     |
  |     +-- comments (1:N)
  |     |
  |     +-- likes (1:N)
  |     |
  |     +-- shares (1:N)
  |
  +-- comments (1:N)
  |
  +-- likes (1:N)
  |
  +-- friendships (as requester) (1:N)
  |
  +-- friendships (as addressee) (1:N)
  |
  +-- messages (as sender) (1:N)
  |
  +-- messages (as receiver) (1:N)
  |
  +-- conversations (N:N through conversation_participants)
```

---

## Migration Files

All schema changes are managed through Laravel migrations located in `/database/migrations/`:

1. `0001_01_01_000000_create_users_table.php` - Creates users, sessions, and password reset tokens tables
2. `2024_06_16_050419_create_profiles_table.php` - Creates profiles table
3. `2024_06_16_062256_create_posts_table.php` - Creates posts table
4. `2024_06_16_063032_create_comments_table.php` - Creates comments table
5. `2024_06_16_064613_create_likes_table.php` - Creates likes table
6. `2024_06_16_065210_create_friendships_table.php` - Creates friendships table
7. `2024_06_16_065805_create_messages_table.php` - Creates messages table
8. `2026_02_15_210000_add_bio_to_users_table.php` - Adds bio field to users table

Additional migrations exist for conversation support, message reactions, video support, and other features.

---

## Running Migrations

To create all database tables, run:

```bash
php artisan migrate
```

To rollback all migrations:

```bash
php artisan migrate:rollback
```

To refresh the database (drop all tables and re-run migrations):

```bash
php artisan migrate:fresh
```

To seed the database with sample data:

```bash
php artisan db:seed
```

Or combine migration and seeding:

```bash
php artisan migrate:fresh --seed
```

---

## Seeders

The application includes comprehensive seeders for testing:

### DatabaseSeeder
Main seeder that orchestrates all other seeders. Located at `/database/seeders/DatabaseSeeder.php`.

### SocialNetworkSeeder
Creates sample data for testing the social network functionality:
- 10 sample users
- Multiple posts per user (text, images, and videos)
- Random comments on posts
- Random likes on posts
- Random shares

Usage:
```bash
php artisan db:seed --class=SocialNetworkSeeder
```

### Other Seeders
- `ShieldSeeder` - Role and permission setup
- `MenuSeeder` - Navigation menu setup
- `RolesSeeder` - User roles
- `SiteSettingsSeeder` - Application settings

---

## Factories

Laravel factories are available for all models to generate fake data for testing. Located in `/database/factories/`:

- `UserFactory` - Generate users with realistic data
- `ProfileFactory` - Generate user profiles
- `PostFactory` - Generate posts with optional images/videos
- `CommentFactory` - Generate comments
- `LikeFactory` - Generate likes
- `FriendshipFactory` - Generate friend relationships
- `MessageFactory` - Generate messages

Example usage:

```php
use App\Models\User;
use App\Models\Post;

// Create 10 users
$users = User::factory(10)->create();

// Create a user with a specific email
$user = User::factory()->create([
    'email' => 'test@example.com',
]);

// Create posts for a user
Post::factory(5)->create(['user_id' => $user->id]);
```

---

## Best Practices

### Data Integrity
- All foreign keys use `onDelete('cascade')` to maintain referential integrity
- Unique constraints on email addresses prevent duplicates
- Nullable fields are explicitly marked to avoid null constraint violations

### Performance
- Foreign keys automatically create indexes for efficient joins
- Consider adding indexes on frequently queried columns (e.g., `created_at` for chronological sorting)
- Use eager loading in Eloquent to avoid N+1 query problems

### Security
- Passwords are encrypted using Laravel's built-in hashing
- Two-factor authentication support is built into the users table
- Message encryption support is available
- Soft deletes on messages allow for data retention policies

### Scalability
- The schema is designed to handle millions of records
- Proper indexing ensures query performance at scale
- Consider implementing database partitioning for very large datasets
- Use caching strategies (Redis/Memcached) for frequently accessed data

---

## Design Decisions

### Separate Profiles Table
The `profiles` table is separated from `users` to follow the Single Responsibility Principle and improve performance. Authentication-related data stays in `users`, while extended profile information is in `profiles`.

### Bio Field Placement
The `bio` field is placed in the `users` table rather than `profiles` because it's considered core profile information that's frequently accessed alongside user data. The alternative `profile_photo_path` follows the same pattern.

### Enum for Friendship Status
Using an enum for friendship status (`pending`, `accepted`, `declined`) ensures data consistency and prevents invalid status values.

### Soft Deletes on Messages
Messages use soft deletes to allow users to "delete" messages while retaining them for compliance or recovery purposes.

### Conversation Support
Messages are grouped into conversations to support threaded messaging and group chats.

---

## Future Considerations

Potential schema enhancements for future versions:

1. **Notifications Table** - Dedicated table for user notifications
2. **Groups/Communities** - Support for user groups and communities
3. **Post Categories/Tags** - Categorization system for posts
4. **Media Library** - Centralized media management
5. **Privacy Settings** - Granular privacy controls per user
6. **Blocking System** - User blocking functionality
7. **Report System** - Content reporting and moderation
8. **Analytics Tables** - User engagement and analytics tracking

---

## References

- [Laravel Migrations Documentation](https://laravel.com/docs/11.x/migrations)
- [Laravel Eloquent Relationships](https://laravel.com/docs/11.x/eloquent-relationships)
- [Database Design Best Practices](https://www.databasejournal.com/features/mssql/article.php/3849881/Database-Design-Best-Practices.htm)
- [Laravel Seeding Documentation](https://laravel.com/docs/11.x/seeding)
- [Laravel Factories Documentation](https://laravel.com/docs/11.x/eloquent-factories)

---

## Support

For questions or issues related to the database schema, please:
1. Check the migration files in `/database/migrations/`
2. Review the model relationships in `/app/Models/`
3. Consult the Laravel documentation
4. Open an issue on the GitHub repository

---

**Last Updated**: February 15, 2026  
**Schema Version**: 1.0  
**Laravel Version**: 11.x
