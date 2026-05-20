<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPrivacySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPrivacySetting>
 */
class UserPrivacySettingFactory extends Factory
{
    protected $model = UserPrivacySetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'profile_visibility' => 'public',
            'show_email' => false,
            'show_birth_date' => true,
            'show_location' => true,
            'allow_friend_requests' => true,
            'allow_messages_from_non_friends' => true,
            'show_online_status' => true,
        ];
    }

    /**
     * Indicate that the privacy settings should be private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_visibility' => 'private',
            'show_email' => false,
            'show_birth_date' => false,
            'show_location' => false,
            'allow_friend_requests' => false,
            'allow_messages_from_non_friends' => false,
            'show_online_status' => false,
        ]);
    }

    /**
     * Indicate that the privacy settings should be for friends only.
     */
    public function friendsOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_visibility' => 'friends_only',
        ]);
    }
}
