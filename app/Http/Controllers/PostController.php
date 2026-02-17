<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $posts = Post::with(['user', 'comments.user', 'likes', 'shares', 'media'])
            ->published()
            ->visibleTo($request->user())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($posts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required_without_all:image,video|string|max:5000',
            'image' => 'nullable|image|max:10240', // 10MB max
            'video' => 'nullable|mimes:mp4,mov,avi,wmv|max:51200', // 50MB max
            'privacy' => 'nullable|in:public,friends_only,private',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $mediaType = 'text';
        $imageUrl = null;
        $videoUrl = null;

        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('posts/images', 'public');
            $mediaType = 'image';
        }

        if ($request->hasFile('video')) {
            $videoUrl = $request->file('video')->store('posts/videos', 'public');
            $mediaType = $request->hasFile('image') ? 'mixed' : 'video';
        }

        $scheduledAt = $request->input('scheduled_at');
        $isPublished = true;

        // If scheduled for future, mark as unpublished
        if ($scheduledAt && \Carbon\Carbon::parse($scheduledAt)->isFuture()) {
            $isPublished = false;
        }

        $post = Post::create([
            'user_id' => auth()->id(),
            'content' => $request->content,
            'image_url' => $imageUrl,
            'video_url' => $videoUrl,
            'media_type' => $mediaType,
            'privacy' => $request->input('privacy', 'public'),
            'scheduled_at' => $scheduledAt,
            'is_published' => $isPublished,
        ]);

        $post->load(['user', 'comments.user', 'likes', 'shares', 'media']);

        $response = ['post' => $post];
        
        if (!$isPublished) {
            $response['message'] = 'Post scheduled successfully for ' . \Carbon\Carbon::parse($scheduledAt)->format('Y-m-d H:i:s');
        }

        return response()->json($response, 201);
    }

    public function show(Request $request, $id)
    {
        $post = Post::with(['user', 'comments.user', 'likes.user', 'shares.user', 'media'])
            ->findOrFail($id);

        // Check if user can view this post
        if (!$post->isVisibleTo($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Don't show unpublished posts to others
        if (!$post->is_published && $post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return response()->json($post);
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'sometimes|string|max:5000',
            'privacy' => 'sometimes|in:public,friends_only,private',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post->update($request->only(['content', 'privacy']));

        $post->load(['user', 'comments.user', 'likes', 'shares', 'media']);

        return response()->json($post);
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete associated media files
        if ($post->image_url) {
            Storage::disk('public')->delete($post->image_url);
        }

        if ($post->video_url) {
            Storage::disk('public')->delete($post->video_url);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
