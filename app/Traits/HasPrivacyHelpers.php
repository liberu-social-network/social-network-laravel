<?php

namespace App\Traits;

use App\Models\User;

trait HasPrivacyHelpers
{
    /**
     * Check if a user's profile is visible to the current authenticated user.
     */
    public function canViewProfile(User $profileOwner, ?User $viewer = null): bool
    {
        $viewer = $viewer ?? auth()->user();
        $privacySettings = $profileOwner->privacySettings ?? $profileOwner->getPrivacySettings();

        return $privacySettings->isProfileVisibleTo($viewer);
    }

    /**
     * Check if a user's email should be visible to the current authenticated user.
     */
    public function canViewEmail(User $profileOwner, ?User $viewer = null): bool
    {
        $viewer = $viewer ?? auth()->user();
        $privacySettings = $profileOwner->privacySettings ?? $profileOwner->getPrivacySettings();

        return $privacySettings->shouldShowEmailTo($viewer);
    }

    /**
     * Check if a user's birth date should be visible to the current authenticated user.
     */
    public function canViewBirthDate(User $profileOwner, ?User $viewer = null): bool
    {
        $viewer = $viewer ?? auth()->user();
        $privacySettings = $profileOwner->privacySettings ?? $profileOwner->getPrivacySettings();

        return $privacySettings->shouldShowBirthDateTo($viewer);
    }

    /**
     * Check if a user's location should be visible to the current authenticated user.
     */
    public function canViewLocation(User $profileOwner, ?User $viewer = null): bool
    {
        $viewer = $viewer ?? auth()->user();
        $privacySettings = $profileOwner->privacySettings ?? $profileOwner->getPrivacySettings();

        return $privacySettings->shouldShowLocationTo($viewer);
    }

    /**
     * Check if a user's online status should be visible to the current authenticated user.
     */
    public function canViewOnlineStatus(User $profileOwner, ?User $viewer = null): bool
    {
        $viewer = $viewer ?? auth()->user();
        $privacySettings = $profileOwner->privacySettings ?? $profileOwner->getPrivacySettings();

        return $privacySettings->shouldShowOnlineStatusTo($viewer);
    }

    /**
     * Check if a user accepts friend requests.
     */
    public function acceptsFriendRequests(User $user): bool
    {
        $privacySettings = $user->privacySettings ?? $user->getPrivacySettings();

        return $privacySettings->allow_friend_requests;
    }

    /**
     * Check if a user accepts messages from non-friends.
     */
    public function acceptsMessagesFromNonFriends(User $user, ?User $sender = null): bool
    {
        $sender = $sender ?? auth()->user();
        $privacySettings = $user->privacySettings ?? $user->getPrivacySettings();

        if (!$sender || $user->id === $sender->id) {
            return true;
        }

        if ($privacySettings->allow_messages_from_non_friends) {
            return true;
        }

        return $user->isFriendsWith($sender);
    }
}
