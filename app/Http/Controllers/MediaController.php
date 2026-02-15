<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Album;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Display a listing of the user's media.
     */
    public function index(Request $request)
    {
        $query = Media::with(['user', 'album', 'tags'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Filter by album if provided
        if ($request->has('album_id')) {
            $query->where('album_id', $request->album_id);
        }

        // Filter by tag if provided
        if ($request->has('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('file_type', $request->type);
        }

        $media = $query->paginate(20);

        return response()->json($media);
    }

    /**
     * Store a newly created media in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,mp4,mov,avi,wmv|max:51200', // 50MB max
            'album_id' => 'nullable|exists:albums,id',
            'description' => 'nullable|string|max:1000',
            'privacy' => 'required|in:public,friends_only,private',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $file = $request->file('file');
        $user = $request->user();

        // Determine file type
        $mimeType = $file->getMimeType();
        $fileType = str_starts_with($mimeType, 'image') ? 'image' : 'video';

        // Store the file
        $directory = 'media/' . $fileType . 's/' . $user->id;
        $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs($directory, $fileName, 'public');

        // Get file dimensions for images
        $width = null;
        $height = null;
        if ($fileType === 'image') {
            $imageInfo = getimagesize($file->getRealPath());
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
        }

        // Create media record
        $media = Media::create([
            'user_id' => $user->id,
            'album_id' => $request->album_id,
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'description' => $request->description,
            'privacy' => $request->privacy,
            'width' => $width,
            'height' => $height,
        ]);

        // Attach tags
        if ($request->has('tags') && is_array($request->tags)) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
                $tagIds[] = $tag->id;
            }
            $media->tags()->sync($tagIds);
        }

        return response()->json($media->load(['user', 'album', 'tags']), 201);
    }

    /**
     * Display the specified media.
     */
    public function show(Request $request, $id)
    {
        $media = Media::with(['user', 'album', 'tags'])->findOrFail($id);

        // Check if user can view this media
        if (!$media->isVisibleTo($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($media);
    }

    /**
     * Update the specified media.
     */
    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        // Only owner can update
        if ($media->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'album_id' => 'nullable|exists:albums,id',
            'description' => 'nullable|string|max:1000',
            'privacy' => 'sometimes|in:public,friends_only,private',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $media->update($request->only(['album_id', 'description', 'privacy']));

        // Update tags if provided
        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
                $tagIds[] = $tag->id;
            }
            $media->tags()->sync($tagIds);
        }

        return response()->json($media->load(['user', 'album', 'tags']));
    }

    /**
     * Remove the specified media from storage.
     */
    public function destroy(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        // Only owner can delete
        if ($media->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete the file from storage
        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        // Delete thumbnail if exists
        if ($media->thumbnail_path && Storage::disk('public')->exists($media->thumbnail_path)) {
            Storage::disk('public')->delete($media->thumbnail_path);
        }

        $media->delete();

        return response()->json(['message' => 'Media deleted successfully']);
    }

    /**
     * Get media visible to the current user.
     */
    public function feed(Request $request)
    {
        $user = $request->user();
        
        $media = Media::with(['user', 'album', 'tags'])
            ->visibleTo($user)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($media);
    }

    /**
     * Get user's gallery (media organized by albums).
     */
    public function gallery(Request $request, $userId)
    {
        $user = \App\Models\User::findOrFail($userId);
        $viewer = $request->user();

        // Get albums visible to viewer
        $albums = Album::with('media')
            ->where('user_id', $userId)
            ->visibleTo($viewer)
            ->withCount('media')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get media not in any album
        $unalbummedMedia = Media::where('user_id', $userId)
            ->whereNull('album_id')
            ->visibleTo($viewer)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'user' => $user,
            'albums' => $albums,
            'recent_media' => $unalbummedMedia,
        ]);
    }
}
