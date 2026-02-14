<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow-xl rounded-lg overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 h-[600px]">
            <!-- Conversations List -->
            <div class="border-r border-gray-200 overflow-y-auto">
                <div class="p-4 border-b border-gray-200">
                    <input 
                        type="text" 
                        wire:model.live="searchTerm" 
                        placeholder="Search conversations..." 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($conversations as $user)
                        <div 
                            wire:click="selectUser({{ $user->id }})"
                            class="p-4 hover:bg-gray-50 cursor-pointer {{ $selectedUserId == $user->id ? 'bg-blue-50' : '' }}"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <img 
                                        src="{{ $user->profile_photo_url }}" 
                                        alt="{{ $user->name }}" 
                                        class="w-10 h-10 rounded-full mr-3"
                                    />
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                                    </div>
                                </div>
                                @if($user->received_messages_count > 0)
                                    <span class="bg-blue-500 text-white text-xs font-bold rounded-full px-2 py-1">
                                        {{ $user->received_messages_count }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500">
                            No conversations yet
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Messages Area -->
            <div class="col-span-2 flex flex-col">
                @if($selectedUserId)
                    <!-- Messages Header -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex items-center">
                            @php
                                $selectedUser = \App\Models\User::find($selectedUserId);
                            @endphp
                            @if($selectedUser)
                                <img 
                                    src="{{ $selectedUser->profile_photo_url }}" 
                                    alt="{{ $selectedUser->name }}" 
                                    class="w-10 h-10 rounded-full mr-3"
                                />
                                <h3 class="font-semibold text-lg">{{ $selectedUser->name }}</h3>
                            @endif
                        </div>
                    </div>

                    <!-- Messages List -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-4">
                        @forelse($messages as $message)
                            <div class="flex {{ $message->sender_id == auth()->id() ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-xs lg:max-w-md">
                                    <div class="px-4 py-2 rounded-lg {{ $message->sender_id == auth()->id() ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-900' }}">
                                        <p class="break-words">{{ $message->content }}</p>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 {{ $message->sender_id == auth()->id() ? 'text-right' : 'text-left' }}">
                                        {{ $message->created_at->diffForHumans() }}
                                        @if($message->sender_id == auth()->id() && $message->read_at)
                                            <span class="text-blue-500">✓✓</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-500">
                                No messages yet. Start the conversation!
                            </div>
                        @endforelse
                    </div>

                    <!-- Message Input -->
                    <div class="p-4 border-t border-gray-200">
                        <form wire:submit.prevent="sendMessage" class="flex space-x-2">
                            <textarea 
                                wire:model="messageContent"
                                placeholder="Type your message..." 
                                rows="2"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                            ></textarea>
                            <button 
                                type="submit"
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                Send
                            </button>
                        </form>
                        @error('messageContent') 
                            <span class="text-red-500 text-sm">{{ $errors->first('messageContent') }}</span> 
                        @enderror
                    </div>
                @else
                    <div class="flex items-center justify-center h-full text-gray-500">
                        Select a conversation to start messaging
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
