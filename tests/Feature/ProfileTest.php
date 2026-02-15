<?php

namespace Tests\Feature;

use App\Http\Livewire\EditProfile;
use App\Http\Livewire\ShowProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_profile_page(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'This is my bio',
        ]);

        $response = $this->actingAs($user)->get(route('user-profile.show'));

        $response->assertStatus(200);
        $response->assertSeeLivewire('show-profile');
    }

    public function test_user_can_view_another_users_profile(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create([
            'name' => 'Jane Doe',
            'bio' => 'Jane bio',
        ]);

        $response = $this->actingAs($user)->get(route('user-profile.view', ['userId' => $otherUser->id]));

        $response->assertStatus(200);
        $response->assertSeeLivewire('show-profile');
    }

    public function test_show_profile_component_displays_user_information(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'This is my bio',
        ]);

        Livewire::actingAs($user)
            ->test(ShowProfile::class)
            ->assertSee('John Doe')
            ->assertSee('john@example.com')
            ->assertSee('This is my bio');
    }

    public function test_show_profile_component_creates_profile_if_not_exists(): void
    {
        $user = User::factory()->create();
        
        $this->assertNull($user->profile);

        Livewire::actingAs($user)
            ->test(ShowProfile::class);

        $user->refresh();
        $this->assertNotNull($user->profile);
    }

    public function test_user_can_access_profile_edit_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('user-profile.edit'));

        $response->assertStatus(200);
        $response->assertSeeLivewire('edit-profile');
    }

    public function test_edit_profile_component_loads_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'Original bio',
        ]);
        
        $user->profile()->create([
            'location' => 'New York',
            'website' => 'https://example.com',
            'gender' => 'male',
        ]);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->assertSet('name', 'John Doe')
            ->assertSet('email', 'john@example.com')
            ->assertSet('bio', 'Original bio')
            ->assertSet('location', 'New York')
            ->assertSet('website', 'https://example.com')
            ->assertSet('gender', 'male');
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'bio' => 'Old bio',
        ]);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('name', 'Jane Doe')
            ->set('bio', 'New bio')
            ->set('location', 'San Francisco')
            ->set('website', 'https://newsite.com')
            ->set('gender', 'female')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user-profile.show'));

        $user->refresh();
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('New bio', $user->bio);
        $this->assertEquals('San Francisco', $user->profile->location);
        $this->assertEquals('https://newsite.com', $user->profile->website);
        $this->assertEquals('female', $user->profile->gender);
    }

    public function test_user_can_update_email(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('email', 'new@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('new@example.com', $user->email);
    }

    public function test_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');
        
        $user = User::factory()->create();

        $photo = UploadedFile::fake()->image('profile.jpg');

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('profile_photo', $photo)
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->profile_photo_path);
    }

    public function test_user_can_delete_profile_photo(): void
    {
        Storage::fake('public');
        
        $user = User::factory()->create();
        $user->updateProfilePhoto(UploadedFile::fake()->image('profile.jpg'));
        
        $this->assertNotNull($user->profile_photo_path);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->call('deleteProfilePhoto');

        $user->refresh();
        $this->assertNull($user->profile_photo_path);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_email_is_required(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('email', '')
            ->call('save')
            ->assertHasErrors(['email' => 'required']);
    }

    public function test_email_must_be_valid(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('email', 'invalid-email')
            ->call('save')
            ->assertHasErrors(['email' => 'email']);
    }

    public function test_website_must_be_valid_url(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('website', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['website' => 'url']);
    }

    public function test_bio_cannot_exceed_maximum_length(): void
    {
        $user = User::factory()->create();
        $longBio = str_repeat('a', 1001);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('bio', $longBio)
            ->call('save')
            ->assertHasErrors(['bio']);
    }

    public function test_profile_photo_must_be_image(): void
    {
        Storage::fake('public');
        
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('profile_photo', $file)
            ->call('save')
            ->assertHasErrors(['profile_photo' => 'image']);
    }

    public function test_profile_photo_cannot_exceed_maximum_size(): void
    {
        Storage::fake('public');
        
        $user = User::factory()->create();
        $largeFile = UploadedFile::fake()->image('profile.jpg')->size(2049); // 2049 KB

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('profile_photo', $largeFile)
            ->call('save')
            ->assertHasErrors(['profile_photo' => 'max']);
    }

    public function test_guest_cannot_access_profile_page(): void
    {
        $response = $this->get(route('user-profile.show'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_profile_edit_page(): void
    {
        $response = $this->get(route('user-profile.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_profile_changes_are_persisted(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('name', 'Updated Name')
            ->set('bio', 'Updated bio')
            ->set('location', 'Los Angeles')
            ->call('save');

        // Test that changes are visible in ShowProfile component
        Livewire::actingAs($user)
            ->test(ShowProfile::class)
            ->assertSee('Updated Name')
            ->assertSee('Updated bio')
            ->assertSee('Los Angeles');
    }

    public function test_user_can_set_birth_date(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('birth_date', '1990-01-15')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('1990-01-15', $user->profile->birth_date->format('Y-m-d'));
    }

    public function test_birth_date_must_be_in_the_past(): void
    {
        $user = User::factory()->create();
        $futureDate = now()->addDay()->format('Y-m-d');

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('birth_date', $futureDate)
            ->call('save')
            ->assertHasErrors(['birth_date' => 'before']);
    }
}
