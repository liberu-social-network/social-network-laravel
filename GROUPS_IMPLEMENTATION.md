# Groups and Communities Feature - Implementation Guide

## Overview
This document describes the implementation of the Groups and Communities feature for the Laravel Social Network application. This feature enables users to create and join groups based on shared interests, with support for public and private groups, member management, and group-specific content.

## Acceptance Criteria Met ✅

### ✅ Users can create, join, and leave groups
- Users can create public or private groups
- Users can join public groups instantly
- Users must request to join private groups (requires admin approval)
- Members can leave groups (except group owners)

### ✅ Group admins can manage members and content
- Admins can approve/reject membership requests
- Admins can remove members from the group
- Owners can assign roles (admin, moderator, member)
- Admins can moderate posts (update/delete any post in the group)

### ✅ Group-specific posts and discussions are visible to members
- Members can create posts within groups
- Posts in private groups are only visible to members
- Posts in public groups are visible to everyone
- Group posts support text, images, and videos

### ✅ Users can search for and discover relevant groups
- Search groups by name or description
- Get personalized group suggestions
- View popular groups by member count
- Filter by privacy level (public/private)

## Database Schema

### `groups` Table
```sql
- id: bigint (primary key)
- name: string (required)
- description: text (nullable)
- image_url: string (nullable)
- user_id: bigint (foreign key to users, owner)
- privacy: enum('public', 'private') (default: 'public')
- is_active: boolean (default: true)
- created_at: timestamp
- updated_at: timestamp
```

### `group_members` Table
```sql
- id: bigint (primary key)
- group_id: bigint (foreign key to groups)
- user_id: bigint (foreign key to users)
- role: enum('admin', 'moderator', 'member') (default: 'member')
- status: enum('pending', 'approved', 'rejected') (default: 'approved')
- created_at: timestamp
- updated_at: timestamp
- UNIQUE constraint on (group_id, user_id)
```

### `posts` Table Update
```sql
- Added: group_id: bigint (nullable, foreign key to groups)
```

## API Endpoints

### Group Management

#### `GET /api/groups`
List all groups (with optional filters)
- Query params: `privacy`, `my_groups`
- Returns: Paginated list of groups with member/post counts

#### `POST /api/groups`
Create a new group
- Body: `name`, `description`, `privacy`, `image`
- Returns: Created group with creator as admin

#### `GET /api/groups/{id}`
Get group details
- Returns: Group info with membership status for current user

#### `PUT /api/groups/{id}`
Update group (admin/owner only)
- Body: `name`, `description`, `privacy`, `image`
- Returns: Updated group

#### `DELETE /api/groups/{id}`
Delete group (owner only)
- Returns: Success message

### Membership Management

#### `POST /api/groups/{groupId}/join`
Join a group
- Public groups: Auto-approved
- Private groups: Pending approval
- Returns: Membership status

#### `POST /api/groups/{groupId}/leave`
Leave a group
- Cannot leave if you're the owner
- Returns: Success message

#### `GET /api/groups/{id}/members`
List group members
- Returns: List of approved members with roles

#### `GET /api/groups/{id}/pending-members`
List pending members (admin only)
- Returns: List of pending membership requests

#### `POST /api/groups/{groupId}/members/{userId}/approve`
Approve membership request (admin only)
- Returns: Success message

#### `POST /api/groups/{groupId}/members/{userId}/reject`
Reject membership request (admin only)
- Returns: Success message

#### `DELETE /api/groups/{groupId}/members/{userId}`
Remove a member (admin only)
- Cannot remove owner
- Returns: Success message

#### `PUT /api/groups/{groupId}/members/{userId}/role`
Update member role (owner only)
- Body: `role` (admin, moderator, member)
- Cannot change owner's role
- Returns: Updated membership

### Group Posts

#### `GET /api/groups/{groupId}/posts`
List posts in a group
- Returns: Paginated posts with like/comment counts

#### `POST /api/groups/{groupId}/posts`
Create a post in a group (members only)
- Body: `content`, `image`, `video`
- Returns: Created post

#### `PUT /api/groups/{groupId}/posts/{postId}`
Update a post (author or admin)
- Body: `content`
- Returns: Updated post

#### `DELETE /api/groups/{groupId}/posts/{postId}`
Delete a post (author or admin)
- Returns: Success message

### Discovery

#### `GET /api/groups/search/query?query={search}`
Search for groups
- Query params: `query` (required), `privacy`
- Returns: Matching groups

#### `GET /api/groups/search/suggestions`
Get suggested groups
- Returns: Public groups user is not a member of

#### `GET /api/groups/search/popular`
Get popular groups
- Returns: Top groups by member count

## Models

### Group Model
**Location:** `app/Models/Group.php`

**Key Relationships:**
- `owner()` - BelongsTo User (group creator)
- `members()` - BelongsToMany User (approved members)
- `pendingMembers()` - BelongsToMany User (pending members)
- `admins()` - BelongsToMany User (admin members)
- `posts()` - HasMany Post

**Key Methods:**
- `hasMember(User $user): bool` - Check if user is a member
- `isAdmin(User $user): bool` - Check if user is an admin
- `isOwner(User $user): bool` - Check if user is the owner
- `getMembersCountAttribute(): int` - Get member count
- `getPostsCountAttribute(): int` - Get post count

### User Model Updates
**Location:** `app/Models/User.php`

**New Relationships:**
- `ownedGroups()` - HasMany Group
- `groups()` - BelongsToMany Group (approved memberships)

