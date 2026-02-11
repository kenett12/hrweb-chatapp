/**
 * Chat UI
 * Handles UI interactions for the chat interface
 */

document.addEventListener("DOMContentLoaded", () => {
  // Initialize UI components
  initScrollToBottom()
  initMessageInput()
  initEmojiPicker()
  initFileAttachments()

  // Apply Teams/Skype style to messages
  applyTeamsStyle()
})

/**
 * Initialize auto-scroll to bottom
 */
function initScrollToBottom() {
  const chatMessages = document.getElementById("chat-messages")
  if (!chatMessages) return

  // Scroll to bottom initially
  scrollToBottom()

  // Add scroll event listener to show "scroll to bottom" button when needed
  chatMessages.addEventListener("scroll", () => {
    const scrollButton = document.getElementById("scroll-to-bottom")
    if (!scrollButton) return

    // Show button if not at bottom
    const isAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop <= chatMessages.clientHeight + 100
    scrollButton.style.display = isAtBottom ? "none" : "flex"
  })

  // Add click event to scroll button
  const scrollButton = document.getElementById("scroll-to-bottom")
  if (scrollButton) {
    scrollButton.addEventListener("click", scrollToBottom)
  }
}

/**
 * Scroll chat to bottom
 */
function scrollToBottom() {
  const chatMessages = document.getElementById("chat-messages")
  if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight
  }
}

/**
 * Initialize message input auto-resize
 */
function initMessageInput() {
  const messageInput = document.getElementById("message-input")
  if (!messageInput) return

  // Auto-resize input as user types
  messageInput.addEventListener("input", function () {
    this.style.height = "auto"
    this.style.height = this.scrollHeight + "px"

    // Reset height if empty
    if (this.value === "") {
      this.style.height = ""
    }
  })

  // Send on Enter (but allow Shift+Enter for new line)
  messageInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault()
      document.getElementById("send-btn").click()
    }
  })
}

/**
 * Initialize emoji picker
 */
function initEmojiPicker() {
  const emojiButton = document.getElementById("emoji-btn")
  const emojiPicker = document.getElementById("emoji-picker")

  if (!emojiButton || !emojiPicker) return

  // Toggle emoji picker
  emojiButton.addEventListener("click", () => {
    emojiPicker.classList.toggle("hidden")
  })

  // Close emoji picker when clicking outside
  document.addEventListener("click", (e) => {
    if (!emojiButton.contains(e.target) && !emojiPicker.contains(e.target)) {
      emojiPicker.classList.add("hidden")
    }
  })

  // Handle emoji selection
  const emojis = emojiPicker.querySelectorAll(".emoji")
  emojis.forEach((emoji) => {
    emoji.addEventListener("click", function () {
      const messageInput = document.getElementById("message-input")
      messageInput.value += this.textContent
      messageInput.focus()
      emojiPicker.classList.add("hidden")
    })
  })
}

/**
 * Initialize file attachments
 */
function initFileAttachments() {
  const attachButton = document.getElementById("attach-btn")
  const fileInput = document.getElementById("file-input")

  if (!attachButton || !fileInput) return

  // Open file dialog
  attachButton.addEventListener("click", () => {
    fileInput.click()
  })

  // Handle file selection
  fileInput.addEventListener("change", function () {
    if (this.files.length > 0) {
      // Show selected file name in input
      const messageInput = document.getElementById("message-input")
      messageInput.value = `File: ${this.files[0].name}`
      messageInput.disabled = true

      // Show file preview if possible
      showFilePreview(this.files[0])
    }
  })
}

/**
 * Show file preview
 * @param {File} file - Selected file
 */
function showFilePreview(file) {
  const previewContainer = document.getElementById("file-preview")
  if (!previewContainer) return

  previewContainer.innerHTML = ""
  previewContainer.classList.remove("hidden")

  // Create preview based on file type
  if (file.type.startsWith("image/")) {
    // Image preview
    const img = document.createElement("img")
    img.src = URL.createObjectURL(file)
    img.className = "file-preview-image"

    const caption = document.createElement("div")
    caption.className = "file-preview-caption"
    caption.textContent = file.name

    previewContainer.appendChild(img)
    previewContainer.appendChild(caption)
  } else {
    // Generic file preview
    const icon = document.createElement("div")
    icon.className = "file-preview-icon"
    icon.innerHTML = '<i class="fas fa-file"></i>'

    const info = document.createElement("div")
    info.className = "file-preview-info"

    const name = document.createElement("div")
    name.className = "file-preview-name"
    name.textContent = file.name

    const size = document.createElement("div")
    size.className = "file-preview-size"
    size.textContent = formatFileSize(file.size)

    info.appendChild(name)
    info.appendChild(size)

    previewContainer.appendChild(icon)
    previewContainer.appendChild(info)
  }

  // Add cancel button
  const cancelBtn = document.createElement("button")
  cancelBtn.className = "file-preview-cancel"
  cancelBtn.innerHTML = '<i class="fas fa-times"></i>'
  cancelBtn.addEventListener("click", () => {
    previewContainer.classList.add("hidden")
    document.getElementById("file-input").value = ""
    document.getElementById("message-input").value = ""
    document.getElementById("message-input").disabled = false
  })

  previewContainer.appendChild(cancelBtn)
}

