document.addEventListener("DOMContentLoaded", () => {
    initStatusDropdown()

    if (typeof window.socket !== "undefined") {
        initSocketStatusListeners(window.socket)
    }

    const savedStatus = sessionStorage.getItem("userStatus")
    if (savedStatus) {
        updateStatusIndicator(savedStatus)
    } else {
        const serverStatus = document.querySelector('meta[name="user-status"]')?.content
        if (serverStatus) {
            sessionStorage.setItem("userStatus", serverStatus)
            updateStatusIndicator(serverStatus)
        }
    }
})

function initStatusDropdown() {
    if (window.statusDropdownInitialized) return

    const statusDropdownBtn = document.getElementById("status-dropdown-btn")
    const statusDropdown = document.getElementById("status-dropdown")
    const statusText = document.getElementById("status-text")
    const statusItems = document.querySelectorAll("#status-dropdown li")

    if (!statusDropdownBtn || !statusDropdown) return

    statusDropdownBtn.addEventListener("click", (e) => {
        e.preventDefault()
        e.stopPropagation()
        statusDropdown.classList.toggle("hidden")
    })

    document.addEventListener("click", (e) => {
        if (!statusDropdownBtn.contains(e.target) && !statusDropdown.contains(e.target)) {
            statusDropdown.classList.add("hidden")
        }
    })

    statusItems.forEach((item) => {
        item.addEventListener("click", function (e) {
            e.preventDefault()
            e.stopPropagation()

            const status = this.getAttribute("data-status")
            if (statusText) statusText.textContent = this.textContent.trim()

            updateStatusIndicator(status)
            updateUserStatus(status)
            statusDropdown.classList.add("hidden")
        })
    })

    window.statusDropdownInitialized = true
}

function updateStatusIndicator(status) {
    const mainStatusIndicator = document.querySelector(".user-profile .status-indicator")
    if (mainStatusIndicator) {
        mainStatusIndicator.classList.remove("Online", "Away", "Busy", "Offline")
        mainStatusIndicator.classList.add(status)
    }

    const currentUserId = getCurrentUserId()
    if (currentUserId) {
        updateUserStatusInList(currentUserId, status)
    }
}

function getCurrentUserId() {
    const userProfile = document.querySelector(".user-profile")
    const userId = userProfile ? userProfile.getAttribute("data-user-id") : null
    return userId || document.querySelector('meta[name="user-id"]')?.content || sessionStorage.getItem("userId")
}

function updateUserStatusInList(userId, status) {
    userId = Number(userId)
    const userItems = document.querySelectorAll(`.user-item[data-id="${userId}"]`)

    userItems.forEach((item) => {
        const statusIndicator = item.querySelector(".online-indicator")
        if (statusIndicator) {
            statusIndicator.classList.remove("Online", "Away", "Busy", "Offline")
            statusIndicator.classList.add(status)
        }
    })

    const directMessageItems = document.querySelectorAll(`#direct-messages-list .conversations-list-item[data-user-id="${userId}"]`)
    directMessageItems.forEach((item) => {
        const statusIndicator = item.querySelector(".status-indicator")
        if (statusIndicator) {
            statusIndicator.classList.remove("Online", "Away", "Busy", "Offline")
            statusIndicator.classList.add(status)
        }
    })
}

function updateUserStatus(status) {
    const currentUserId = getCurrentUserId()
    if (!currentUserId) return

    const apiUrl = "backend/update_user_status.php"

    fetch(apiUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest",
        },
        body: new URLSearchParams({
            status: status,
            user_id: currentUserId,
        }),
    })
    .then(response => {
        if (!response.ok) throw new Error("File not found or server error")
        return response.json()
    })
    .then(data => {
        if (data.success) {
            sessionStorage.setItem("userStatus", status)
            if (window.socket && window.socket.connected) {
                window.socket.emit("status_change", {
                    user_id: Number(currentUserId),
                    status: status,
                    timestamp: new Date().toISOString(),
                })
            }
        }
    })
    .catch(error => console.error("Update failed:", error))
}

function initSocketStatusListeners(socket) {
    socket.on("status_change", (data) => {
        const currentUserId = Number(getCurrentUserId())
        const userId = Number(data.user_id)
        const status = data.status

        if (userId === currentUserId) {
            updateStatusIndicator(status)
        } else {
            updateUserStatusInList(userId, status)
        }
    })
}

window.updateUserStatus = updateUserStatus
window.updateStatusIndicator = updateStatusIndicator
window.updateUserStatusInList = updateUserStatusInList