**New Methods:**
- `isMemberOf(Group $group): bool`
- `isAdminOf(Group $group): bool`

### Post Model Updates
**Location:** `app/Models/Post.php`

**New Fields:**
- `group_id` (nullable foreign key)

**New Relationships:**
- `group()` - BelongsTo Group

## Authorization

### GroupPolicy
**Location:** `app/Policies/GroupPolicy.php`

**Methods:**
- `viewAny(User $user)` - Always true
- `view(User $user, Group $group)` - True for public groups or members of private groups
- `create(User $user)` - Always true
- `update(User $user, Group $group)` - True for admins/owners
- `delete(User $user, Group $group)` - True for owner only
- `manageMembers(User $user, Group $group)` - True for admins/owners
- `createPost(User $user, Group $group)` - True for members

## Testing

### Test Coverage
Total: **57 tests** across 4 test suites

#### GroupTest.php (15 tests)
- Group creation (public, private, with image)
- Viewing groups (public, private, permissions)
- Updating groups (owner, admin, member permissions)
- Deleting groups (owner only)
- Validation (name, privacy required)
- Guest access restrictions

#### GroupMembershipTest.php (15 tests)
- Joining groups (public auto-approval, private pending)
- Leaving groups (owner cannot leave)
- Approving/rejecting members (admin only)
- Removing members (admin, cannot remove owner)
- Updating member roles (owner only)
- Duplicate membership prevention

#### GroupPostTest.php (16 tests)
- Creating posts in groups (members only)
- Creating posts with media (images)
- Viewing posts (public/private access)
- Updating posts (author or admin)
- Deleting posts (author or admin)
- Non-member restrictions
- Validation

#### GroupSearchTest.php (11 tests)
- Searching by name/description
- Privacy filtering in search
- Group suggestions
- Popular groups
- Membership status in results
- Validation

### Running Tests

```bash
# Run all group-related tests
php artisan test --filter Group

# Run specific test suites
php artisan test tests/Feature/GroupTest.php
php artisan test tests/Feature/GroupMembershipTest.php
php artisan test tests/Feature/GroupPostTest.php
php artisan test tests/Feature/GroupSearchTest.php
```

## Usage Examples

### Creating a Group
```javascript
POST /api/groups
{
    "name": "Laravel Developers",
    "description": "A community for Laravel enthusiasts",
    "privacy": "public"
}
```

### Joining a Public Group
```javascript
POST /api/groups/1/join
// Response: { "message": "Successfully joined the group", "status": "approved" }
```

### Posting in a Group
```javascript
POST /api/groups/1/posts
{
    "content": "Check out this awesome Laravel feature!"
}
```

### Searching for Groups
```javascript
GET /api/groups/search/query?query=Laravel
// Returns groups matching "Laravel" in name or description
```

## Security Considerations

### Implemented Security Measures
1. **Authentication**: All endpoints require authentication via Sanctum
2. **Authorization**: Comprehensive policies enforce permissions
3. **Privacy Controls**: Private groups enforce member-only access
4. **Ownership Protection**: Owners cannot be removed, groups can only be deleted by owners
5. **Input Validation**: All inputs are validated
6. **File Upload Security**: Image uploads validated for type and size
7. **SQL Injection Prevention**: Eloquent ORM used throughout
8. **Mass Assignment Protection**: Only fillable fields allowed

### Best Practices Applied
- Role-based access control (owner, admin, moderator, member)
- Soft permission checks before operations
- Cascading deletes properly configured
- Unique constraints on membership
- Status tracking for membership requests

## Migration Instructions

### Running Migrations
```bash
php artisan migrate
```

This will create:
1. `groups` table
2. `group_members` table
3. Add `group_id` column to `posts` table

### Rolling Back
```bash
php artisan migrate:rollback --step=3
```

## Future Enhancements

Potential improvements for future iterations:

1. **Events & Notifications**
   - Notify users when they're added to a group
   - Notify admins of pending membership requests
   - Notify members of new posts

2. **Group Categories**
   - Categorize groups for better discovery
   - Tag-based filtering

3. **Advanced Moderation**
   - Reported content handling
   - Ban/mute users from groups
   - Auto-moderation rules

4. **Analytics**
   - Group activity metrics
   - Member engagement tracking
   - Growth statistics

5. **Group Settings**
   - Allow/disallow member invitations
   - Post approval requirements
   - Visibility settings for group content

6. **Rich Features**
   - Group events/calendar
   - Pinned posts
   - Group files/resources
   - Polls within groups

## Troubleshooting

### Common Issues

**Issue**: Route conflicts with group ID
**Solution**: Ensure search routes are defined before `/{id}` routes in `routes/api.php`

**Issue**: Cannot join private group
**Solution**: Private groups require admin approval. Check `group_members` table for pending status.

**Issue**: Posts not showing in group
**Solution**: Verify user is an approved member of the group.

**Issue**: Cannot upload group image
**Solution**: Ensure storage link is created: `php artisan storage:link`

## Support

For issues or questions about this feature:
- Check the test files for usage examples
- Review the API documentation in this file
- Check Laravel logs for error details
- Ensure migrations have been run

## Changelog

### Version 1.0.0 (2026-02-15)
- Initial implementation of Groups and Communities feature
- Full CRUD operations for groups
- Membership management system
- Group-specific posts
- Search and discovery features
- Comprehensive test coverage (57 tests)
- Authorization policies
- Documentation
