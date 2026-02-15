<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use JoelButcher\Socialstream\HasConnectedAccounts;
use JoelButcher\Socialstream\SetsProfilePhotoFromUrl;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasDefaultTenant, HasTenants, FilamentUser
{
    use HasApiTokens;
    use HasConnectedAccounts;
    use HasRoles;
    use HasFactory;
    use HasProfilePhoto {
        HasProfilePhoto::profilePhotoUrl as getPhotoUrl;
    }
    use Notifiable;
    use SetsProfilePhotoFromUrl;
    use TwoFactorAuthenticatable;
    use HasTeams;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * Get the URL to the user's profile photo.
     */
    public function profilePhotoUrl(): Attribute
    {
        return filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)
            ? Attribute::get(fn () => $this->profile_photo_path)
            : $this->getPhotoUrl();
    }

    /**
     * @return array<Model> | Collection
     */
    public function getTenants(Panel $panel): array|Collection
    {
        return $this->ownedTeams;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return true; //$this->ownedTeams->contains($tenant);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        //        return $this->hasVerifiedEmail();
        return true;
    }

    public function canAccessFilament(): bool
    {
        //        return $this->hasVerifiedEmail();
        return true;
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->latestTeam;
    }

    public function latestTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('joined_at', 'left_at', 'last_read_at')
            ->withTimestamps();
    }

    public function activeConversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->wherePivotNull('left_at')
            ->withPivot('joined_at', 'last_read_at')
            ->withTimestamps();
    }
    // Friend request relationships
    public function sentFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    public function receivedFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'addressee_id');
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'requester_id', 'addressee_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps()
            ->union(
                $this->belongsToMany(User::class, 'friendships', 'addressee_id', 'requester_id')
                    ->wherePivot('status', 'accepted')
                    ->withTimestamps()
            );
    }

    // Follower relationships
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    // Friend request methods
    public function sendFriendRequest(User $user)
    {
        if ($this->id === $user->id) {
            return false;
        }

        if ($this->hasFriendRequestPending($user) || $this->isFriendWith($user)) {
            return false;
        }

        return Friendship::create([
            'requester_id' => $this->id,
            'addressee_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function acceptFriendRequest(User $user)
    {
        $friendship = Friendship::where('requester_id', $user->id)
            ->where('addressee_id', $this->id)
            ->where('status', 'pending')
            ->first();

        if ($friendship) {
            $friendship->update(['status' => 'accepted']);
            return $friendship;
        }

        return false;
    }

    public function rejectFriendRequest(User $user)
    {
        $friendship = Friendship::where('requester_id', $user->id)
            ->where('addressee_id', $this->id)
            ->where('status', 'pending')
            ->first();

        if ($friendship) {
            $friendship->update(['status' => 'declined']);
            return $friendship;
        }

        return false;
    }

    public function hasFriendRequestPending(User $user)
    {
        return Friendship::where(function ($query) use ($user) {
            $query->where('requester_id', $this->id)
                ->where('addressee_id', $user->id);
        })->orWhere(function ($query) use ($user) {
            $query->where('requester_id', $user->id)
                ->where('addressee_id', $this->id);
        })->where('status', 'pending')->exists();
    }

    public function isFriendWith(User $user)
    public function privacySettings()
    {
        return $this->hasOne(UserPrivacySetting::class);
    }

    public function isFriendsWith(User $user): bool
    {
        return Friendship::where(function ($query) use ($user) {
            $query->where('requester_id', $this->id)
                ->where('addressee_id', $user->id);
        })->orWhere(function ($query) use ($user) {
            $query->where('requester_id', $user->id)
                ->where('addressee_id', $this->id);
        })->where('status', 'accepted')->exists();
    }

    // Follower methods
    public function follow(User $user)
    {
        if ($this->id === $user->id) {
            return false;
        }

        if ($this->isFollowing($user)) {
            return false;
        }

        return Follower::create([
            'follower_id' => $this->id,
            'following_id' => $user->id,
        ]);
    }

    public function unfollow(User $user)
    {
        return Follower::where('follower_id', $this->id)
            ->where('following_id', $user->id)
            ->delete();
    }

    public function isFollowing(User $user)
    {
        return Follower::where('follower_id', $this->id)
            ->where('following_id', $user->id)
            ->exists();
    }

    public function isFollowedBy(User $user)
    {
        return Follower::where('follower_id', $user->id)
            ->where('following_id', $this->id)
            ->exists();
    }

    // Count methods
    public function getFriendsCountAttribute()
    {
        return Friendship::where(function ($query) {
            $query->where('requester_id', $this->id)
                ->orWhere('addressee_id', $this->id);
        })->where('status', 'accepted')->count();
    }

    public function getFollowersCountAttribute()
    {
        return $this->followers()->count();
    }

    public function getFollowingCountAttribute()
    {
        return $this->following()->count();
    }

    // Group relationships
    public function ownedGroups()
    {
        return $this->hasMany(Group::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members')
            ->withPivot('role', 'status')
            ->withTimestamps()
            ->wherePivot('status', 'approved');
    }

    public function isMemberOf(Group $group): bool
    {
        return $this->groups()->where('group_id', $group->id)->exists();
    }

    public function isAdminOf(Group $group): bool
    {
        return $this->groups()
            ->where('group_id', $group->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    /**
     * Get or create privacy settings for the user.
     */
    public function getPrivacySettings(): UserPrivacySetting
    {
        return $this->privacySettings()->firstOrCreate(['user_id' => $this->id]);
    }
}
