<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Friendship;
use App\Models\Follower;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // Get IDs of friends (accepted friendships)
        $friendIds = Friendship::where(function($query) use ($user) {
                $query->where('requester_id', $user->id)
                      ->orWhere('addressee_id', $user->id);
            })
            ->where('status', 'accepted')
            ->get()
            ->map(function($friendship) use ($user) {
                return $friendship->requester_id === $user->id 
                    ? $friendship->addressee_id 
                    : $friendship->requester_id;
            })
            ->toArray();

        // Get IDs of users being followed
        $followingIds = Follower::where('follower_id', $user->id)
            ->pluck('following_id')
            ->toArray();

        // Merge friend IDs, following IDs, and own user ID
        $userIds = array_unique(array_merge($friendIds, $followingIds, [$user->id]));

        // Get posts from friends and self, respecting privacy settings
        // Get posts from friends, followed users, and self
        $posts = Post::whereIn('user_id', $userIds)
            ->visibleTo($user)
            ->with(['user', 'comments.user', 'likes', 'shares', 'media'])
            ->withCount(['likes', 'comments', 'shares'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Add user interaction flags
        $posts->getCollection()->transform(function ($post) use ($user) {
            $post->is_liked = $post->isLikedBy($user);
            $post->is_shared = $post->isSharedBy($user);
            return $post;
        });

        return response()->json($posts);
    }

    public function timeline(Request $request, $userId)
    {
        // Get a specific user's timeline
        $posts = Post::where('user_id', $userId)
            ->visibleTo($request->user())
            ->with(['user', 'comments.user', 'likes', 'shares', 'media'])
            ->withCount(['likes', 'comments', 'shares'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Add user interaction flags if authenticated
        if (auth()->check()) {
            $user = auth()->user();
            $posts->getCollection()->transform(function ($post) use ($user) {
                $post->is_liked = $post->isLikedBy($user);
                $post->is_shared = $post->isSharedBy($user);
                return $post;
            });
        }

        return response()->json($posts);
    }
}
