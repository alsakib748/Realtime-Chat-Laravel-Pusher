<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatRoomUser extends Model
{

    protected $fillable = [
        'chat_room_id',
        'user_id',
        'joined_at',
        'last_read_at',
        'is_admin',
        'is_active'
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function chatRoom(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasUnreadMessages(): bool
    {
        if (!$this->last_read_at) {
            return true;
        }

        return $this->chatRoom->messages()
            ->where('created_at', '>', $this->last_read_at)
            ->where('user_id', '!=', $this->user_id)
            ->exists();
    }

    public function getUnreadMessagesCount(): int
    {
        if (!$this->last_read_at) {
            return $this->chatRoom->messages()
                ->where('user_id', '!=', $this->user_id)
                ->count();
        }

        return $this->chatRoom->messages()
            ->where('created_at', '>', $this->last_read_at)
            ->where('user_id', '!=', $this->user_id)
            ->count();
    }

    public function markAsRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function makeAdmin(): void
    {
        $this->update(['is_admin' => true]);
    }

    public function removeAdmin(): void
    {
        $this->update(['is_admin' => false]);
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

}
