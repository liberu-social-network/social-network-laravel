<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the media that are tagged with this tag.
     */
    public function media()
    {
        return $this->belongsToMany(Media::class, 'media_tag');
    }

    /**
     * Set the tag name and auto-generate slug.
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    /**
     * Get media count for this tag.
     */
    public function getMediaCountAttribute(): int
    {
        return $this->media()->count();
    }
}
