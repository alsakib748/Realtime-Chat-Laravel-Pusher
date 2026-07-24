<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\ChatRoom;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $chatRoom;
    public $typing;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, ChatRoom $chatRoom, bool $typing)
    {
        $this->user = $user;
        $this->chatRoom = $chatRoom;
        $this->typing = $typing;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('chat-room.' . $this->chatRoom->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'initials' => $this->user->getInitials()
            ],
            'typing' => $this->typing,
            'chat_room_id' => $this->chatRoom->id
        ];
    }
}