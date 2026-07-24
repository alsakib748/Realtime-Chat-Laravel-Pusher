<div class="h-full" x-data="chatApp()" x-init="init()">
    <div class="flex h-full">
        {{-- Sidebar remains the same --}}
        <div class="w-80 bg-gray-50 shadow-lg flex flex-col border-r border-gray-200">
            <div class="p-4 border-b bg-white flex-shrink-0">
                <div class="flex items-center justify-between mb-2">
                    <h1 class="text-lg font-semibold text-gray-800">Chat Rooms</h1>
                    <div class="flex items-center space-x-2">
                        <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" class="w-6 h-6 rounded-full">
                        <span class="text-sm text-gray-600 hidden sm:block">{{ $user->name }}</span>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                <div class="p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Rooms</h3>

                    @if($generalRoom)
                        <div class="space-y-2 mb-4">
                            <button
                                @click="loadRoom({{ $generalRoom->id }}, 'General Chat')"
                                data-general-room="{{ $generalRoom->id }}"
                                class="w-full text-left p-3 rounded-lg hover:bg-white border transition-colors duration-200"
                                :class="currentRoom?.id == {{ $generalRoom->id }} ? 'bg-blue-50 border-blue-200' : 'border-gray-200'"
                            >
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-white font-semibold text-sm">#</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-gray-800 truncate">General Chat</h4>
                                        <p class="text-xs text-gray-600 truncate">Public room for everyone</p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Users List --}}
                <div class="border-t p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Users</h3>
                    <div class="space-y-1">
                        @foreach($allUsers as $otherUser)
                            <div data-user-id="{{ $otherUser->id }}"
                                 class="flex items-center space-x-3 p-2 rounded-lg hover:bg-white cursor-pointer transition-colors duration-200"
                                 @click="startPrivateChat({{ $otherUser->id }}, '{{ $otherUser->name }}')">
                                <div class="relative flex-shrink-0">
                                    <img src="{{ $otherUser->getAvatarUrl() }}" alt="{{ $otherUser->name }}" class="w-8 h-8 rounded-full">
                                    <div class="status-dot absolute -bottom-1 -right-1 w-3 h-3 {{ $otherUser->isOnline() ? 'bg-green-400' : 'bg-gray-400' }} rounded-full border-2 border-white"></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-gray-800 truncate">{{ $otherUser->name }}</h4>
                                    <p class="text-xs text-gray-600 truncate">
                                        {{ $otherUser->isOnline() ? 'Online' : 'Offline' }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Chat Area --}}
        <div class="flex-1 flex flex-col bg-white min-w-0">
            <div class="p-4 border-b bg-gray-50 flex-shrink-0">
                <h2 class="text-lg font-semibold text-gray-800 truncate" x-text="currentRoom ? currentRoom.name : 'Select a chat room'"></h2>
            </div>

            <div id="messages-container" class="flex-1 overflow-y-auto p-4 space-y-4" x-show="currentRoom" style="min-height: 0;">
                <template x-for="(message, index) in messages" :key="'message_' + (message.uniqueKey || message.id || index) + '_' + index">
                    <div class="flex space-x-3" x-show="message && message.user">
                        <img :src="message.user?.avatar" :alt="message.user?.name" class="w-8 h-8 rounded-full flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <h4 class="font-medium text-gray-800" x-text="message.user?.name"></h4>
                                <span class="text-xs text-gray-500" x-text="formatTime(message.created_at)"></span>
                            </div>
                            <div class="mt-1">
                                <p class="text-gray-700 break-words" x-text="message.content" x-show="message.content"></p>

                                <!-- File Attachments -->
                                <template x-for="attachment in (message.attachments || [])">
                                    <div class="mt-2 max-w-sm">
                                        <!-- Image Attachment -->
                                        <div x-show="attachment.is_image" class="relative">
                                            <img :src="attachment.thumbnail_url || attachment.url"
                                                 :alt="attachment.filename"
                                                 class="rounded-lg cursor-pointer hover:opacity-90 transition-opacity max-w-full h-auto"
                                                 @click="window.open(attachment.url, '_blank')"
                                                 style="max-height: 300px;">
                                            <div x-show="attachment.uploading" class="absolute inset-0 bg-black bg-opacity-50 rounded-lg flex items-center justify-center">
                                                <div class="text-white text-sm">Uploading...</div>
                                            </div>
                                        </div>

                                        <!-- File Attachment -->
                                        <div x-show="!attachment.is_image"
                                             class="bg-gray-100 rounded-lg p-3 border flex items-center space-x-3 hover:bg-gray-200 transition-colors cursor-pointer"
                                             @click="downloadFile(attachment.url, attachment.filename)">
                                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm8 2a1 1 0 000 2h3a1 1 0 100-2h-3zM4 8a1 1 0 000 2h3a1 1 0 000-2H4z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-sm font-medium text-gray-800 truncate" x-text="attachment.filename"></h4>
                                                <p class="text-xs text-gray-600" x-text="attachment.size"></p>
                                            </div>
                                            <div x-show="attachment.uploading" class="text-xs text-blue-600">Uploading...</div>
                                            <div x-show="!attachment.uploading" class="text-xs text-gray-500">Click to download</div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="getTypingText()" class="flex items-center space-x-2 text-gray-500 text-sm">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                    <span x-text="getTypingText()"></span>
                </div>
            </div>

            <!-- Message Input Area -->
            <div class="p-4 border-t bg-white flex-shrink-0" x-show="currentRoom">
                <!-- File Preview Modal -->
                <div x-show="showFileModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="clearSelectedFile()">
                    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 class="text-lg font-semibold mb-4">Send File</h3>

                        <div x-show="filePreview" class="mb-4">
                            <img :src="filePreview" class="max-w-full h-auto rounded-lg" style="max-height: 200px;">
                        </div>

                        <div x-show="selectedFile" class="mb-4">
                            <p class="text-sm text-gray-600"><strong>File:</strong> <span x-text="selectedFile?.name"></span></p>
                            <p class="text-sm text-gray-600"><strong>Size:</strong> <span x-text="selectedFile ? formatFileSize(selectedFile.size) : ''"></span></p>
                        </div>

                        <input type="text" x-model="messageInput" placeholder="Add a message (optional)..."
                               class="w-full p-2 border border-gray-300 rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500">

                        <div class="flex justify-end space-x-2">
                            <button @click="clearSelectedFile()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                            <button @click="sendMessage()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Send</button>
                        </div>
                    </div>
                </div>

                <!-- Message Input -->
                <div class="flex space-x-2 items-end">
                    <input type="file" id="file-input" @change="handleFileSelect($event)" class="hidden" accept="*/*">

                    <button @click="document.getElementById('file-input').click()"
                            class="p-3 text-gray-500 hover:text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"/>
                        </svg>
                    </button>

                    <input type="text" x-model="messageInput" @keyup.enter="sendMessage" @input="handleTyping" @blur="stopTyping"
                           placeholder="Type your message..."
                           class="flex-1 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                    <button @click="sendMessage" :disabled="!messageInput.trim() && !selectedFile"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Send
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.currentUserId = {{ $user->id }};
</script>
