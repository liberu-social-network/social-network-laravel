<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'album_id',
        'file_path',
        'file_name',
        'file_type',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'description',
        'privacy',
        'width',
        'height',
        'duration',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
    ];

    /**
     * Get the user that owns the media.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post this media belongs to.
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the album this media belongs to.
     */
    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * Get the tags for the media.
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'media_tag');
    }

    /**
     * Check if media is visible to the given user.
     */
    public function isVisibleTo(?User $viewer): bool
    {
        // Public media is visible to everyone
        if ($this->privacy === 'public') {
            return true;
        }

        // Private media is only visible to the owner
        if ($this->privacy === 'private') {
            return $viewer && $viewer->id === $this->user_id;
        }

        // Friends-only media is visible to friends and the owner
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
     * Scope to filter media visible to a user.
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

    /**
     * Get the full URL for the media file.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get the full URL for the thumbnail.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : null;
    }
}
