<div class="bg-white rounded-lg shadow-md p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Profile</h2>
        <p class="text-gray-600 mt-1">Update your profile information and settings</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <form wire:submit.prevent="save">
        {{-- Profile Photo --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Profile Photo</label>
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    @if ($profile_photo)
                        <img src="{{ $profile_photo->temporaryUrl() }}" 
                             alt="New profile photo" 
                             class="w-24 h-24 rounded-full object-cover">
                    @else
                        <img src="{{ $currentPhotoUrl }}" 
                             alt="Current profile photo" 
                             class="w-24 h-24 rounded-full object-cover">
                    @endif
                </div>
                <div>
                    <input type="file" wire:model="profile_photo" accept="image/*" class="hidden" id="profile_photo">
                    <label for="profile_photo" class="cursor-pointer inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        Select New Photo
                    </label>
                    @if($currentPhotoUrl && !str_contains($currentPhotoUrl, 'ui-avatars.com'))
                        <button type="button" wire:click="deleteProfilePhoto" class="ml-2 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring focus:ring-red-300 disabled:opacity-25 transition">
                            Remove Photo
                        </button>
                    @endif
                    @error('profile_photo') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="profile_photo" class="text-sm text-gray-600 mt-1">Uploading...</div>
                </div>
            </div>
        </div>

        {{-- Name --}}
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
            <input type="text" wire:model="name" id="name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            @error('name') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Email --}}
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input type="email" wire:model="email" id="email" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            @error('email') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Bio --}}
        <div class="mb-4">
            <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
            <textarea wire:model="bio" id="bio" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Tell us about yourself..."></textarea>
            @error('bio') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
            <p class="text-sm text-gray-500 mt-1">Maximum 1000 characters</p>
        </div>

        {{-- Location --}}
        <div class="mb-4">
            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Location</label>
            <input type="text" wire:model="location" id="location" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="City, Country">
            @error('location') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Website --}}
        <div class="mb-4">
            <label for="website" class="block text-sm font-medium text-gray-700 mb-2">Website</label>
            <input type="url" wire:model="website" id="website" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="https://example.com">
            @error('website') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Gender --}}
        <div class="mb-4">
            <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
            <select wire:model="gender" id="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
                <option value="prefer_not_to_say">Prefer not to say</option>
            </select>
            @error('gender') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Birth Date --}}
        <div class="mb-6">
            <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-2">Birth Date</label>
            <input type="date" wire:model="birth_date" id="birth_date" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            @error('birth_date') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end space-x-3 border-t border-gray-200 pt-4">
            <a href="{{ route('user-profile.show') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-25 transition">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition">
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>
</div>
