<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\ChatRoom;
use Intervention\Image\Image;
use Storage;

class ChatController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        $chatRooms = $user->activeChatRooms()
            ->with(['latestMessage.user', 'users'])
            ->orderByDesc('last_message_at')
            ->get();

        $generalRoom = $chatRooms->where('type', 'general')->first();

        $allUsers = User::where('id', '!=', $user->id)
            ->select(['id', 'name', 'is_online', 'last_seen_at', 'avatar'])
            ->orderBy('is_online', 'desc')
            ->orderBy('name')
            ->get();

        return view('chat.index', compact('chatRooms', 'generalRoom', 'allUsers', 'user'));
    }

    public function showRoom(ChatRoom $chatRoom)
    {
        $user = Auth::user();

        if (!$chatRoom->users()->where('user_id', $user->id)->where('is_active', true)->exists()) {
            abort(403, 'You do not have access to this chat room');
        }

        $messages = $chatRoom->messages()
            ->with(['user', 'attachements'])
            ->orderBy('created_at')
            ->take(50)
            ->get();

        $chatRoom->users()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        return response()->json([
            'room' => [
                'id' => $chatRoom->id,
                'name' => $chatRoom->getDisplayName(),
                'type' => $chatRoom->type
            ],
            'messages' => $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->getDisplayContent(),
                    'type' => $message->type,
                    'metadata' => $message->metadata,
                    'created_at' => $message->create_at->format('Y-m-d H:i:s'),
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'avatar' => $message->user->getAvatarUrl(),
                        'initials' => $message->user->getInitials()
                    ],
                    'attachments' => $message->attachments->map(function ($attachment) {
                        return [
                            'id' => $attachment->id,
                            'filename' => $attachment->original_filename,
                            'size' => $attachment->getFileSizeFormatted(),
                            'url' => $attachment->getUrl(),
                            'thumbnail_url' => $attachment->getThumbnailUrl(),
                            'is_image' => $attachment->isImage()
                        ];
                    })
                ];
            })
        ]);

    }

    public function sendMessage(Request $request, ChatRoom $chatRoom)
    {
        $request->validate([
            'content' => 'required_without:attachmen|string|max:1000',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        $user = Auth::user();

        if (!$chatRoom->users()->where('user_id', $user->id)->where('is_active', true)->exists()) {
            abort(403, 'You do not have access to this chat room');
        }

        $messageType = 'text';
        $content = $request->content;
        $metadata = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $messageType = $this->getFileMessageType($file);

            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = "chat-attachments/{$chatRoom->id}/" . $filename;

            Storage::disk('public')->put($path, $file->getContent());

            if ($messageType === 'image') {
                $this->createImageThumbnail($file, $path);
            }

            $metadata = [
                'original_filename' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];

            $content = $content ?: 'Shared a file: ' . $file->getClientOriginalName();

        }

        $message = $chatRoom->messages()->create([
            'user_id' => $user->id,
            'content' => $content,
            'type' => $messageType,
            'metadata' => $metadata
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            MessageAttachment::create([
                'message_id' => $message->id,
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'file_hash' => hash_file('sha256', $file->getPathname())
            ]);
        }

        $message->load('user', 'attachments');
        $chatRoom->update(['last_message_at' => now()]);

        broadcast(new MessageSent($message));

        return response()->json([
            'message' => 'Message sent successfully',
            'message_id' => $message->id
        ]);

    }

    public function downloadAttachment(MessageAttachment $attachment)
    {
        $user = Auth::user();

        if (!$attachment->message->chatRoom->users()->where('user_id', $user->id)->where('is_active', true)->exists()) {
            abort(403, 'You do not have access to this file');
        }

        if (!Storage::disk('public')->exists($attachment->path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download($attachment->path, $attachment->original_filename);
    }

    private function getFileMessageType($file): string
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        return 'file';
    }

    private function createImageThumbnail($file, $path): void
    {
        try {
            $thumbnailPath = str_replace('.', '_thumb.', $path);

            $image = Image::read($file->getPathname());
            $image->cover(300, 300);

            Storage::disk('public')->put($thumbnailPath, $image->encode());

        } catch (\Exception $e) {
            \Log::warning('Failed to create thumbnail: ' . $e->getMessage());
        }
    }

    public function sendTyping(Request $request, ChatRoom $chatRoom)
    {
        $user = Auth::user();

        if (!$chatRoom->users()->where('user_id', $user->id)->where('is_active', true)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        broadcast(new UserTyping($user, $chatRoom, $request->boolean('typing')));

        return response()->json(['status' => 'ok']);
    }


    public function createPrivateRoom(User $otherUser)
    {
        $currentUser = Auth::user();

        if ($currentUser->id === $otherUser->id) {
            return response()->json(['error' => 'Cannot create private room with yourself'], 400);
        }

        $existingRoom = ChatRoom::where('type', 'private')
            ->whereHas('users', function ($query) use ($currentUser) {
                $query->where('user_id', $currentUser->id)->where('is_active', true);
            })
            ->whereHas('users', function ($query) use ($otherUser) {
                $query->where('user_id', $otherUser->id)->where('is_active', true);
            })
            ->whereRaw('(SELECT COUNT(*) FROM chat_room_users WHERE chat_room_id = chat_rooms.id AND is_active = 1) = 2')
            ->first();

        if ($existingRoom) {
            return response()->json([
                'room' => [
                    'id' => $existingRoom->id,
                    'name' => $otherUser->name,
                    'type' => 'private'
                ]
            ]);
        }

        $privateRoom = ChatRoom::create([
            'name' => $currentUser->name . ' & ' . $otherUser->name,
            'type' => 'private',
            'created_by' => $currentUser->id,
            'last_message_at' => now()
        ]);
        $privateRoom->users()->attach([
            $currentUser->id => [
                'joined_at' => now(),
                'is_active' => true
            ],
            $otherUser->id => [
                'joined_at' => now(),
                'is_active' => true
            ]
        ]);

        return response()->json([
            'room' => [
                'id' => $privateRoom->id,
                'name' => $otherUser->name,
                'type' => 'private'
            ]
        ]);
    }


}
