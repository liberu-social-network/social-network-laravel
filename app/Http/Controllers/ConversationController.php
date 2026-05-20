<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $conversations = $user->activeConversations()
            ->with(['participants', 'latestMessage.sender'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json($conversations);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:direct,group',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        // For direct conversations, check if one already exists
        if ($request->type === 'direct' && count($request->participant_ids) === 1) {
            $otherUserId = $request->participant_ids[0];
            
            $existingConversation = Conversation::where('type', 'direct')
                ->whereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->whereHas('participants', function ($query) use ($otherUserId) {
                    $query->where('user_id', $otherUserId);
                })
                ->first();

            if ($existingConversation) {
                return response()->json($existingConversation->load('participants'), 200);
            }
        }

        // Create new conversation
        $conversation = Conversation::create([
            'name' => $request->name,
            'type' => $request->type,
            'created_by' => $user->id,
        ]);

        // Add creator as participant
        $conversation->addParticipant($user);

        // Add other participants
        foreach ($request->participant_ids as $participantId) {
            if ($participantId != $user->id) {
                $participant = User::find($participantId);
                if ($participant) {
                    $conversation->addParticipant($participant);
                }
            }
        }

        return response()->json($conversation->load('participants'), 201);
    }

    public function show(Conversation $conversation)
    {
        $user = Auth::user();

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation->load(['participants', 'messages.sender', 'messages.attachments', 'messages.reactions']);

        return response()->json($conversation);
    }

    public function addParticipants(Request $request, Conversation $conversation)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only allow adding participants to group conversations
        if (!$conversation->isGroupConversation()) {
            return response()->json(['error' => 'Cannot add participants to direct conversations'], 422);
        }

        foreach ($request->user_ids as $userId) {
            $participant = User::find($userId);
            if ($participant) {
                $conversation->addParticipant($participant);
            }
        }

        return response()->json($conversation->load('participants'));
    }

    public function removeParticipant(Request $request, Conversation $conversation, User $user)
    {
        $authUser = Auth::user();

        // Check if auth user is participant
        if (!$conversation->participants()->where('user_id', $authUser->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Users can only remove themselves or creator can remove others
        if ($user->id !== $authUser->id && $conversation->created_by !== $authUser->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation->removeParticipant($user);

        return response()->json(['message' => 'Participant removed successfully']);
    }
}
