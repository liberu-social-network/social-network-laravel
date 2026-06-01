<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\TeamInvitation as JetstreamTeamInvitation;

class TeamInvitation extends JetstreamTeamInvitation
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    #[\Override]
    protected $fillable = [
        'email',
        'role',
        'token',
    ];

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
        });
    }

    /**
     * Get the team that the invitation belongs to.
     */
    #[\Override]
    public function team(): BelongsTo
    {
        return $this->belongsTo(Jetstream::teamModel());
    }
}
