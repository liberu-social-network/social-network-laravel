<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_id',
        'content',
        'image_url',
        'video_url',
        'media_type',
        'privacy',
        'moderation_status',
        'moderation_notes',
        'moderated_by',
        'moderated_at',
    ];

    protected $casts = [
        'moderated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function shares()
    {
        return $this->hasMany(Share::class);
    }

    public function likesCount()
    {
        return $this->likes()->count();
    }

    public function commentsCount()
    {
        return $this->comments()->count();
    }

    public function sharesCount()
    {
        return $this->shares()->count();
    }

    public function isLikedBy($user)
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function isSharedBy($user)
    {
        return $this->shares()->where('user_id', $user->id)->exists();
    }

    /**
     * Get the moderator who moderated this post.
     */
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Get all reports for this post.
     */
    public function reports()
    {
        return $this->morphMany(ContentReport::class, 'reportable');
    }

    /**
     * Scope to filter only approved posts.
     */
    public function scopeApproved($query)
    {
        return $query->where('moderation_status', 'approved');
    }

    /**
     * Get the media attached to the post.
     */
    public function media()
    {
        return $this->hasMany(Media::class);
    }

    /**
     * Check if post is visible to the given user.
     */
    public function isVisibleTo(?User $viewer): bool
    {
        // Public posts are visible to everyone
        if ($this->privacy === 'public') {
            return true;
        }

        // Private posts are only visible to the owner
        if ($this->privacy === 'private') {
            return $viewer && $viewer->id === $this->user_id;
        }

        // Friends-only posts are visible to friends and the owner
        if ($this->privacy === 'friends_only') {
            if (!$viewer) {
                return false;
            }
            
            if ($viewer->id === $this->user_id) {
                return true;
            }

            return $viewer->isFriendsWith($this->user);
        }

        return false;
    }

    /**
     * Scope to filter posts visible to a user.
     */
    public function scopeVisibleTo($query, ?User $viewer)
    {
        if (!$viewer) {
            return $query->where('privacy', 'public');
        }

        return $query->where(function ($q) use ($viewer) {
            $q->where('privacy', 'public')
                ->orWhere(function ($q) use ($viewer) {
                    $q->where('user_id', $viewer->id);
                })
                ->orWhere(function ($q) use ($viewer) {
                    $q->where('privacy', 'friends_only')
                        ->whereHas('user', function ($q) use ($viewer) {
                            $q->whereIn('id', function ($query) use ($viewer) {
                                $query->select('requester_id')
                                    ->from('friendships')
                                    ->where('addressee_id', $viewer->id)
                                    ->where('status', 'accepted')
                                    ->union(
                                        \DB::table('friendships')
                                            ->select('addressee_id')
                                            ->where('requester_id', $viewer->id)
                                            ->where('status', 'accepted')
                                    );
                            });
                        });
                });
        });
    }
}
