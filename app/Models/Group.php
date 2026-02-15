<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'user_id',
        'privacy',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns/created the group.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all members of the group.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot('role', 'status')
            ->withTimestamps()
            ->wherePivot('status', 'approved');
    }

    /**
     * Get all pending members.
     */
    public function pendingMembers()
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot('role', 'status')
            ->withTimestamps()
            ->wherePivot('status', 'pending');
    }

    /**
     * Get all admins of the group.
     */
    public function admins()
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot('role', 'status')
            ->withTimestamps()
            ->wherePivot('role', 'admin')
            ->wherePivot('status', 'approved');
    }

    /**
     * Get all posts in this group.
     */
    public function posts()
    {
        return $this->hasMany(Post::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if a user is a member of this group.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is an admin of this group.
     */
    public function isAdmin(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    /**
     * Check if a user is the owner of this group.
     */
    public function isOwner(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Get the count of members.
     */
    public function getMembersCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Get the count of posts.
     */
    public function getPostsCountAttribute(): int
    {
        return $this->posts()->count();
    }
}
