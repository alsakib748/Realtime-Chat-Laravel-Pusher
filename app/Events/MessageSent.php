<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message->load('user', 'attachments');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('chat-room.' . $this->message->chat_room_id),
        ];
    }


    public function boradcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->getDisplayContent(),
                'type' => $this->message->type,
                'metadata' => $this->message->metadata,
                'created_at' => $this->message->created_at->toISOString(),
                'edited_at' => $this->message->edited_at?->toISOString(),
                'user' => [
                    'id' => $this->message->user->id,
                    'name' => $this->message->user->name,
                    'avatar' => $this->message->user->getAvatarUrl(),
                    'initials' => $this->message->user->getInitials()
                ],
                'attachments' => $this->message->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'filename' => $attachment->original_filename,
                        'size' => $attachment->getFileSizeFormatted(),
                        'url' => $attachment->getUrl(),
                        'thumbnail_url' => $attachment->getThumbnailUrl(),
                        'is_image' => $attachment->isImage()
                    ];
                })
            ]
        ];
    }
}