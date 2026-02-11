/**
 * Reply Handler for Chat Application
 * Manages the reply functionality for messages
 */
class ReplyHandler {
  constructor() {
    this.replyContainer = document.getElementById("reply-container")
    this.replyClose = document.querySelector(".reply-close")
    this.replyHeader = document.querySelector(".reply-header")
    this.replyText = document.querySelector(".reply-text")
    this.activeReply = null

    this.setupEventListeners()
    console.log("ReplyHandler initialized")
  }

  setupEventListeners() {
    // Set up the reply button click event on all messages
    document.addEventListener("click", (e) => {
      // Check if the clicked element is a reply button or its child
      if (e.target.closest('.message-action-btn[title="Reply"]')) {
        const messageElement = e.target.closest(".message")
        this.handleReplyClick(messageElement)
      }
    })

    // Set up close button
    if (this.replyClose) {
      this.replyClose.addEventListener("click", () => this.cancelReply())
    }
  }

  // Update the handleReplyClick method to better extract message content
  handleReplyClick(messageElement) {
    if (!messageElement) return

    console.log("Reply clicked on message element:", messageElement)

    // Get message data
    const messageId = messageElement.dataset.messageId || messageElement.dataset.id

    // CRITICAL FIX: Ensure we always have a valid sender ID
    // First try to get it from the dataset
    let senderId = messageElement.dataset.senderId

    // If it's missing or invalid, determine based on message ownership
    if (!senderId || senderId === "null" || senderId === "undefined" || senderId === "0") {
      if (messageElement.classList.contains("own-message")) {
        // If it's our own message, use our ID
        senderId = window.currentUserId || document.getElementById("current-user-id")?.value
        console.log("Using current user ID for own message:", senderId)
      } else {
        // If it's someone else's message, use the other user's ID
        senderId = window.otherUserId || document.getElementById("other-user-id")?.value
        console.log("Using other user ID for received message:", senderId)
      }
    }

    // IMPROVED CONTENT EXTRACTION: Get the actual message content
    let content = ""

    // First try to get content from the message-text element
    const messageText = messageElement.querySelector(".message-text")
    if (messageText) {
      content = messageText.textContent.trim()
    }
    // If not found, try the message-body element
    else {
      const messageBody = messageElement.querySelector(".message-body")
      if (messageBody) {
        // Clone the node to avoid modifying the original
        const tempBody = messageBody.cloneNode(true)

        // Remove any replied-message elements from our clone
        const repliedMessage = tempBody.querySelector(".replied-message")
        if (repliedMessage) {
          repliedMessage.remove()
        }

        content = tempBody.textContent.trim()
      }
    }

    // If still no content, try dataset or fallback to the entire message text
    if (!content) {
      content = messageElement.dataset.content || messageElement.textContent.trim()
    }

    // Clean the content - remove timestamps and "Sent" text
    content = content.replace(/\d{1,2}:\d{2}/g, "")
    content = content.replace(/\s*Sent\s*/g, "")
    content = content.replace(/\s*Seen\s*/g, "")
    content = content.trim()

    // If content is still empty, use a fallback
    if (!content) {
      content = "This message"
    }

    // Get sender name - improved logic to avoid "Unknown User"
    let senderName = "User"

    // First check if this is the current user's message
    if (messageElement.classList.contains("own-message")) {
      // If it's the current user's message, use the current user's username or nickname
      senderName = window.currentUser?.nickname || window.currentUser?.username || "You"
      console.log("ReplyHandler: Sender is current user, name:", senderName)
    } else {
      // Try to get from message-sender element
      const senderElement = messageElement.querySelector(".message-sender")
      if (senderElement && senderElement.textContent.trim()) {
        senderName = senderElement.textContent.trim()
        console.log("ReplyHandler: Sender name from message-sender:", senderName)
      }
      // If not found, try to get from chat header
      else {
        const headerName =
          document.querySelector(".chat-header-title") || document.querySelector(".chat-header-text h2")
        if (headerName) {
          senderName = headerName.textContent.trim()
          console.log("ReplyHandler: Sender name from chat header:", senderName)
        } else {
          // Fallback to activeChat name if available
          senderName = window.activeChat?.name || "User"
          console.log("ReplyHandler: Sender name from activeChat:", senderName)
        }
      }
    }

    console.log("Reply data:", { id: messageId, senderId, content, senderName })

    // Store reply data
    this.activeReply = {
      id: messageId,
      senderId: senderId,
      content: content,
      senderName: senderName,
    }

    // Update UI
    const replyContainer = document.getElementById("reply-container")
    if (replyContainer) {
      // Set data attributes for the container
      replyContainer.dataset.replyToId = messageId
      replyContainer.dataset.replyToSenderId = senderId
      replyContainer.dataset.replyToContent = content
      replyContainer.dataset.replyToSenderName = senderName

      // Update the visual elements
      const replyHeader = replyContainer.querySelector(".reply-header")
      const replyText = replyContainer.querySelector(".reply-text")

      if (replyHeader) {
        replyHeader.textContent = `Replying to ${senderName}`
      }

      if (replyText) {
        replyText.textContent = content
      }

      // Show the container
      replyContainer.classList.remove("hidden")
    }

    // Focus the message input
    const messageInput = document.getElementById("message-input")
    if (messageInput) {
      messageInput.focus()
    }

    // Store the reply data in a global variable for the send function to use
    window.pendingReplyData = {
      reply_to_id: messageId,
      reply_to_sender_id: senderId,
      reply_to_content: content,
      senderName: senderName,
    }
  }

