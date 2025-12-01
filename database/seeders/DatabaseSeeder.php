<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->withPersonalTeam()->create();
        $this->call([
            // SiteSettingsSeeder::class,
            // PermissionsSeeder::class,
            MenuSeeder::class,
            RolesSeeder::class,
        ]);

        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make("password"),
        ]);
        $user->assignRole('admin');
    }
}
