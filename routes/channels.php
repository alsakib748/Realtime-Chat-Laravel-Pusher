<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ChatRoom;

Broadcast::channel('users-status', function ($user) {
    return $user ? ['id' => $user->id, 'name' => $user->name] : false;
});

Broadcast::channel('chat-room.{chatRoomId}', function ($user, $chatRoomId) {

    $chatRoom = ChatRoom::find($chatRoomId);

    if (!$chatRoom) {
        return false;
    }

    $userInRoom = $chatRoom->users()->where('user_id', $user->id)->where('is_active', true)->exists();

    if ($userInRoom) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->getAvatarUrl(),
            'initials' => $user->getInitials(),
            'is_online' => $user->isOnline()
        ];
    }

    return false;

});
