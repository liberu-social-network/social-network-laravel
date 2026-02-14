<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $messages = Message::where('receiver_id', $user->id)
            ->orWhere('sender_id', $user->id)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        if ($user->id == $request->receiver_id) {
            return response()->json([
                'error' => 'You cannot send a message to yourself'
            ], 422);
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
        ]);

        $message->load(['sender', 'receiver']);

        return response()->json($message, 201);
    }

    public function show(Message $message)
    {
        $this->authorize('view', $message);

        if ($message->receiver_id === Auth::id() && !$message->isRead()) {
            $message->markAsRead();
        }

        $message->load(['sender', 'receiver']);

        return response()->json($message);
    }

    public function conversation(Request $request, User $user)
    {
        $authUser = Auth::user();
        
        $messages = Message::betweenUsers($authUser->id, $user->id)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        $messages->each(function ($message) use ($authUser) {
            if ($message->receiver_id === $authUser->id && !$message->isRead()) {
                $message->markAsRead();
            }
        });

        return response()->json($messages);
    }

    public function destroy(Message $message)
    {
        $this->authorize('delete', $message);

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
}
