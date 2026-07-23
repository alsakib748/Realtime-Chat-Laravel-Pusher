<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatRoom extends Model
{

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_active',
        'created_by',
        'last_message_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_message_at' => 'datetime'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_room_users')
            ->withPivot(['joined_at', 'last_read_at', 'is_admin', 'is_active'])
            ->withTimestamps();
    }

    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('is_active', true);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->where('is_deleted', false);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany()->where('is_deleted', false);
    }

    public function isGeneral(): bool
    {
        return $this->type === 'general';
    }

    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function getDisplayName(): string
    {
        if ($this->isPrivate()) {
            return 'Private Chat';
        }

        return $this->name;
    }


}
