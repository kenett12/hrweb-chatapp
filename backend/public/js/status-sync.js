/**
 * Status Synchronization
 * Ensures user status changes are reflected across all pages in real-time
 */

// Centralized API configuration to match your server structure
const API_CONFIG = {
    BASE_URL: '/chat-app/backend/api',
    endpoints: {
        getAllUserStatuses: '/getAllUserStatuses',
        updateUserStatus: '/updateUserStatus'
    }
};

// Helper to construct full API URLs
const getApiUrl = (endpoint) => `${API_CONFIG.BASE_URL}${endpoint}`;

document.addEventListener("DOMContentLoaded", () => {
    initStatusSync();

    if (window.socket) {
        setupSocketListeners();
    } else {
        setTimeout(() => {
            if (window.socket) {
                setupSocketListeners();
            }
        }, 2000);
    }

    startStatusPolling();
    
    // Initialize ticketing safely
    initTicketingSystem();
});

function initStatusSync() {
    window.currentUserId = getCurrentUserId();
    loadAllUserStatuses();
}

function getCurrentUserId() {
    const userProfile = document.querySelector(".user-profile");
    const userId = userProfile ? userProfile.getAttribute("data-user-id") : null;

    if (!userId) {
        return document.querySelector('meta[name="user-id"]')?.content || sessionStorage.getItem("userId");
    }

    return userId;
}

function loadAllUserStatuses() {
    fetch(getApiUrl(API_CONFIG.endpoints.getAllUserStatuses))
        .then((response) => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then((data) => {
            if (data.success && data.statuses) {
                Object.entries(data.statuses).forEach(([userId, status]) => {
                    updateUserStatusInUI(userId, status || "offline");
                });
            }
        })
        .catch((error) => {
            console.error("Error loading user statuses:", error);
        });
}

function setupSocketListeners() {
    if (!window.socket) return;

    window.socket.on("status_change", (data) => {
        if (data && data.user_id && data.status) {
            updateUserStatusInUI(data.user_id, data.status);
        }
    });

    window.socket.on("online_users", (userIds) => {
        // Handled elsewhere if needed
    });
}

function updateUserStatusInUI(userId, status) {
    userId = Number(userId);

    if (!status || typeof status !== "string") {
        return;
    }

    const userItems = document.querySelectorAll(
        `.user-item[data-id="${userId}"], .conversation-item[data-user-id="${userId}"]`
    );

    userItems.forEach((item) => {
        const statusIndicator = item.querySelector(".status-indicator, .online-indicator");
        if (statusIndicator) {
            statusIndicator.classList.remove("Online", "Away", "Busy", "Offline");
            statusIndicator.classList.add(status);
        }
    });

    const directMessageItems = document.querySelectorAll(
        `#direct-messages-list .conversation-item[data-user-id="${userId}"]`
    );

    directMessageItems.forEach((item) => {
        const statusIndicator = item.querySelector(".status-indicator");
        if (statusIndicator) {
            statusIndicator.classList.remove("Online", "Away", "Busy", "Offline");
            statusIndicator.classList.add(status);
        }
    });

    if (userId == window.currentUserId) {
        const mainStatusIndicator = document.querySelector(".user-profile .status-indicator");
        if (mainStatusIndicator) {
            mainStatusIndicator.classList.remove("Online", "Away", "Busy", "Offline");
            mainStatusIndicator.classList.add(status);

            const statusText = document.getElementById("status-text");
            if (statusText) {
                const statusDisplayMap = {
                    Online: "Available",
                    Away: "Away",
                    Busy: "Busy",
                    Offline: "Offline",
                };
                statusText.textContent = statusDisplayMap[status] || status.charAt(0).toUpperCase() + status.slice(1);
            }

            sessionStorage.setItem("userStatus", status);
        }
    }
}

function startStatusPolling() {
    setInterval(() => {
        loadAllUserStatuses();
    }, 30000);
}

function updateUserStatus(status) {
    const userId = window.currentUserId || getCurrentUserId();

    if (!userId) {
        console.error("Cannot update status: User ID not found");
        return;
    }

    fetch(getApiUrl(API_CONFIG.endpoints.updateUserStatus), {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({
            status: status,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                sessionStorage.setItem("userStatus", status);
                updateUserStatusInUI(userId, status);

                if (window.socket && window.socket.connected) {
                    window.socket.emit("status_change", {
                        user_id: Number(userId),
                        status: status,
                        timestamp: new Date().toISOString(),
                    });
                }
            }
        })
        .catch((error) => {
            console.error("Error updating status:", error);
        });
}

// Ticketing System Initialization (Safety Wrapped)
function initTicketingSystem() {
    const ticketList = document.getElementById('ticket-list');
    const createTicketBtn = document.getElementById('create-ticket-btn');

    // If these elements don't exist on the current page, exit early to prevent errors
    if (!ticketList || !createTicketBtn) {
        return;
    }

    function loadTickets() {
        const tickets = [
            { id: 1, title: 'Issue with login', status: 'Open' },
            { id: 2, title: 'Bug in chat interface', status: 'In Progress' },
        ];

        ticketList.innerHTML = '';
        tickets.forEach(ticket => {
            const ticketItem = document.createElement('div');
            ticketItem.className = 'ticket-item';
            ticketItem.innerHTML = `
                <h4>${ticket.title}</h4>
                <p>Status: ${ticket.status}</p>
            `;
            ticketList.appendChild(ticketItem);
        });
    }

    createTicketBtn.addEventListener('click', function () {
        const ticketTitle = prompt('Enter ticket title:');
        if (ticketTitle) {
            alert(`Ticket "${ticketTitle}" created successfully!`);
            loadTickets();
        }
    });

    loadTickets();
}

window.updateUserStatus = updateUserStatus;
window.updateUserStatusInUI = updateUserStatusInUI;