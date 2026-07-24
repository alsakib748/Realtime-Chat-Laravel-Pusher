import axios from "axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";
import Alpine from "alpinejs";

window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.Pusher = Pusher;
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    encrypted: true,
    forceTLS: false,
});

Alpine.data("chatApp", () => ({
    currentRoom: null,
    _messages: [],
    messageInput: "",
    users: [],
    typingUsers: new Set(),
    isTyping: false,
    typingTimeout: null,
    onlineUsers: new Set(),
    roomChannel: null,
    previousRoomId: null,
    messageCounter: 0,

    selectedFile: null,
    filePreview: null,
    showFileModal: false,
    uploadProgress: 0,
    isUploading: false,

    get messages() {
        if (!Array.isArray(this._messages)) {
            this._messages = [];
        }
        return this._messages;
    },

    set messages(value) {
        this._messages = Array.isArray(value) ? value : [];
    },

    init() {
        this.messages = [];
        this.typingUsers = new Set();

        setTimeout(() => {
            this.loadGeneralRoom();
            this.setupUserStatusChannel();
        }, 100);
    },

    loadGeneralRoom() {
        const generalRoom = document.querySelector("[data-general-room]");

        if (generalRoom) {
            const roomId = generalRoom.dataset.generalRoom;
            this.loadRoom(roomId, "General Chat");
        } else {
            this.currentRoom = { id: 1, name: "General Chat" };
            this.messages = [];
            this.setupRoomChannel(1);
        }
    },

    setupUserStatusChannel() {
        try {
            window.Echo.channel("users-status").listen(
                "UserStatusChanged",
                (e) => {
                    this.updateUserStatus(e.user);
                },
            );
        } catch (error) {
            console.error("Error setting up user status channel:", error);
        }
    },

    updateUserStatus(user) {
        if (user.is_online) {
            this.onlineUsers.add(user.id);
        } else {
            this.onlineUsers.delete(user.id);
        }

        const userElement = document.querySelector(
            `[data-user-id="${user.id}"]`,
        );
        if (userElement) {
            const statusDot = userElement.querySelector(".status-dot");
            if (statusDot) {
                statusDot.className = user.is_online
                    ? "status-dot absolute -bottom-1 -right-1 w-3 h-3 bg-green-400 rounded-full border-2 border-white"
                    : "status-dot absolute -bottom-1 -right-1 w-3 h-3 bg-gray-400 rounded-full border-2 border-white";
            }
        }
    },

    loadRoom(roomId, roomName) {
        this.currentRoom = { id: parseInt(roomId), name: roomName };
        this.typingUsers = new Set();

        if (this.previousRoomId && this.roomChannel) {
            try {
                window.Echo.leaveChannel(`chat-room.${this.previousRoomId}`);
                this.roomChannel = null;
            } catch (err) {
                console.warn("Error leaving previous room:", err);
            }
        }

        this.previousRoomId = roomId;

        fetch(`/chat/room/${roomId}`)
            .then((response) => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then((data) => {
                this.messages = Array.isArray(data.messages)
                    ? data.messages
                    : [];
                this.setupRoomChannel(roomId);
                setTimeout(() => this.scrollToBottom(), 100);
            })
            .catch((error) => {
                console.error("Error loading room:", error);
                this.messages = [];
                this.setupRoomChannel(roomId);
            });
    },

    setupRoomChannel(roomId) {
        if (this.roomChannel) {
            try {
                this.roomChannel.stopListening("MessageSent");
                this.roomChannel.stopListening("UserTyping");
                window.Echo.leaveChannel(`chat-room.${roomId}`);
                this.roomChannel = null;
            } catch (err) {
                console.warn("Error cleaning up existing channel:", err);
            }
        }

        try {
            this.roomChannel = window.Echo.join(`chat-room.${roomId}`)
                .here((users) => {
                    users.forEach((user) => this.onlineUsers.add(user.id));
                })
                .joining((user) => {
                    this.onlineUsers.add(user.id);
                    this.updateUserStatus({ ...user, is_online: true });
                })
                .leaving((user) => {
                    this.onlineUsers.delete(user.id);
                    this.updateUserStatus({ ...user, is_online: false });
                    this.typingUsers.delete(user.name);
                })
                .listen("MessageSent", (e) => {
                    if (e.message.user.id !== window.currentUserId) {
                        const currentMessages = [...this.messages];
                        e.message.uniqueKey = ++this.messageCounter;
                        currentMessages.push(e.message);
                        this.messages = currentMessages;
                        setTimeout(() => this.scrollToBottom(), 50);
                    }
                })
                .listen("UserTyping", (e) => {
                    if (e.user.id === window.currentUserId) {
                        return;
                    }

                    const userName = e.user.name;
                    const newTypingUsers = new Set(this.typingUsers);

                    if (e.typing) {
                        newTypingUsers.add(userName);
                    } else {
                        newTypingUsers.delete(userName);
                    }

                    this.typingUsers = newTypingUsers;
                });
        } catch (error) {
            console.error("Error setting up room channel:", error);
        }
    },

    sendMessage() {
        if (
            (!this.messageInput.trim() && !this.selectedFile) ||
            !this.currentRoom
        )
            return;

        const message = this.messageInput.trim();
        const hasFile = this.selectedFile !== null;

        const tempMessage = {
            id: "temp-" + Date.now(),
            content:
                message ||
                (hasFile ? `Shared a file: ${this.selectedFile.name}` : ""),
            type: hasFile
                ? this.selectedFile.type.startsWith("image/")
                    ? "image"
                    : "file"
                : "text",
            user: {
                id: window.currentUserId,
                name:
                    document.querySelector('meta[name="user-name"]')?.content ||
                    "You",
                avatar:
                    document.querySelector('meta[name="user-avatar"]')
                        ?.content ||
                    "https://ui-avatars.com/api/?name=" +
                        encodeURIComponent(
                            document.querySelector('meta[name="user-name"]')
                                ?.content || "User",
                        ) +
                        "&background=3b82f6&color=fff",
            },
            created_at: new Date().toISOString(),
            uniqueKey: ++this.messageCounter,
            attachments: hasFile
                ? [
                      {
                          id: "temp",
                          filename: this.selectedFile.name,
                          size: this.formatFileSize(this.selectedFile.size),
                          url: this.filePreview,
                          is_image: this.selectedFile.type.startsWith("image/"),
                          uploading: true,
                      },
                  ]
                : [],
        };

        const currentMessages = [...this.messages];
        currentMessages.push(tempMessage);
        this.messages = currentMessages;

        this.messageInput = "";
        this.stopTyping();
        this.showFileModal = false;
        setTimeout(() => this.scrollToBottom(), 50);

        const formData = new FormData();
        if (message) formData.append("content", message);
        if (hasFile) formData.append("attachment", this.selectedFile);

        this.isUploading = true;
        this.uploadProgress = 0;

        fetch(`/chat/room/${this.currentRoom.id}/message`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
            },
            body: formData,
        })
            .then((response) => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then((data) => {
                const messageIndex = this.messages.findIndex(
                    (msg) => msg.id === tempMessage.id,
                );
                if (messageIndex !== -1) {
                    const updatedMessages = [...this.messages];
                    updatedMessages[messageIndex] = {
                        ...tempMessage,
                        id: data.message_id,
                        attachments: tempMessage.attachments.map((att) => ({
                            ...att,
                            uploading: false,
                        })),
                    };
                    this.messages = updatedMessages;
                }
            })
            .catch((error) => {
                console.error("Error sending message:", error);
                const failedMessageIndex = this.messages.findIndex(
                    (msg) => msg.id === tempMessage.id,
                );
                if (failedMessageIndex !== -1) {
                    const updatedMessages = [...this.messages];
                    updatedMessages.splice(failedMessageIndex, 1);
                    this.messages = updatedMessages;
                }
                this.messageInput = message;
            })
            .finally(() => {
                this.isUploading = false;
                this.uploadProgress = 0;
                this.clearSelectedFile();
            });
    },

    handleFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (file.size > 10 * 1024 * 1024) {
            alert("File size must be less than 10MB");
            return;
        }

        this.selectedFile = file;

        if (file.type.startsWith("image/")) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.filePreview = e.target.result;
                this.showFileModal = true;
            };
            reader.readAsDataURL(file);
        } else {
            this.filePreview = null;
            this.showFileModal = true;
        }
    },

    clearSelectedFile() {
        this.selectedFile = null;
        this.filePreview = null;
        this.showFileModal = false;
        const fileInput = document.getElementById("file-input");
        if (fileInput) fileInput.value = "";
    },

    formatFileSize(bytes) {
        if (bytes === 0) return "0 Bytes";
        const k = 1024;
        const sizes = ["Bytes", "KB", "MB", "GB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    },

    downloadFile(attachmentUrl, filename) {
        const link = document.createElement("a");
        link.href = attachmentUrl;
        link.download = filename;
        link.click();
    },

    handleTyping() {
        if (!this.isTyping && this.currentRoom) {
            this.isTyping = true;
            this.sendTypingStatus(true);
        }

        clearTimeout(this.typingTimeout);
        this.typingTimeout = setTimeout(() => this.stopTyping(), 1500);
    },

    stopTyping() {
        if (this.isTyping && this.currentRoom) {
            this.isTyping = false;
            this.sendTypingStatus(false);
        }
        clearTimeout(this.typingTimeout);
    },

    sendTypingStatus(typing) {
        if (!this.currentRoom) return;

        fetch(`/chat/room/${this.currentRoom.id}/typing`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
            },
            body: JSON.stringify({ typing }),
        }).catch((error) =>
            console.error("Error sending typing status:", error),
        );
    },

    startPrivateChat(userId, userName) {
        fetch(`/chat/private/${userId}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
            },
        })
            .then((response) => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then((data) => {
                this.loadRoom(data.room.id, data.room.name);
            })
            .catch((error) => {
                console.error("Error creating private room:", error);
            });
    },

    scrollToBottom() {
        const container = document.getElementById("messages-container");
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    },

    formatTime(timestamp) {
        return new Date(timestamp).toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
        });
    },

    getTypingText() {
        const users = Array.from(this.typingUsers);
        if (!users.length) return "";
        if (users.length === 1) return `${users[0]} is typing...`;
        if (users.length === 2)
            return `${users[0]} and ${users[1]} are typing...`;
        return `${users[0]} and ${users.length - 1} others are typing...`;
    },
}));

window.Alpine = Alpine;
Alpine.start();

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import "./echo";
