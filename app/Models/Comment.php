<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'content',
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

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the moderator who moderated this comment.
     */
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Get all reports for this comment.
     */
    public function reports()
    {
        return $this->morphMany(ContentReport::class, 'reportable');
    }

    /**
     * Scope to filter only approved comments.
     */
    public function scopeApproved($query)
    {
        return $query->where('moderation_status', 'approved');
    }
}