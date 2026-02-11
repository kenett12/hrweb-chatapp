document.addEventListener("DOMContentLoaded", () => {
    // 1. Configuration & IDs
    const ticketElement = document.querySelector("[data-ticket-id]");
    const userElement = document.querySelector("[data-user-id]");
    
    if (!ticketElement || !userElement) return;

    const ticketId = ticketElement.getAttribute("data-ticket-id");
    const currentUserId = userElement.getAttribute("data-user-id");
    
    // 2. DYNAMIC ENDPOINT (The Fix)
    // Check if we are on a staff page or customer page to verify the route
    const isStaffPage = window.location.href.includes('/tsr/') || window.location.href.includes('/admin/');
    const apiSegment = isStaffPage ? 'tsr' : 'customer'; 
    const senderType = isStaffPage ? 'tsr' : 'customer';

    // 3. Base URL
    const baseUrl = (typeof API_URL !== 'undefined') 
        ? API_URL 
        : window.location.origin + '/chat-app/backend/api';

    console.log(`=== CHAT STARTED (${apiSegment.toUpperCase()}) ===`);

    // 4. Elements
    const chatMessages = document.getElementById("chatMessages");
    const messageForm = document.getElementById("messageForm");
    const messageInput = document.getElementById("messageInput");
    const sendBtn = document.getElementById("sendBtn");
    const ticketStatusBadge = document.getElementById("ticketStatus");

    // 5. Start Chat
    initChat();

    function initChat() {
        loadMessages();
        checkTicketStatus();
        
        // Poll every 3 seconds
        setInterval(() => {
            loadMessages(true);
            checkTicketStatus();
        }, 3000);

        if (messageForm) {
            messageForm.addEventListener("submit", (e) => {
                e.preventDefault();
                const msg = messageInput.value.trim();
                if (msg) sendMessage(msg);
            });
        }
    }

    // --- API FUNCTIONS ---

    function checkTicketStatus() {
        // FIXED: Uses apiSegment (customer/tsr) instead of hardcoded 'tsr'
        fetch(`${baseUrl}/tickets/${apiSegment}/${ticketId}/status`)
            .then(res => res.json())
            .then(data => {
                const status = (data.ticket && data.ticket.status) ? data.ticket.status : data.status;
                if (status && ticketStatusBadge) {
                    ticketStatusBadge.textContent = status.toUpperCase();
                    
                    const isClosed = ["closed", "resolved"].includes(status.toLowerCase());
                    if (isClosed && messageInput) {
                        messageInput.disabled = true;
                        messageInput.placeholder = "Ticket is closed";
                        if (sendBtn) sendBtn.disabled = true;
                    }
                }
            })
            .catch(err => console.error("Status Check Error:", err));
    }

    function loadMessages(isPolling = false) {
        // FIXED: Uses apiSegment (customer/tsr)
        fetch(`${baseUrl}/tickets/${apiSegment}/${ticketId}/messages`)
            .then(res => res.json())
            .then(data => {
                const messages = Array.isArray(data) ? data : (data.messages || []);
                renderMessages(messages, isPolling);
            })
            .catch(err => console.error("Load Messages Error:", err));
    }

    function sendMessage(messageText) {
        // Optimistic UI Update
        const tempId = 'temp-' + Date.now();
        renderSingleMessage({
            id: tempId,
            sender_id: currentUserId,
            content: messageText,
            created_at: new Date().toISOString()
        });
        
        messageInput.value = "";
        scrollToBottom();

        // FIXED: Uses apiSegment (customer/tsr)
        fetch(`${baseUrl}/tickets/${apiSegment}/send-message`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                ticket_id: ticketId,
                message: messageText,
                sender_type: senderType
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadMessages(true);
            } else {
                alert("Message failed: " + (data.error || "Unknown Error"));
            }
        });
    }

    // --- UI FUNCTIONS ---

    function renderMessages(messages, isPolling) {
        if (!isPolling) chatMessages.innerHTML = "";
        
        const existingIds = new Set(
            Array.from(chatMessages.querySelectorAll('[id^="msg-"]'))
                 .map(el => el.id.replace('msg-', ''))
        );

        messages.forEach(msg => {
            if (!existingIds.has(msg.id.toString())) {
                renderSingleMessage(msg);
            }
        });
        
        if (!isPolling) scrollToBottom();
    }

    function renderSingleMessage(msg) {
        const div = document.createElement("div");
        div.id = `msg-${msg.id}`;
        
        const isMe = (msg.sender_id == currentUserId);
        div.className = `message ${isMe ? 'message-customer' : 'message-tsr'}`;
        
        const timeString = msg.created_at 
            ? new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
            : '...';

        div.innerHTML = `
            <div class="message-content">
                <div class="message-bubble">
                    <div class="message-text">${msg.message || msg.content}</div>
                </div>
                <div class="message-time"><span>${timeString}</span></div>
            </div>
        `;
        
        chatMessages.appendChild(div);
    }

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});