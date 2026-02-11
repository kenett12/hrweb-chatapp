<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Chat - Ticket #<?= $ticket['id'] ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #a78bfa;
            --primary-dark: #8b5cf6;
            --primary-darker: #7c3aed;
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --border: #334155;
            --border-light: #475569;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
            height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        #app-container {
            display: flex;
            height: 100vh;
            gap: 1px;
            background: var(--border);
        }
        
        /* ===== SIDEBAR ===== */
        .chat-sidebar {
            width: 360px;
            background: var(--bg-secondary);
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-secondary);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .back-link:hover {
            color: var(--primary-dark);
            gap: 12px;
        }
        
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        .sidebar-content::-webkit-scrollbar { width: 6px; }
        .sidebar-content::-webkit-scrollbar-track { background: transparent; }
        .sidebar-content::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.2);
            border-radius: 3px;
        }
        .sidebar-content::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.3); }
        
        .contact-card {
            background: var(--bg-tertiary);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        
        .contact-card:hover {
            background: rgba(167, 139, 250, 0.1);
            border-color: var(--border-light);
        }
        
        .contact-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-darker) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 22px;
            color: white;
            position: relative;
            box-shadow: 0 4px 16px rgba(124, 58, 237, 0.25);
        }
        
        .online-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 14px;
            height: 14px;
            background: #10b981;
            border: 3px solid var(--bg-secondary);
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.15);
        }
        
        .contact-info { flex: 1; }
        
        .contact-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .contact-status {
            font-size: 13px;
            color: var(--text-tertiary);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
            min-width: 0;
        }
        
        .chat-header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            z-index: 10;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .header-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-darker) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
        }
        
        .header-info { flex: 1; min-width: 0; }
        
        .header-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .header-subtitle {
            font-size: 13px;
            color: var(--text-tertiary);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .header-subtitle::before {
            content: '';
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
        }
        
        /* ===== MESSAGES ===== */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            scroll-behavior: smooth;
            background: linear-gradient(180deg, var(--bg-primary) 0%, rgba(30, 41, 59, 0.5) 100%);
        }
        
        .chat-messages::-webkit-scrollbar { width: 8px; }
        .chat-messages::-webkit-scrollbar-track { background: transparent; }
        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.2);
            border-radius: 4px;
        }
        .chat-messages::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.3); }
        
        .message {
            display: flex;
            width: 100%;
            animation: slideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message-customer { justify-content: flex-end; }
        .message-tsr,
        .message-bot { justify-content: flex-start; }
        .message-system { justify-content: center; margin: 12px 0; }
        
        .message-system .message-content {
            max-width: none;
            width: auto;
        }
        
        .message-system .message-bubble {
            background: rgba(167, 139, 250, 0.1);
            color: var(--text-tertiary);
            font-size: 12px;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 12px;
            border: 1px solid rgba(167, 139, 250, 0.2);
            box-shadow: none;
        }
        
        .message-system .message-time,
        .message-system .message-sender-name { display: none !important; }
        
        .message-content {
            display: flex;
            flex-direction: column;
            max-width: 70%;
            width: auto;
        }
        
        .message-customer .message-content { align-items: flex-end; }
        .message-tsr .message-content,
        .message-bot .message-content { align-items: flex-start; }
        
        .message-bubble {
            padding: 10px 14px;
            border-radius: 18px;
            max-width: 100%;
            width: auto;
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-size: 15px;
            line-height: 1.5;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
        }
        
        .message-bubble:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transform: translateY(-1px);
        }
        
        .message-customer .message-bubble {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-darker) 100%);
            color: white;
            border-radius: 18px 4px 18px 18px;
        }
        
        .message-tsr .message-bubble,
        .message-bot .message-bubble {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border-radius: 4px 18px 18px 18px;
            border: 1px solid var(--border-light);
        }
        
        .message-text {
            font-size: 15px;
            line-height: 1.5;
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .message-time {
            font-size: 11px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
            opacity: 0.6;
        }
        
        .message-customer .message-time {
            color: rgba(241, 245, 249, 0.6);
            justify-content: flex-end;
        }
        
        .message-tsr .message-time,
        .message-bot .message-time { color: var(--text-tertiary); }
        
        .message-time i { font-size: 9px; }
        
        /* ===== TYPING ===== */
        .typing-indicator {
            display: inline-flex;
            gap: 4px;
            padding: 4px 0;
            align-items: center;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background: var(--text-tertiary);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        
        .typing-dot:nth-child(1) { animation-delay: 0s; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
            30% { transform: translateY(-8px); opacity: 1; }
        }
        
        /* ===== QUICK REPLIES ===== */
        .quick-replies {
            padding: 12px 24px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border);
            transition: all 0.3s ease;
        }
        
        .quick-replies.hidden {
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
            max-height: 0;
            padding: 0 24px;
            overflow: hidden;
        }
        
        .quick-reply-btn {
            background: var(--bg-tertiary);
            border: 1.5px solid var(--border-light);
            color: var(--text-secondary);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .quick-reply-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-darker) 100%);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }
        
        .quick-reply-btn i { font-size: 12px; }
        
        /* ===== COMPOSER ===== */
        .message-composer {
            background: var(--bg-secondary);
            padding: 16px 24px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: flex-end;
            gap: 12px;
        }
        
        .composer-input-wrapper {
            flex: 1;
            background: var(--bg-tertiary);
            border-radius: 24px;
            padding: 10px 16px;
            display: flex;
            align-items: flex-end;
            gap: 8px;
            border: 1.5px solid transparent;
            transition: all 0.2s ease;
        }
        
        .composer-input-wrapper:focus-within {
            background: rgba(167, 139, 250, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.15);
        }
        
        #message-input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-primary);
            padding: 10px 0;
            font-size: 15px;
            line-height: 1.5;
            resize: none;
            max-height: 100px;
            font-family: inherit;
        }
        
        #message-input::placeholder { color: var(--text-tertiary); }
        
        .composer-actions {
            display: flex;
            gap: 4px;
        }
        
        .composer-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: transparent;
            border: none;
            color: var(--text-tertiary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 16px;
        }
        
        .composer-btn:hover {
            background: rgba(167, 139, 250, 0.15);
            color: var(--primary);
            transform: scale(1.1);
        }
        
        .send-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-darker) 100%);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.4);
        }
        
        .send-btn:hover:not(:disabled) {
            transform: scale(1.08);
            box-shadow: 0 6px 16px rgba(124, 58, 237, 0.5);
        }
        
        .send-btn:active:not(:disabled) { transform: scale(0.95); }
        
        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .chat-sidebar { display: none; }
            #app-container { gap: 0; }
            .message-content { max-width: 85%; }
        }
        
        .hidden { display: none !important; }
    </style>
