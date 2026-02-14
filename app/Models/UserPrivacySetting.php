<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPrivacySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_visibility',
        'show_email',
        'show_birth_date',
        'show_location',
        'allow_friend_requests',
        'allow_messages_from_non_friends',
        'show_online_status',
    ];

    protected $casts = [
        'show_email' => 'boolean',
        'show_birth_date' => 'boolean',
        'show_location' => 'boolean',
        'allow_friend_requests' => 'boolean',
        'allow_messages_from_non_friends' => 'boolean',
        'show_online_status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the profile is visible to the given user.
     */
    public function isProfileVisibleTo(?User $viewer): bool
    {
        if (!$viewer) {
            return $this->profile_visibility === 'public';
        }

        if ($this->user_id === $viewer->id) {
            return true;
        }

        switch ($this->profile_visibility) {
            case 'public':
                return true;
            case 'friends_only':
                return $this->user->isFriendsWith($viewer);
            case 'private':
                return false;
            default:
                return false;
        }
    }

    /**
     * Check if email should be visible to the given user.
     */
    public function shouldShowEmailTo(?User $viewer): bool
    {
        if (!$viewer) {
            return false;
        }

        if ($this->user_id === $viewer->id) {
            return true;
        }

        return $this->show_email && $this->isProfileVisibleTo($viewer);
    }

    /**
     * Check if birth date should be visible to the given user.
     */
    public function shouldShowBirthDateTo(?User $viewer): bool
    {
        if (!$viewer || !$this->isProfileVisibleTo($viewer)) {
            return false;
        }

        if ($this->user_id === $viewer->id) {
            return true;
        }

        return $this->show_birth_date;
    }

    /**
     * Check if location should be visible to the given user.
     */
    public function shouldShowLocationTo(?User $viewer): bool
    {
        if (!$viewer || !$this->isProfileVisibleTo($viewer)) {
            return false;
        }

        if ($this->user_id === $viewer->id) {
            return true;
        }

        return $this->show_location;
    }

    /**
     * Check if online status should be visible to the given user.
     */
    public function shouldShowOnlineStatusTo(?User $viewer): bool
    {
        if (!$viewer || !$this->isProfileVisibleTo($viewer)) {
            return false;
        }

        if ($this->user_id === $viewer->id) {
            return true;
        }

        return $this->show_online_status;
    }
}
