const SEEN_CONFIG = {
  ENABLED: true,
  DEBUG: false,
  MAX_RETRIES: 2,
  RETRY_DELAY: 3000,
  MAX_MESSAGES: 10,
  REFRESH_INTERVAL: 5000,
  API_ENDPOINT: "/chat-app/backend/api/getMessageSeenUsers/",
}

const seenUsersCache = {}

let isApiEndpointAvailable = true

function logDebug(...args) {
  if (SEEN_CONFIG.DEBUG) {
    console.log("[SeenAvatars]", ...args)
  }
}

function initSeenAvatars() {
  if (!SEEN_CONFIG.ENABLED) {
    return
  }

  checkApiEndpoint().then((available) => {
    isApiEndpointAvailable = available

    if (available) {
      setupSeenAvatars()
    }
  })
}

async function checkApiEndpoint() {
  try {
    const response = await fetch(`${SEEN_CONFIG.API_ENDPOINT}test`, {
      method: "GET",
      headers: {
        Accept: "application/json",
      },
    })

    if (response.status === 404) {
      return false
    }

    const data = await response.json()
    return true
  } catch (error) {
    return false
  }
}

function setupSeenAvatars() {
  setupSocketListeners()
  setupMutationObserver()
  updateSeenStatusForVisibleMessages()
  setInterval(updateSeenStatusForVisibleMessages, SEEN_CONFIG.REFRESH_INTERVAL)

  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      updateSeenStatusForVisibleMessages()
    }
  })
}

function setupSocketListeners() {
  if (!window.socket) {
    setTimeout(() => {
      if (window.socket) {
        window.socket.on("message_seen", handleMessageSeen)
      }
    }, 1000)
    return
  }

  window.socket.on("message_seen", handleMessageSeen)
}

function handleMessageSeen(data) {
  if (data && data.message_id) {
    const messageElement = document.querySelector(`.message[data-message-id="${data.message_id}"]`)
    if (messageElement) {
      const messageStatus = messageElement.querySelector(".message-status")
      if (messageStatus) {
        messageStatus.innerHTML = `
          <div class="seen-indicator">
            <span class="seen-text">Seen</span>
          </div>
        `

        messageStatus.classList.add("status-updated")
        setTimeout(() => {
          messageStatus.classList.remove("status-updated")
        }, 1000)

        storeSeenStatus(data.message_id, true)
      }
    }
  }
}

function storeSeenStatus(messageId, isSeen) {
  try {
    const seenMessages = JSON.parse(localStorage.getItem("seenMessages") || "{}")
    seenMessages[messageId] = isSeen
    localStorage.setItem("seenMessages", JSON.stringify(seenMessages))
  } catch (error) {
    console.error(error)
  }
}

function getStoredSeenStatus(messageId) {
  try {
    const seenMessages = JSON.parse(localStorage.getItem("seenMessages") || "{}")
    return seenMessages[messageId] || false
  } catch (error) {
    return false
  }
}

function setupMutationObserver() {
  const chatMessages = document.getElementById("chat-messages")
  if (!chatMessages) {
    return
  }

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.type === "childList" && mutation.addedNodes.length > 0) {
        setTimeout(updateSeenStatusForVisibleMessages, 500)
      }
    })
  })

  observer.observe(chatMessages, { childList: true })
}

function updateSeenStatusForVisibleMessages() {
  if (!SEEN_CONFIG.ENABLED) return

  const ownMessages = Array.from(document.querySelectorAll(".message.own-message[data-message-id]"))
  if (!ownMessages.length) return

  const recentMessages = ownMessages.slice(-SEEN_CONFIG.MAX_MESSAGES)

  recentMessages.forEach((message) => {
    const messageId = message.dataset.messageId
    if (messageId) {
      const messageStatus = message.querySelector(".message-status")
      if (messageStatus) {
        if (getStoredSeenStatus(messageId)) {
          updateSeenStatusElement(messageStatus, true)
        } else {
          fetchSeenStatus(messageId, messageStatus)
        }
      }
    }
  })
}

async function fetchSeenStatus(messageId, statusElement) {
  if (!isApiEndpointAvailable) {
    return
  }

  try {
    if (seenUsersCache[messageId]) {
      updateSeenStatusElement(statusElement, seenUsersCache[messageId].seen)
      return
    }

    const response = await fetch(`${SEEN_CONFIG.API_ENDPOINT}${messageId}`)

    if (!response.ok) {
      return
    }

    const data = await response.json()

    seenUsersCache[messageId] = {
      seen: data.seen_count > 0,
      users: data.seen_users || [],
    }

    if (data.seen_count > 0) {
      storeSeenStatus(messageId, true)
    }

    updateSeenStatusElement(statusElement, data.seen_count > 0)
  } catch (error) {
    console.error(error)
  }
}

function updateSeenStatusElement(element, isSeen) {
  if (!element) return

  if (isSeen) {
    element.innerHTML = `
      <div class="seen-indicator">
        <span class="seen-text">Seen</span>
      </div>
    `
  } else {
    element.innerHTML = `
      <div class="sent-indicator">
        <span class="sent-text">Sent</span>
      </div>
    `
  }
}

function fetchSeenUsers(messageId) {
  return new Promise((resolve, reject) => {
    if (!isApiEndpointAvailable) {
      resolve([])
      return
    }

    if (seenUsersCache[messageId]) {
      resolve(seenUsersCache[messageId].users)
      return
    }

    fetch(`${SEEN_CONFIG.API_ENDPOINT}${messageId}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`API error: ${response.status}`)
        }
        return response.json()
      })
      .then((data) => {
        seenUsersCache[messageId] = {
          seen: data.seen_count > 0,
          users: data.seen_users || [],
        }

        if (data.seen_count > 0) {
          storeSeenStatus(messageId, true)
        }

        resolve(data.seen_users || [])
      })
      .catch((error) => {
        resolve([])
      })
  })
}

function updateSeenIndicator(messageElement, seenUsers) {
  if (!messageElement) return

  const messageStatus = messageElement.querySelector(".message-status")
  if (!messageStatus) return

  const messageId = messageElement.dataset.messageId
  const isSeen = seenUsers && seenUsers.length > 0

  if (isSeen) {
    messageStatus.innerHTML = `
      <div class="seen-indicator">
        <span class="seen-text">Seen</span>
      </div>
    `
    storeSeenStatus(messageId, true)
  } else {
    messageStatus.innerHTML = `
      <div class="sent-indicator">
        <span class="sent-text">Sent</span>
      </div>
    `
  }
}

document.addEventListener("DOMContentLoaded", initSeenAvatars)

window.seenAvatars = {
  init: initSeenAvatars,
  fetchSeenUsers: fetchSeenUsers,
  updateSeenIndicator: updateSeenIndicator,
  update: updateSeenStatusForVisibleMessages,
  enable: () => {
    SEEN_CONFIG.ENABLED = true
    initSeenAvatars()
  },
  disable: () => {
    SEEN_CONFIG.ENABLED = false
  },
}