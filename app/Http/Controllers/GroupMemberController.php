<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupMemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Join a group.
     */
    public function join($groupId)
    {
        $group = Group::findOrFail($groupId);
        $user = Auth::user();

        // Check if already a member
        if ($group->hasMember($user)) {
            return response()->json([
                'message' => 'You are already a member of this group',
            ], 400);
        }

        // Check if there's a pending request
        $existingRequest = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'message' => 'You already have a pending request to join this group',
            ], 400);
        }

        // For public groups, auto-approve; for private groups, require approval
        $status = $group->privacy === 'public' ? 'approved' : 'pending';

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => $status,
        ]);

        return response()->json([
            'message' => $status === 'approved' 
                ? 'Successfully joined the group' 
                : 'Join request sent. Waiting for admin approval',
            'status' => $status,
        ], 201);
    }

    /**
     * Leave a group.
     */
    public function leave($groupId)
    {
        $group = Group::findOrFail($groupId);
        $user = Auth::user();

        // Check if user is the owner
        if ($group->isOwner($user)) {
            return response()->json([
                'message' => 'Group owners cannot leave their own group. Please transfer ownership or delete the group.',
            ], 400);
        }

        // Remove membership
        $deleted = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'You are not a member of this group',
            ], 400);
        }

        return response()->json([
            'message' => 'Successfully left the group',
        ]);
    }

    /**
     * Approve a membership request (admin only).
     */
    public function approve($groupId, $userId)
    {
        $group = Group::findOrFail($groupId);
        $user = Auth::user();

        // Check if user is admin or owner
        if (!$group->isAdmin($user) && !$group->isOwner($user)) {
            return response()->json([
                'message' => 'You do not have permission to approve members',
            ], 403);
        }

        $membership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->firstOrFail();

        $membership->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Member approved successfully',
        ]);
    }

    /**
     * Reject a membership request (admin only).
     */
    public function reject($groupId, $userId)
    {
        $group = Group::findOrFail($groupId);
        $user = Auth::user();

        // Check if user is admin or owner
        if (!$group->isAdmin($user) && !$group->isOwner($user)) {
            return response()->json([
                'message' => 'You do not have permission to reject members',
            ], 403);
        }

        $membership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->firstOrFail();

        $membership->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Member request rejected',
        ]);
    }

    /**
     * Remove a member from the group (admin only).
     */
    public function removeMember($groupId, $userId)
    {
        $group = Group::findOrFail($groupId);
        $user = Auth::user();

        // Check if user is admin or owner
        if (!$group->isAdmin($user) && !$group->isOwner($user)) {
            return response()->json([
                'message' => 'You do not have permission to remove members',
            ], 403);
        }

        // Cannot remove the owner
        if ($group->user_id == $userId) {
            return response()->json([
                'message' => 'Cannot remove the group owner',
            ], 400);
        }

        $deleted = GroupMember::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'User is not a member of this group',
            ], 400);
        }

        return response()->json([
            'message' => 'Member removed successfully',
        ]);
    }

    /**
     * Update member role (owner only).
     */
    public function updateRole(Request $request, $groupId, $userId)
    {
        $group = Group::findOrFail($groupId);
        $user = Auth::user();

        // Only owner can update roles
        if (!$group->isOwner($user)) {
            return response()->json([
                'message' => 'Only the group owner can update member roles',
            ], 403);
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,moderator,member',
        ]);

        // Cannot change owner's role
        if ($group->user_id == $userId) {
            return response()->json([
                'message' => 'Cannot change the group owner\'s role',
            ], 400);
        }

        $membership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $membership->update(['role' => $validated['role']]);

        return response()->json([
            'message' => 'Member role updated successfully',
            'membership' => $membership->fresh(),
        ]);
    }
}
