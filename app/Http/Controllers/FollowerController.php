<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $user = Auth::user();
        
        return response()->json([
            'followers' => $user->followers,
            'following' => $user->following,
            'followers_count' => $user->followers_count,
            'following_count' => $user->following_count,
        ]);
    }

    public function follow(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        $targetUser = User::findOrFail($request->user_id);

        $follower = $user->follow($targetUser);

        if (!$follower) {
            return response()->json([
                'message' => 'Unable to follow user.',
            ], 400);
        }

        return response()->json([
            'message' => 'Successfully followed user.',
            'follower' => $follower,
        ], 201);
    }

    public function unfollow(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        $targetUser = User::findOrFail($request->user_id);

        $result = $user->unfollow($targetUser);

        if (!$result) {
            return response()->json([
                'message' => 'Unable to unfollow user.',
            ], 400);
        }

        return response()->json([
            'message' => 'Successfully unfollowed user.',
        ]);
    }
}
