/**
 * Message Formatter
 * Handles formatting, grouping, and beautifying attachments
 * Updated to match your custom CSS classes
 */

document.addEventListener("DOMContentLoaded", () => {
  groupMessages()
  observeMessageChanges()
  // Run initial pass on existing messages
  document.querySelectorAll('.message').forEach(msg => processMessage(msg));
})

/**
 * Main processing function for a single message
 */
function processMessage(message) {
    if (message.dataset.processed) return;
    
    // 1. Format the visual attachment (Files/Images)
    enhanceAttachments(message);
    
    // 2. Clean up the DOM structure (Time stamps, etc)
    simplifyMessageRendering(message);
    
    // 3. Mark as processed so we don't redo it
    message.dataset.processed = "true";
}

/**
 * Group messages from the same sender
 */
function groupMessages() {
  const chatMessages = document.getElementById("chat-messages")
  if (!chatMessages) return

  const messages = chatMessages.querySelectorAll(".message")
  let lastSenderId = null

  messages.forEach((message, index) => {
    const senderId = message.getAttribute("data-sender-id")
    if (index > 0) {
      if (senderId === lastSenderId) {
        message.classList.add("grouped")
      } else {
        message.classList.remove("grouped")
      }
    }
    lastSenderId = senderId
  })
}

/**
 * Observe changes to the chat messages container
 */
function observeMessageChanges() {
  const chatMessages = document.getElementById("chat-messages")
  if (!chatMessages) return

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.addedNodes.length) {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1 && node.classList.contains('message')) {
                    processMessage(node);
                }
            });
            // Re-group after adding new nodes
            groupMessages();
        }
    });
  })

  observer.observe(chatMessages, {
    childList: true,
    subtree: true,
  })
}

/**
 * TRANSFORM ATTACHMENTS TO MATCH YOUR CSS
 */
function enhanceAttachments(message) {
    const bubble = message.querySelector(".message-bubble");
    if (!bubble) return;

    // --- 1. HANDLE IMAGES ---
    // We look for an image that isn't an emoji
    const img = bubble.querySelector('img:not(.emoji)'); 
    
    if (img) {
        const imgSrc = img.getAttribute('src');
        const imgAlt = img.getAttribute('alt') || 'Image';
        
        // Create your specific HTML structure for images
        const imageHtml = `
            <div class="image-container" onclick="window.open('${imgSrc}', '_blank')">
                <img src="${imgSrc}" class="message-image" alt="${imgAlt}">
                <div class="image-overlay">
                    <span class="open-image-btn">Click to Expand</span>
                </div>
            </div>
            `;
        
        // Replace the raw img tag with your container
        // We replace the parent anchor if it exists, or just the image
        const parentLink = img.closest('a');
        if (parentLink) {
            parentLink.outerHTML = imageHtml;
        } else {
            img.outerHTML = imageHtml;
        }
        
        bubble.classList.add('has-attachment');
        return; 
    }

    // --- 2. HANDLE FILES ---
    // Check for File Links (anchors linking to uploads that aren't images)
    const fileLink = bubble.querySelector('a[href*="uploads"]'); 
    
    // Ensure it's not a link we just created for an image
    if (fileLink && !fileLink.querySelector('.image-container') && !fileLink.closest('.image-container')) {
        
        const fileName = fileLink.textContent.trim() || fileLink.getAttribute('href').split('/').pop();
        const fileUrl = fileLink.getAttribute('href');
        const fileExt = fileName.split('.').pop().toLowerCase();
        
        // Determine Icon based on extension
        let iconClass = "fa-file";
        if (['pdf'].includes(fileExt)) iconClass = "fa-file-pdf";
        else if (['doc', 'docx'].includes(fileExt)) iconClass = "fa-file-word";
        else if (['xls', 'xlsx', 'csv'].includes(fileExt)) iconClass = "fa-file-excel";
        else if (['zip', 'rar', '7z'].includes(fileExt)) iconClass = "fa-file-archive";
        else if (['txt', 'log'].includes(fileExt)) iconClass = "fa-file-alt";
        else if (['ppt', 'pptx'].includes(fileExt)) iconClass = "fa-file-powerpoint";

        // Generate HTML matching your CSS classes: 
        // .file-attachment-card, .file-card-icon, .file-card-details, .file-card-name
        const fileCardHtml = `
            <a href="${fileUrl}" class="file-attachment-card" target="_blank" download>
                <div class="file-card-icon">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="file-card-details">
                    <div class="file-card-name" title="${fileName}">${fileName}</div>
                    <div class="file-card-info">${fileExt.toUpperCase()} • ${formatFileSize(0)}</div>
                    <div class="file-download-text">Click to download</div>
                </div>
            </a>
        `;

        // Replace the raw link with the card
        fileLink.outerHTML = fileCardHtml;
        bubble.classList.add('has-attachment');
    }
}

/**
 * Simplify message rendering (Move Time inside Bubble)
 */
function simplifyMessageRendering(message) {
  const bubble = message.querySelector(".message-bubble");
  if (!bubble) return;

  // Find the meta/time element
  const messageTime = message.querySelector(".message-time, .message-meta");
  
  // Move it inside the bubble if it isn't already
  if (messageTime && !bubble.contains(messageTime)) {
    // Add a specific class to style it nicely at the bottom right
    messageTime.style.display = "block";
    messageTime.style.fontSize = "0.7rem";
    messageTime.style.textAlign = "right";
    messageTime.style.marginTop = "4px";
    messageTime.style.opacity = "0.7";
    
    // If it's an image, we might want to overlay it (handled by your CSS .image-overlay usually)
    // but for text/files, append to bubble
    bubble.appendChild(messageTime);
  }

  // Remove old wrapper divs that clutter the DOM
  const unnecessaryDivs = message.querySelectorAll(".message-status-container");
  unnecessaryDivs.forEach((div) => div.remove());
}

/**
 * Helper: Format file size (Placeholder as size usually isn't in the link)
 */
function formatFileSize(bytes) {
    // Since we often don't have size in the href, we return a generic label or leave empty
    return "File"; 
}

// Global exposure
window.messageFormatter = {
  groupMessages
}