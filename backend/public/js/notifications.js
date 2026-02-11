class NotificationManager {
    constructor() {
        this.baseUrl = window.baseUrl || '/chat-app/backend/public/';
        if (!this.baseUrl.endsWith('/')) this.baseUrl += '/';

        this.notifications = [];
        this.maxNotifications = 50;
        this.container = document.getElementById("notifications-list");
        this.badge = document.getElementById("total-unread-badge");
        this.initialized = false;
        
        this.recentlyProcessedMessages = new Map();
        this.userNotificationMap = new Map(); 
        this.groupInfoCache = new Map();

        this.init();
    }

    init() {
        if (this.initialized) return;
        this.loadNotifications();
        this.setupEventListeners();
        this.setupSocketListeners();
        this.buildUserNotificationMap();
        this.renderNotifications();
        this.updateBadge();
        this.initialized = true;
    }

    buildUserNotificationMap() {
        this.userNotificationMap.clear();
        for (const notification of this.notifications) {
            if (notification.userId || notification.chatId) {
                const key = notification.isGroup ? `group_${notification.chatId}` : `user_${notification.userId}`;
                if (!this.userNotificationMap.has(key) || 
                    new Date(notification.timestamp) > new Date(this.userNotificationMap.get(key).timestamp)) {
                    this.userNotificationMap.set(key, notification);
                }
            }
        }
    }

    cleanupDuplicateNotifications() {
        const userGroupMap = new Map();
        const cleaned = [];
        const sorted = [...this.notifications].sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

        for (const notif of sorted) {
            const key = notif.isGroup ? `group_${notif.chatId}` : `user_${notif.userId}`;
            if (!userGroupMap.has(key)) {
                userGroupMap.set(key, true);
                cleaned.push(notif);
            }
        }
        this.notifications = cleaned;
    }

    loadNotifications() {
        try {
            const saved = localStorage.getItem("notifications");
            if (saved) {
                this.notifications = JSON.parse(saved);
                this.notifications = this.notifications.filter(notif => 
                    notif.title && !notif.title.includes("System Test") && 
                    !notif.message.includes("Socket")
                );
                this.cleanupDuplicateNotifications();
            }
        } catch (error) {
            this.notifications = [];
        }
    }

    saveNotifications() {
        try {
            localStorage.setItem("notifications", JSON.stringify(this.notifications));
        } catch (error) {}
    }

    setupEventListeners() {
        const clearBtn = document.getElementById("clear-notifications-btn");
        if (clearBtn) {
            clearBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                this.clearAllNotifications();
            });
        }

        const dropdownToggle = document.getElementById('notificationsDropdown');
        if (dropdownToggle) {
            const dropdownParent = dropdownToggle.closest('.dropdown');
            if (dropdownParent) {
                dropdownParent.addEventListener('show.bs.dropdown', () => {
                    this.markAllAsRead();
                });
            }
        }
    }

    setupSocketListeners() {
        if (!window.socket) return;

        window.socket.on("receive_message", (message) => {
            const currentUserId = window.currentUser?.id;
            if (!currentUserId || message.sender_id == currentUserId) return;

            const activeChat = window.activeChat || {};
            const isActive = (activeChat.type === "private" && message.sender_id == activeChat.id) ||
                             (activeChat.type === "group" && message.is_group && message.group_id == activeChat.id);

            if (isActive && document.visibilityState === "visible") return;

            this.processNewMessage(message);
        });

        window.socket.on("reaction", (data) => {
            if (data.user_id != window.currentUser?.id) {
                this.addReactionNotification(data);
            }
        });
    }

    processNewMessage(message) {
        const senderName = message.nickname || message.username || "Someone";
        const key = message.is_group ? `group_${message.group_id}` : `user_${message.sender_id}`;
        
        let msgPreview = message.type === "text" ? message.content : `Sent a ${message.type}`;
        if (msgPreview.length > 50) msgPreview = msgPreview.substring(0, 47) + "...";

        const existing = this.userNotificationMap.get(key);

        if (existing && !existing.read) {
            existing.messageCount = (existing.messageCount || 1) + 1;
            existing.message = msgPreview;
            existing.timestamp = new Date().toISOString();
        } else {
            this.addNotification(
                message.is_group ? `${senderName} in ${message.group_name || 'Group'}` : senderName,
                msgPreview,
                "message",
                message.is_group ? "group" : "private",
                message.is_group ? message.group_id : message.sender_id,
                message.sender_id,
                message.username,
                null,
                message.is_group ? message.group_image : message.avatar,
                !!message.is_group
            );
        }

        this.playNotificationSound();
        this.renderNotifications();
        this.updateBadge();
    }

    addNotification(title, message, type, chatType, chatId, userId, username, reaction, avatar, isGroup) {
        const notification = {
            id: Date.now(),
            title, message, type, chatType, chatId, userId,
            username, reaction, avatar, isGroup,
            read: false,
            messageCount: 1,
            timestamp: new Date().toISOString()
        };

        this.notifications.unshift(notification);
        if (this.notifications.length > this.maxNotifications) this.notifications.pop();

        const key = isGroup ? `group_${chatId}` : `user_${userId}`;
        this.userNotificationMap.set(key, notification);

        this.saveNotifications();
        return notification;
    }

    addReactionNotification(data) {
        this.addNotification(
            `${data.username || 'Someone'} reacted ${data.reaction || '👍'}`,
            data.message_preview || "Your message",
            "reaction",
            data.is_group ? "group" : "private",
            data.is_group ? data.group_id : data.user_id,
            data.user_id,
            data.username,
            data.reaction,
            data.avatar,
            !!data.is_group
        );
        this.playNotificationSound();
        this.renderNotifications();
        this.updateBadge();
    }

    renderNotifications() {
    if (!this.container) this.container = document.getElementById("notifications-list");
    if (!this.container) return;

    this.container.innerHTML = "";

    if (this.notifications.length === 0) {
        this.container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-bell-slash"></i></div>
                <h2>Clean slate!</h2>
                <p>No new notifications at the moment.</p>
            </div>`;
        return;
    }

    this.notifications.forEach(notif => {
        const item = document.createElement("div");
        item.className = `notification-card ${notif.read ? 'read' : 'unread'}`;
        
        const folder = notif.isGroup ? 'uploads/groups/' : 'public/uploads/avatars/';
        const defaultAvatar = `${this.baseUrl}public/uploads/avatars/default-avatar.png`;
        const avatarSrc = notif.avatar ? `${this.baseUrl}${folder}${notif.avatar}` : defaultAvatar;

        item.innerHTML = `
            <div class="notif-avatar-wrapper">
                <img src="${avatarSrc}" class="notif-avatar" onerror="this.onerror=null; this.src='${defaultAvatar}';">
                ${notif.messageCount > 1 && !notif.read ? `<span class="notif-badge">${notif.messageCount}</span>` : ''}
            </div>
            <div class="notif-details">
                <div class="notif-header-row">
                    <span class="notif-title">${notif.title}</span>
                    <span class="notif-time">${this.formatDetailedTime(notif.timestamp)}</span>
                </div>
                <div class="notif-msg">${notif.message}</div>
            </div>
        `;

        item.onclick = (e) => {
            this.markAsRead(notif.chatId, notif.chatType);
            if (typeof window.openChat === 'function') {
                window.openChat(notif.chatId, notif.chatType);
            } else {
                window.location.href = `${this.baseUrl}chat/${notif.chatType}/${notif.chatId}`;
            }
        };

        this.container.appendChild(item);
    });
}
    formatDetailedTime(ts) {
        const date = new Date(ts);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    updateBadge() {
        const unread = this.getUnreadCount();
        if (this.badge) {
            this.badge.textContent = unread;
            this.badge.style.display = unread > 0 ? 'inline-block' : 'none';
        }
    }

    getUnreadCount() {
        return this.notifications.filter(n => !n.read).length;
    }

    markAllAsRead() {
        this.notifications.forEach(n => n.read = true);
        this.saveNotifications();
        this.updateBadge();
        this.renderNotifications();
    }

    markAsRead(chatId, chatType) {
        this.notifications.forEach(n => {
            if (n.chatId == chatId && n.chatType == chatType) n.read = true;
        });
        this.saveNotifications();
        this.updateBadge();
        this.renderNotifications();
    }

    clearAllNotifications() {
        this.notifications = [];
        this.userNotificationMap.clear();
        this.saveNotifications();
        this.renderNotifications();
        this.updateBadge();
    }

    playNotificationSound() {
        try {
            if (!this.audioElement) {
                this.audioElement = new Audio(`${this.baseUrl}sounds/notification.mp3`);
            }
            this.audioElement.play().catch(e => {});
        } catch (e) {}
    }
}

document.addEventListener("DOMContentLoaded", () => {
    if (!window.notificationManager) {
        window.notificationManager = new NotificationManager();
    }
});