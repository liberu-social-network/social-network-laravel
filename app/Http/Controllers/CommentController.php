<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Events\CommentCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($postId)
    {
        $comments = Comment::where('post_id', $postId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($comments);
    }

    public function store(Request $request, $postId)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post = Post::findOrFail($postId);

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'post_id' => $post->id,
            'content' => $request->content,
        ]);

        $comment->load('user');
        
        $commentsCount = $post->comments()->count();
        event(new CommentCreated($comment, $commentsCount));

        return response()->json($comment, 201);
    }

    public function update(Request $request, $commentId)
    {
        $comment = Comment::findOrFail($commentId);

        if ($comment->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment->update([
            'content' => $request->content,
        ]);

        $comment->load('user');

        return response()->json($comment);
    }

    public function destroy($commentId)
    {
        $comment = Comment::findOrFail($commentId);

        if ($comment->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
