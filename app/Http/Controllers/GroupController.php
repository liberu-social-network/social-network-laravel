<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of groups.
     */
    public function index(Request $request)
    {
        $query = Group::with(['owner', 'members'])
            ->where('is_active', true);

        // Filter by privacy if specified
        if ($request->has('privacy')) {
            $query->where('privacy', $request->privacy);
        }

        // Filter to only groups the user is a member of
        if ($request->has('my_groups') && $request->my_groups) {
            $query->whereHas('members', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        $groups = $query->withCount(['members', 'posts'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($groups);
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'required|in:public,private',
            'image' => 'nullable|image|max:2048',
        ]);

        $groupData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'privacy' => $validated['privacy'],
            'user_id' => Auth::id(),
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('groups', 'public');
            $groupData['image_url'] = Storage::url($path);
        }

        $group = Group::create($groupData);

        // Add creator as admin member
        $group->members()->attach(Auth::id(), [
            'role' => 'admin',
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => 'Group created successfully',
            'group' => $group->load(['owner', 'members']),
        ], 201);
    }

    /**
     * Display the specified group.
     */
    public function show($id)
    {
        $group = Group::with(['owner', 'members', 'admins'])
            ->withCount(['members', 'posts'])
            ->findOrFail($id);

        // Check if user can view private groups
        if ($group->privacy === 'private' && !$group->hasMember(Auth::user())) {
            return response()->json([
                'message' => 'You do not have permission to view this group',
            ], 403);
        }

        return response()->json([
            'group' => $group,
            'is_member' => $group->hasMember(Auth::user()),
            'is_admin' => $group->isAdmin(Auth::user()),
            'is_owner' => $group->isOwner(Auth::user()),
        ]);
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);

        // Check if user is admin or owner
        if (!$group->isAdmin(Auth::user()) && !$group->isOwner(Auth::user())) {
            return response()->json([
                'message' => 'You do not have permission to update this group',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'sometimes|required|in:public,private',
            'image' => 'nullable|image|max:2048',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($group->image_url) {
                $oldPath = str_replace('/storage/', '', $group->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            
            $path = $request->file('image')->store('groups', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $group->update($validated);

        return response()->json([
            'message' => 'Group updated successfully',
            'group' => $group->fresh(['owner', 'members']),
        ]);
    }

    /**
     * Remove the specified group.
     */
    public function destroy($id)
    {
        $group = Group::findOrFail($id);

        // Only owner can delete
        if (!$group->isOwner(Auth::user())) {
            return response()->json([
                'message' => 'Only the group owner can delete this group',
            ], 403);
        }

        // Delete group image if exists
        if ($group->image_url) {
            $path = str_replace('/storage/', '', $group->image_url);
            Storage::disk('public')->delete($path);
        }

        $group->delete();

        return response()->json([
            'message' => 'Group deleted successfully',
        ]);
    }

    /**
     * Get members of a group.
     */
    public function members($id)
    {
        $group = Group::findOrFail($id);

        // Check if user can view members
        if ($group->privacy === 'private' && !$group->hasMember(Auth::user())) {
            return response()->json([
                'message' => 'You do not have permission to view group members',
            ], 403);
        }

        $members = $group->members()
            ->withPivot('role', 'status')
            ->get();

        return response()->json([
            'members' => $members,
        ]);
    }

    /**
     * Get pending members (for admins).
     */
    public function pendingMembers($id)
    {
        $group = Group::findOrFail($id);

        // Only admins and owner can view pending members
        if (!$group->isAdmin(Auth::user()) && !$group->isOwner(Auth::user())) {
            return response()->json([
                'message' => 'You do not have permission to view pending members',
            ], 403);
        }

        $pendingMembers = $group->pendingMembers()
            ->withPivot('role', 'status')
            ->get();

        return response()->json([
            'pending_members' => $pendingMembers,
        ]);
    }
}
