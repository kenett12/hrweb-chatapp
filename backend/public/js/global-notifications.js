/**
 * Global Notifications System
 * This script initializes the notification manager globally and ensures it's available across all pages
 */

// Initialize the global notification manager as soon as possible
;(() => {
  // Create a global namespace for our app if it doesn't exist
  window.ChatApp = window.ChatApp || {}

  // Store the original NotificationManager class reference
  const OriginalNotificationManager = window.ChatApp.NotificationManager

  // Initialize notification manager when the DOM is ready
  document.addEventListener("DOMContentLoaded", () => {
    console.log("Initializing global notification system")

    // Check if NotificationManager class is available
    if (typeof OriginalNotificationManager === "function") {
      // Create a singleton instance if it doesn't exist
      if (!window.notificationManager) {
        console.log("Creating global notification manager instance")
        window.notificationManager = new OriginalNotificationManager()

        // Store in our namespace as well
        window.ChatApp.notificationManager = window.notificationManager
      }

      // Set up global notification methods
      window.showNotification = (title, message, type = "info") => {
        if (window.notificationManager && typeof window.notificationManager.addNotification === "function") {
          return window.notificationManager.addNotification(title, message, type)
        } else {
          console.error("Notification manager not properly initialized")
          // Fallback to alert for critical messages
          if (type === "error") {
            alert(`${title}: ${message}`)
          }
        }
      }

      // Set up global handler for opening chats from notifications
      window.openChatFromNotification = (chatType, chatId) => {
        console.log(`Opening ${chatType} chat with ID: ${chatId}`)

        // Get base URL
        const baseUrl = document.getElementById("base-url")?.value || ""

        // Navigate to the appropriate chat page
        window.location.href = `${baseUrl}/chat/${chatType === "group" ? "group" : "direct"}/${chatId}`
      }

      // Force socket setup for notifications if socket is available
      if (window.socket) {
        console.log("Setting up socket listeners for notifications")
        window.notificationManager.setupSocketListeners()
      }

      // Set up a MutationObserver to watch for socket initialization
      const bodyObserver = new MutationObserver((mutations) => {
        if (window.socket && !window.notificationManager._socketListenersInitialized) {
          console.log("Socket detected, setting up notification listeners")
          window.notificationManager.setupSocketListeners()
          window.notificationManager._socketListenersInitialized = true
        }
      })

      // Start observing the body for changes
      bodyObserver.observe(document.body, { childList: true, subtree: true })

      // Ensure notifications are rendered
      setTimeout(() => {
        if (window.notificationManager && typeof window.notificationManager.renderNotifications === "function") {
          window.notificationManager.renderNotifications()
        }
      }, 1000)
    } else {
      console.error("NotificationManager class not found, attempting to load it")

      // Try to load the notifications.js script
      const script = document.createElement("script")
      script.src = "/js/notifications.js"
      script.onload = () => {
        console.log("Notifications script loaded, initializing manager")
        if (typeof window.ChatApp.NotificationManager === "function") {
          window.notificationManager = new window.ChatApp.NotificationManager()
          window.ChatApp.notificationManager = window.notificationManager
        }
      }
      document.head.appendChild(script)
    }
  })

  // Handle page visibility changes to refresh notifications when page becomes visible
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      console.log("Page became visible, refreshing notifications")
      if (window.notificationManager && typeof window.notificationManager.renderNotifications === "function") {
        window.notificationManager.renderNotifications()
        window.notificationManager.updateBadge()
      }
    }
  })
})()
