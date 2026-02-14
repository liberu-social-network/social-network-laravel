<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use App\Models\UserPrivacySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_settings_are_created_with_defaults_for_new_user(): void
    {
        $user = User::factory()->create();

        $privacySettings = $user->getPrivacySettings();

        $this->assertEquals('public', $privacySettings->profile_visibility);
        $this->assertFalse($privacySettings->show_email);
        $this->assertTrue($privacySettings->show_birth_date);
        $this->assertTrue($privacySettings->show_location);
        $this->assertTrue($privacySettings->allow_friend_requests);
        $this->assertTrue($privacySettings->allow_messages_from_non_friends);
        $this->assertTrue($privacySettings->show_online_status);
    }

    public function test_user_can_update_privacy_settings(): void
    {
        $user = User::factory()->create();
        $privacySettings = $user->getPrivacySettings();

        $privacySettings->update([
            'profile_visibility' => 'private',
            'show_email' => true,
            'show_birth_date' => false,
            'show_location' => false,
            'allow_friend_requests' => false,
            'allow_messages_from_non_friends' => false,
            'show_online_status' => false,
        ]);

        $refreshed = $privacySettings->fresh();
        $this->assertEquals('private', $refreshed->profile_visibility);
        $this->assertTrue($refreshed->show_email);
        $this->assertFalse($refreshed->show_birth_date);
        $this->assertFalse($refreshed->show_location);
        $this->assertFalse($refreshed->allow_friend_requests);
        $this->assertFalse($refreshed->allow_messages_from_non_friends);
        $this->assertFalse($refreshed->show_online_status);
    }

    public function test_public_profile_is_visible_to_everyone(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $privacySettings = UserPrivacySetting::factory()->create([
            'user_id' => $owner->id,
            'profile_visibility' => 'public',
        ]);

        $this->assertTrue($privacySettings->isProfileVisibleTo($viewer));
        $this->assertTrue($privacySettings->isProfileVisibleTo(null));
    }

    public function test_friends_only_profile_is_visible_to_friends(): void
    {
        $owner = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();

        Friendship::create([
            'requester_id' => $owner->id,
            'addressee_id' => $friend->id,
            'status' => 'accepted',
        ]);

        $privacySettings = UserPrivacySetting::factory()->friendsOnly()->create([
            'user_id' => $owner->id,
        ]);

        $this->assertTrue($privacySettings->isProfileVisibleTo($friend));
        $this->assertFalse($privacySettings->isProfileVisibleTo($stranger));
        $this->assertFalse($privacySettings->isProfileVisibleTo(null));
    }

    public function test_private_profile_is_only_visible_to_owner(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $privacySettings = UserPrivacySetting::factory()->private()->create([
            'user_id' => $owner->id,
        ]);

        $this->assertTrue($privacySettings->isProfileVisibleTo($owner));
        $this->assertFalse($privacySettings->isProfileVisibleTo($viewer));
        $this->assertFalse($privacySettings->isProfileVisibleTo(null));
    }

    public function test_email_visibility_respects_privacy_settings(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $privacySettings = UserPrivacySetting::factory()->create([
            'user_id' => $owner->id,
            'show_email' => true,
        ]);

        $this->assertTrue($privacySettings->shouldShowEmailTo($owner));
        $this->assertTrue($privacySettings->shouldShowEmailTo($viewer));

        $privacySettings->update(['show_email' => false]);
        $this->assertTrue($privacySettings->shouldShowEmailTo($owner));
        $this->assertFalse($privacySettings->shouldShowEmailTo($viewer));
    }

    public function test_birth_date_visibility_respects_privacy_settings(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $privacySettings = UserPrivacySetting::factory()->create([
            'user_id' => $owner->id,
            'show_birth_date' => true,
        ]);

        $this->assertTrue($privacySettings->shouldShowBirthDateTo($viewer));

        $privacySettings->update(['show_birth_date' => false]);
        $this->assertFalse($privacySettings->shouldShowBirthDateTo($viewer));
        $this->assertTrue($privacySettings->shouldShowBirthDateTo($owner));
    }

    public function test_location_visibility_respects_privacy_settings(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $privacySettings = UserPrivacySetting::factory()->create([
            'user_id' => $owner->id,
            'show_location' => true,
        ]);

        $this->assertTrue($privacySettings->shouldShowLocationTo($viewer));

        $privacySettings->update(['show_location' => false]);
        $this->assertFalse($privacySettings->shouldShowLocationTo($viewer));
        $this->assertTrue($privacySettings->shouldShowLocationTo($owner));
    }

    public function test_online_status_visibility_respects_privacy_settings(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $privacySettings = UserPrivacySetting::factory()->create([
            'user_id' => $owner->id,
            'show_online_status' => true,
        ]);

        $this->assertTrue($privacySettings->shouldShowOnlineStatusTo($viewer));

        $privacySettings->update(['show_online_status' => false]);
        $this->assertFalse($privacySettings->shouldShowOnlineStatusTo($viewer));
        $this->assertTrue($privacySettings->shouldShowOnlineStatusTo($owner));
    }

    public function test_user_is_friends_with_method(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Friendship::create([
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
            'status' => 'accepted',
        ]);

        $this->assertTrue($user1->isFriendsWith($user2));
        $this->assertTrue($user2->isFriendsWith($user1));
        $this->assertFalse($user1->isFriendsWith($user3));
        $this->assertFalse($user3->isFriendsWith($user1));
    }

    public function test_privacy_settings_are_not_visible_when_profile_is_hidden(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $privacySettings = UserPrivacySetting::factory()->create([
            'user_id' => $owner->id,
            'profile_visibility' => 'private',
            'show_birth_date' => true,
            'show_location' => true,
            'show_online_status' => true,
        ]);

        $this->assertFalse($privacySettings->shouldShowBirthDateTo($viewer));
        $this->assertFalse($privacySettings->shouldShowLocationTo($viewer));
        $this->assertFalse($privacySettings->shouldShowOnlineStatusTo($viewer));
    }

    public function test_get_privacy_settings_creates_settings_if_not_exists(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->privacySettings);

        $privacySettings = $user->getPrivacySettings();

        $this->assertNotNull($privacySettings);
        $this->assertInstanceOf(UserPrivacySetting::class, $privacySettings);
        $this->assertEquals($user->id, $privacySettings->user_id);
    }

    public function test_privacy_settings_are_created_on_user_registration(): void
    {
        // Simulate user registration
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        
        // Privacy settings should be automatically created
        $this->assertNotNull($user->privacySettings);
        $this->assertEquals('public', $user->privacySettings->profile_visibility);
    }
}
