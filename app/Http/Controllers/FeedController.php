<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Friendship;
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

        // Get IDs of friends and users being followed
        $friendIds = Friendship::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('friend_id', $user->id);
            })
            ->where('status', 'accepted')
            ->get()
            ->map(function($friendship) use ($user) {
                return $friendship->user_id === $user->id 
                    ? $friendship->friend_id 
                    : $friendship->user_id;
            })
            ->toArray();

        // Include own user ID to show own posts
        $userIds = array_merge($friendIds, [$user->id]);

        // Get posts from friends and self, respecting privacy settings
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
