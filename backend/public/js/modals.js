document.addEventListener("DOMContentLoaded", () => {


  // ================================================
  // IMAGE PREVIEW LOGIC FOR "CREATE GROUP" MODAL
  // ================================================
  const createGroupInput = document.getElementById("create-group-image");
  const createGroupPreview = document.getElementById("create-group-preview");

  if (createGroupInput && createGroupPreview) {
    createGroupInput.addEventListener("change", function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          // Set the src of the preview image to the uploaded file
          createGroupPreview.src = e.target.result;
        }
        reader.readAsDataURL(file);
      }
    });
  }
    // ==========================================
    // 1. GLOBAL VARIABLES & MOCKS
    // ==========================================
    const API_BASE_URL = window.API_BASE_URL || "http://localhost:3000/api"
    const activeChat = window.activeChat || null
    const currentUser = window.currentUser || { id: null, username: null, avatar: null }
    
    // Mock functions if not defined globally
    const showNotification = window.showNotification || ((message, type) => console.log(`${type}: ${message}`))
    const createNewGroup = window.createNewGroup || ((groupName, selectedMembers) => console.log(`Creating group ${groupName} with members ${selectedMembers}`))
    const socket = window.socket || { emit: (event, data) => console.log(`Socket emit: ${event}`, data) }
  
    // ==========================================
    // 2. DOM ELEMENTS
    // ==========================================
    
    // Modals
    const createGroupModal = document.getElementById("create-group-modal")
    const groupSettingsModal = document.getElementById("group-settings-modal")
    const userSettingsModal = document.getElementById("user-settings-modal")
  
    // Buttons
    const createGroupBtn = document.getElementById("create-group-btn")
    const chatSettingsBtn = document.getElementById("chat-settings-btn")
    const userSettingsBtn = document.getElementById("settings-btn")
    const closeButtons = document.querySelectorAll(".close-btn")
  
    // Forms
    const createGroupForm = document.getElementById("create-group-form")
    const groupSettingsForm = document.getElementById("group-settings-form")
    const userSettingsForm = document.getElementById("user-settings-form")
  
    // ==========================================
    // 3. MODAL OPEN/CLOSE LOGIC
    // ==========================================
  
    // Open Create Group Modal
    if (createGroupBtn) {
        createGroupBtn.addEventListener("click", () => {
            createGroupModal.classList.remove("hidden")
            loadUsersForGroupCreation()
        })
    }
  
    // Open Group Settings Modal
    if (chatSettingsBtn) {
        chatSettingsBtn.addEventListener("click", () => {
            if (activeChat && activeChat.type === "group") {
                document.getElementById("group-settings-name").value = activeChat.name
                groupSettingsModal.classList.remove("hidden")
            }
        })
    }
  
    // Open User Settings Modal
    if (userSettingsBtn) {
        userSettingsBtn.addEventListener("click", () => {
            document.getElementById("user-nickname").value = currentUser.username
            userSettingsModal.classList.remove("hidden")
        })
    }
  
    // Close Modals (X buttons)
    closeButtons.forEach((button) => {
        button.addEventListener("click", () => {
            createGroupModal.classList.add("hidden")
            groupSettingsModal.classList.add("hidden")
            userSettingsModal.classList.add("hidden")
        })
    })
  
    // Close when clicking outside
    window.addEventListener("click", (e) => {
        if (e.target === createGroupModal) createGroupModal.classList.add("hidden")
        if (e.target === groupSettingsModal) groupSettingsModal.classList.add("hidden")
        if (e.target === userSettingsModal) userSettingsModal.classList.add("hidden")
    })
  
    // ==========================================
    // 4. IMAGE PREVIEW LOGIC (THE FIX)
    // ==========================================

    // Helper function to handle image previews
    function setupImagePreview(inputId, imgId) {
        const inputElement = document.getElementById(inputId)
        const imgElement = document.getElementById(imgId)

        if (inputElement && imgElement) {
            inputElement.addEventListener("change", (e) => {
                const file = e.target.files[0]
                if (file) {
                    const reader = new FileReader()
                    reader.onload = (e) => {
                        imgElement.src = e.target.result // Sets src on the <img> tag
                    }
                    reader.readAsDataURL(file)
                }
            })
        }
    }

    // Initialize previews for all modals
    // Ensure your HTML has these IDs for the <img> tags
    setupImagePreview("create-group-image", "create-group-preview")   // New Create Group Preview
    setupImagePreview("group-settings-image", "group-image-preview")  // Group Settings Preview
    setupImagePreview("user-avatar", "user-avatar-preview")           // User Profile Preview

  
    // ==========================================
    // 5. DATA LOADING & SUBMISSIONS
    // ==========================================
  
    function loadUsersForGroupCreation() {
        const membersList = document.getElementById("group-members-list")
        if (!membersList) return

        fetch(`${API_BASE_URL}/users`, {
            headers: { Authorization: `Bearer ${getAuthToken()}` },
        })
        .then((response) => response.json())
        .then((users) => {
            membersList.innerHTML = ""
            users.forEach((user) => {
                if (user.id === currentUser.id) return
  
                const memberItem = document.createElement("div")
                memberItem.className = "member-item"
                // Using generic avatar if none provided
                const avatarSrc = user.avatar || "/assets/default-avatar.png"
                
                memberItem.innerHTML = `
                    <img src="${avatarSrc}" alt="${user.username}">
                    <span>${user.username}</span>
                    <input type="checkbox" name="members" value="${user.id}">
                `
                membersList.appendChild(memberItem)
            })
        })
        .catch((error) => {
            console.error("Error loading users:", error)
            showNotification("Failed to load users", "error")
        })
    }
  
    // Create Group Submit
    if (createGroupForm) {
        createGroupForm.addEventListener("submit", (e) => {
            e.preventDefault()
            const groupName = document.getElementById("group-name").value
            const selectedMembers = Array.from(document.querySelectorAll('input[name="members"]:checked')).map(input => input.value)
            
            // Handle Image Upload for Create Group (Optional)
            const groupImageFile = document.getElementById("create-group-image")?.files[0]

            selectedMembers.push(currentUser.id)
  
            if (groupName && selectedMembers.length > 1) {
                // If you have logic to handle image upload during creation, add it here.
                // Otherwise, just create the group:
                createNewGroup(groupName, selectedMembers)
                createGroupModal.classList.add("hidden")
            } else {
                showNotification("Please enter a group name and select at least one member", "error")
            }
        })
    }
  
    // Group Settings Submit
    if (groupSettingsForm) {
        groupSettingsForm.addEventListener("submit", (e) => {
            e.preventDefault()
            const groupName = document.getElementById("group-settings-name").value
            const groupImageFile = document.getElementById("group-settings-image").files[0]
  
            if (groupName) {
                if (groupImageFile) {
                    const formData = new FormData()
                    formData.append("file", groupImageFile)
  
                    fetch(`${API_BASE_URL}/upload`, {
                        method: "POST",
                        headers: { Authorization: `Bearer ${getAuthToken()}` },
                        body: formData,
                    })
                    .then((response) => response.json())
                    .then((data) => {
                        updateGroupSettings(activeChat.id, groupName, data.fileUrl)
                        groupSettingsModal.classList.add("hidden")
                    })
                    .catch((error) => {
                        console.error("Error uploading image:", error)
                        showNotification("Failed to upload image", "error")
                    })
                } else {
                    updateGroupSettings(activeChat.id, groupName, null)
                    groupSettingsModal.classList.add("hidden")
                }
            } else {
                showNotification("Please enter a group name", "error")
            }
        })
    }
  
    // User Settings Submit
    if (userSettingsForm) {
        userSettingsForm.addEventListener("submit", (e) => {
            e.preventDefault()
            const nickname = document.getElementById("user-nickname").value
            const avatarFile = document.getElementById("user-avatar").files[0]
  
            if (nickname) {
                if (avatarFile) {
                    const formData = new FormData()
                    formData.append("file", avatarFile)
  
                    fetch(`${API_BASE_URL}/upload`, {
                        method: "POST",
                        headers: { Authorization: `Bearer ${getAuthToken()}` },
                        body: formData,
                    })
                    .then((response) => response.json())
                    .then((data) => {
                        updateUserProfile(nickname, data.fileUrl)
                        userSettingsModal.classList.add("hidden")
                    })
                    .catch((error) => {
                        console.error("Error uploading avatar:", error)
                        showNotification("Failed to upload avatar", "error")
                    })
                } else {
                    updateUserProfile(nickname, null)
                    userSettingsModal.classList.add("hidden")
                }
            } else {
                showNotification("Please enter a nickname", "error")
            }
        })
    }
  
    // ==========================================
    // 6. HELPER FUNCTIONS
    // ==========================================
  
    function updateUserProfile(nickname, avatarUrl) {
        fetch(`${API_BASE_URL}/users/profile`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${getAuthToken()}`,
            },
            body: JSON.stringify({ nickname, avatar: avatarUrl }),
        })
        .then((response) => response.json())
        .then((user) => {
            currentUser.username = nickname
            if (avatarUrl) {
                currentUser.avatar = avatarUrl
                const profileImg = document.getElementById("user-profile-img")
                if(profileImg) profileImg.src = avatarUrl
            }
            socket.emit("update_nickname", { newNickname: nickname })
            
            const profileName = document.getElementById("user-profile-name")
            if(profileName) profileName.textContent = nickname
            
            showNotification("Profile updated successfully", "success")
        })
        .catch((error) => {
            console.error("Error updating profile:", error)
            showNotification("Failed to update profile", "error")
        })
    }
  
    function getAuthToken() {
        return localStorage.getItem("auth_token")
    }
  
    function updateGroupSettings(groupId, groupName, imageUrl) {
        console.log(`Updating group ${groupId} with name ${groupName} and image ${imageUrl}`)
        // Add actual API call here if needed
    }
})