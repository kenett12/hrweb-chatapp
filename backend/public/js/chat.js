/**
 * Messaging Handler with Philippines Time Sync
 */

// Global base URL configuration
window.baseUrl = window.baseUrl || '/chat-app/backend/';
if (!window.baseUrl.endsWith('/')) window.baseUrl += '/';

/**
 * Ensures timestamps are in Philippines time (GMT+8)
 * @param {string|Date} timestamp 
 */
function formatTimePhilippines(timestamp) {
  if (!timestamp) return "";

  const date = new Date(timestamp);
  
  // If the browser is already in PHT, we use the local time directly.
  // Otherwise, we force an 8-hour offset for UTC strings.
  const isPHT = Intl.DateTimeFormat().resolvedOptions().timeZone === 'Asia/Manila';
  
  return date.toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: true,
    timeZone: 'Asia/Manila' // This is the cleaner, modern way to force PHT
  });
}

/**
 * Send Message functionality
 */
function sendMessage() {
  const messageInput = document.getElementById("message-input");
  const message = messageInput.value ? messageInput.value.trim() : "";

  if (!message) return;

  // 1. Handle Replies
  if (window.replyHandler && window.pendingReplyData) {
    window.sendMessageWithReply(message, window.pendingReplyData);
  } else {
    // 2. Normal Message Flow
    const formData = new FormData();
    formData.append("content", message);
    
    // Fallback logic for IDs
    const otherUserId = window.otherUserId || document.getElementById("other-user-id")?.value;
    const currentUserId = window.currentUserId || document.getElementById("current-user-id")?.value;
    
    formData.append("receiver_id", otherUserId);
    formData.append("is_group", window.groupId ? "1" : "0");

    // 3. Create Temporary Message UI
    if (typeof window.appendMessage === "function") {
      window.appendMessage({
        temp: true,
        sender_id: currentUserId,
        receiver_id: otherUserId,
        content: message,
        type: "text",
        created_at: new Date().toISOString(), // Raw UTC, formatter handles the +8
        username: window.currentUser?.username || "You",
        avatar: window.currentUser?.avatar || "default-avatar.png",
      });
    }

    // 4. API Call
    fetch(`${window.baseUrl}api/saveMessage`, {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
    .then((response) => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then((data) => {
      console.log("Message sent:", data);
      // Optional: replace temp message with real data if necessary
    })
    .catch((error) => {
      console.error("Error sending message:", error);
      const msg = "Failed to send message: " + error.message;
      typeof window.showNotification === "function" ? window.showNotification(msg, "error") : alert(msg);
    });
  }

  messageInput.value = "";
}

// Initialization
document.addEventListener("DOMContentLoaded", () => {
  const sendButton = document.getElementById("send-button");
  const messageInput = document.getElementById("message-input");

  if (sendButton) {
    sendButton.addEventListener("click", sendMessage);
  }

  if (messageInput) {
    messageInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
  }
});