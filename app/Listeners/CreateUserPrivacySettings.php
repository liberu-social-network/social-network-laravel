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
            ]);
        }
    }
}
