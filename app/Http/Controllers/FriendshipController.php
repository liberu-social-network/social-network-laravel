<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendshipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $user = Auth::user();
        
        return response()->json([
            'friends' => $user->friends,
            'sent_requests' => $user->sentFriendRequests()->where('status', 'pending')->with('addressee')->get(),
            'received_requests' => $user->receivedFriendRequests()->where('status', 'pending')->with('requester')->get(),
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        $targetUser = User::findOrFail($request->user_id);

        $friendship = $user->sendFriendRequest($targetUser);

        if (!$friendship) {
            return response()->json([
                'message' => 'Unable to send friend request.',
            ], 400);
        }

        return response()->json([
            'message' => 'Friend request sent successfully.',
            'friendship' => $friendship,
        ], 201);
    }

    public function accept(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        $requester = User::findOrFail($request->user_id);

        $friendship = $user->acceptFriendRequest($requester);

        if (!$friendship) {
            return response()->json([
                'message' => 'Unable to accept friend request.',
            ], 400);
        }

        return response()->json([
            'message' => 'Friend request accepted successfully.',
            'friendship' => $friendship,
        ]);
    }

    public function reject(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        $requester = User::findOrFail($request->user_id);

        $friendship = $user->rejectFriendRequest($requester);

        if (!$friendship) {
            return response()->json([
                'message' => 'Unable to reject friend request.',
            ], 400);
        }

        return response()->json([
            'message' => 'Friend request rejected successfully.',
            'friendship' => $friendship,
        ]);
    }
}
