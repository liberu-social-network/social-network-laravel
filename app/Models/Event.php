<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'location',
        'image_url',
        'start_time',
        'end_time',
        'max_attendees',
        'is_public',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendees()
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function goingAttendees()
    {
        return $this->hasMany(EventAttendee::class)->where('status', 'going');
    }

    public function maybeAttendees()
    {
        return $this->hasMany(EventAttendee::class)->where('status', 'maybe');
    }

    public function notGoingAttendees()
    {
        return $this->hasMany(EventAttendee::class)->where('status', 'not_going');
    }

    public function attendeeUsers()
    {
        return $this->belongsToMany(User::class, 'event_attendees')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function goingCount()
    {
        return $this->attendees()->where('status', 'going')->count();
    }

    public function maybeCount()
    {
        return $this->attendees()->where('status', 'maybe')->count();
    }

    public function notGoingCount()
    {
        return $this->attendees()->where('status', 'not_going')->count();
    }

    public function isUserAttending($userId)
    {
        return $this->attendees()
            ->where('user_id', $userId)
            ->where('status', 'going')
            ->exists();
    }

    public function getUserRsvpStatus($userId)
    {
        $attendee = $this->attendees()
            ->where('user_id', $userId)
            ->first();

        return $attendee ? $attendee->status : null;
    }

    public function isFull()
    {
        if ($this->max_attendees === null) {
            return false;
        }

        return $this->goingCount() >= $this->max_attendees;
    }

    public function isPast()
    {
        return $this->end_time->isPast();
    }

    public function isUpcoming()
    {
        return $this->start_time->isFuture();
    }

    public function isOngoing()
    {
        return $this->start_time->isPast() && $this->end_time->isFuture();
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_time', '<', now());
    }

    public function scopeOngoing($query)
    {
        return $query->where('start_time', '<=', now())
            ->where('end_time', '>=', now());
    }
}
