/**
 * Socket Manager - Handles socket.io connections across the application
 */
class SocketManager {
  constructor() {
    this.socket = null
    this.socketUrl = window.socketUrl || "http://localhost:3001"
    this.connected = false
    this.connectionAttempts = 0
    this.maxConnectionAttempts = 5
    this.connectionStatus = document.getElementById("connection-status")
    this.currentUserId = null
    this.eventListeners = {}

    // Create connection status element if it doesn't exist
    if (!this.connectionStatus) {
      this.connectionStatus = this.createConnectionStatus()
    }

    console.log("SocketManager initialized with URL:", this.socketUrl)
  }

  /**
   * Create connection status element
   */
  createConnectionStatus() {
    const statusElement = document.createElement("div")
    statusElement.id = "connection-status"
    statusElement.className = "connection-status connecting"
    statusElement.innerHTML = `
      <span>Connecting to chat server...</span>
      <button id="retry-connection" class="retry-btn">Retry</button>
    `
    document.body.insertBefore(statusElement, document.body.firstChild)

    // Add event listener to retry button
    statusElement.querySelector("#retry-connection").addEventListener("click", () => {
      this.connect()
    })

    return statusElement
  }

  /**
   * Check if Socket.IO is loaded
   */
  isSocketIOLoaded() {
    return typeof io !== "undefined"
  }

  /**
   * Load Socket.IO library dynamically
   */
  loadSocketIO() {
    return new Promise((resolve, reject) => {
      console.log("Loading Socket.IO library dynamically")

      this.updateConnectionStatus("Loading Socket.IO library...", "connecting")

      const script = document.createElement("script")
      script.src = "https://cdn.socket.io/4.5.4/socket.io.min.js"
      script.integrity = "sha384-/KNQL8Nu5gCHLqwqfQjA689Hhoqgi2S84SNUxC3roTe4EhJ9AfLkp8QiQcU8AMzI"
      script.crossOrigin = "anonymous"

      script.onload = () => {
        console.log("Socket.IO loaded successfully!")
        resolve()
      }

      script.onerror = (error) => {
        console.error("Failed to load Socket.IO:", error)
        this.updateConnectionStatus("Failed to load Socket.IO library. Please refresh the page.", "error")
        reject(error)
      }

      document.head.appendChild(script)
    })
  }

  /**
   * Update connection status UI
   */
  updateConnectionStatus(message, status) {
    if (!this.connectionStatus) return

    this.connectionStatus.innerHTML = `
      <span>${message}</span>
      <button id="retry-connection" class="retry-btn">Retry</button>
    `
    this.connectionStatus.className = `connection-status ${status}`
    this.connectionStatus.style.display = "flex"

    // Add event listener to retry button
    const retryBtn = this.connectionStatus.querySelector("#retry-connection")
    if (retryBtn) {
      retryBtn.addEventListener("click", () => {
        this.connect()
      })
    }

    // Hide status after 3 seconds if connected
    if (status === "connected") {
      setTimeout(() => {
        this.connectionStatus.style.display = "none"
      }, 3000)
    }
  }

  /**
   * Connect to socket server
   */
  async connect(userId = null) {
    if (userId) {
      this.currentUserId = userId
    }

    console.log("Attempting to connect to socket server at:", this.socketUrl)
    this.updateConnectionStatus("Connecting to chat server...", "connecting")

    // Check if Socket.IO is loaded
    if (!this.isSocketIOLoaded()) {
      try {
        await this.loadSocketIO()
      } catch (error) {
        console.error("Failed to load Socket.IO:", error)
        return
      }
    }

    try {
      console.log("Initializing socket connection with io:", typeof io)

      // Initialize socket
      this.socket = io(this.socketUrl, {
        transports: ["websocket", "polling"],
        reconnection: true,
        reconnectionAttempts: 5,
        reconnectionDelay: 1000,
        timeout: 10000,
        autoConnect: true,
      })

      console.log("Socket initialized:", this.socket)

      // Set up socket for global access
      window.socket = this.socket

      // Set up connection events
      this.setupConnectionEvents()

      // Set up custom event listeners
      this.setupEventListeners()

      return this.socket
    } catch (error) {
      console.error("Error connecting to socket:", error)
      this.updateConnectionStatus(`Failed to connect to chat server: ${error.message}`, "error")
      return null
    }
  }

