document.addEventListener("DOMContentLoaded", () => {
    console.log("Chat index page initialized");

    const currentUserId = document.querySelector('meta[name="user-id"]')?.content || window.currentUser?.id;
    if (!currentUserId) {
        console.error("User ID not found in meta tags");
        return;
    }

    // We don't call connect() here anymore. SocketManager handles the connection.
    // We just need to wait for the socket to be ready to set up specific index page logic.
    
    const checkSocket = setInterval(() => {
        if (window.socket && window.socket.connected) {
            console.log("Chat index linked to active socket.");
            setupIndexListeners(window.socket);
            clearInterval(checkSocket);
        }
    }, 500);

    function setupIndexListeners(socket) {
        // Listen for online users updates specific to the sidebar
        socket.on("online_users", (users) => {
            console.log("Online users list updated:", users);
            updateOnlineUsersUI(users);
        });

        // Initialize notification system after socket is confirmed
        if (window.notificationManager && typeof window.notificationManager.setupSocketListeners === 'function') {
            window.notificationManager.setupSocketListeners();
        }
    }

    function updateOnlineUsersUI(onlineUserIds) {
        document.querySelectorAll(".user-item, .conversation-item[data-user-id]").forEach((userItem) => {
            const userId = Number(userItem.dataset.userId || userItem.dataset.id);
            const isOnline = onlineUserIds.includes(userId);
            const indicator = userItem.querySelector(".status-indicator");
            if (indicator) {
                indicator.className = `status-indicator ${isOnline ? "online" : "offline"}`;
            }
        });
    }
}); 