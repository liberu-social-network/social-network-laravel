<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'created_by',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('joined_at', 'left_at', 'last_read_at')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activeParticipants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->wherePivotNull('left_at')
            ->withPivot('joined_at', 'last_read_at')
            ->withTimestamps();
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function isGroupConversation(): bool
    {
        return $this->type === 'group';
    }

    public function isDirectConversation(): bool
    {
        return $this->type === 'direct';
    }

    public function addParticipant(User $user): void
    {
        $this->participants()->syncWithoutDetaching([
            $user->id => [
                'joined_at' => now(),
                'left_at' => null,
            ]
        ]);
    }

    public function removeParticipant(User $user): void
    {
        $this->participants()->updateExistingPivot($user->id, [
            'left_at' => now(),
        ]);
    }
}
