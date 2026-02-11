// Notification Settings UI
document.addEventListener("DOMContentLoaded", () => {
    // Helper to get correct asset paths
    const getAssetPath = (path) => {
        const base = window.baseUrl || '/chat-app/backend/';
        // Remove leading slash if it exists to prevent double slashes
        const cleanPath = path.startsWith('/') ? path.substring(1) : path;
        return base.endsWith('/') ? `${base}${cleanPath}` : `${base}/${cleanPath}`;
    };

    // Create notification settings UI
    function createNotificationSettings() {
      // Safety check: ensure notificationManager exists
      if (!window.notificationManager) {
        console.warn("NotificationManager not ready, retrying UI creation...");
        setTimeout(createNotificationSettings, 500);
        return;
      }

      const settingsContainer = document.createElement("div")
      settingsContainer.id = "notification-settings"
      settingsContainer.className = "notification-settings hidden"
  
      settingsContainer.innerHTML = `
        <div class="settings-header">
          <h3>Notification Settings</h3>
          <button class="close-btn">&times;</button>
        </div>
        <div class="settings-body">
          <div class="setting-item">
            <label for="desktop-notifications">Desktop Notifications</label>
            <div class="toggle-switch">
              <input type="checkbox" id="desktop-notifications" ${window.notificationManager.notificationEnabled ? "checked" : ""}>
              <span class="toggle-slider"></span>
            </div>
          </div>
          <div class="setting-item">
            <label for="sound-notifications">Sound Notifications</label>
            <div class="toggle-switch">
              <input type="checkbox" id="sound-notifications" ${window.notificationManager.soundEnabled ? "checked" : ""}>
              <span class="toggle-slider"></span>
            </div>
          </div>
          <div class="setting-item">
            <button id="test-notification" class="btn">Test Notification</button>
          </div>
        </div>
      `
  
      document.body.appendChild(settingsContainer)
  
      // Add event listeners
      settingsContainer.querySelector(".close-btn").addEventListener("click", () => {
        settingsContainer.classList.add("hidden")
      })
  
      document.getElementById("desktop-notifications").addEventListener("change", (e) => {
        window.notificationManager.toggleNotifications(e.target.checked)
  
        if (e.target.checked && Notification.permission !== "granted") {
          window.notificationManager.requestPermission().then((permission) => {
            if (permission !== "granted") {
              e.target.checked = false
              window.notificationManager.toggleNotifications(false)
              alert("You need to allow notifications in your browser settings.")
            }
          })
        }
      })
  
      document.getElementById("sound-notifications").addEventListener("change", (e) => {
        window.notificationManager.toggleSound(e.target.checked)
      })
  
      document.getElementById("test-notification").addEventListener("click", () => {
        // Test notification - FIXED PATHS
        window.notificationManager.showNotification("Test Notification", {
          body: "This is a test notification",
          icon: getAssetPath("uploads/default-avatar.png"), // Changed from assets to uploads based on your logs
        })
        window.notificationManager.playSound(false)
  
        // Show a test mention notification after 1 second
        setTimeout(() => {
          window.notificationManager.showNotification("Test Mention Notification", {
            body: "This is a test @mention notification",
            icon: getAssetPath("uploads/default-avatar.png"),
          })
          window.notificationManager.playSound(true)
        }, 1000)
      })
    }
  
    // Add notification settings button to the header
    function addNotificationSettingsButton() {
      const header = document.querySelector(".user-profile")
      if (!header) return
  
      const settingsButton = document.createElement("button")
      settingsButton.id = "notification-settings-btn"
      settingsButton.className = "notification-settings-btn"
      settingsButton.innerHTML = `
        <i class="fas fa-bell"></i>
        <span id="total-unread-badge" class="unread-badge hidden">0</span>
      `
  
      header.appendChild(settingsButton)
  
      settingsButton.addEventListener("click", () => {
        const settings = document.getElementById("notification-settings");
        if(settings) settings.classList.toggle("hidden");
      })
    }
  
    // Initialize notification settings UI
    if (document.getElementById("chat-container") || document.querySelector(".user-profile")) {
      createNotificationSettings()
      addNotificationSettingsButton()
    }
})