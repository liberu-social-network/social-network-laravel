<?php

namespace App\Listeners;

use App\Models\UserPrivacySetting;
use Illuminate\Auth\Events\Registered;

class CreateUserPrivacySettings
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Create default privacy settings for the new user
        if (!$user->privacySettings) {
            UserPrivacySetting::create([
                'user_id' => $user->id,
                'profile_visibility' => 'public',
                'show_email' => false,
                'show_birth_date' => true,
                'show_location' => true,
                'allow_friend_requests' => true,
                'allow_messages_from_non_friends' => true,
                'show_online_status' => true,
            ]);
        }
    }
}
