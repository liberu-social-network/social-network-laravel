<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Search for groups.
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1',
            'privacy' => 'nullable|in:public,private',
        ]);

        $query = Group::query()
            ->where('is_active', true)
            ->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->query . '%')
                  ->orWhere('description', 'like', '%' . $request->query . '%');
            });

        // Filter by privacy
        if ($request->has('privacy')) {
            $query->where('privacy', $request->privacy);
        } else {
            // By default, only show public groups or groups user is a member of
            $query->where(function ($q) {
                $q->where('privacy', 'public')
                  ->orWhereHas('members', function ($memberQuery) {
                      $memberQuery->where('user_id', Auth::id());
                  });
            });
        }

        $groups = $query->with(['owner'])
            ->withCount(['members', 'posts'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Add membership status for each group
        $groups->each(function ($group) {
            $group->is_member = $group->hasMember(Auth::user());
            $group->is_admin = $group->isAdmin(Auth::user());
        });

        return response()->json($groups);
    }

    /**
     * Get suggested groups based on user's interests.
     */
    public function suggestions()
    {
        // Get public groups that the user is not a member of
        $groups = Group::where('is_active', true)
            ->where('privacy', 'public')
            ->whereDoesntHave('members', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->with(['owner'])
            ->withCount(['members', 'posts'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Add membership status
        $groups->each(function ($group) {
            $group->is_member = false;
            $group->is_admin = false;
        });

        return response()->json([
            'suggestions' => $groups,
        ]);
    }

    /**
     * Get popular groups.
     */
    public function popular()
    {
        $groups = Group::where('is_active', true)
            ->where('privacy', 'public')
            ->with(['owner'])
            ->withCount(['members', 'posts'])
            ->orderBy('members_count', 'desc')
            ->limit(10)
            ->get();

        // Add membership status
        $groups->each(function ($group) {
            $group->is_member = $group->hasMember(Auth::user());
            $group->is_admin = $group->isAdmin(Auth::user());
        });

        return response()->json([
            'popular_groups' => $groups,
        ]);
    }
}