/**
 * Format file size
 * @param {number} bytes - File size in bytes
 * @returns {string} Formatted size
 */
function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes"

  const k = 1024
  const sizes = ["Bytes", "KB", "MB", "GB", "TB"]
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
}

/**
 * Apply Teams/Skype style to messages
 */
function applyTeamsStyle() {
  // Group messages by sender
  window.messageFormatter?.groupMessages()

  // Add hover actions to messages
  const messages = document.querySelectorAll(".message")
  messages.forEach((message) => {
    // Add hover actions
    message.addEventListener("mouseenter", function () {
      const actions = this.querySelector(".message-actions")
      if (actions) {
        actions.style.display = "flex"
      }
    })

    message.addEventListener("mouseleave", function () {
      const actions = this.querySelector(".message-actions")
      if (actions) {
        actions.style.display = "none"
      }
    })
  })
}

// Add this to the existing applyTeamsStyle function or create it if it doesn't exist
function applySkypeStyle() {
  // Simplify message structure
  const messages = document.querySelectorAll(".message")
  messages.forEach((message) => {
    // Remove unnecessary nested divs
    const content = message.querySelector(".message-content")
    if (content) {
      const bubble = content.querySelector(".message-bubble")
      if (bubble) {
        // Move all children of content directly to message except bubble
        Array.from(content.children).forEach((child) => {
          if (child !== bubble && child.className !== "message-avatar") {
            message.appendChild(child)
          }
        })
      }
    }

    // Simplify reply structure
    const repliedMessage = message.querySelector('[style*="background-color: #6c5ce7"]')
    if (repliedMessage) {
      repliedMessage.className = "replied-message"
      repliedMessage.removeAttribute("style")

      // Simplify reply sender
      const replySender = repliedMessage.querySelector('div[style*="font-weight: bold"]')
      if (replySender) {
        replySender.className = "replied-message-sender"
        replySender.removeAttribute("style")
      }

      // Simplify reply content
      const replyContent = repliedMessage.querySelector('div[style*="color: white"]')
      if (replyContent) {
        replyContent.className = "replied-message-content"
        replyContent.removeAttribute("style")
      }
    }
  })

  // Call the simplify function if available
  if (window.messageFormatter && window.messageFormatter.simplifyMessageRendering) {
    window.messageFormatter.simplifyMessageRendering()
  }
}

// Add to window.chatUI
if (window.chatUI) {
  window.chatUI.applySkypeStyle = applySkypeStyle
}

// Call on document ready
document.addEventListener("DOMContentLoaded", () => {
  // Call after a short delay to ensure DOM is fully loaded
  setTimeout(applySkypeStyle, 500)

  // Set up observer to apply style to new messages
  const chatMessages = document.getElementById("chat-messages")
  if (chatMessages) {
    const observer = new MutationObserver(() => {
      applySkypeStyle()
    })

    observer.observe(chatMessages, { childList: true })
  }
})

// Add this function to the existing window.chatUI object
function setupHoverActions() {
  // Find all messages
  const messages = document.querySelectorAll(".message")

  messages.forEach((message) => {
    // Make sure message actions are properly positioned
    const actions = message.querySelector(".message-actions")
    if (!actions) return

    // Position the actions container
    if (message.classList.contains("own-message")) {
      actions.style.right = "10px"
    } else {
      actions.style.left = "40px"
    }

    // Add hover event listeners
    message.addEventListener("mouseenter", () => {
      actions.style.display = "flex"
    })

    message.addEventListener("mouseleave", () => {
      actions.style.display = "none"
    })
  })
}

// Add the function to the window.chatUI object
if (window.chatUI) {
  window.chatUI.setupHoverActions = setupHoverActions
} else {
  window.chatUI = {
    setupHoverActions,
    // ... other existing functions
  }
}

// Call on document ready
document.addEventListener("DOMContentLoaded", () => {
  // Call after a short delay to ensure DOM is fully loaded
  setTimeout(() => {
    if (window.chatUI && window.chatUI.setupHoverActions) {
      window.chatUI.setupHoverActions()
    }
  }, 500)

  // Set up observer to apply hover actions to new messages
  const chatMessages = document.getElementById("chat-messages")
  if (chatMessages) {
    const observer = new MutationObserver(() => {
      if (window.chatUI && window.chatUI.setupHoverActions) {
        window.chatUI.setupHoverActions()
      }
    })

    observer.observe(chatMessages, { childList: true })
  }
})

// Make functions available globally
window.chatUI = {
  scrollToBottom,
  showFilePreview,
  formatFileSize,
  applyTeamsStyle,
  applySkypeStyle,
  setupHoverActions,
}
