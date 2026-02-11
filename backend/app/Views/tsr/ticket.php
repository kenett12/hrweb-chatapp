<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Chat - Ticket #<?= $ticket['id'] ?></title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?= base_url('css/theme.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/styles.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Prevent automatic image viewer */
        .image-viewer-modal, #imageModal, .modal.show {
            display: none !important;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #fafbfc;
            color: #1a1a1a;
            height: 100vh;
            overflow: hidden;
        }

        .chat-container {
            display: flex;
            height: 100vh;
            background: #fff;
        }

        .chat-sidebar {
            width: 350px;
            background: #fff;
            border-right: 1px solid #f3f4f6;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            background: #fafbfc;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
            border: 1px solid #e5e7eb;
        }

        .back-btn:hover {
            background: #f9fafb;
            color: #374151;
            border-color: #d1d5db;
        }

        .sidebar-content {
            padding: 1.5rem;
            flex: 1;
            overflow-y: auto;
        }

        .customer-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }

        .customer-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: #fafbfc;
            border-radius: 12px;
            border: 1px solid #f3f4f6;
        }

        .customer-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(108, 92, 231, 1), rgba(108, 92, 231, 0.8));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.125rem;
        }

        .customer-name {
            font-weight: 600;
            color: #111827;
            margin: 0;
        }

        .ticket-details {
            margin-bottom: 2rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f9fafb;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }

        .detail-value {
            font-weight: 600;
            color: #111827;
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-open { background: rgba(245, 158, 11, 0.1); color: #d97706; }
        .status-in-progress { background: rgba(108, 92, 231, 0.1); color: rgba(108, 92, 231, 1); }
        .status-resolved { background: rgba(16, 185, 129, 0.1); color: #059669; }
        .status-closed { background: rgba(156, 163, 175, 0.1); color: #6b7280; }

        .priority-urgent { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .priority-high { background: rgba(245, 158, 11, 0.1); color: #d97706; }
        .priority-medium { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
        .priority-low { background: rgba(16, 185, 129, 0.1); color: #059669; }

        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .chat-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #f3f4f6;
            background: #fafbfc;
        }

        .chat-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
            margin: 0 0 0.5rem 0;
        }

        .chat-subtitle {
            color: #6b7280;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
        }

        /* Waiting Screen */
        .waiting-screen {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            text-align: center;
            background: #fafbfc;
        }

        .waiting-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(108, 92, 231, 1), rgba(108, 92, 231, 0.8));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            position: relative;
            animation: pulse 2s infinite;
        }

        .waiting-icon i {
            font-size: 3rem;
            color: white;
        }

        .waiting-icon::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border: 2px solid rgba(108, 92, 231, 0.3);
            border-radius: 50%;
            animation: ripple 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes ripple {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.2); opacity: 0; }
        }

        .waiting-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 1rem;
        }

        .waiting-description {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 2rem;
            max-width: 400px;
        }

        .waiting-info {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .waiting-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .waiting-info-item i {
            color: rgba(108, 92, 231, 1);
        }

        /* Chat Messages */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
            background: #fafbfc;
            display: none;
        }

        .chat-messages.active {
            display: block;
        }

        .message {
            margin-bottom: 1.5rem;
            max-width: 70%;
            clear: both;
        }

        .message-customer {
            float: left;
            margin-right: auto;
        }

        .message-tsr {
            float: right;
            margin-left: auto;
        }

        .message-system {
            clear: both;
            float: none;
            margin: 1.5rem auto;
            max-width: 80%;
            text-align: center;
        }

        .message-bubble {
            padding: 1rem 1.25rem;
            border-radius: 16px;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .message-customer .message-bubble {
            background: rgba(108, 92, 231, 0.3);
            color: #1a1a1a;
            border-bottom-left-radius: 4px;
        }

        .message-tsr .message-bubble {
            background: #fff;
            color: #111827;
            border: 1px solid #f3f4f6;
            border-bottom-right-radius: 4px;
        }

        .message-system .message-bubble {
            background: #f9fafb;
            color: #6b7280;
            border-radius: 16px;
            font-style: italic;
            padding: 0.75rem 1rem;
            border: 1px solid #f3f4f6;
        }

        .message-sender {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.375rem;
            opacity: 0.8;
        }

        .message-content {
            line-height: 1.5;
            word-wrap: break-word;
        }

        .message-time {
            font-size: 0.6875rem;
            opacity: 0.7;
            margin-top: 0.5rem;
            text-align: right;
        }

        .message-system .message-time {
            text-align: center;
            margin-top: 0.375rem;
        }

        /* Chat Input */
        .chat-input {
            padding: 1.5rem 2rem;
            background: #fff;
            border-top: 1px solid #f3f4f6;
            display: none;
        }

        .chat-input.active {
            display: block;
        }

        .input-container {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .message-input {
            flex: 1;
            border: 2px solid #f3f4f6;
            border-radius: 20px;
            padding: 0.875rem 1.25rem;
            outline: none;
            font-size: 0.875rem;
            resize: none;
            min-height: 44px;
            max-height: 120px;
            transition: border-color 0.2s;
            font-family: inherit;
        }

        .message-input:focus {
            border-color: rgba(108, 92, 231, 0.3);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
        }

        .send-btn {
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 50%;
            background: rgba(108, 92, 231, 0.3);
            color: #1a1a1a;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 600;
        }

        .send-btn:hover {
            background: rgba(108, 92, 231, 0.4);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(108, 92, 231, 0.2);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }

        .notification.success {
            border-left: 4px solid #10b981;
        }

        .notification.error {
            border-left: 4px solid #ef4444;
        }

        .notification.info {
            border-left: 4px solid rgba(108, 92, 231, 1);
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notification-content i {
            font-size: 18px;
        }

        .notification.success .notification-content i {
            color: #10b981;
        }

        .notification.error .notification-content i {
            color: #ef4444;
        }

        .notification.info .notification-content i {
            color: rgba(108, 92, 231, 1);
        }

        .notification-close {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #6b7280;
            transition: color 0.2s;
        }

        .notification-close:hover {
            color: #374151;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
            }

            .chat-sidebar {
                width: 100%;
                height: auto;
                max-height: 40vh;
            }

            .chat-main {
                height: 60vh;
            }

            .message {
                max-width: 85%;
            }

            .chat-messages {
                padding: 1.5rem;
            }

            .chat-input {
                padding: 1rem 1.5rem;
            }

            .waiting-screen {
                padding: 2rem 1rem;
            }

            .waiting-icon {
                width: 80px;
                height: 80px;
            }

            .waiting-icon i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <a href="<?= base_url('chat') ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Chat
                </a>
            </div>
            
            <div class="sidebar-content">
                <!-- Customer Info -->
                <div class="customer-section">
                    <div class="section-title">Support Agent</div>
                    <div class="customer-card" id="agentCard">
                        <div class="customer-avatar">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div>
                            <h6 class="customer-name" id="agentName">
                                <?= isset($ticket['assigned_tsr_name']) ? $ticket['assigned_tsr_name'] : 'Waiting for assignment...' ?>
                            </h6>
                        </div>
                    </div>
                </div>

                <!-- Ticket Details -->
                <div class="ticket-details">
                    <div class="section-title">Ticket Details</div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Ticket ID</span>
                        <span class="detail-value">#<?= $ticket['id'] ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Subject</span>
                        <span class="detail-value"><?= $ticket['subject'] ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Category</span>
                        <span class="detail-value"><?= ucfirst($ticket['category']) ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Priority</span>
                        <span class="status-badge priority-<?= strtolower($ticket['priority']) ?>">
                            <?= ucfirst($ticket['priority']) ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="status-badge status-<?= str_replace('-', '-', strtolower($ticket['status'])) ?>" id="ticketStatus">
                            <?= ucfirst(str_replace('-', ' ', $ticket['status'])) ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Created</span>
                        <span class="detail-value"><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-main">
            <div class="chat-header">
                <h1 class="chat-title"><?= $ticket['subject'] ?></h1>
                <p class="chat-subtitle">
                    <span>Ticket #<?= $ticket['id'] ?></span>
                    <span class="status-badge status-<?= str_replace('-', '-', strtolower($ticket['status'])) ?>" id="headerStatus">
                        <?= ucfirst(str_replace('-', ' ', $ticket['status'])) ?>
                    </span>
                    <span class="status-badge priority-<?= strtolower($ticket['priority']) ?>">
                        <?= ucfirst($ticket['priority']) ?> Priority
                    </span>
                </p>
            </div>

            <!-- Waiting Screen -->
            <div class="waiting-screen" id="waitingScreen" style="<?= ($ticket['status'] === 'open' || !isset($ticket['assigned_tsr_id'])) ? 'display: flex;' : 'display: none;' ?>">
                <div class="waiting-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h2 class="waiting-title">Waiting for Technical Support</h2>
                <p class="waiting-description">A TSR will be with you shortly to assist with your ticket</p>
                <div class="waiting-info">
                    <div class="waiting-info-item">
                        <i class="fas fa-clock"></i>
                        <span>Average response time: 2-5 minutes</span>
                    </div>
                    <div class="waiting-info-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Your conversation is secure and private</span>
                    </div>
                </div>
            </div>

            <!-- Chat Messages -->
            <div class="chat-messages" id="chatMessages" data-ticket-id="<?= $ticket['id'] ?>" style="<?= ($ticket['status'] !== 'open' && isset($ticket['assigned_tsr_id'])) ? 'display: block;' : 'display: none;' ?>">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): ?>
                        <?php if ($message['sender_type'] === 'system' || $message['is_system'] == 1): ?>
                            <div class="message message-system">
                                <div class="message-bubble">
                                    <i class="fas fa-info-circle"></i> <?= $message['content'] ?? $message['message'] ?>
                                    <div class="message-time"><?= date('M j, g:i A', strtotime($message['created_at'])) ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php $isCustomer = $message['sender_id'] == session()->get('id'); ?>
                            <div class="message <?= $isCustomer ? 'message-customer' : 'message-tsr' ?>">
                                <div class="message-bubble">
                                    <div class="message-sender">
                                        <?= $isCustomer ? 'You' : ($ticket['assigned_tsr_name'] ?? 'Support Agent') ?>
                                    </div>
                                    <div class="message-content">
                                        <?= nl2br(esc($message['content'] ?? $message['message'])) ?>
                                    </div>
                                    <div class="message-time">
                                        <?= date('M j, g:i A', strtotime($message['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Chat Input -->
            <div class="chat-input" id="chatInput" style="<?= ($ticket['status'] !== 'open' && isset($ticket['assigned_tsr_id'])) ? 'display: block;' : 'display: none;' ?>">
                <form id="messageForm">
                    <div class="input-container">
                        <textarea 
                            id="messageInput" 
                            class="message-input" 
                            placeholder="Type your message here..."
                            rows="1"
                        ></textarea>
                        <button type="submit" class="send-btn" id="sendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

   <!-- Image Viewer Modal -->
   <div id="imageViewer" class="image-viewer" style="display: none;">
       <div class="image-viewer-overlay" onclick="closeImageViewer()"></div>
       <div class="image-viewer-content">
           <button class="image-viewer-close" onclick="closeImageViewer()">
               <i class="fas fa-times"></i>
           </button>
           <img id="imageViewerImg" src="/placeholder.svg" alt="Image">
       </div>
   </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.socket.io/4.0.0/socket.io.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ticketId = <?= $ticket['id'] ?>;
            const currentUserId = <?= session()->get('id') ?>;
            const baseUrl = '<?= base_url() ?>';
            
            // DOM elements
            const waitingScreen = document.getElementById('waitingScreen');
            const chatMessages = document.getElementById('chatMessages');
            const chatInput = document.getElementById('chatInput');
            const messageForm = document.getElementById('messageForm');
            const messageInput = document.getElementById('messageInput');
            const sendBtn = document.getElementById('sendBtn');
            const ticketStatus = document.getElementById('ticketStatus');
            const headerStatus = document.getElementById('headerStatus');
            const agentName = document.getElementById('agentName');
            
            let socket = null;
            let isSocketConnected = false;
            let reconnectAttempts = 0;
            const maxReconnectAttempts = 3;

            // Initialize Socket.IO with better error handling
            function initializeSocket() {
                try {
                    socket = io('http://localhost:3001', {
                        transports: ['websocket', 'polling'],
                        timeout: 5000,
                        forceNew: true
                    });

                    socket.on('connect', function() {
                        console.log('Socket connected successfully');
                        isSocketConnected = true;
                        reconnectAttempts = 0;
                        
                        // Join ticket room
                        socket.emit('join_ticket_room', {
                            ticket_id: ticketId,
                            user_id: currentUserId,
                            user_type: 'customer'
                        });
                    });

                    socket.on('disconnect', function() {
                        console.log('Socket disconnected');
                        isSocketConnected = false;
                    });

                    socket.on('connect_error', function(error) {
                        console.log('Socket connection error:', error);
                        isSocketConnected = false;
                        
                        if (reconnectAttempts < maxReconnectAttempts) {
                            reconnectAttempts++;
                            console.log(`Retrying connection... Attempt ${reconnectAttempts}`);
                        } else {
                            console.log('Max reconnection attempts reached. Disabling socket.');
                            socket.disconnect();
                            socket = null;
                        }
                    });

                    // Listen for ticket claimed event
                    socket.on('ticket_claimed', function(data) {
                        console.log('Ticket claimed:', data);
                        if (data.ticket_id == ticketId) {
                            handleTicketClaimed(data);
                        }
                    });

                    // Listen for TSR joined event
                    socket.on('tsr_joined', function(data) {
                        console.log('TSR joined:', data);
                        if (data.ticket_id == ticketId) {
                            handleTSRJoined(data);
                        }
                    });

                    // Listen for new messages
                    socket.on('new_ticket_message', function(data) {
                        console.log('New message received:', data);
                        if (data.ticket_id == ticketId) {
                            addMessageToChat(data);
                        }
                    });

                    // Listen for status updates
                    socket.on('ticket_status_updated', function(data) {
                        console.log('Ticket status updated:', data);
                        if (data.ticket_id == ticketId) {
                            updateTicketStatus(data.status);
                        }
                    });

                } catch (error) {
                    console.error('Error initializing socket:', error);
                    socket = null;
                }
            }

            // Handle ticket claimed
            function handleTicketClaimed(data) {
                showNotification('A support agent has joined your ticket!', 'success');
                
                // Update agent info
                if (data.tsr && data.tsr.username) {
                    agentName.textContent = data.tsr.username;
                }
                
                // Update status
                updateTicketStatus('in-progress');
                
                // Transition to chat
                transitionToChat();
                
                // Add system message
                addSystemMessage('Support agent has joined the conversation');
            }

            // Handle TSR joined
            function handleTSRJoined(data) {
                if (data.tsr_name) {
                    agentName.textContent = data.tsr_name;
                }
                transitionToChat();
            }

            // Transition from waiting to chat
            function transitionToChat() {
                waitingScreen.style.display = 'none';
                chatMessages.style.display = 'block';
                chatInput.style.display = 'block';
                
                // Scroll to bottom
                setTimeout(() => {
                    scrollToBottom();
                }, 100);
            }

            // Update ticket status
            function updateTicketStatus(status) {
                const statusText = status.charAt(0).toUpperCase() + status.slice(1).replace('-', ' ');
                const statusClass = `status-${status.replace('_', '-')}`;
                
                // Update status badges
                if (ticketStatus) {
                    ticketStatus.textContent = statusText;
                    ticketStatus.className = `status-badge ${statusClass}`;
                }
                
                if (headerStatus) {
                    headerStatus.textContent = statusText;
                    headerStatus.className = `status-badge ${statusClass}`;
                }
            }

            // Add system message
            function addSystemMessage(content) {
                const messageElement = document.createElement('div');
                messageElement.className = 'message message-system';
                messageElement.innerHTML = `
                    <div class="message-bubble">
                        <i class="fas fa-info-circle"></i> ${content}
                        <div class="message-time">${new Date().toLocaleString('en-US', {month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit'})}</div>
                    </div>
                `;
                
                chatMessages.appendChild(messageElement);
                scrollToBottom();
            }

            // Add message to chat
            function addMessageToChat(data) {
                const isCustomer = data.sender_id == currentUserId;
                const messageElement = document.createElement('div');
                messageElement.className = `message ${isCustomer ? 'message-customer' : 'message-tsr'}`;
                messageElement.innerHTML = `
                    <div class="message-bubble">
                        <div class="message-sender">
                            ${isCustomer ? 'You' : (data.sender_name || 'Support Agent')}
                        </div>
                        <div class="message-content">
                            ${data.content.replace(/\n/g, '<br>')}
                        </div>
                        <div class="message-time">
                            ${new Date(data.created_at).toLocaleString('en-US', {month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit'})}
                        </div>
                    </div>
                `;
                
                chatMessages.appendChild(messageElement);
                scrollToBottom();
            }

            // Send message
            function sendMessage() {
                const message = messageInput.value.trim();
                if (!message) return;

                // Disable send button
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                // Show message immediately (optimistic UI)
                const messageElement = document.createElement('div');
                messageElement.className = 'message message-customer';
                messageElement.innerHTML = `
                    <div class="message-bubble">
                        <div class="message-sender">You</div>
                        <div class="message-content">${message.replace(/\n/g, '<br>')}</div>
                        <div class="message-time">${new Date().toLocaleString('en-US', {month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit'})}</div>
                    </div>
                `;
                
                chatMessages.appendChild(messageElement);
                
                // Clear input and scroll
                messageInput.value = '';
                messageInput.style.height = 'auto';
                scrollToBottom();

                // Send to server
                fetch(`${baseUrl}/api/tickets/customer/send-message`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        ticket_id: ticketId,
                        message: message,
                        sender_type: 'customer'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Message sent successfully');
                        
                        // Emit socket event if connected
                        if (socket && isSocketConnected) {
                            socket.emit('ticket_message', {
                                ticket_id: ticketId,
                                message: message,
                                sender_id: currentUserId,
                                sender_type: 'customer',
                                sender_name: 'Customer'
                            });
                        }
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    // Remove the optimistic message on error
                    messageElement.remove();
                    // Restore the message in input
                    messageInput.value = message;
                    showNotification('Failed to send message: ' + error.message, 'error');
                })
                .finally(() => {
                    // Re-enable send button
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                });
            }

            // Scroll to bottom
            function scrollToBottom() {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            // Show notification
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerHTML = `
                    <div class="notification-content">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                        <span>${message}</span>
                    </div>
                    <button class="notification-close">
                        <i class="fas fa-times"></i>
                    </button>
                `;

                document.body.appendChild(notification);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 5000);

                // Close button functionality
                notification.querySelector('.notification-close').addEventListener('click', () => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                });
            }

            // Auto-resize textarea
            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });
            }

            // Handle form submission
            if (messageForm) {
                messageForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    sendMessage();
                });
            }

            
            if (messageInput) {
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }

            // Poll for status updates every 10 seconds
            function pollTicketStatus() {
                fetch(`${baseUrl}/api/tickets/customer/${ticketId}/status`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.ticket) {
                            const ticket = data.ticket;
                            
                            // Check if ticket was claimed
                            if (ticket.assigned_tsr_id && waitingScreen.style.display !== 'none') {
                                handleTicketClaimed({
                                    ticket_id: ticketId,
                                    tsr: { username: ticket.assigned_tsr_name },
                                    status: ticket.status
                                });
                            }
                            
                            // Update status if changed
                            updateTicketStatus(ticket.status);
                            
                            // Update agent name if available
                            if (ticket.assigned_tsr_name && agentName.textContent !== ticket.assigned_tsr_name) {
                                agentName.textContent = ticket.assigned_tsr_name;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error polling ticket status:', error);
                    });
            }

            // Initialize socket and start polling
            initializeSocket();
            
            // Start polling for status updates
            setInterval(pollTicketStatus, 10000);
            
            // Initial scroll to bottom if chat is active
            if (chatMessages.style.display === 'block') {
                setTimeout(scrollToBottom, 100);
            }
        });
    </script>
</body>
</html>
