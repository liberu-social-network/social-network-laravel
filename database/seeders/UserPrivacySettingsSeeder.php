<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPrivacySetting;
use Illuminate\Database\Seeder;

class UserPrivacySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Create default privacy settings for all users who don't have them yet.
     */
    public function run(): void
    {
        $usersWithoutPrivacySettings = User::doesntHave('privacySettings')->get();

        foreach ($usersWithoutPrivacySettings as $user) {
            UserPrivacySetting::create([
                'user_id' => $user->id,
            ]);
        }

        $this->command->info('Privacy settings created for ' . $usersWithoutPrivacySettings->count() . ' users.');
    }
}
