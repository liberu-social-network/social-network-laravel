<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    /**
     * Display a listing of the user's albums.
     */
    public function index(Request $request)
    {
        $albums = Album::with('media')
            ->where('user_id', $request->user()->id)
            ->withCount('media')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($albums);
    }

    /**
     * Store a newly created album in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'privacy' => 'required|in:public,friends_only,private',
        ]);

        $album = Album::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'privacy' => $request->privacy,
        ]);

        return response()->json($album, 201);
    }

    /**
     * Display the specified album.
     */
    public function show(Request $request, $id)
    {
        $album = Album::with(['media' => function ($query) use ($request) {
            $query->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        // Check if user can view this album
        if (!$album->isVisibleTo($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($album);
    }

    /**
     * Update the specified album.
     */
    public function update(Request $request, $id)
    {
        $album = Album::findOrFail($id);

        // Only owner can update
        if ($album->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'privacy' => 'sometimes|in:public,friends_only,private',
        ]);

        $album->update($request->only(['name', 'description', 'privacy']));

        return response()->json($album);
    }

    /**
     * Remove the specified album from storage.
     */
    public function destroy(Request $request, $id)
    {
        $album = Album::findOrFail($id);

        // Only owner can delete
        if ($album->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Remove album association from media (don't delete media)
        $album->media()->update(['album_id' => null]);

        $album->delete();

        return response()->json(['message' => 'Album deleted successfully']);
    }

    /**
     * Get albums visible to the current user.
     */
    public function publicAlbums(Request $request)
    {
        $user = $request->user();
        
        $albums = Album::with('media')
            ->visibleTo($user)
            ->withCount('media')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($albums);
    }
}
