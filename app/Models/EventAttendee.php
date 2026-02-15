<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeGoing($query)
    {
        return $query->where('status', 'going');
    }

    public function scopeMaybe($query)
    {
        return $query->where('status', 'maybe');
    }

    public function scopeNotGoing($query)
    {
        return $query->where('status', 'not_going');
    }
}
