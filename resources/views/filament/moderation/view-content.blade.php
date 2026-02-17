<div class="p-4">
    <div class="mb-4">
        <h3 class="text-lg font-semibold mb-2">Content Type: {{ $type }}</h3>
    </div>

    @if($content)
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <div class="mb-4">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    <strong>Author:</strong> {{ $content->user->name ?? 'Unknown' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    <strong>Posted:</strong> {{ $content->created_at->format('F j, Y, g:i a') }}
                </p>
                @if($type === 'Post')
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                        <strong>Likes:</strong> {{ $content->likes_count ?? $content->likesCount() }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                        <strong>Comments:</strong> {{ $content->comments_count ?? $content->commentsCount() }}
                    </p>
                @endif
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <p class="text-sm font-semibold mb-2">Content:</p>
                <div class="prose dark:prose-invert max-w-none">
                    {!! nl2br(e($content->content)) !!}
                </div>
                
                @if($type === 'Post')
                    @if($content->image_url)
                        <div class="mt-4">
                            <img src="{{ asset('storage/' . $content->image_url) }}" 
                                 alt="Post image" 
                                 class="max-w-full h-auto rounded-lg">
                        </div>
                    @endif
                    @if($content->video_url)
                        <div class="mt-4">
                            <video controls class="max-w-full h-auto rounded-lg">
                                <source src="{{ asset('storage/' . $content->video_url) }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    @endif
                @endif
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <strong>Moderation Status:</strong> 
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($content->moderation_status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($content->moderation_status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @elseif($content->moderation_status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                        @endif">
                        {{ ucfirst($content->moderation_status) }}
                    </span>
                </p>
                @if($content->moderation_notes)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        <strong>Moderation Notes:</strong> {{ $content->moderation_notes }}
                    </p>
                @endif
            </div>
        </div>
    @else
        <p class="text-gray-500 dark:text-gray-400">Content not found or has been deleted.</p>
    @endif
</div>
