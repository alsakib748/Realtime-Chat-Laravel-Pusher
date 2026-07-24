<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'avatar', 'is_online', 'last_seen_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_online' => 'boolean',
            'last_seen_at' => 'datetime'
        ];
    }

    public function chatRooms(): BelongsToMany
    {
        return $this->belongsToMany(ChatRoom::class, 'chat_room_users')
            ->withPivot(['joined_at', 'last_read_at', 'is_admin', 'is_active'])
            ->withTimestamps();
    }


    public function activeChatRooms(): BelongsToMany
    {
        return $this->chatRooms()->wherePivot('is_active', true);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function isOnline(): bool
    {
        return $this->is_online && $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(5));
    }

    public function setOnline(): void
    {
        $this->update([
            'is_online' => true,
            'last_seen_at' => now()
        ]);
    }

    public function setOffline(): void
    {
        $this->update([
            'is_online' => false,
            'last_seen_at' => now()
        ]);
    }

    public function getInitials(): string
    {
        $names = explode(' ', $this->name);
        $initials = '';

        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }

        return substr($initials, 0, 2);
    }

    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=3B82F6&color=ffffff';
    }

}
