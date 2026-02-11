function throttle(func, limit) {
  let inThrottle
  return function () {
    const args = arguments
    const context = this
    if (!inThrottle) {
      func.apply(context, args)
      inThrottle = true
      setTimeout(() => (inThrottle = false), limit)
    }
  }
}

function debounce(func, wait) {
  let timeout
  return function () {
    const context = this
    const args = arguments
    clearTimeout(timeout)
    timeout = setTimeout(() => func.apply(context, args), wait)
  }
}

function optimizeScrollEvents() {
  const chatMessages = document.getElementById("chat-messages")
  if (!chatMessages) return

  const oldElement = chatMessages.cloneNode(true)
  chatMessages.parentNode.replaceChild(oldElement, chatMessages)

  oldElement.addEventListener(
    "scroll",
    throttle(() => {
      window.requestAnimationFrame(() => {
        if (typeof window.trackVisibleMessages === "function") {
          window.trackVisibleMessages()
        }
      })
    }, 300),
  )
}

function setupMessageVirtualization() {
  const chatMessages = document.getElementById("chat-messages")
  if (!chatMessages) return

  window.allMessages = []

  if (typeof window.originalAppendMessage !== "function") {
    window.originalAppendMessage = window.appendMessage || (() => {})
  }

  window.appendMessage = (message) => {
    window.allMessages.push(message)
    renderVisibleMessages()
  }

  window.renderVisibleMessages = () => {
    if (!window.allMessages || !window.allMessages.length) return

    const containerHeight = chatMessages.clientHeight
    const messageHeight = 80
    const bufferCount = 10

    const scrollTop = chatMessages.scrollTop
    const startIndex = Math.max(0, Math.floor(scrollTop / messageHeight) - bufferCount)
    const endIndex = Math.min(
      window.allMessages.length,
      Math.ceil((scrollTop + containerHeight) / messageHeight) + bufferCount,
    )

    if (chatMessages.children.length > 200) {
      chatMessages.innerHTML = ""
    }

    const renderedMessageIds = new Set()
    Array.from(chatMessages.querySelectorAll(".message[data-message-id]")).forEach((el) => {
      renderedMessageIds.add(el.dataset.messageId)
    })

    for (let i = startIndex; i < endIndex; i++) {
      const message = window.allMessages[i]
      if (!message || !message.id || renderedMessageIds.has(message.id.toString())) continue
      window.originalAppendMessage(message)
    }

    if (typeof window.trackVisibleMessages === "function") {
      window.trackVisibleMessages()
    }
  }

  chatMessages.addEventListener("scroll", throttle(window.renderVisibleMessages, 100))
}

function optimizeNotifications() {
  const closeBtn = document.getElementById("notification-close")
  if (closeBtn) {
    const newCloseBtn = closeBtn.cloneNode(true)
    closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn)

    newCloseBtn.addEventListener("click", (e) => {
      e.preventDefault()
      requestAnimationFrame(() => {
        const toast = document.getElementById("notification-toast")
        if (toast) {
          toast.classList.add("hidden")
        }
      })
    })
  }

  if (typeof window.showNotification === "function") {
    const originalShowNotification = window.showNotification
    window.showNotification = (type, title, message) => {
      if (window.notificationQueue && window.notificationQueue.length > 5) {
        return
      }

      if (!window.notificationQueue) {
        window.notificationQueue = []
      }

      window.notificationQueue.push({ type, title, message })

      if (!window.processingNotifications) {
        processNotificationQueue()
      }
    }

    window.processNotificationQueue = () => {
      if (!window.notificationQueue || window.notificationQueue.length === 0) {
        window.processingNotifications = false
        return
      }

      window.processingNotifications = true
      const notification = window.notificationQueue.shift()

      requestAnimationFrame(() => {
        originalShowNotification(notification.type, notification.title, notification.message)
        setTimeout(() => {
          processNotificationQueue()
        }, 300)
      })
    }
  }
}

function optimizeMessageInput() {
  const messageInput = document.getElementById("message-input")
  if (!messageInput) return

  const newMessageInput = messageInput.cloneNode(true)
  messageInput.parentNode.replaceChild(newMessageInput, messageInput)

  newMessageInput.addEventListener(
    "input",
    debounce(() => {
      if (window.socket && window.socket.connected) {
        window.socket.emit("typing", {
          user_id: window.userId || window.currentUserId,
          receiver_id: window.otherUserId,
          is_typing: 1,
          is_group: 0,
          username: window.username,
        })
      }

      if (window.typingTimeout) {
        clearTimeout(window.typingTimeout)
      }

      window.typingTimeout = setTimeout(() => {
        if (window.socket && window.socket.connected) {
          window.socket.emit("typing", {
            user_id: window.userId || window.currentUserId,
            receiver_id: window.otherUserId,
            is_typing: 0,
            is_group: 0,
            username: window.username,
          })
        }
      }, 2000)
    }, 300),
  )
}

