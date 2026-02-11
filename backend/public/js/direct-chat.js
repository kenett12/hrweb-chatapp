const userId = ""
const otherUserId = ""
const otherUser = {}

function getFileIconClass(fileExt) {
    return "fas fa-file"
}

function formatFileSize(size) {
    return size + " bytes"
}

function formatTime(time) {
    return new Date(time).toLocaleTimeString()
}

function scrollToBottom() {
    const chatMessages = document.getElementById("chat-messages")
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight
    }
}

function handleReplyClick(messageId, senderName, content) {
    const replyInput = document.getElementById("reply_to_id")
    if (replyInput) {
        replyInput.value = messageId
    }

    const replyPreview = document.getElementById("reply-preview")
    const replyContent = document.getElementById("reply-content-preview")
    
    if (replyPreview) {
        replyPreview.style.display = "flex"
        if (replyContent) {
            replyContent.innerText = `Replying to ${senderName}`
        }
    }

    const messageInput = document.getElementById("message-input")
    if (messageInput) {
        messageInput.focus()
    }
}

function appendMessage(message) {
    const chatMessages = document.getElementById("chat-messages")
    const isCurrentUser = message.sender_id == userId

    if (message.id) {
        const existingMessage = document.querySelector(`.message[data-message-id="${message.id}"]`)
        if (existingMessage) {
            return
        }
    }

    const messageDiv = document.createElement("div")
    messageDiv.className = `message ${isCurrentUser ? "own-message" : ""}`

    if (message.id) {
        messageDiv.dataset.messageId = message.id
    }
    messageDiv.dataset.senderId = message.sender_id
    messageDiv.dataset.senderName = message.nickname || message.username || "User"

    let messageHTML = ""

    if (!isCurrentUser) {
        messageHTML += `
      <div class="message-avatar">
        <img src="<?= base_url('public/uploads/avatars/') ?>/${message.avatar || "default-avatar.png"}" 
          alt="${message.nickname || message.username}" class="user-avatar">
      </div>
    `
    }

    if (!isCurrentUser) {
        messageHTML += `<div class="message-sender">${message.nickname || message.username}</div>`
    }

    messageHTML += `<div class="message-bubble">`

    if (message.reply_to_id) {
        let replySenderName = "Unknown User"

        if (message.reply_to_sender_name) {
            replySenderName = message.reply_to_sender_name
        } else if (message.reply_to_sender_id == userId) {
            replySenderName = "You"
        } else if (message.reply_to_sender_id == otherUserId) {
            replySenderName = otherUser.nickname || otherUser.username
        }

        const replyContent = message.reply_to_content || "This message"

        messageHTML += `
      <div class="replied-message">
        <div class="replied-message-sender">Replying to ${replySenderName}</div>
        <div class="replied-message-content">${replyContent}</div>
      </div>
    `
    }

    if (message.type === "text" || !message.type) {
        messageHTML += `<div class="message-text">${message.content}</div>`
    } else if (message.type === "image") {
        let imageSrc
        if (message.file_url) {
            imageSrc = '<?= base_url("uploads/messages/") ?>/' + message.file_url
        } else {
            imageSrc = '<?= base_url("uploads/messages/") ?>/' + message.content
        }

        messageHTML += `
      <div class="image-container" onclick="openImageViewer('${imageSrc}', '${message.original_filename || "Image"}')">
        <img src="${imageSrc}" alt="Image" class="message-image">
      </div>
    `
    } else if (message.type === "file") {
        let originalFilename = message.original_filename || message.content
        if (message.content && message.content.startsWith("File: ")) {
            originalFilename = message.content.substring(6)
        }

        let fileUrl
        if (message.file_url) {
            fileUrl = '<?= base_url("uploads/messages/") ?>/' + message.file_url
        } else {
            fileUrl = '<?= base_url("uploads/messages/") ?>/' + message.content
        }

        const fileExt = originalFilename.split(".").pop().toLowerCase()
        const fileIconClass = getFileIconClass(fileExt)
        const fileSize = message.file_size ? formatFileSize(message.file_size) : "Unknown size"

        messageHTML += `
      <a href="${fileUrl}" class="file-attachment-card" download="${originalFilename}" target="_blank">
        <div class="file-card-icon">
          <i class="${fileIconClass}"></i>
        </div>
        <div class="file-card-details">
          <div class="file-card-name">${originalFilename}</div>
          <div class="file-card-info">${fileSize}</div>
        </div>
      </a>
    `
    }

    messageHTML += `<div class="message-time">${formatTime(message.created_at)}</div>`

    if (isCurrentUser) {
        messageHTML += `
      <div class="message-status">
        ${
          message.is_read === 1 || message.status === "seen"
            ? `<i class="fas fa-check-double"></i> Seen`
            : `<i class="fas fa-check"></i> Sent`
        }
      </div>
    `
    }

    messageHTML += `</div>`

    messageHTML += `
    <div class="message-actions">
      <button class="message-action-btn reply-btn" title="Reply"><i class="fas fa-reply"></i></button>
      ${
        isCurrentUser
          ? `
        <button class="message-action-btn edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
        <button class="message-action-btn delete-btn" title="Delete"><i class="fas fa-trash"></i></button>
      `
          : ""
      }
      <button class="message-action-btn reaction-btn" title="Add Reaction"><i class="far fa-smile"></i></button>
      <button class="message-action-btn more-btn" title="More"><i class="fas fa-ellipsis-h"></i></button>
    </div>
  `

    messageDiv.innerHTML = messageHTML
    chatMessages.appendChild(messageDiv)

    const replyBtn = messageDiv.querySelector('.reply-btn')
    if (replyBtn) {
        replyBtn.onclick = function() {
            const senderName = message.nickname || message.username || "User"
            const content = message.content || "Attachment"
            handleReplyClick(message.id, senderName, content)
        }
    }

    if (window.chatUI && window.chatUI.setupHoverActions) {
        window.chatUI.setupHoverActions()
    }

    scrollToBottom()
}