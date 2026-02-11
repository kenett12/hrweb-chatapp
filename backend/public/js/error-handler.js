/**
 * Global error handler to prevent page crashes and refreshes
 */
;(() => {
  // Store original console methods
  const originalConsoleError = console.error
  const originalConsoleWarn = console.warn

  // Override console.error to catch and report errors
  console.error = (...args) => {
    // Call original method
    originalConsoleError.apply(console, args)

    // Log to error tracking system if available
    if (window.errorTracker) {
      window.errorTracker.logError(args.join(" "))
    }

    // Check if this is a critical error that might cause a page refresh
    const errorString = args.join(" ")
    if (
      errorString.includes("Uncaught") ||
      errorString.includes("undefined is not a function") ||
      errorString.includes("null is not an object") ||
      errorString.includes("cannot read property")
    ) {
      console.warn("Critical error detected, taking preventive measures")

      // Add to session storage to track error frequency
      incrementErrorCount()
    }

    // Check if this is a groups loading error
    if (errorString.includes("Error loading groups")) {
      // Check if groups are already loaded
      const groupItems = document.querySelectorAll(".conversation-item[data-group-id]")
      if (groupItems.length > 0) {
        console.log("Groups already loaded, suppressing error notification")
        return
      }
    }
  }

  // Override console.warn to track warnings
  console.warn = (...args) => {
    // Call original method
    originalConsoleWarn.apply(console, args)

    // Log to error tracking system if available
    if (window.errorTracker) {
      window.errorTracker.logWarning(args.join(" "))
    }
  }

  // Track error frequency
  function incrementErrorCount() {
    try {
      const now = Date.now()
      const errorCount = Number.parseInt(sessionStorage.getItem("errorCount") || "0")
      const lastErrorTime = Number.parseInt(sessionStorage.getItem("lastErrorTime") || "0")

      // If errors are happening rapidly (within 5 seconds)
      if (now - lastErrorTime < 5000) {
        sessionStorage.setItem("errorCount", (errorCount + 1).toString())

        // If too many errors in a short time, take action
        if (errorCount > 5) {
          console.warn("Too many errors detected, stabilizing page")
          sessionStorage.setItem("preventRefresh", "true")

          // Create a visible error message for the user
          showErrorMessage("The application encountered multiple errors. Stabilizing...")

          // Reset error count
          sessionStorage.setItem("errorCount", "0")
        }
      } else {
        // Reset counter if errors aren't happening rapidly
        sessionStorage.setItem("errorCount", "1")
      }

      sessionStorage.setItem("lastErrorTime", now.toString())
    } catch (e) {
      // Fail silently if sessionStorage is not available
    }
  }

  // Show error message to user
  function showErrorMessage(message) {
    // Check if error message container exists
    let errorContainer = document.getElementById("error-message-container")

    // Create it if it doesn't exist
    if (!errorContainer) {
      errorContainer = document.createElement("div")
      errorContainer.id = "error-message-container"
      errorContainer.style.position = "fixed"
      errorContainer.style.bottom = "20px"
      errorContainer.style.right = "20px"
      errorContainer.style.backgroundColor = "#f44336"
      errorContainer.style.color = "white"
      errorContainer.style.padding = "15px"
      errorContainer.style.borderRadius = "4px"
      errorContainer.style.boxShadow = "0 2px 5px rgba(0,0,0,0.3)"
      errorContainer.style.zIndex = "9999"
      errorContainer.style.maxWidth = "300px"

      // Add close button
      const closeButton = document.createElement("button")
      closeButton.innerHTML = "&times;"
      closeButton.style.background = "none"
      closeButton.style.border = "none"
      closeButton.style.color = "white"
      closeButton.style.float = "right"
      closeButton.style.fontSize = "20px"
      closeButton.style.cursor = "pointer"
      closeButton.style.marginLeft = "10px"
      closeButton.onclick = () => {
        errorContainer.style.display = "none"
      }

      errorContainer.appendChild(closeButton)
      document.body.appendChild(errorContainer)
    }

    // Add the message
    const messageElement = document.createElement("p")
    messageElement.style.margin = "0"
    messageElement.textContent = message

    // Clear previous messages
    while (errorContainer.childNodes.length > 1) {
      errorContainer.removeChild(errorContainer.lastChild)
    }

    errorContainer.appendChild(messageElement)
    errorContainer.style.display = "block"

    // Hide after 10 seconds
    setTimeout(() => {
      errorContainer.style.display = "none"
    }, 10000)
  }

  // Global error handler
  window.addEventListener("error", (event) => {
    // Prevent default browser error handling
    event.preventDefault()

    // Log the error
    console.error("Caught global error:", event.error || event.message)

    // Check if this is a socket-related error
    const errorMessage = event.message || ""
    if (
      errorMessage.includes("io is not defined") ||
      errorMessage.includes("socket") ||
      errorMessage.includes("connection")
    ) {
      console.log("Socket-related error detected, handling gracefully")

      // Try to reconnect socket if needed
      if (window.socketManager && typeof window.socketManager.reconnect === "function") {
        window.socketManager.reconnect()
      }

      // Show user-friendly message
      showErrorNotification("Connection issue detected. Attempting to reconnect...")

      return false
    }

    // For other errors, show a generic message
    showErrorNotification("An error occurred. Please refresh the page if issues persist.")

    return false
  })

  // Unhandled promise rejection handler
  window.addEventListener("unhandledrejection", (event) => {
    console.error("Unhandled promise rejection:", event.reason)

    // Prevent default behavior
    event.preventDefault()

    // Increment error count
    incrementErrorCount()

    return true
  })

  // Initialize error tracking
  window.errorTracker = {
    errors: [],
    warnings: [],
    maxLogSize: 50,

    logError: function (error) {
      this.errors.push({
        timestamp: new Date().toISOString(),
        message: error,
      })

      // Keep log size manageable
      if (this.errors.length > this.maxLogSize) {
        this.errors.shift()
      }
    },

    logWarning: function (warning) {
      this.warnings.push({
        timestamp: new Date().toISOString(),
        message: warning,
      })

      // Keep log size manageable
      if (this.warnings.length > this.maxLogSize) {
        this.warnings.shift()
      }
    },

    getErrors: function () {
      return this.errors
    },

    getWarnings: function () {
      return this.warnings
    },

    clearLogs: function () {
      this.errors = []
      this.warnings = []
    },
  }

  // Function to show error notification
  function showErrorNotification(message) {
    // Check if we already have an error notification with this message
    const existingNotifications = document.querySelectorAll(".error-notification")
    for (let i = 0; i < existingNotifications.length; i++) {
      if (existingNotifications[i].textContent === message) {
        return // Don't show duplicate notifications
      }
    }

    // Create notification element
    const notification = document.createElement("div")
    notification.className = "error-notification"
    notification.textContent = message

    // Add close button
    const closeBtn = document.createElement("span")
    closeBtn.className = "close-notification"
    closeBtn.innerHTML = "&times;"
    closeBtn.onclick = () => {
      document.body.removeChild(notification)
    }

    notification.appendChild(closeBtn)

    // Add to DOM
    document.body.appendChild(notification)

    // Auto-remove after 5 seconds
    setTimeout(() => {
      if (document.body.contains(notification)) {
        document.body.removeChild(notification)
      }
    }, 5000)
  }

  // Add CSS for notifications
  const style = document.createElement("style")
  style.textContent = `
        .error-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #f44336;
            color: white;
            padding: 15px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 10000;
            max-width: 300px;
            animation: slideIn 0.3s ease-out;
        }

        .close-notification {
            margin-left: 15px;
            color: white;
            font-weight: bold;
            float: right;
            font-size: 22px;
            line-height: 20px;
            cursor: pointer;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `
  document.head.appendChild(style)

  console.log("Error handler initialized")
})()
