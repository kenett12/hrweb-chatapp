class MessageVirtualizer {
    constructor(options = {}) {
        this.options = Object.assign({ containerId: "chat-messages" }, options);
        this.container = document.getElementById(this.options.containerId);
        this.getUploadsUrl = () => window.uploadsUrl || '/chat-app/backend/public/uploads/';
    }

    scrollToBottom(smooth = true) {
        if (!this.container) return;
        this.container.scrollTo({ top: this.container.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
    }

    markAllAsSeen() {
        const sentIcons = this.container.querySelectorAll('.status-sent');
        sentIcons.forEach(icon => {
            icon.className = "fas fa-check-double status-icon status-seen";
            icon.style.color = "#3b82f6"; 
        });
    }

    replaceMessage(tempId, realMessage) {
        const tempEl = this.container.querySelector(`.message[data-message-id="${tempId}"]`);
        if (tempEl) {
            const realEl = this.createMessageElement(realMessage);
            if (realEl) this.container.replaceChild(realEl, tempEl);
        } else {
            this.appendMessage(realMessage);
        }
    }

    appendMessage(message) {
        if (!message || (message.id && document.querySelector(`.message[data-message-id="${message.id}"]`))) return;
        
        const isCurrentUser = (message.sender_id == window.userId);
        const el = this.createMessageElement(message);
        if (el) {
            this.container.appendChild(el);
            setTimeout(() => this.scrollToBottom(true), 50);
        }
    }

    setMessages(messages) {
        if (!messages) return;
        messages.sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());
        this.container.innerHTML = '';
        const frag = document.createDocumentFragment();
        messages.forEach(msg => {
            const el = this.createMessageElement(msg);
            if (el) frag.appendChild(el);
        });
        this.container.appendChild(frag);
        this.scrollToBottom(false);
    }

    updateMessage(messageId, updates) {
        const el = this.container.querySelector(`.message[data-message-id="${messageId}"]`);
        if (!el) return;
        if (updates.is_read === 1) {
            const icon = el.querySelector('.status-icon');
            if(icon) {
                icon.className = "fas fa-check-double status-icon status-seen";
                icon.style.color = "#3b82f6";
            }
        }
    }

    createMessageElement(message) {
        const isCurrentUser = (message.sender_id == window.userId);
        const div = document.createElement('div');
        div.className = `message ${isCurrentUser ? "own-message" : ""}`;
        if(message.id) div.dataset.messageId = message.id;

        let cleanContent = message.content || "";
        if (cleanContent.startsWith("File: ") || cleanContent.startsWith("Image: ")) {
            cleanContent = cleanContent.split(": ").pop(); 
        }

        let avatarHtml = '';
        if (!isCurrentUser) {
            const url = this.getUploadsUrl() + 'avatars/' + (message.avatar || 'default-avatar.png');
            avatarHtml = `<div class="message-avatar"><img src="${url}" onerror="this.src='${this.getUploadsUrl()}avatars/default-avatar.png'"></div>`;
        }

        let replyHtml = '';
        if (message.reply_to_id) {
            let rName = (message.reply_to_sender_id == window.userId) ? "You" : (message.reply_to_sender_name || "User");
            replyHtml = `<div class="replied-message-block"><span class="replied-sender">${rName}</span><span class="replied-text">${message.reply_to_content || "..."}</span></div>`;
        }

        let contentHtml = '';
        if (message.type === 'image') {
            const imagePath = message.temp ? message.file_url : (this.getUploadsUrl() + 'messages/' + cleanContent);
            contentHtml = `<img src="${imagePath}" class="message-image" style="max-width:200px; border-radius:10px; cursor:pointer;" onclick="if(window.openImageViewer) openImageViewer('${imagePath}')">`;
        } else if (message.type === 'file') {
            const filePath = message.temp ? message.file_url : (this.getUploadsUrl() + 'messages/' + cleanContent);
            contentHtml = `<a href="${filePath}" class="file-attachment-card" target="_blank" style="color:inherit; text-decoration:none;"><i class="fas fa-file"></i> ${message.original_filename || cleanContent}</a>`;
        } else {
            contentHtml = `<div class="message-text">${message.content}</div>`;
        }

        let statusIcon = '';
        if (isCurrentUser) {
            const isSeen = message.is_read == 1 || message.status === 'seen';
            const iconClass = isSeen ? 'fa-check-double' : 'fa-check';
            const colorClass = isSeen ? 'status-seen' : 'status-sent';
            const colorStyle = isSeen ? 'color:#3b82f6;' : 'color:#737373;';
            statusIcon = `<div class="message-status"><i class="fas ${iconClass} status-icon ${colorClass}" style="${colorStyle}"></i></div>`;
        }

        div.innerHTML = `
            ${avatarHtml}
            <div class="message-content-wrapper">
                <div class="message-bubble">
                    ${replyHtml}
                    ${contentHtml}
                </div>
                <div class="message-meta">
                    <span class="message-time">${this.formatTime(message.created_at)}</span>
                    ${statusIcon}
                </div>
            </div>
            <div class="message-actions" style="opacity:0; transition:0.2s;">
                <button class="icon-btn reply-trigger" title="Reply" style="width:30px; height:30px; font-size:12px;"><i class="fas fa-reply"></i></button>
            </div>
        `;

        div.onmouseenter = () => { const a=div.querySelector('.message-actions'); if(a) a.style.opacity=1; };
        div.onmouseleave = () => { const a=div.querySelector('.message-actions'); if(a) a.style.opacity=0; };

        const rBtn = div.querySelector('.reply-trigger');
        if(rBtn) rBtn.onclick = () => {
            if(window.handleReplyClick) window.handleReplyClick(message.id, (message.nickname||message.username||"User"), message.content);
        };

        return div;
    }

    formatTime(timestamp) {
        if (!timestamp) return "";
        if (timestamp.includes(' ') && timestamp.includes(':')) {
            const timePart = timestamp.split(' ')[1]; 
            if (timePart) return timePart.substring(0, 5); 
        }
        if (timestamp.includes('T')) {
             const timePart = timestamp.split('T')[1];
             if (timePart) return timePart.substring(0, 5);
        }
        return new Date(timestamp).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    }
}

document.addEventListener("DOMContentLoaded", () => {
    window.messageVirtualizer = new MessageVirtualizer();
});