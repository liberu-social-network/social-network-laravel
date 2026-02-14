<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Events\PostLiked;
use App\Events\PostUnliked;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function toggle($postId)
    {
        $post = Post::findOrFail($postId);

        $like = Like::where('post_id', $post->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
            $likesCount = $post->likes()->count();
            
            event(new PostUnliked($post, auth()->user(), $likesCount));
        } else {
            Like::create([
                'post_id' => $post->id,
                'user_id' => auth()->id(),
            ]);
            $liked = true;
            $likesCount = $post->likes()->count();
            
            event(new PostLiked($post, auth()->user(), $likesCount));
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $likesCount,
        ]);
    }

    public function index($postId)
    {
        $likes = Like::where('post_id', $postId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($likes);
    }
}
