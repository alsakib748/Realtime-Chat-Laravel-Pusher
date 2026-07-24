<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Broadcasting routes with auth middleware
Route::post('/broadcasting/auth', [\Illuminate\Broadcasting\BroadcastController::class, 'authenticate'])
    ->middleware('auth')
    ->name('broadcasting.auth');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/room/{chatRoom}', [ChatController::class, 'showRoom'])->name('chat.room');
    Route::post('/chat/room/{chatRoom}/message', [ChatController::class, 'sendMessage'])->name('chat.message');
    Route::post('/chat/room/{chatRoom}/typing', [ChatController::class, 'sendTyping'])->name('chat.typing');
    Route::post('/chat/private/{otherUser}', [ChatController::class, 'createPrivateRoom'])->name('chat.private');
    Route::get('/chat/attachment/{attachment}/download', [ChatController::class, 'downloadAttachment'])->name('chat.attachment.download');
});

require __DIR__ . '/auth.php';