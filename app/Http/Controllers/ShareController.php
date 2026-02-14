<?php

namespace App\Http\Controllers;

use App\Models\Share;
use App\Models\Post;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function toggle($postId)
    {
        $post = Post::findOrFail($postId);

        $share = Share::where('post_id', $post->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($share) {
            $share->delete();
            $shared = false;
        } else {
            Share::create([
                'post_id' => $post->id,
                'user_id' => auth()->id(),
            ]);
            $shared = true;
        }

        return response()->json([
            'shared' => $shared,
            'shares_count' => $post->shares()->count(),
        ]);
    }

    public function index($postId)
    {
        $shares = Share::where('post_id', $postId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($shares);
    }
}
