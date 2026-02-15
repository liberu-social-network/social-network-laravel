<?php

namespace App\Http\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ShowProfile extends Component
{
    public $user;
    public $isOwnProfile = false;

    public function mount($userId = null)
    {
        if ($userId) {
            $this->user = User::with('profile')->findOrFail($userId);
        } else {
            $this->user = Auth::user()->load('profile');
        }
        
        $this->isOwnProfile = Auth::id() === $this->user->id;
        
        // Create profile if it doesn't exist
        if (!$this->user->profile) {
            $this->user->profile()->create([]);
            $this->user->load('profile');
        }
    }

    public function render()
    {
        return view('livewire.show-profile');
    }
}
