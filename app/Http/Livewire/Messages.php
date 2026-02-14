<?php

namespace App\Http\Livewire;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Messages extends Component
{
    use WithPagination;

    public $selectedUserId;
    public $messageContent = '';
    public $searchTerm = '';

    protected $rules = [
        'messageContent' => 'required|string|max:5000',
        'selectedUserId' => 'required|exists:users,id',
    ];

    public function mount()
    {
        $this->selectedUserId = null;
    }

    public function sendMessage()
    {
        $this->validate();

        if (Auth::id() == $this->selectedUserId) {
            $this->addError('messageContent', 'You cannot send a message to yourself.');
            return;
        }

        Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->selectedUserId,
            'content' => $this->messageContent,
        ]);

        $this->messageContent = '';
        $this->dispatch('message-sent');
    }

    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
        $this->markMessagesAsRead($userId);
    }

    public function markMessagesAsRead($userId)
    {
        Message::where('sender_id', $userId)
            ->where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function getConversationsProperty()
    {
        $userId = Auth::id();
        
        return User::whereHas('sentMessages', function ($query) use ($userId) {
                $query->where('receiver_id', $userId);
            })
            ->orWhereHas('receivedMessages', function ($query) use ($userId) {
                $query->where('sender_id', $userId);
            })
            ->when($this->searchTerm, function ($query) {
                $query->where('name', 'like', '%' . $this->searchTerm . '%');
            })
            ->withCount(['receivedMessages' => function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->whereNull('read_at');
            }])
            ->get();
    }

    public function getMessagesProperty()
    {
        if (!$this->selectedUserId) {
            return collect();
        }

        return Message::betweenUsers(Auth::id(), $this->selectedUserId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function render()
    {
        return view('livewire.messages', [
            'conversations' => $this->conversations,
            'messages' => $this->messages,
        ]);
    }
}
