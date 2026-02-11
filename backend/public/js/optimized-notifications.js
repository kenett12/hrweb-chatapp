/**
 * Optimized Notification System
 * This file provides a more efficient notification system that reduces UI lag
 */

class OptimizedNotificationManager {
  constructor(options = {}) {
    this.baseUrl = window.baseUrl || '/chat-app/backend/';
    if (!this.baseUrl.endsWith('/')) this.baseUrl += '/';

    // Default options
    this.options = {
      toastId: "notification-toast",
      titleId: "notification-title",
      messageId: "notification-message",
      closeId: "notification-close",
      iconId: "notification-icon",
      maxNotifications: 50,
      displayTime: 3000,
      queueLimit: 5,
      ...options,
    }

    // State
    this.notifications = []
    this.queue = []
    this.isProcessing = false
    this.lastNotificationTime = 0

    // Initialize
    this.init()
  }

  init() {
    this.loadNotifications()
    this.setupEventListeners()
    this.overrideGlobalFunctions()
    console.log("OptimizedNotificationManager initialized")
  }

  loadNotifications() {
    try {
      const savedNotifications = localStorage.getItem("notifications")
      if (savedNotifications) {
        this.notifications = JSON.parse(savedNotifications)

        if (this.notifications.length > this.options.maxNotifications) {
          this.notifications = this.notifications.slice(0, this.options.maxNotifications)
          this.saveNotifications()
        }
      }
    } catch (error) {
      console.error("Error loading notifications:", error)
      this.notifications = []
    }
  }

  saveNotifications() {
    try {
      localStorage.setItem("notifications", JSON.stringify(this.notifications))
    } catch (error) {
      console.error("Error saving notifications:", error)
    }
  }

  setupEventListeners() {
    const closeBtn = document.getElementById(this.options.closeId)
    if (closeBtn) {
      const newCloseBtn = closeBtn.cloneNode(true)
      closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn)

      newCloseBtn.addEventListener("click", (e) => {
        e.preventDefault()
        this.hideToast()
      })
    }
  }

  overrideGlobalFunctions() {
    if (typeof window.showNotification === "function") {
      window.originalShowNotification = window.showNotification
      window.showNotification = (type, title, message) => {
        this.showNotification(type, title, message)
      }
    }
  }

  showNotification(type, title, message) {
    this.queue.push({ type, title, message })

    if (this.queue.length > this.options.queueLimit) {
      this.queue.shift()
    }

    if (!this.isProcessing) {
      this.processQueue()
    }
  }

  processQueue() {
    if (this.queue.length === 0) {
      this.isProcessing = false
      return
    }

    this.isProcessing = true
    const notification = this.queue.shift()

    this.addNotification(notification.type, notification.title, notification.message)
    this.displayToast(notification.type, notification.title, notification.message)

    setTimeout(() => {
      this.processQueue()
    }, 300)
  }

  addNotification(type, title, message) {
    const notification = {
      id: Date.now(),
      type,
      title,
      message,
      timestamp: new Date().toISOString(),
    }

    this.notifications.unshift(notification)

    if (this.notifications.length > this.options.maxNotifications) {
      this.notifications = this.notifications.slice(0, this.options.maxNotifications)
    }

    this.saveNotifications()
    return notification
  }

  displayToast(type, title, message) {
    const toast = document.getElementById(this.options.toastId)
    const titleEl = document.getElementById(this.options.titleId)
    const messageEl = document.getElementById(this.options.messageId)
    const iconEl = document.getElementById(this.options.iconId)?.querySelector("i")

    if (!toast || !titleEl || !messageEl) {
      console.error("Toast elements not found")
      return
    }

    titleEl.textContent = title
    messageEl.textContent = message

    toast.className = "notification-toast"
    toast.classList.add(type)

    if (iconEl) {
      if (type === "success") {
        iconEl.className = "fas fa-check-circle"
      } else if (type === "error") {
        iconEl.className = "fas fa-exclamation-circle"
      } else if (type === "warning") {
        iconEl.className = "fas fa-exclamation-triangle"
      } else {
        iconEl.className = "fas fa-info-circle"
      }
    }

    requestAnimationFrame(() => {
      toast.classList.remove("hidden")
      setTimeout(() => {
        this.hideToast()
      }, this.options.displayTime)
    })
  }

  hideToast() {
    const toast = document.getElementById(this.options.toastId)
    if (toast) {
      requestAnimationFrame(() => {
        toast.classList.add("hidden")
      })
    }
  }

  clearAllNotifications() {
    this.notifications = []
    this.saveNotifications()
  }
}

document.addEventListener("DOMContentLoaded", () => {
  if (!window.optimizedNotificationManager) {
    window.optimizedNotificationManager = new OptimizedNotificationManager()
  }
})