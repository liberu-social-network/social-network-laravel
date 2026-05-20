<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Determine whether the user can view any groups.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the group.
     */
    public function view(User $user, Group $group): bool
    {
        // Public groups can be viewed by anyone
        if ($group->privacy === 'public') {
            return true;
        }

        // Private groups can only be viewed by members
        return $group->hasMember($user);
    }

    /**
     * Determine whether the user can create groups.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the group.
     */
    public function update(User $user, Group $group): bool
    {
        return $group->isAdmin($user) || $group->isOwner($user);
    }

    /**
     * Determine whether the user can delete the group.
     */
    public function delete(User $user, Group $group): bool
    {
        return $group->isOwner($user);
    }

    /**
     * Determine whether the user can manage members.
     */
    public function manageMembers(User $user, Group $group): bool
    {
        return $group->isAdmin($user) || $group->isOwner($user);
    }

    /**
     * Determine whether the user can post in the group.
     */
    public function createPost(User $user, Group $group): bool
    {
        return $group->hasMember($user);
    }
}
