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
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
