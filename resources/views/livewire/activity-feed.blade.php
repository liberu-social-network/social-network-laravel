<div class="bg-white rounded-lg shadow-md p-6" wire:poll.10s="loadActivities">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Activity Feed</h2>

    @if($activities && $activities->count() > 0)
        <div class="space-y-4">
            @foreach($activities as $activity)
                <div class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            @if($activity->actor && $activity->actor->profile_photo_url)
                                <img src="{{ $activity->actor->profile_photo_url }}" 
                                     alt="{{ $activity->actor->name }}" 
                                     class="w-10 h-10 rounded-full">
                            @else
                                <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-gray-600 text-sm font-semibold">
                                        {{ $activity->actor ? strtoupper(substr($activity->actor->name, 0, 1)) : '?' }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="text-sm">
                                <span class="font-semibold text-gray-900">
                                    {{ $activity->actor ? $activity->actor->name : 'Unknown User' }}
                                </span>
                                
                                @if($activity->type === 'post_created')
                                    <span class="text-gray-600">created a new post</span>
                                @elseif($activity->type === 'post_liked')
                                    <span class="text-gray-600">liked a post</span>
                                @elseif($activity->type === 'comment_added')
                                    <span class="text-gray-600">commented on a post</span>
                                @endif
                            </div>

                            @if($activity->data && isset($activity->data['content_preview']))
                                <p class="mt-1 text-sm text-gray-700 line-clamp-2">
                                    {{ $activity->data['content_preview'] }}
                                </p>
                            @elseif($activity->data && isset($activity->data['comment_preview']))
                                <p class="mt-1 text-sm text-gray-700 line-clamp-2">
                                    "{{ $activity->data['comment_preview'] }}"
                                </p>
                            @elseif($activity->data && isset($activity->data['post_content_preview']))
                                <p class="mt-1 text-sm text-gray-500 line-clamp-2">
                                    on: "{{ $activity->data['post_content_preview'] }}"
                                </p>
                            @endif

                            <p class="mt-1 text-xs text-gray-500">
                                {{ $activity->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($activities->count() >= $limit)
            <div class="mt-6 text-center">
                <button wire:click="loadMore" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                    Load More
                </button>
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No activities yet</h3>
            <p class="mt-1 text-sm text-gray-500">
                Connect with friends to see their activities in your feed!
            </p>
        </div>
    @endif

    <div wire:loading class="mt-4 text-center">
        <div class="inline-flex items-center px-4 py-2 text-sm text-gray-600">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Updating...
        </div>
    </div>
</div>