  setActiveReply(messageId, senderId, content, senderName) {
    if (!this.replyContainer) return

    this.activeReply = {
      id: messageId,
      senderId: senderId,
      content: content,
      senderName: senderName,
    }

    // Update the UI
    if (this.replyHeader) {
      this.replyHeader.textContent = `Replying to ${senderName}`
    }

    if (this.replyText) {
      this.replyText.textContent = content
    }

    this.replyContainer.classList.remove("hidden")

    // Focus the message input
    const messageInput = document.getElementById("message-input")
    if (messageInput) {
      messageInput.focus()
    }

    // Store the reply data in a global variable for the send function to use
    window.pendingReplyData = {
      reply_to_id: messageId,
      reply_to_sender_id: senderId,
      reply_to_content: content,
      senderName: senderName,
    }
  }

  cancelReply() {
    if (!this.replyContainer) return

    this.activeReply = null
    this.replyContainer.classList.add("hidden")

    if (this.replyHeader) {
      this.replyHeader.textContent = ""
    }

    if (this.replyText) {
      this.replyText.textContent = ""
    }

    // Clear the global pending reply data
    window.pendingReplyData = null
  }

  getActiveReply() {
    return this.activeReply
  }

  // Update the formatRepliedMessage method to ensure it uses the correct sender name
  formatRepliedMessage(senderName, content) {
    // Extract just the message content without any timestamps or status indicators
    let cleanContent = content

    // Remove any timestamp patterns (like 01:59) from the content
    cleanContent = cleanContent.replace(/\d{1,2}:\d{2}/g, "")

    // Remove "Sent" text if present
    cleanContent = cleanContent.replace(/\s*Sent\s*/g, "")

    return `
      <div class="replied-message" style="
          background-color: #6c5ce7; 
          padding: 8px 12px; 
          border-radius: 8px 8px 0 0; 
          margin-bottom: 2px;
          font-size: 0.85em;
      ">
          <div style="color: white; font-weight: bold;">Replying to ${senderName}</div>
          <div style="color: white;">${cleanContent.trim()}</div>
      </div>
  `
  }

  // Add this method to the ReplyHandler class to ensure it properly formats the sender name
  startReply(messageData) {
    console.log("Starting reply with data:", messageData)

    // Extract content properly, with fallbacks
    let content = messageData.content
    if (!content || content === "undefined") {
      if (messageData.dataset && messageData.dataset.content && messageData.dataset.content !== "undefined") {
        content = messageData.dataset.content
      } else if (messageData.querySelector) {
        // If messageData is an element, try to extract content
        const messageBody = messageData.querySelector(".message-body")
        if (messageBody) {
          // Remove any nested reply content
          const replyContainer = messageBody.querySelector(".replied-to")
          if (replyContainer) {
            const tempBody = messageBody.cloneNode(true)
            const tempReply = tempBody.querySelector(".replied-to")
            if (tempReply) tempReply.remove()
            content = tempBody.textContent.trim()
          } else {
            content = messageBody.textContent.trim()
          }
        } else {
          content = messageData.textContent?.trim()
        }
      }
    }

    // If still no content, use a fallback
    if (!content || content === "undefined") {
      content = "This message"
    }

    // CRITICAL FIX: Ensure we have a valid sender ID
    let senderId = messageData.senderId || messageData.dataset?.senderId

    // If it's missing or invalid, determine based on message ownership
    if (!senderId || senderId === "null" || senderId === "undefined" || senderId === "0") {
      // Check if this is the current user's message
      if (messageData.classList && messageData.classList.contains("own-message")) {
        senderId = window.currentUserId || window.userId
        console.log("Using current user ID for own message:", senderId)
      } else {
        // If not own message, use the other user's ID
        senderId = window.otherUserId || window.activeChat?.id
        console.log("Using other user ID for received message:", senderId)
      }
    }

    this.activeReply = {
      id: messageData.id || messageData.dataset?.messageId,
      senderId: senderId,
      content: content,
      senderName: messageData.senderName || messageData.dataset?.senderName || "User",
    }

    console.log("Active reply set to:", this.activeReply)

    // Update the UI
    if (this.replyHeader) {
      this.replyHeader.textContent = `Replying to ${this.activeReply.senderName}`
    }

    if (this.replyText) {
      this.replyText.textContent = this.activeReply.content
    }

    if (this.replyContainer) {
      this.replyContainer.classList.remove("hidden")

      // Set data attributes for the container
      this.replyContainer.dataset.replyToId = this.activeReply.id
      this.replyContainer.dataset.replyToSenderId = this.activeReply.senderId
      this.replyContainer.dataset.replyToContent = this.activeReply.content
      this.replyContainer.dataset.replyToSenderName = this.activeReply.senderName
    }

    // Focus the message input
    const messageInput = document.getElementById("message-input")
    if (messageInput) {
      messageInput.focus()
    }

    // Store the reply data in a global variable for the send function to use
    window.pendingReplyData = {
      reply_to_id: this.activeReply.id,
      reply_to_sender_id: this.activeReply.senderId,
      reply_to_content: this.activeReply.content,
      senderName: this.activeReply.senderName,
    }
  }
}

// Initialize the reply handler when the DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  if (!window.replyHandler) {
    window.replyHandler = new ReplyHandler()
    console.log("Reply handler initialized")
  }
})
