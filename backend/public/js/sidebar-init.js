const SidebarManager = {
    init() {
        if (window.sidebarInitialized) return;
        window.sidebarInitialized = true;

        this.baseUrl = window.baseUrl || '/';
        if (!this.baseUrl.endsWith('/')) this.baseUrl += '/';

        this.cacheElements();
        this.bindEvents();
        this.setupSocketListeners();
        this.refreshAll();
        
        // Periodic notification check
        setInterval(() => this.updateNotificationBadge(), 30000);
    },

    cacheElements() {
        this.tabs = document.querySelectorAll(".nav-rail-btn[data-tab]");
        this.panes = document.querySelectorAll(".tab-pane");
        this.searchInput = document.getElementById("search-input");
        this.statusBtn = document.getElementById("status-dropdown-btn");
        this.statusMenu = document.getElementById("status-dropdown");
        this.notifBadge = document.getElementById("notifications-badge");
        
        this.settingsBtn = document.getElementById("settings-btn");
        this.settingsOverlay = document.getElementById("settings-overlay");
        
        this.profileBtn = document.getElementById("edit-profile-btn");
        this.profileOverlay = document.getElementById("edit-profile-modal");
    },

    bindEvents() {
        // Tab switching
        this.tabs.forEach(tab => {
            tab.addEventListener("click", () => this.switchTab(tab));
        });

        // Settings Handler (Smart Check: Only adds listener if HTML onclick is missing)
        if (this.settingsBtn && this.settingsOverlay) {
            if (!this.settingsBtn.getAttribute('onclick')) {
                this.settingsBtn.addEventListener("click", (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.settingsOverlay.classList.remove("hidden");
                });
            }
        }

        // Profile Handler (Smart Check: Only adds listener if HTML onclick is missing)
        if (this.profileBtn && this.profileOverlay) {
            if (!this.profileBtn.getAttribute('onclick')) {
                this.profileBtn.addEventListener("click", (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.profileOverlay.classList.remove("hidden");
                });
            }
        }

        // Status dropdown
        if (this.statusBtn && this.statusMenu) {
            this.statusBtn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.statusMenu.classList.toggle("hidden");
            });

            document.querySelectorAll("#status-dropdown li").forEach(item => {
                item.addEventListener("click", (e) => {
                    e.preventDefault();
                    this.handleStatusChange(item);
                });
            });
        }

        // Global click handlers
        document.addEventListener("click", (e) => {
            // Close status dropdown when clicking outside
            if (this.statusMenu && !this.statusMenu.classList.contains("hidden")) {
                if (!this.statusBtn.contains(e.target) && !this.statusMenu.contains(e.target)) {
                    this.statusMenu.classList.add("hidden");
                }
            }
            
            // Close overlay on backdrop click
            if (e.target.classList.contains('overlay-fixed')) {
                e.target.classList.add("hidden");
            }
            
            // Close button handler
            if (e.target.classList.contains("close-overlay") || e.target.closest(".close-overlay")) {
                const btn = e.target.classList.contains("close-overlay") ? e.target : e.target.closest(".close-overlay");
                const targetId = btn.getAttribute("data-target");
                const target = document.getElementById(targetId);
                if (target) target.classList.add("hidden");
            }
        });

        // Search with debounce
        if (this.searchInput) {
            let timer;
            this.searchInput.addEventListener("input", (e) => {
                clearTimeout(timer);
                timer = setTimeout(() => this.filterAll(e.target.value.toLowerCase()), 300);
            });
        }

        // Create group button
        const createBtn = document.getElementById("create-group-btn");
        if (createBtn) {
            createBtn.onclick = () => {
                if (typeof showCreateGroupModal === "function") {
                    showCreateGroupModal();
                } else {
                    document.getElementById("create-group-modal")?.classList.remove("hidden");
                }
            };
        }
    },

    setupSocketListeners() {
        if (this.socketListenersBound) return;

        const checkSocket = setInterval(() => {
            if (window.socket) {
                this.socketListenersBound = true;
                
                window.socket.on('refresh_call_logs', () => {
                    if (this.getActiveTab() === "calls") this.loadCallHistory();
                });
                
                window.socket.on('user_status_change', (data) => {
                    this.updateRemoteStatusIndicator(data.user_id, data.status);
                });

                window.socket.on('receive_message', () => {
                    this.loadSidebarUsers();
                    this.loadSidebarGroups();
                });

                window.socket.on('receive_notification', () => {
                    this.updateNotificationBadge();
                    if (this.getActiveTab() === "notifications") this.loadNotifications();
                });

                clearInterval(checkSocket);
            }
        }, 500);
    },

    async switchTab(selectedTab) {
        const tabName = selectedTab.getAttribute("data-tab");
        
        // Clear all active states
        this.tabs.forEach(t => t.classList.remove("active"));
        this.panes.forEach(p => {
            p.style.display = "none";
            p.classList.remove("active");
        });
        
        // Set new active state
        selectedTab.classList.add("active");
        const target = document.getElementById(`${tabName}-content`);
        
        if (target) {
            target.style.display = "block";
            target.classList.add("active");
            
            // Special handling for chats tab layout
            if (tabName === "chats") {
                target.style.display = "flex";
                target.style.flexDirection = "column";
            }
        }

        // Load tab-specific data
        if (tabName === "calls") await this.loadCallHistory();
        if (tabName === "notifications") await this.loadNotifications();
    },

    async loadNotifications() {
        const list = document.getElementById("notifications-list");
        if (!list) return;

        list.innerHTML = this.getLoadingHTML();

        try {
            const response = await fetch(`${this.baseUrl}api/notifications`);
            const responseData = await response.json();
            
            let notifications = [];
            if (Array.isArray(responseData)) {
                notifications = responseData;
            } else if (responseData.data && Array.isArray(responseData.data)) {
                notifications = responseData.data;
            } else if (responseData.notifications && Array.isArray(responseData.notifications)) {
                notifications = responseData.notifications;
            }

            list.innerHTML = "";
            
            if (notifications.length === 0) {
                list.innerHTML = this.getEmptyStateHTML("No Notifications", "You're all caught up!");
                return;
            }

            notifications.forEach(notif => {
                const isRead = notif.read_at !== null && notif.read_at !== undefined && notif.read_at !== "";
                const div = document.createElement("div");
                div.className = `conversation-item ${!isRead ? 'unread' : ''}`;
                
                const icon = this.getNotificationIcon(notif.type);
                const title = notif.title || notif.subject || 'Notification';
                const body = notif.body || notif.message || notif.content || '';

                div.innerHTML = `
                    <div class="avatar-container">
                        <div class="user-avatar" style="background: ${!isRead ? 'var(--primary)' : 'var(--bg-hover)'}; display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="fas ${icon}"></i>
                        </div>
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-name">${this.escapeHtml(title)}</div>
                        <div class="conversation-preview">${this.escapeHtml(body)}</div>
                    </div>
                    <div class="conversation-meta">
                        <div class="conversation-time">${this.formatNiceDate(notif.created_at)}</div>
                    </div>
                `;
                
                div.onclick = () => {
                    if (notif.link) {
                        window.location.href = notif.link;
                    } else if (notif.id) {
                        this.markNotificationRead(notif.id, div);
                    }
                };

                list.appendChild(div);
            });
            
            if (this.notifBadge) this.notifBadge.classList.add("hidden");

        } catch (error) {
            console.error("Notification load error:", error);
            list.innerHTML = this.getEmptyStateHTML("Error", "Failed to load notifications");
        }
    },

    getNotificationIcon(type) {
        const iconMap = {
            'message': 'fa-comment',
            'alert': 'fa-exclamation-circle',
            'system': 'fa-info-circle',
            'warning': 'fa-exclamation-triangle',
            'success': 'fa-check-circle'
        };
        return iconMap[type] || 'fa-bell';
    },

    async markNotificationRead(id, element) {
        element.classList.remove("unread");
        const avatar = element.querySelector(".user-avatar");
        if (avatar) avatar.style.background = "var(--bg-hover)";
        
        try {
            await fetch(`${this.baseUrl}api/markNotificationRead`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: id })
            });
        } catch (error) {
            console.error("Failed to mark notification as read:", error);
        }
    },

    async loadCallHistory() {
        const list = document.getElementById("calls-list");
        if (!list) return;

        list.innerHTML = this.getLoadingHTML("Loading call history...");

        try {
            const response = await fetch(`${this.baseUrl}api/getCallLogs`);
            const logs = await response.json();
            
            list.innerHTML = "";
            
            if (!Array.isArray(logs) || logs.length === 0) {
                list.innerHTML = this.getEmptyStateHTML("No Recent Calls", "Your call history will appear here");
                return;
            }

            const uniqueLogs = Array.from(new Map(logs.map(item => [item.id, item])).values());

            uniqueLogs.forEach(log => {
                const isIncoming = log.receiver_id == window.userId;
                const isMissed = ['missed', 'rejected'].includes(log.status);
                const icon = isIncoming ? 'fa-arrow-down-left' : 'fa-arrow-up-right';
                const statusColor = isMissed ? 'var(--danger)' : 'var(--success)';
                
                const avatar = log.other_avatar 
                    ? `${this.baseUrl}uploads/avatars/${log.other_avatar}` 
                    : `${this.baseUrl}uploads/avatars/default-avatar.png`;

                const div = document.createElement("div");
                div.className = "conversation-item";
                div.innerHTML = `
                    <div class="avatar-container">
                        <img src="${avatar}" class="user-avatar" onerror="this.src='${this.baseUrl}uploads/avatars/default-avatar.png'" alt="${this.escapeHtml(log.other_name || 'User')}">
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-name">${this.escapeHtml(log.other_name || 'Unknown')}</div>
                        <div class="conversation-preview" style="color: ${statusColor}">
                            <i class="fas ${icon} fa-xs"></i> 
                            ${log.status_label} ${!isMissed ? `(${this.formatDuration(log.duration_seconds)})` : ''}
                        </div>
                    </div>
                    <div class="conversation-meta">
                        <div class="conversation-time">${this.formatNiceDate(log.created_at)}</div>
                    </div>
                `;
                div.onclick = () => window.location.href = `${this.baseUrl}chat/direct/${log.other_id}`;
                list.appendChild(div);
            });
        } catch (error) {
            console.error("Call history load error:", error);
            list.innerHTML = this.getEmptyStateHTML("Error", "Failed to load call history");
        }
    },

    async loadSidebarUsers() {
        const list = document.getElementById("direct-messages-list");
        if (!list) return;

        try {
            const response = await fetch(`${this.baseUrl}api/users`);
            const users = await response.json();
            list.innerHTML = "";

            if (!users || users.length === 0) {
                list.innerHTML = this.getEmptyStateHTML("No Conversations", "Start a new chat");
                return;
            }

            users.forEach(user => {
                if (user.id == window.userId) return;
                
                const isActive = (window.otherUser && user.id == window.otherUser.id);
                const avatar = user.avatar 
                    ? `${this.baseUrl}uploads/avatars/${user.avatar}` 
                    : `${this.baseUrl}uploads/avatars/default-avatar.png`;
                const previewText = this.formatMessagePreview(user.last_message, "Click to start chatting");

                const div = document.createElement("div");
                div.className = `conversation-item ${isActive ? "active" : ""}`;
                div.innerHTML = `
                    <div class="avatar-container">
                        <img src="${avatar}" class="user-avatar" onerror="this.src='${this.baseUrl}uploads/avatars/default-avatar.png'" alt="${this.escapeHtml(user.nickname || user.username)}">
                        <span class="status-indicator ${user.status || "offline"}" data-user-indicator="${user.id}"></span>
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-name">${this.escapeHtml(user.nickname || user.username)}</div>
                        <div class="conversation-preview">${this.escapeHtml(previewText)}</div>
                    </div>
                    <div class="conversation-meta">
                        ${user.last_activity ? `<div class="conversation-time">${this.formatNiceDate(user.last_activity)}</div>` : ""}
                        ${user.unread_count > 0 ? `<div class="conversation-badge">${user.unread_count > 99 ? '99+' : user.unread_count}</div>` : ""}
                    </div>
                `;
                div.onclick = () => window.location.href = `${this.baseUrl}chat/direct/${user.id}`;
                list.appendChild(div);
            });
        } catch (error) {
            console.error("Users load error:", error);
        }
    },

    async loadSidebarGroups() {
        const list = document.getElementById("groups-list");
        if (!list) return;
        
        try {
            const response = await fetch(`${this.baseUrl}api/groups`);
            const groups = await response.json();
            list.innerHTML = "";
            
            if (!groups || groups.length === 0) {
                list.innerHTML = this.getEmptyStateHTML("No Groups", "Create or join a group");
                return;
            }

            const seenIds = new Set();
            groups.forEach(group => {
                if (seenIds.has(group.id)) return;
                seenIds.add(group.id);

                const previewText = this.formatMessagePreview(group.last_message, "No messages yet");
                let avatarHtml;
                
                if (group.image && group.image !== "") {
                    const imgUrl = `${this.baseUrl}uploads/groups/${group.image}`;
                    avatarHtml = `
                        <img src="${imgUrl}" class="user-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'" alt="${this.escapeHtml(group.name)}">
                        <div class="user-avatar group-avatar-placeholder" style="display:none">${group.name.charAt(0).toUpperCase()}</div>
                    `;
                } else {
                    avatarHtml = `<div class="user-avatar group-avatar-placeholder">${group.name.charAt(0).toUpperCase()}</div>`;
                }

                const div = document.createElement("div");
                div.className = "conversation-item";
                div.innerHTML = `
                    <div class="avatar-container">
                        ${avatarHtml}
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-name">${this.escapeHtml(group.name)}</div>
                        <div class="conversation-preview">${this.escapeHtml(previewText)}</div>
                    </div>
                    <div class="conversation-meta">
                        ${group.unread_count > 0 ? `<div class="conversation-badge">${group.unread_count > 99 ? '99+' : group.unread_count}</div>` : ""}
                    </div>
                `;
                div.onclick = () => window.location.href = `${this.baseUrl}chat/group/${group.id}`;
                list.appendChild(div);
            });
        } catch (error) {
            console.error("Groups load error:", error);
        }
    },

    handleStatusChange(item) {
        const status = item.getAttribute("data-status");
        const label = item.textContent.trim();
        const indicator = document.querySelector(".status-bar .status-dot"); 
        const textLabel = document.getElementById("status-text");

        if (textLabel) textLabel.textContent = label;
        if (indicator) indicator.className = `status-dot ${status}`; 

        fetch(`${this.baseUrl}api/updateUserStatus`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ status })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && window.socket?.connected) {
                window.socket.emit("status_change", { 
                    user_id: window.userId, 
                    status 
                });
            }
        })
        .catch(error => {
            console.error("Status update failed:", error);
        });
        
        if (this.statusMenu) this.statusMenu.classList.add("hidden");
    },

    filterAll(term) {
        const lists = ["direct-messages-list", "groups-list", "calls-list", "notifications-list"];
        
        lists.forEach(id => {
            const container = document.getElementById(id);
            if (!container) return;
            
            const items = container.querySelectorAll(".conversation-item");
            items.forEach(item => {
                const content = item.innerText.toLowerCase();
                item.style.display = content.includes(term) ? "flex" : "none";
            });
        });
    },

    // Utility functions
    formatMessagePreview(msg, defaultText) {
        if (!msg) return defaultText;
        if (typeof msg === 'string') return msg;
        if (typeof msg === 'object') {
            return msg.message || msg.content || msg.text || "Sent an attachment";
        }
        return defaultText;
    },

    formatNiceDate(timestamp) {
        const date = new Date(timestamp);
        if (isNaN(date.getTime())) return "";
        
        const now = new Date();
        const yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        
        if (date.toDateString() === now.toDateString()) {
            return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
        } else if (date.toDateString() === yesterday.toDateString()) {
            return "Yesterday";
        }
        
        return date.toLocaleDateString([], { month: "short", day: "numeric" });
    },

    formatDuration(seconds) {
        if (!seconds || seconds < 0) return "0:00";
        const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
        const secs = (seconds % 60).toString().padStart(2, '0');
        return `${mins}:${secs}`;
    },

    updateRemoteStatusIndicator(userId, status) {
        const indicators = document.querySelectorAll(`[data-user-indicator="${userId}"]`);
        indicators.forEach(el => {
            el.className = `status-indicator ${status}`;
        });
    },

    getActiveTab() {
        const activeTab = document.querySelector(".nav-rail-btn.active"); 
        return activeTab ? activeTab.getAttribute("data-tab") : null;
    },

    refreshAll() {
        this.loadSidebarUsers();
        this.loadSidebarGroups();
        this.updateNotificationBadge();
    },

    async updateNotificationBadge() {
        try {
            const response = await fetch(`${this.baseUrl}api/unreadNotificationsCount`);
            const data = await response.json();
            
            if (this.notifBadge) {
                const count = data.count || 0;
                this.notifBadge.textContent = count > 99 ? "99+" : count;
                this.notifBadge.classList.toggle("hidden", count === 0);
            }
        } catch (error) {
            console.error("Notification badge update failed:", error);
        }
    },

    // HTML template helpers
    getLoadingHTML(message = "Loading...") {
        return `
            <div class="loading-indicator" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 12px; display: block;"></i>
                <span>${message}</span>
            </div>
        `;
    },

    getEmptyStateHTML(title, description) {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h2>${title}</h2>
                <p>${description}</p>
            </div>
        `;
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize on DOM ready
document.addEventListener("DOMContentLoaded", () => SidebarManager.init());