document.addEventListener("DOMContentLoaded", () => {
    // Get DOM elements
    const leaveGroupBtn = document.querySelector(".leave-group-btn")
    const editGroupBtn = document.querySelector(".edit-group-btn")
    const manageGroupBtn = document.querySelector(".manage-members-btn")
  
    // API base URL - adjust this to match your actual API URL
    const API_BASE_URL = window.location.origin + "/api"
  
    // Get auth token from session instead of localStorage
    function getAuthToken() {
      return null
    }
  
    // Show notification
    function showNotification(message, type) {
      console.log(`Notification: ${message} (${type})`)
  
      // Create notification element if it doesn't exist
      let notification = document.querySelector(".notification-message")
      if (!notification) {
        notification = document.createElement("div")
        notification.className = `notification-message ${type}`
        document.body.appendChild(notification)
      } else {
        notification.className = `notification-message ${type}`
      }
  
      notification.textContent = message
      notification.style.display = "block"
  
      // Show notification
      setTimeout(() => {
        notification.classList.add("show")
      }, 10)
  
      // Hide notification after 3 seconds
      setTimeout(() => {
        notification.classList.remove("show")
        setTimeout(() => {
          notification.style.display = "none"
        }, 300)
      }, 3000)
    }
  
    // Get current group ID from URL
    function getCurrentGroupId() {
      const url = window.location.pathname
      const matches = url.match(/\/chat\/groups\/(\d+)/)
      if (matches) {
        return matches[1]
      }
  
      // Try alternative URL pattern
      const altMatches = url.match(/\/chat\/group\/(\d+)/)
      return altMatches ? altMatches[1] : null
    }
  
    // Get CSRF token from the page
    function getCsrfToken() {
      const tokenInput = document.querySelector('input[name="csrf_token"]')
      return tokenInput ? tokenInput.value : ""
    }
  
    // Leave group function
    function leaveGroup(e) {
      if (e) {
        e.preventDefault()
      }
  
      const groupId = getCurrentGroupId()
      if (!groupId) {
        console.error("Could not determine group ID")
        showNotification("Could not determine group ID", "error")
        return
      }
  
      // Show confirmation dialog
      if (!confirm("Are you sure you want to leave this group?")) {
        return
      }
  
      console.log(`Attempting to leave group ${groupId}`)
  
      // Create form data with CSRF token
      const formData = new FormData()
      formData.append("csrf_token", getCsrfToken())
  
      // Use fetch with credentials to ensure cookies are sent
      fetch(`${API_BASE_URL}/leave-group/${groupId}`, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "include", // Important for including session cookies
        body: formData,
      })
        .then((response) => {
          console.log("Leave group response status:", response.status)
          if (!response.ok) {
            return response.json().then((data) => {
              throw new Error(data.error || "Failed to leave group")
            })
          }
          return response.json()
        })
        .then((data) => {
          console.log("Leave group success:", data)
          showNotification(data.message || "Successfully left the group", "success")
  
          // Delay redirect to ensure notification is seen
          setTimeout(() => {
            window.location.href = "/chat"
          }, 1500)
        })
        .catch((error) => {
          console.error("Error leaving group:", error)
          showNotification(error.message || "Failed to leave group. Please try again.", "error")
        })
    }
  
    // Edit group function
    function updateGroup(formData) {
      const groupId = getCurrentGroupId()
      if (!groupId) {
        showNotification("Could not determine group ID", "error")
        return
      }
  
      formData.append("csrf_token", getCsrfToken())
  
      fetch(`${API_BASE_URL}/update-group/${groupId}`, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "include",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            return response.json().then((data) => {
              throw new Error(data.error || "Failed to update group")
            })
          }
          return response.json()
        })
        .then((data) => {
          showNotification(data.message || "Group updated successfully", "success")
          // Reload page to show changes
          setTimeout(() => {
            window.location.reload()
          }, 1500)
        })
        .catch((error) => {
          console.error("Error updating group:", error)
          showNotification(error.message || "Failed to update group. Please try again.", "error")
        })
    }
  
    // Add member function
    function addGroupMember(userId) {
      const groupId = getCurrentGroupId()
      if (!groupId) {
        showNotification("Could not determine group ID", "error")
        return
      }
  
      const formData = new FormData()
      formData.append("user_id", userId)
      formData.append("csrf_token", getCsrfToken())
  
      fetch(`${API_BASE_URL}/group-members/${groupId}`, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "include",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            return response.json().then((data) => {
              throw new Error(data.error || "Failed to add member")
            })
          }
          return response.json()
        })
        .then((data) => {
          showNotification(data.message || "Member added successfully", "success")
          // Reload page to show changes
          setTimeout(() => {
            window.location.reload()
          }, 1500)
        })
        .catch((error) => {
          console.error("Error adding member:", error)
          showNotification(error.message || "Failed to add member. Please try again.", "error")
        })
    }
  
    // Remove member function
    function removeGroupMember(userId) {
      const groupId = getCurrentGroupId()
      if (!groupId) {
        showNotification("Could not determine group ID", "error")
        return
      }
  
      if (!confirm("Are you sure you want to remove this member?")) {
        return
      }
  
      const formData = new FormData()
      formData.append("user_id", userId)
      formData.append("csrf_token", getCsrfToken())
  
      fetch(`${API_BASE_URL}/group-members/${groupId}`, {
        method: "DELETE",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "include",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            return response.json().then((data) => {
              throw new Error(data.error || "Failed to remove member")
            })
          }
          return response.json()
        })
        .then((data) => {
          showNotification(data.message || "Member removed successfully", "success")
          // Reload page to show changes
          setTimeout(() => {
            window.location.reload()
          }, 1500)
        })
        .catch((error) => {
          console.error("Error removing member:", error)
          showNotification(error.message || "Failed to remove member. Please try again.", "error")
        })
    }
  
    // Initialize event listeners
    function initEventListeners() {
      console.log("Initializing group management event listeners")
  
      // Leave group button
      if (leaveGroupBtn) {
        console.log("Found leave group button, adding event listener")
        leaveGroupBtn.addEventListener("click", leaveGroup)
      } else {
        console.log("Leave group button not found")
  
        // Try to find by ID instead
        const leaveGroupBtnById = document.getElementById("leave-group-btn")
        if (leaveGroupBtnById) {
          console.log("Found leave group button by ID, adding event listener")
          leaveGroupBtnById.addEventListener("click", leaveGroup)
        }
  
        // Try to find all buttons with text "Leave Group"
        document.querySelectorAll("button").forEach((button) => {
          if (button.textContent.trim() === "Leave Group") {
            console.log("Found leave group button by text content, adding event listener")
            button.addEventListener("click", leaveGroup)
          }
        })
      }
  
      // Edit group form submission
      const editGroupForm = document.getElementById("edit-group-form")
      if (editGroupForm) {
        console.log("Found edit group form, adding event listener")
        editGroupForm.addEventListener("submit", (e) => {
          e.preventDefault()
          const formData = new FormData(editGroupForm)
          updateGroup(formData)
        })
      }
  
      // Add member buttons
      document.querySelectorAll(".add-member-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
          const userId = btn.dataset.userId
          addGroupMember(userId)
        })
      })
  
      // Remove member buttons
      document.querySelectorAll(".remove-member-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
          const userId = btn.dataset.userId
          removeGroupMember(userId)
        })
      })
    }
  
    // Initialize
    initEventListeners()
  
    // Make these functions available globally
    window.groupManagement = {
      leaveGroup,
      updateGroup,
      addGroupMember,
      removeGroupMember,
    }
  })
  
  