  /**
   * Set up connection events
   */
  setupConnectionEvents() {
    this.socket.on("connect", () => {
      console.log("Socket connected:", this.socket.id)
      this.connected = true
      this.connectionAttempts = 0

      this.updateConnectionStatus("Connected to chat server", "connected")

      // Emit user_connected event if we have a user ID
      if (this.currentUserId) {
        this.socket.emit("user_connected", this.currentUserId)
        console.log("Emitted user_connected event with ID:", this.currentUserId)
      }

      // Trigger onConnect callbacks
      if (this.eventListeners.connect) {
        this.eventListeners.connect.forEach((callback) => callback())
      }
    })

    this.socket.on("disconnect", (reason) => {
      console.log("Socket disconnected:", reason)
      this.connected = false

      this.updateConnectionStatus(`Disconnected from chat server: ${reason}`, "disconnected")

      // Trigger onDisconnect callbacks
      if (this.eventListeners.disconnect) {
        this.eventListeners.disconnect.forEach((callback) => callback(reason))
      }
    })

    this.socket.on("connect_error", (error) => {
      console.error("Socket connection error:", error)
      this.connected = false
      this.connectionAttempts++

      this.updateConnectionStatus(`Failed to connect to chat server: ${error.message}`, "error")

      // Trigger onConnectError callbacks
      if (this.eventListeners.connect_error) {
        this.eventListeners.connect_error.forEach((callback) => callback(error))
      }

      // If we've reached max connection attempts, stop trying
      if (this.connectionAttempts >= this.maxConnectionAttempts) {
        console.error(`Failed to connect after ${this.maxConnectionAttempts} attempts. Giving up.`)
        this.updateConnectionStatus(
          `Failed to connect after ${this.maxConnectionAttempts} attempts. Please check your connection and try again.`,
          "error",
        )
      }
    })
  }

  /**
   * Set up custom event listeners
   */
  setupEventListeners() {
    // Set up receive_message event
    this.socket.on("receive_message", (message) => {
      console.log("Received message:", message)

      // Trigger onMessage callbacks
      if (this.eventListeners.receive_message) {
        this.eventListeners.receive_message.forEach((callback) => callback(message))
      }
    })

    // Set up online_users event
    this.socket.on("online_users", (users) => {
      console.log("Online users:", users)

      // Trigger onOnlineUsers callbacks
      if (this.eventListeners.online_users) {
        this.eventListeners.online_users.forEach((callback) => callback(users))
      }
    })

    // Add message_seen event handler
    this.socket.on("message_seen", (data) => {
      console.log("Message seen event received in socket manager:", data)

      // Trigger message_seen callbacks
      if (this.eventListeners.message_seen) {
        this.eventListeners.message_seen.forEach((callback) => callback(data))
      }

      // Also update UI if the global function exists
      if (window.updateSeenStatus && typeof window.updateSeenStatus === "function") {
        window.updateSeenStatus(data)
      }
    })
  }

  /**
   * Add event listener
   */
  on(event, callback) {
    if (!this.eventListeners[event]) {
      this.eventListeners[event] = []
    }

    this.eventListeners[event].push(callback)

    // If socket exists, also add the listener directly
    if (this.socket) {
      this.socket.on(event, callback)
    }

    return this
  }

  /**
   * Remove event listener
   */
  off(event, callback) {
    if (this.eventListeners[event]) {
      this.eventListeners[event] = this.eventListeners[event].filter((cb) => cb !== callback)
    }

    // If socket exists, also remove the listener directly
    if (this.socket) {
      this.socket.off(event, callback)
    }

    return this
  }

  /**
   * Emit event
   */
  emit(event, data) {
    if (!this.socket || !this.connected) {
      console.warn(`Cannot emit ${event} - socket not connected`)
      return false
    }

    this.socket.emit(event, data)
    return true
  }

