// Browser Notification Handler
// This script handles browser notifications across all pages

;(() => {
  // Add at the beginning of the file or in the appropriate scope
  const processedNotifications = new Set()

  // Add a function to fetch user avatars when processing notifications

  // Add this function to the file (place it before any existing functions):
  function getUserAvatar(userId) {
    // Try to get from window.users if available
    if (window.users) {
      const user = window.users.find((u) => u.id == userId)
      if (user && user.avatar) {
        return user.avatar
      }
    }

    // Try to get from contacts if available
    if (window.contacts) {
      const contact = window.contacts.find((c) => c.id == userId)
      if (contact && contact.avatar) {
        return contact.avatar
      }
    }

    return null
  }

  // Global notification handler
  window.showBrowserNotification = (title, options) => {
    console.log("Global notification handler called:", title, options)

    // Check if browser supports notifications
    if (!("Notification" in window)) {
      console.warn("This browser does not support desktop notifications")
      return false
    }

    // Check if we already have permission
    if (Notification.permission === "granted") {
      // Create notification
      createNotification(title, options)
    }
    // Otherwise, request permission
    else if (Notification.permission !== "denied") {
      Notification.requestPermission().then((permission) => {
        // If the user accepts, create notification
        if (permission === "granted") {
          createNotification(title, options)
        }
      })
    }

    return true
  }

  // Helper function to create notification
  function createNotification(title, options) {
    // Create a unique key for this notification
    const notificationKey = `${options.tag || ""}_${title}_${options.body || ""}`

    // Check if we've already processed this notification
    if (processedNotifications.has(notificationKey)) {
      console.log("Duplicate notification detected, ignoring:", notificationKey)
      return null
    }

    // Add to processed notifications
    processedNotifications.add(notificationKey)

    // Remove from set after 5 seconds to prevent memory leaks
    setTimeout(() => {
      processedNotifications.delete(notificationKey)
    }, 5000)

    try {
      if (options && options.data && options.data.userId) {
        options.icon = getUserAvatar(options.data.userId) || options.icon
      }

      const notification = new Notification(title, options)

      // Auto close after 5 seconds
      setTimeout(() => {
        notification.close()
      }, 5000)

      // Handle notification click
      notification.onclick = function () {
        console.log("Notification clicked")
        window.focus()
        if (options && options.data && options.data.chatId) {
          // Trigger chat opening if handler is set
          if (window.openChatFromNotification) {
            console.log("Opening chat from notification click:", options.data.chatType, options.data.chatId)
            window.openChatFromNotification(options.data.chatType, options.data.chatId)
          } else {
            // Fallback to direct URL navigation
            window.location.href = `/chat/${options.data.chatType === "group" ? "group" : "direct"}/${options.data.chatId}`
          }
        }
        this.close()
      }

      return notification
    } catch (error) {
      console.error("Error creating notification:", error)
      return null
    }
  }

  // Request permission on page load
  document.addEventListener("DOMContentLoaded", () => {
    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
      Notification.requestPermission()
    }
  })
})()
