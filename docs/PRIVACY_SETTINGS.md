# Privacy Settings Documentation

## Overview

This feature provides users with comprehensive control over their profile visibility, personal information, and communication preferences on the social network platform.

## Privacy Controls

### Profile Visibility

Users can set their profile visibility to one of three levels:

1. **Public**: Profile is visible to everyone, including non-authenticated users
2. **Friends Only**: Profile is only visible to accepted friends
3. **Private**: Profile is only visible to the user themselves

### Personal Information Display

Users can individually control the visibility of:

- **Email Address**: Toggle to show/hide email on profile
- **Birth Date**: Toggle to show/hide birth date on profile
- **Location**: Toggle to show/hide location on profile

### Communication Preferences

Users can control how others interact with them:

- **Allow Friend Requests**: Toggle to enable/disable incoming friend requests
- **Allow Messages from Non-Friends**: Toggle to allow/prevent messages from users who aren't friends
- **Show Online Status**: Toggle to show/hide when the user is online

## Database Schema

### Table: `user_privacy_settings`

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| id | bigint | - | Primary key |
| user_id | bigint | - | Foreign key to users table |
| profile_visibility | enum | 'public' | Profile visibility level (public, friends_only, private) |
| show_email | boolean | false | Whether to show email on profile |
| show_birth_date | boolean | true | Whether to show birth date on profile |
| show_location | boolean | true | Whether to show location on profile |
| allow_friend_requests | boolean | true | Whether to accept friend requests |
| allow_messages_from_non_friends | boolean | true | Whether to accept messages from non-friends |
| show_online_status | boolean | true | Whether to show online status |
| created_at | timestamp | - | Record creation timestamp |
| updated_at | timestamp | - | Record update timestamp |

## Usage

### In Code

#### Check if a profile is visible to a user:

```php
$privacySettings = $user->privacySettings;
$isVisible = $privacySettings->isProfileVisibleTo($viewer);
```

#### Check if specific information should be displayed:

```php
// Email visibility
if ($user->privacySettings->shouldShowEmailTo($viewer)) {
    echo $user->email;
}

// Birth date visibility
if ($user->privacySettings->shouldShowBirthDateTo($viewer)) {
    echo $user->profile->birth_date;
}

// Location visibility
if ($user->privacySettings->shouldShowLocationTo($viewer)) {
    echo $user->profile->location;
}

// Online status
if ($user->privacySettings->shouldShowOnlineStatusTo($viewer)) {
    // Display online indicator
}
```

#### Get or create privacy settings for a user:

```php
$privacySettings = $user->getPrivacySettings();
```

### In Views

When displaying user information in Blade templates:

```blade
@if($user->privacySettings->shouldShowEmailTo(auth()->user()))
    <div>Email: {{ $user->email }}</div>
@endif

@if($user->privacySettings->shouldShowBirthDateTo(auth()->user()))
    <div>Birth Date: {{ $user->profile->birth_date }}</div>
@endif
```

## Accessing the Privacy Settings Page

Users can access their privacy settings through:

1. Navigate to the Filament app panel
2. Look for "Privacy Settings" in the "Account" navigation group
3. Update settings as desired
4. Click "Save Privacy Settings" to persist changes

## Default Settings

When a user account is created, default privacy settings are:

- Profile Visibility: **Public**
- Show Email: **False** (Hidden)
- Show Birth Date: **True** (Visible)
- Show Location: **True** (Visible)
- Allow Friend Requests: **True** (Enabled)
- Allow Messages from Non-Friends: **True** (Enabled)
- Show Online Status: **True** (Visible)

## Migration

To create the privacy settings table, run:

```bash
php artisan migrate
```

To create privacy settings for existing users:

```bash
php artisan db:seed --class=UserPrivacySettingsSeeder
```

## Testing

Comprehensive tests are available in `tests/Feature/PrivacySettingsTest.php` covering:

- Default privacy settings creation
- Updating privacy settings
- Profile visibility rules (public, friends only, private)
- Information visibility based on settings
- Friend relationship checking
- Privacy enforcement when profile is hidden

Run tests with:

```bash
php artisan test --filter=PrivacySettingsTest
```

## Security Considerations

1. **Default Privacy**: Email addresses are hidden by default to protect user privacy
2. **Profile Privacy**: Private profiles cannot expose any information to non-owners
3. **Friend Verification**: Friends-only visibility properly checks friendship status
4. **Null Safety**: All visibility methods handle unauthenticated (null) viewers appropriately
5. **Owner Override**: Users can always see their own information regardless of privacy settings

## Future Enhancements

Potential future improvements could include:

- Granular control over individual post visibility
- Privacy settings for photo albums
- Custom friend lists (e.g., "Close Friends", "Acquaintances")
- Privacy settings for tagged content
- Block list management
- Privacy audit log
