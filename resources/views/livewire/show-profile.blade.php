<div class="bg-white rounded-lg shadow-md overflow-hidden">
    {{-- Profile Header --}}
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-24 sm:h-32"></div>
    
    <div class="px-4 sm:px-6 pb-6">
        <div class="flex flex-col sm:flex-row items-center sm:items-end -mt-16 mb-4">
            {{-- Profile Photo --}}
            <div class="flex-shrink-0 mb-4 sm:mb-0">
                <img src="{{ $user->profile_photo_url }}" 
                     alt="{{ $user->name }}" 
                     class="w-32 h-32 rounded-full border-4 border-white shadow-lg object-cover">
            </div>
            
            {{-- User Info --}}
            <div class="sm:ml-6 text-center sm:text-left flex-1">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
                <p class="text-sm sm:text-base text-gray-600">{{ $user->email }}</p>
            </div>
            
            {{-- Edit Button --}}
            @if($isOwnProfile)
                <div class="mt-4 sm:mt-0">
                    <a href="{{ route('user-profile.edit') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition">
                        Edit Profile
                    </a>
                </div>
            @endif
        </div>

        {{-- Bio Section --}}
        @if($user->bio)
            <div class="mt-6 border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">About</h2>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $user->bio }}</p>
            </div>
        @endif

        {{-- Profile Details --}}
        @if($user->profile && ($user->profile->location || $user->profile->website || $user->profile->gender || $user->profile->birth_date))
            <div class="mt-6 border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if($user->profile->location)
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="text-gray-700">{{ $user->profile->location }}</span>
                        </div>
                    @endif

                    @if($user->profile->website)
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                            <a href="{{ $user->profile->website }}" target="_blank" class="text-blue-600 hover:underline">
                                {{ $user->profile->website }}
                            </a>
                        </div>
                    @endif

                    @if($user->profile->gender)
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $user->profile->gender)) }}</span>
                        </div>
                    @endif

                    @if($user->profile->birth_date)
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-gray-700">{{ $user->profile->birth_date->format('F d, Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Stats --}}
        <div class="mt-6 border-t border-gray-200 pt-6">
            <div class="grid grid-cols-3 gap-2 sm:gap-4 text-center">
                <div>
                    <div class="text-xl sm:text-2xl font-bold text-gray-900">{{ $user->friends_count }}</div>
                    <div class="text-xs sm:text-sm text-gray-600">Friends</div>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-bold text-gray-900">{{ $user->followers_count }}</div>
                    <div class="text-xs sm:text-sm text-gray-600">Followers</div>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-bold text-gray-900">{{ $user->following_count }}</div>
                    <div class="text-xs sm:text-sm text-gray-600">Following</div>
                </div>
            </div>
        </div>
    </div>
</div>
