<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditProfile extends Component
{
    use WithFileUploads;

    public $name;
    public $email;
    public $bio;
    public $location;
    public $website;
    public $gender;
    public $birth_date;
    public $profile_photo;
    public $currentPhotoUrl;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'bio' => 'nullable|string|max:1000',
        'location' => 'nullable|string|max:255',
        'website' => 'nullable|url|max:255',
        'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
        'birth_date' => 'nullable|date|before:today',
        'profile_photo' => 'nullable|image|max:2048',
    ];

    public function mount()
    {
        $user = Auth::user()->load('profile');
        
        // Create profile if it doesn't exist
        if (!$user->profile) {
            $user->profile()->create([]);
            $user->load('profile');
        }

        $this->name = $user->name;
        $this->email = $user->email;
        $this->bio = $user->bio;
        $this->location = $user->profile->location;
        $this->website = $user->profile->website;
        $this->gender = $user->profile->gender;
        $this->birth_date = $user->profile->birth_date?->format('Y-m-d');
        $this->currentPhotoUrl = $user->profile_photo_url;
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();

        // Update user data
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'bio' => $this->bio,
        ]);

        // Update profile data
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'location' => $this->location,
                'website' => $this->website,
                'gender' => $this->gender,
                'birth_date' => $this->birth_date,
            ]
        );

        // Handle profile photo upload
        if ($this->profile_photo) {
            $user->updateProfilePhoto($this->profile_photo);
            $this->currentPhotoUrl = $user->fresh()->profile_photo_url;
            $this->profile_photo = null;
        }

        session()->flash('message', 'Profile updated successfully!');
        
        return redirect()->route('user-profile.show');
    }

    public function deleteProfilePhoto()
    {
        $user = Auth::user();
        $user->deleteProfilePhoto();
        $this->currentPhotoUrl = $user->fresh()->profile_photo_url;
        session()->flash('message', 'Profile photo deleted successfully!');
    }

    public function render()
    {
        return view('livewire.edit-profile');
    }
}