  /**
   * Join room
   */
  joinRoom(room) {
    if (!this.socket || !this.connected) {
      console.warn(`Cannot join room ${room} - socket not connected`)
      return false
    }

    console.log(`Joining room: ${room}`)
    this.socket.emit("join_room", room)
    return true
  }

  /**
   * Leave room
   */
  leaveRoom(room) {
    if (!this.socket || !this.connected) {
      console.warn(`Cannot leave room ${room} - socket not connected`)
      return false
    }

    console.log(`Leaving room: ${room}`)
    this.socket.emit("leave_room", room)
    return true
  }

  /**
   * Disconnect socket
   */
  disconnect() {
    if (this.socket) {
      this.socket.disconnect()
      this.socket = null
      this.connected = false
    }
  }
}

/**
 * Handle status change events
 */
function handleStatusChange(data) {
  console.log("Status change received:", data)
  const userId = data.user_id
  const status = data.status

  // Update status indicators in the sidebar
  const userItems = document.querySelectorAll(`.conversation-item[data-user-id="${userId}"]`)
  userItems.forEach((item) => {
    const statusIndicator = item.querySelector(".status-indicator")
    if (statusIndicator) {
      // Remove all status classes
      statusIndicator.classList.remove("online", "away", "busy", "offline")
      // Add new status class
      statusIndicator.classList.add(status)
    }
  })
}

// Create global instance
window.socketManager = new SocketManager()

// Initialize connection when document is ready
document.addEventListener("DOMContentLoaded", () => {
  console.log("Socket manager initializing on page load")

  // Get user ID from meta tag or global variable
  const userId = document.querySelector('meta[name="user-id"]')?.content || window.userId

  if (userId) {
    console.log("Found user ID:", userId)
    window.socketManager.connect(userId)
  } else {
    console.warn("No user ID found, socket connection will be initialized without user authentication")
    window.socketManager.connect()
  }
})

// Add the status_change event listener to the socket
if (window.socket) {
  // Access the socket from the window object
  window.socket.on("status_change", handleStatusChange)

  // Add this new event handler for message_seen events
  window.socket.on("message_seen", (data) => {
    console.log("Message seen event received in global handler:", data)

    // Update UI to show seen status
    if (window.updateSeenStatus && typeof window.updateSeenStatus === "function") {
      window.updateSeenStatus(data)
    } else {
      console.warn("updateSeenStatus function not available, will try again in 1 second")
      // Try again after a delay in case the function hasn't been defined yet
      setTimeout(() => {
        if (window.updateSeenStatus && typeof window.updateSeenStatus === "function") {
          window.updateSeenStatus(data)
        } else {
          console.error("updateSeenStatus function still not available after delay")
        }
      }, 1000)
    }
  })
}

// Add support for handling reply data in received messages

// Look for the socket.on("receive_message"... event handler and update it to handle reply data
// Add this code after your existing socket event handlers:

// Make sure we handle reply data when receiving messages via socket
function handleSocketMessage(message) {
  console.log("Processing socket message with possible reply data:", message)

  // Check if message has reply data
  if (message.reply_to_id) {
    console.log("Message contains reply data:", {
      reply_to_id: message.reply_to_id,
      reply_to_sender_id: message.reply_to_sender_id,
      reply_to_content: message.reply_to_content,
    })
  }

  // Rest of your message handling logic...
}

// Configure socket to handle reply data
// This is a helper function you can call to ensure new sockets are configured
function configureSocketForReplies(socket) {
  if (!socket) return

  // Remove any existing listener to prevent duplicates
  socket.off("receive_message")

  // Add enhanced listener that handles replies
  socket.on("receive_message", (message) => {
    console.log("Received message with possible reply data:", message)

    // Process the message
    handleSocketMessage(message)

    // Your existing message handling code...
  })
}

// Export the function to make it available globally
window.configureSocketForReplies = configureSocketForReplies

// Check if socket already exists and configure it
if (window.socket) {
  configureSocketForReplies(window.socket)
} 
