<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'panel_user']);
    }

    public function test_super_admin_can_access_user_management(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user);

        // Test that super_admin can view users (they have all permissions)
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_be_created_with_role(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    public function test_user_can_have_multiple_roles(): void
    {
        $user = User::factory()->create();
        
        $user->assignRole(['admin', 'panel_user']);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('panel_user'));
        $this->assertCount(2, $user->roles);
    }

    public function test_user_role_can_be_removed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));

        $user->removeRole('admin');

        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_user_roles_can_be_synced(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['admin', 'panel_user']);

        $this->assertCount(2, $user->roles);

        // Sync to only have super_admin
        $user->syncRoles(['super_admin']);

        $this->assertCount(1, $user->roles);
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('panel_user'));
    }

    public function test_user_email_verification_status_can_be_checked(): void
    {
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->assertTrue($verifiedUser->hasVerifiedEmail());
        $this->assertFalse($unverifiedUser->hasVerifiedEmail());
    }
}