</head>
<body>
    <div id="app-container">
        <aside class="chat-sidebar">
            <div class="sidebar-header">
                <a href="<?= base_url('chat') ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i> <span>Back to chats</span>
                </a>
            </div>
            <div class="sidebar-content">
                <div class="contact-card">
                    <div class="contact-avatar" id="sidebarAvatar">
                        <i class="fas <?= isset($ticket['assigned_to']) ? 'fa-user-tie' : 'fa-robot' ?>"></i>
                        <div class="online-indicator"></div>
                    </div>
                    <div class="contact-info">
                        <div class="contact-name" id="sidebarName">
                            <?= $ticket['assigned_tsr_name'] ?? 'Support Bot' ?>
                        </div>
                        <div class="contact-status" id="sidebarStatus">
                            <?= isset($ticket['assigned_to']) ? 'Online now' : 'Always available' ?>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="chat-header">
                <div class="header-avatar" id="headerAvatar">
                    <i class="fas <?= isset($ticket['assigned_to']) ? 'fa-user-tie' : 'fa-robot' ?>"></i>
                </div>
                <div class="header-info">
                    <div class="header-title"><?= $ticket['subject'] ?></div>
                    <div class="header-subtitle" id="headerStatus">
                        <?= isset($ticket['assigned_to']) ? 'Online' : 'Active now' ?>
                    </div>
                </div>
            </header>

            <div class="chat-messages" id="chatMessages"></div>

            <div class="quick-replies" id="quickReplies" style="<?= isset($ticket['assigned_to']) ? 'display: none;' : '' ?>">
                <button class="quick-reply-btn" onclick="handleQuickReply('I forgot my password')">
                    <i class="fas fa-key"></i> <span>Forgot Password</span>
                </button>
                
                <button class="quick-reply-btn" onclick="handleQuickReply('How do I file a leave?')">
                    <i class="fas fa-calendar-plus"></i> <span>File Leave</span>
                </button>
                
                <button class="quick-reply-btn" onclick="handleQuickReply('When is payday?')">
                    <i class="fas fa-money-bill-wave"></i> <span>Payday?</span>
                </button>
                
                <button class="quick-reply-btn" onclick="handleQuickReply('Contact HR')">
                    <i class="fas fa-phone"></i> <span>Contact HR</span>
                </button>
            </div>

            <div class="message-composer">
                <form id="messageForm" style="display: contents;">
                    <div class="composer-input-wrapper">
                        <textarea id="message-input" placeholder="Type a message..." rows="1"></textarea>
                        <div class="composer-actions">
                            <button type="button" class="composer-btn" title="Emoji">
                                <i class="far fa-smile"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="send-btn" id="sendBtn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
    (function() {
        'use strict';
        
        const ticketId = <?= $ticket['id'] ?>;
        const currentUserId = <?= session()->get('id') ?>;
        
        let rawBaseUrl = '<?= base_url() ?>';
        const baseUrl = rawBaseUrl.endsWith('/') ? rawBaseUrl.slice(0, -1) : rawBaseUrl;
        
        let isAssigned = <?= !empty($ticket['assigned_to']) ? 'true' : 'false' ?>;
        let isTicketClosed = <?= (in_array($ticket['status'], ['closed', 'resolved'])) ? 'true' : 'false' ?>;

        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('message-input');
        const messageForm = document.getElementById('messageForm');
        const quickReplies = document.getElementById('quickReplies');
        const sendBtn = document.getElementById('sendBtn');
        
        const displayedMessages = new Set();
        let isLoadingMessages = false;
        let lastMessageId = 0;
        let hasShownWelcome = false;
        let isInitialLoad = true;

        function smoothScrollToBottom() {
            if (!chatMessages) return;
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        let resizeTimeout;
        messageInput?.addEventListener('input', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            }, 10);
        });
        
        async function loadMessages() {
            if (isLoadingMessages) return;
            isLoadingMessages = true;
            
            const messageCountBefore = displayedMessages.size;
            
            try {
                const response = await fetch(`${baseUrl}/api/tickets/customer/${ticketId}/messages`);
                if (!response.ok) throw new Error('Failed to load messages');
                
                const data = await response.json();
                
                if (Array.isArray(data)) {
                    data.forEach(msg => {
                        const msgId = msg.id || `temp-${msg.content}-${msg.created_at}`;
                        
                        if (!displayedMessages.has(msgId)) {
                            addMessageToUI({
                                id: msg.id,
                                sender_type: msg.sender_type,
                                sender_name: msg.sender_name,
                                content: msg.content || msg.message,
                                created_at: msg.created_at
                            }, false);
                            
                            displayedMessages.add(msgId);
                            if (msg.id > lastMessageId) lastMessageId = msg.id;
                        }
                    });
                }

                if (!hasShownWelcome && !isAssigned) {
                    addMessageToUI({
                        id: 'welcome-bot-msg',
                        sender_type: 'bot',
                        content: "Hi there! 👋 I'm here to help. Choose a topic below or type your question.",
                        created_at: new Date().toISOString()
                    }, false);
                    hasShownWelcome = true;
                }

                const hasNewMessages = displayedMessages.size > messageCountBefore;
                if (isInitialLoad || hasNewMessages) {
                    requestAnimationFrame(() => smoothScrollToBottom());
                    isInitialLoad = false;
                }

            } catch (err) {
                console.error('Load messages error:', err);
            } finally {
                isLoadingMessages = false;
            }
        }
        
        async function checkStatus() {
            try {
                const response = await fetch(`${baseUrl}/api/tickets/customer/${ticketId}/status`);
                if (!response.ok) return;
                
                const data = await response.json();
                
                if (data.success && data.ticket.assigned_to && !isAssigned) {
                    isAssigned = true;
                    
                    const updates = [
                        { el: document.getElementById('sidebarName'), text: data.ticket.assigned_tsr_name },
                        { el: document.getElementById('sidebarStatus'), text: 'Online now' },
                        { el: document.getElementById('headerStatus'), text: 'Online' }
                    ];
                    
                    updates.forEach(({ el, text }) => {
                        if (el) el.textContent = text;
                    });
                    
                    const sidebarAvatar = document.getElementById('sidebarAvatar');
                    const headerAvatar = document.getElementById('headerAvatar');
                    
                    if (sidebarAvatar) sidebarAvatar.innerHTML = '<i class="fas fa-user-tie"></i><div class="online-indicator"></div>';
                    if (headerAvatar) headerAvatar.innerHTML = '<i class="fas fa-user-tie"></i>';
                    
                    if (quickReplies) quickReplies.classList.add('hidden');
                    
                    const welcomeMsg = document.getElementById('msg-welcome-bot-msg');
                    if (welcomeMsg) welcomeMsg.remove();

                    addMessageToUI({
                        id: `sys-${Date.now()}`,
                        sender_type: 'system',
                        content: `${data.ticket.assigned_tsr_name} joined the chat`,
                        created_at: new Date().toISOString()
                    }, true);
                }
            } catch (err) { }
        }
        
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message || isTicketClosed) return;
            
            const tempId = `temp-${Date.now()}`;
            
            addMessageToUI({
                id: tempId,
                sender_type: 'customer',
                content: message,
                created_at: new Date().toISOString()
            }, true);
            
            messageInput.value = '';
            messageInput.style.height = 'auto';
            
            try {
                const response = await fetch(`${baseUrl}/api/tickets/customer/send-message`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        ticket_id: ticketId, 
                        message: message,
                        sender_type: 'customer' 
                    })
                });
                
                if (!response.ok) throw new Error('Send failed');
                
                const tempMsg = document.getElementById(`msg-${tempId}`);
                if (tempMsg) {
                    tempMsg.remove();
                    displayedMessages.delete(tempId);
                }
                
                await loadMessages();
                
                if (!isAssigned) {
                    setTimeout(() => getBotResponse(message), 800);
                }
            } catch (err) {
                console.error('Send error:', err);
                const tempMsg = document.getElementById(`msg-${tempId}`);
                if (tempMsg) tempMsg.remove();
                displayedMessages.delete(tempId);
                alert('Failed to send message');
            }
        }
        
        function addMessageToUI(data, shouldScroll = true) {
            if (!data || !data.content) return;
            
            const msgId = data.id || `temp-${Date.now()}`;
            if (document.getElementById(`msg-${msgId}`)) return;
            
            const div = document.createElement('div');
            div.id = `msg-${msgId}`;
            
            const isCustomer = data.sender_type === 'customer';
            const isBot = data.sender_type === 'bot';
            const isSystem = data.sender_type === 'system';
            
            if (isSystem) {
                div.className = 'message message-system';
            } else if (isCustomer) {
                div.className = 'message message-customer';
            } else if (isBot) {
                div.className = 'message message-bot';
            } else {
                div.className = 'message message-tsr';
            }
            
            const timeString = data.created_at 
                ? new Date(data.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                : new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            // --- THE FIX IS HERE ---
            let finalContent = data.content;

            // Only escape HTML if it comes from the CUSTOMER (Security)
            // We TRUST the Bot/System/Agent messages to have safe HTML (like <b>, <br>)
            if (isCustomer) {
                finalContent = finalContent
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }
            // -----------------------
            
            div.innerHTML = `
                <div class="message-content">
                    <div class="message-bubble">
                        <div class="message-text">${finalContent}</div>
                    </div>
                    <div class="message-time">
                        <span>${timeString}</span>
                        ${isCustomer ? '<i class="fas fa-check"></i>' : ''}
                    </div>
                </div>`;
            
            chatMessages.appendChild(div);
            
            if (shouldScroll) requestAnimationFrame(() => smoothScrollToBottom());
        }
        
        async function getBotResponse(query) {
    // 1. Setup Typing Indicator
    const typingIndicator = document.getElementById('typing-indicator');
    const typingText = document.getElementById('typing-text');
    
    if (typingIndicator) {
        if(typingText) typingText.textContent = "Bot is typing...";
        typingIndicator.classList.remove('hidden');
        
        // Auto-scroll to show typing
        const chatContainer = document.getElementById('chat-messages');
        if(chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    try {
        // 2. Fetch Data (Ensure slash '/' exists before 'api')
        const response = await fetch(`${baseUrl}/api/knowledge-base/search?q=${encodeURIComponent(query)}`);
        
        // Check for 404/500 errors first
        if (!response.ok) {
            throw new Error(`Server Error: ${response.status} ${response.statusText}`);
        }

        // 3. Define 'data' (THIS IS THE MISSING PART)
        const data = await response.json(); 

        // 4. Smart Logic
        let botContent = '';
        
        // Safely check if data exists and has an answer
        if (!data || !data.answer || (data.score && data.score < 0.6)) {
            botContent = "I'm not 100% sure about that. Would you like to <a href='javascript:void(0)' onclick='openTicketModal()' style='color:var(--primary);text-decoration:underline;'>submit a ticket</a>?";
        } else {
            botContent = formatBotText(data.answer);
        }

        // 5. Natural Delay
        const readingDelay = Math.min(Math.max(botContent.length * 20, 600), 2500);

        setTimeout(() => {
            if (typingIndicator) typingIndicator.classList.add('hidden');

            const msgData = {
                id: `bot-${Date.now()}`,
                sender_id: 'bot',
                username: 'Support Bot',
                avatar: 'bot-avatar.png', 
                content: botContent,
                created_at: new Date().toISOString(),
                is_read: 1
            };

            // Use whichever append function your app uses
            if (typeof appendMessage === 'function') {
                appendMessage(msgData);
            } else if (typeof addMessageToUI === 'function') {
                addMessageToUI(msgData, true);
            }

            new Audio(`${baseUrl}/assets/sounds/message.mp3`).play().catch(()=>{});

        }, readingDelay);

    } catch (err) {
        console.error("Bot Error:", err);
        if (typingIndicator) typingIndicator.classList.add('hidden');
        
        const errData = {
            id: `err-${Date.now()}`,
            sender_id: 'bot',
            username: 'System',
            content: "I couldn't connect to the knowledge base. Please try again later.",
            created_at: new Date().toISOString()
        };
        
        if (typeof appendMessage === 'function') appendMessage(errData);
    }
}

// Helper needed for the above function
function formatBotText(text) {
    if (!text) return '';
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    let formatted = text.replace(urlRegex, function(url) {
        return `<a href="${url}" target="_blank" style="color: #a78bfa; text-decoration: underline;">${url}</a>`;
    });
    return formatted.replace(/\n/g, '<br>');
}
        
        window.handleQuickReply = function(text) {
            if (isAssigned || isTicketClosed) return;
            messageInput.value = text;
            messageInput.focus();
            sendMessage();
        };
        
        messageInput?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (!isTicketClosed) sendMessage();
            }
        });
        
        messageForm?.addEventListener('submit', e => {
            e.preventDefault();
            sendMessage();
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            loadMessages();
            setInterval(() => {
                if (!document.hidden) {
                    loadMessages();
                    checkStatus();
                }
            }, 3000);
            
            if (isTicketClosed) {
                messageInput.disabled = true;
                messageInput.placeholder = "This conversation has ended";
                sendBtn.disabled = true;
            }
        });
    })();
    </script>
</body>
</html>