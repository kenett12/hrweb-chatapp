<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical Support Dashboard</title>
    <link rel="stylesheet" href="/public/css/styles.css">
    <link rel="stylesheet" href="/public/css/tsr.css">
    <link rel="stylesheet" href="/public/css/ticket-chat.css">
    <link rel="stylesheet" href="/public/css/message-actions.css">
    <link rel="stylesheet" href="/public/css/file-attachments.css">
    <link rel="stylesheet" href="/public/css/notifications.css">
    <link rel="stylesheet" href="/public/css/seen-indicator.css">
    <style>
        .tsr-chat-container {
            display: flex;
            height: calc(100vh - 60px);
            background: var(--bg-primary);
        }
        
        .ticket-sidebar {
            width: 300px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
        }
        
        .ticket-item {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .ticket-item:hover {
            background: var(--bg-hover);
        }
        
        .ticket-item.active {
            background: var(--primary-color);
            color: white;
        }
        
        .ticket-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .ticket-meta {
            font-size: 12px;
            color: var(--text-secondary);
            display: flex;
            justify-content: space-between;
        }
        
        .ticket-status {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .status-open { background: #e3f2fd; color: #1976d2; }
        .status-in-progress { background: #fff3e0; color: #f57c00; }
        .status-resolved { background: #e8f5e8; color: #388e3c; }
        .status-closed { background: #fafafa; color: #757575; }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 15px 20px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ticket-info h3 {
            margin: 0;
            font-size: 16px;
        }
        
        .ticket-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 6px 12px;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-action:hover {
            background: var(--bg-hover);
        }
        
        .no-ticket-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1>Technical Support Dashboard</h1>
                <p class="lead">Welcome to the TSR dashboard. Manage customer support tickets here.</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> You are now being redirected to the dashboard...
                </div>
            </div>
        </div>
    </div>

    <div class="tsr-chat-container">
        <div class="ticket-sidebar">
            <div class="sidebar-header">
                <h3>Active Tickets</h3>
                <button id="refreshTickets" class="btn-action">Refresh</button>
            </div>
            <div id="ticketsList">
                <!-- Tickets will be loaded here -->
            </div>
        </div>

        <div class="chat-main">
            <div id="noTicketSelected" class="no-ticket-selected">
                <div>Select a ticket to start chatting</div>
            </div>
            
            <div id="ticketChatContainer" style="display: none;">
                <div class="chat-header">
                    <div class="ticket-info">
                        <h3 id="currentTicketTitle">Ticket Title</h3>
                        <div id="currentTicketMeta" class="ticket-meta"></div>
                    </div>
                    <div class="ticket-actions">
                        <select id="statusSelect" class="btn-action">
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <button id="escalateBtn" class="btn-action">Escalate</button>
                    </div>
                </div>

                <div id="messagesContainer" class="messages-container">
                    <!-- Messages will be loaded here -->
                </div>

                <div class="message-input-container">
                    <div class="input-wrapper">
                        <input type="file" id="fileInput" multiple style="display: none;" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt">
                        <button id="attachBtn" class="attach-btn" title="Attach file">📎</button>
                        <input type="text" id="messageInput" placeholder="Type your message..." class="message-input">
                        <button id="sendBtn" class="send-btn">Send</button>
                    </div>
                    <div id="filePreview" class="file-preview" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include necessary scripts -->
    <script src="/public/js/socket-manager.js"></script>
    <script src="/public/js/message-formatter.js"></script>
    <script src="/public/js/message-actions.js"></script>
    <script src="/public/js/image-viewer.js"></script>
    <script src="/public/js/notifications.js"></script>
    
    <script>
        let currentTicketId = null;
        let currentUser = <?= json_encode($user) ?>;
        let socket = null;
        let messageContainer = null;

        document.addEventListener('DOMContentLoaded', function() {
            initializeSocket();
            loadTickets();
            setupEventListeners();
            messageContainer = document.getElementById('messagesContainer');
        });

        function initializeSocket() {
            socket = io('http://localhost:3001');
            
            socket.on('connect', function() {
                console.log('Connected to socket server');
                socket.emit('join_room', 'tsr_' + currentUser.id);
            });

            socket.on('new_ticket_message', function(data) {
                if (data.ticket_id == currentTicketId) {
                    appendMessage(data);
                }
                updateTicketLastMessage(data.ticket_id, data);
            });

            socket.on('ticket_status_updated', function(data) {
                if (data.ticket_id == currentTicketId) {
                    document.getElementById('statusSelect').value = data.status;
                }
                updateTicketStatus(data.ticket_id, data.status);
            });
        }

        function setupEventListeners() {
            document.getElementById('sendBtn').addEventListener('click', sendMessage);
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });

            document.getElementById('attachBtn').addEventListener('click', function() {
                document.getElementById('fileInput').click();
            });

            document.getElementById('fileInput').addEventListener('change', handleFileSelect);
            document.getElementById('refreshTickets').addEventListener('click', loadTickets);
            document.getElementById('statusSelect').addEventListener('change', updateTicketStatus);
            document.getElementById('escalateBtn').addEventListener('click', escalateTicket);
        }

        async function loadTickets() {
            try {
                const response = await fetch('/api/tickets/tsr-list');
                const tickets = await response.json();
                
                const ticketsList = document.getElementById('ticketsList');
                ticketsList.innerHTML = '';

                tickets.forEach(ticket => {
                    const ticketElement = createTicketElement(ticket);
                    ticketsList.appendChild(ticketElement);
                });
            } catch (error) {
                console.error('Error loading tickets:', error);
            }
        }

        function createTicketElement(ticket) {
            const div = document.createElement('div');
            div.className = 'ticket-item';
            div.dataset.ticketId = ticket.id;
            
            const claimButton = ticket.assigned_to ? '' : `
                <button class="claim-btn" onclick="claimTicket(${ticket.id})" style="
                    background: var(--primary-color);
                    color: white;
                    border: none;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 11px;
                    cursor: pointer;
                    margin-left: 10px;
                ">Claim</button>
            `;
            
            div.innerHTML = `
                <div class="ticket-title">${ticket.title || ticket.subject}${claimButton}</div>
                <div class="ticket-meta">
                    <span>Customer: ${ticket.customer_name}</span>
                    <span class="ticket-status status-${ticket.status}">${ticket.status}</span>
                </div>
                <div class="ticket-meta">
                    <span>${new Date(ticket.created_at).toLocaleDateString()}</span>
                    <span>${ticket.priority}</span>
                </div>
            `;

            div.addEventListener('click', (e) => {
                if (!e.target.classList.contains('claim-btn')) {
                    selectTicket(ticket.id);
                }
            });
            return div;
        }

        // Add claim ticket function
        async function claimTicket(ticketId) {
            try {
                const response = await fetch('/api/tickets/claim', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ticket_id=${ticketId}`
                });

                const result = await response.json();
                
                if (result.success) {
                    // Emit socket event
                    socket.emit('ticket_claimed', {
                        ticket_id: ticketId,
                        tsr: result.tsr,
                        room: 'ticket_' + ticketId
                    });
                    
                    // Refresh tickets list
                    loadTickets();
                    
                    // Auto-select the claimed ticket
                    selectTicket(ticketId);
                } else {
                    alert(result.error || 'Failed to claim ticket');
                }
            } catch (error) {
                console.error('Error claiming ticket:', error);
                alert('Failed to claim ticket');
            }
        }

        async function selectTicket(ticketId) {
            // Update UI
            document.querySelectorAll('.ticket-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-ticket-id="${ticketId}"]`).classList.add('active');

            currentTicketId = ticketId;
            
            // Show chat container
            document.getElementById('noTicketSelected').style.display = 'none';
            document.getElementById('ticketChatContainer').style.display = 'flex';

            // Load ticket details and messages
            await loadTicketDetails(ticketId);
            await loadTicketMessages(ticketId);

            // Join ticket room
            socket.emit('join_ticket_room', ticketId);
        }

        async function loadTicketDetails(ticketId) {
            try {
                const response = await fetch(`/api/tickets/${ticketId}`);
                const ticket = await response.json();
                
                document.getElementById('currentTicketTitle').textContent = ticket.title;
                document.getElementById('currentTicketMeta').innerHTML = `
                    Customer: ${ticket.customer_name} | Priority: ${ticket.priority} | Created: ${new Date(ticket.created_at).toLocaleDateString()}
                `;
                document.getElementById('statusSelect').value = ticket.status;
            } catch (error) {
                console.error('Error loading ticket details:', error);
            }
        }

        async function loadTicketMessages(ticketId) {
            try {
                const response = await fetch(`/api/tickets/${ticketId}/messages`);
                const messages = await response.json();
                
                messageContainer.innerHTML = '';
                messages.forEach(message => {
                    appendMessage(message);
                });
                
                scrollToBottom();
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        async function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            const fileInput = document.getElementById('fileInput');
            
            if (!message && fileInput.files.length === 0) return;
            if (!currentTicketId) return;

            const formData = new FormData();
            formData.append('ticket_id', currentTicketId);
            formData.append('message', message);
            formData.append('sender_type', 'tsr');
            
            // Handle file attachments
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('files[]', fileInput.files[i]);
            }

            try {
                const response = await fetch('/api/tickets/send-message', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    messageInput.value = '';
                    fileInput.value = '';
                    document.getElementById('filePreview').style.display = 'none';
                    
                    const messageData = await response.json();
                    
                    // Emit to socket for real-time updates
                    socket.emit('ticket_message', {
                        ticket_id: currentTicketId,
                        message: messageData,
                        room: 'ticket_' + currentTicketId
                    });
                }
            } catch (error) {
                console.error('Error sending message:', error);
            }
        }

        function appendMessage(messageData) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${messageData.sender_type === 'tsr' ? 'sent' : 'received'}`;
            messageDiv.dataset.messageId = messageData.id;

            const time = new Date(messageData.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            let messageContent = `
                <div class="message-header">
                    <span class="sender-name">${messageData.sender_name}</span>
                    <span class="message-time">${time}</span>
                </div>
            `;

            if (messageData.message) {
                messageContent += `<div class="message-text">${escapeHtml(messageData.message)}</div>`;
            }

            if (messageData.file_path) {
                messageContent += createFileAttachment(messageData);
            }

            messageDiv.innerHTML = messageContent;
            messageContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        function createFileAttachment(messageData) {
            const fileName = messageData.original_filename || messageData.file_path.split('/').pop();
            const fileExtension = fileName.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension);
            
            if (isImage) {
                return `
                    <div class="file-attachment image-attachment">
                        <img src="${messageData.file_path}" alt="${fileName}" class="attachment-image" onclick="openImageViewer('${messageData.file_path}')">
                        <div class="file-info">
                            <span class="file-name">${fileName}</span>
                        </div>
                    </div>
                `;
            } else {
                return `
                    <div class="file-attachment">
                        <div class="file-icon">📄</div>
                        <div class="file-info">
                            <span class="file-name">${fileName}</span>
                            <a href="${messageData.file_path}" download class="download-link">Download</a>
                        </div>
                    </div>
                `;
            }
        }

        function handleFileSelect(event) {
            const files = event.target.files;
            const preview = document.getElementById('filePreview');
            
            if (files.length > 0) {
                preview.innerHTML = '';
                preview.style.display = 'block';
                
                Array.from(files).forEach(file => {
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'file-preview-item';
                    fileDiv.innerHTML = `
                        <span>${file.name}</span>
                        <button onclick="removeFile('${file.name}')" class="remove-file">×</button>
                    `;
                    preview.appendChild(fileDiv);
                });
            } else {
                preview.style.display = 'none';
            }
        }

        async function updateTicketStatus() {
            if (!currentTicketId) return;
            
            const status = document.getElementById('statusSelect').value;
            
            try {
                const response = await fetch(`/api/tickets/${currentTicketId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ status: status })
                });

                if (response.ok) {
                    socket.emit('ticket_status_update', {
                        ticket_id: currentTicketId,
                        status: status,
                        room: 'ticket_' + currentTicketId
                    });
                }
            } catch (error) {
                console.error('Error updating ticket status:', error);
            }
        }

        function escalateTicket() {
            if (!currentTicketId) return;
            
            // Implement escalation logic
            console.log('Escalating ticket:', currentTicketId);
        }

        function scrollToBottom() {
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function updateTicketLastMessage(ticketId, messageData) {
            // Update the ticket item in sidebar with last message info
            const ticketElement = document.querySelector(`[data-ticket-id="${ticketId}"]`);
            if (ticketElement) {
                // Add visual indicator for new message if not current ticket
                if (ticketId != currentTicketId) {
                    ticketElement.classList.add('has-new-message');
                }
            }
        }

        function updateTicketStatus(ticketId, status) {
            const ticketElement = document.querySelector(`[data-ticket-id="${ticketId}"]`);
            if (ticketElement) {
                const statusElement = ticketElement.querySelector('.ticket-status');
                statusElement.className = `ticket-status status-${status}`;
                statusElement.textContent = status;
            }
        }
    </script>

    <script>
        // Redirect to dashboard after a short delay
        setTimeout(function() {
            window.location.href = '<?= base_url('tsr/dashboard') ?>';
        }, 1000);
    </script>
</body>
</html>
