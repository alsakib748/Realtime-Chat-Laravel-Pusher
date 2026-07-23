<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{

    protected $fillable = [
        'chat_room_id',
        'user_id',
        'content',
        'type',
        'metadata',
        'edited_at',
        'is_deleted'
    ];

    protected $casts = [
        'metadata' => 'array',
        'edited_at' => 'datetime',
        'is_deleted' => 'boolean'
    ];

    public function chatRoom(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function isEdited(): bool
    {
        return !is_null($this->edited_at);
    }

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    public function isSystem(): bool
    {
        return $this->type === 'system';
    }

    public function markAsDeleted(): void
    {
        $this->update(['is_deleted' => true]);
    }

    public function restore(): void
    {
        $this->update(['is_deleted' => false]);
    }

    public function getDisplayContent(): string
    {
        if ($this->is_deleted) {
            return 'This message was deleted';
        }

        if ($this->isSystem()) {
            return $this->content;
        }

        if ($this->isFile() || $this->isImage()) {
            return $this->metadata['original_filename'] ?? 'File attachment';
        }

        return $this->content;
    }

}
