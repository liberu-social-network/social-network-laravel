<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GroupPostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get posts for a specific group.
     */
    public function index($groupId)
    {
        $group = Group::findOrFail($groupId);

        // Check if user can view posts (must be member for private groups)
        if ($group->privacy === 'private' && !$group->hasMember(Auth::user())) {
            return response()->json([
                'message' => 'You must be a member to view posts in this private group',
            ], 403);
        }

        $posts = Post::where('group_id', $groupId)
            ->with(['user', 'comments.user', 'likes', 'shares'])
            ->withCount(['likes', 'comments', 'shares'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Add liked/shared status for current user
        $posts->each(function ($post) {
            $post->is_liked = $post->isLikedBy(Auth::user());
            $post->is_shared = $post->isSharedBy(Auth::user());
        });

        return response()->json($posts);
    }

    /**
     * Create a post in a group.
     */
    public function store(Request $request, $groupId)
    {
        $group = Group::findOrFail($groupId);

        // Check if user is a member
        if (!$group->hasMember(Auth::user())) {
            return response()->json([
                'message' => 'You must be a member to post in this group',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required_without:image|string',
            'image' => 'nullable|image|max:5120',
            'video' => 'nullable|file|mimes:mp4,mov,avi|max:51200',
        ]);

        $postData = [
            'user_id' => Auth::id(),
            'group_id' => $groupId,
            'content' => $validated['content'] ?? '',
            'media_type' => 'text',
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('posts', 'public');
            $postData['image_url'] = Storage::url($path);
            $postData['media_type'] = 'image';
        }

        // Handle video upload
        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('posts', 'public');
            $postData['video_url'] = Storage::url($path);
            $postData['media_type'] = 'video';
        }

        $post = Post::create($postData);

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post->load(['user', 'group']),
        ], 201);
    }

    /**
     * Update a group post.
     */
    public function update(Request $request, $groupId, $postId)
    {
        $group = Group::findOrFail($groupId);
        $post = Post::where('id', $postId)
            ->where('group_id', $groupId)
            ->firstOrFail();

        // Check if user owns the post or is group admin
        if ($post->user_id !== Auth::id() && !$group->isAdmin(Auth::user()) && !$group->isOwner(Auth::user())) {
            return response()->json([
                'message' => 'You do not have permission to update this post',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'sometimes|required|string',
        ]);

        $post->update($validated);

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post->fresh(['user', 'group']),
        ]);
    }

    /**
     * Delete a group post.
     */
    public function destroy($groupId, $postId)
    {
        $group = Group::findOrFail($groupId);
        $post = Post::where('id', $postId)
            ->where('group_id', $groupId)
            ->firstOrFail();

        // Check if user owns the post or is group admin
        if ($post->user_id !== Auth::id() && !$group->isAdmin(Auth::user()) && !$group->isOwner(Auth::user())) {
            return response()->json([
                'message' => 'You do not have permission to delete this post',
            ], 403);
        }

        // Delete media files if they exist
        if ($post->image_url) {
            $path = str_replace('/storage/', '', $post->image_url);
            Storage::disk('public')->delete($path);
        }
        if ($post->video_url) {
            $path = str_replace('/storage/', '', $post->video_url);
            Storage::disk('public')->delete($path);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully',
        ]);
    }
}
