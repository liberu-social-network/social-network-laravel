<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\ReactionAdded;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $messages = Message::where('receiver_id', $user->id)
            ->orWhere('sender_id', $user->id)
            ->with(['sender', 'receiver', 'attachments', 'reactions'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required_without:conversation_id|exists:users,id',
            'conversation_id' => 'required_without:receiver_id|exists:conversations,id',
            'content' => 'required_without:attachments|string|max:5000',
            'encrypted' => 'boolean',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // For direct messages
        if ($request->receiver_id && $user->id == $request->receiver_id) {
            return response()->json([
                'error' => 'You cannot send a message to yourself'
            ], 422);
        }

        // For conversation messages
        if ($request->conversation_id) {
            $conversation = Conversation::find($request->conversation_id);
            if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
                return response()->json(['error' => 'You are not a participant of this conversation'], 403);
            }
        }

        // Create message
        $messageData = [
            'sender_id' => $user->id,
            'receiver_id' => $request->receiver_id,
            'conversation_id' => $request->conversation_id,
        ];

        // Handle encryption
        if ($request->encrypted && $request->content) {
            $message = new Message($messageData);
            $message->encrypt($request->content);
            $message->save();
        } else {
            $messageData['content'] = $request->content;
            $message = Message::create($messageData);
        }

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('message-attachments', $filename, 'public');

                MessageAttachment::create([
                    'message_id' => $message->id,
                    'filename' => $filename,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'path' => $path,
                ]);
            }
        }

        $message->load(['sender', 'receiver', 'attachments', 'reactions']);

        // Broadcast the message
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }

    public function show(Message $message)
    {
        $this->authorize('view', $message);

        if ($message->receiver_id === Auth::id() && !$message->isRead()) {
            $message->markAsRead();
        }

        $message->load(['sender', 'receiver', 'attachments', 'reactions.user']);

        return response()->json($message);
    }

    public function conversation(Request $request, User $user)
    {
        $authUser = Auth::user();
        
        $messages = Message::betweenUsers($authUser->id, $user->id)
            ->with(['sender', 'receiver', 'attachments', 'reactions.user'])
            ->orderBy('created_at', 'asc')
            ->get();

        $messages->each(function ($message) use ($authUser) {
            if ($message->receiver_id === $authUser->id && !$message->isRead()) {
                $message->markAsRead();
            }
        });

        return response()->json($messages);
    }

    public function conversationMessages(Request $request, Conversation $conversation)
    {
        $authUser = Auth::user();

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $authUser->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with(['sender', 'attachments', 'reactions.user'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function destroy(Message $message)
    {
        $this->authorize('delete', $message);

        // Delete attachments from storage
        foreach ($message->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->path);
        }

        $message->delete();

        return response()->json([
            'message' => 'Message deleted successfully'
        ]);
    }

    public function unreadCount(Request $request)
    {
        $user = Auth::user();
        
        $count = Message::where('receiver_id', $user->id)
            ->unread()
            ->count();

        return response()->json([
            'unread_count' => $count
        ]);
    }

    public function addReaction(Request $request, Message $message)
    {
        $validator = Validator::make($request->all(), [
            'emoji' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Check if user can view the message
        $this->authorize('view', $message);

        $reaction = $message->addReaction($user, $request->emoji);
        $reaction->load('user');

        // Broadcast reaction
        broadcast(new ReactionAdded($reaction))->toOthers();

        return response()->json($reaction, 201);
    }

    public function removeReaction(Request $request, Message $message, string $emoji)
    {
        $user = Auth::user();

        // Check if user can view the message
        $this->authorize('view', $message);

        $message->removeReaction($user, $emoji);

        return response()->json(['message' => 'Reaction removed successfully']);
    }

    public function typing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'nullable|exists:conversations,id',
            'receiver_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Broadcast typing event
        broadcast(new UserTyping(
            $user,
            $request->conversation_id,
            $request->receiver_id
        ))->toOthers();

        return response()->json(['message' => 'Typing event broadcasted']);
    }
}

