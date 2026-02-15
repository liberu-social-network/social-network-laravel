<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'cover_image',
        'privacy',
    ];

    /**
     * Get the user that owns the album.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the media in the album.
     */
    public function media()
    {
        return $this->hasMany(Media::class);
    }

    /**
     * Check if album is visible to the given user.
     */
    public function isVisibleTo(?User $viewer): bool
    {
        // Public albums are visible to everyone
        if ($this->privacy === 'public') {
            return true;
        }

        // Private albums are only visible to the owner
        if ($this->privacy === 'private') {
            return $viewer && $viewer->id === $this->user_id;
        }

        // Friends-only albums are visible to friends and the owner
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
     * Scope to filter albums visible to a user.
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
     * Get the media count in the album.
     */
    public function getMediaCountAttribute(): int
    {
        return $this->media()->count();
    }

    /**
     * Get the cover image URL.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }

        // Use the first media item's thumbnail as cover if available
        $firstMedia = $this->media()->first();
        return $firstMedia ? $firstMedia->thumbnail_url : null;
    }
}
