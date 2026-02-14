<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'receiver_id',
        'content',
        'encrypted_content',
        'encryption_key_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    protected $appends = ['decrypted_content'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeBetweenUsers($query, int $userId1, int $userId2)
    {
        return $query->where(function ($q) use ($userId1, $userId2) {
            $q->where('sender_id', $userId1)->where('receiver_id', $userId2);
        })->orWhere(function ($q) use ($userId1, $userId2) {
            $q->where('sender_id', $userId2)->where('receiver_id', $userId1);
        });
    }

    public function encrypt(string $content): void
    {
        $this->encrypted_content = Crypt::encryptString($content);
        $this->encryption_key_id = config('app.key');
        $this->content = null;
    }

    public function getDecryptedContentAttribute(): ?string
    {
        if ($this->encrypted_content) {
            try {
                return Crypt::decryptString($this->encrypted_content);
            } catch (\Exception $e) {
                return '[Encrypted message - unable to decrypt]';
            }
        }
        
        return $this->content;
    }

    public function addReaction(User $user, string $emoji): MessageReaction
    {
        return $this->reactions()->updateOrCreate(
            [
                'user_id' => $user->id,
                'emoji' => $emoji,
            ]
        );
    }

    public function removeReaction(User $user, string $emoji): void
    {
        $this->reactions()
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->delete();
    }

    public function hasAttachments(): bool
    {
        return $this->attachments()->exists();
    }
}