function optimizeSeenTracking() {
  window.trackVisibleMessages = () => {
    const chatMessages = document.getElementById("chat-messages")
    if (!chatMessages) return

    if ("IntersectionObserver" in window && !window.messageObserver) {
      const options = {
        root: chatMessages,
        threshold: 0.5,
      }

      window.messageObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const message = entry.target
            if (
              !message.classList.contains("own-message") &&
              message.dataset.messageId &&
              !message.classList.contains("seen-by-me")
            ) {
              if (typeof window.markMessageAsSeen === "function") {
                window.markMessageAsSeen(message.dataset.messageId)
                message.classList.add("seen-by-me")
              }
            }
          }
        })
      }, options)

      document.querySelectorAll(".message:not(.own-message)[data-message-id]:not(.seen-by-me)").forEach((message) => {
        window.messageObserver.observe(message)
      })

      return
    }

    const messageElements = chatMessages.querySelectorAll(".message:not(.own-message):not(.seen-by-me)")
    if (messageElements.length === 0) return

    const containerRect = chatMessages.getBoundingClientRect()
    const visibleMessages = []

    messageElements.forEach((message) => {
      const messageRect = message.getBoundingClientRect()
      const isVisible = messageRect.top < containerRect.bottom && messageRect.bottom > containerRect.top

      if (isVisible && message.dataset.messageId) {
        visibleMessages.push(message.dataset.messageId)
        message.classList.add("seen-by-me")
      }
    })

    if (visibleMessages.length > 0 && typeof window.markMessageAsSeen === "function") {
      if (typeof window.markMessagesBatchAsSeen === "function") {
        window.markMessagesBatchAsSeen(visibleMessages)
      } else {
        visibleMessages.forEach((messageId) => {
          window.markMessageAsSeen(messageId)
        })
      }
    }
  }

  if (typeof window.markMessagesBatchAsSeen !== "function") {
    window.markMessagesBatchAsSeen = (messageIds) => {
      if (!messageIds || !messageIds.length) return

      const currentUserId = window.currentUserId || window.userId
      const otherUserId = window.otherUserId

      if (!currentUserId || !otherUserId) {
        return
      }

      const seenData = {
        message_ids: messageIds,
        user_id: currentUserId,
        other_user_id: otherUserId,
        is_group: false,
      }

      const API_BASE_URL = '/chat-app/backend/api'
      
      fetch(`${API_BASE_URL}/markMessagesBatchAsSeen`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(seenData),
      })
        .then((response) => response.json())
        .then((data) => {
          console.log(data)
        })
        .catch((error) => {
          console.error(error)
        })

      if (window.socket && window.socket.connected) {
        window.socket.emit("messages_batch_seen", seenData)
      }
    }
  }
}

function optimizeSocketConnection() {
  if (window.socket) {
    const originalEmit = window.socket.emit

    window.socket.emit = function (event, data) {
      const throttledEvents = ["typing", "message_seen", "user_status"]

      if (throttledEvents.includes(event)) {
        if (!window.socketThrottles) window.socketThrottles = {}
        
        const key = event + (data && data.message_id ? data.message_id : "")

        if (!window.socketThrottles[key]) {
          originalEmit.apply(this, arguments)
          window.socketThrottles[key] = true

          setTimeout(() => {
            window.socketThrottles[key] = false
          }, 300)
        }
      } else {
        originalEmit.apply(this, arguments)
      }
    }
  }
}

function cleanupMemoryLeaks() {
  const originalBeforeUnload = window.onbeforeunload

  window.onbeforeunload = () => {
    if (typeof originalBeforeUnload === "function") {
      originalBeforeUnload()
    }

    if (window.messageObserver) {
      window.messageObserver.disconnect()
      window.messageObserver = null
    }

    if (window.socket) {
      window.socket.removeAllListeners()
    }

    if (window.typingTimeout) {
      clearTimeout(window.typingTimeout)
    }

    window.allMessages = []
  }
}

function initPerformanceOptimizations() {
  optimizeScrollEvents()
  setupMessageVirtualization()
  optimizeNotifications()
  optimizeMessageInput()
  optimizeSeenTracking()
  optimizeSocketConnection()
  cleanupMemoryLeaks()
}

document.addEventListener("DOMContentLoaded", initPerformanceOptimizations)

window.performanceOptimizations = {
  initPerformanceOptimizations,
  optimizeScrollEvents,
  setupMessageVirtualization,
  optimizeNotifications,
  optimizeMessageInput,
  optimizeSeenTracking,
  optimizeSocketConnection,
  cleanupMemoryLeaks,
}