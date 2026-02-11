/**
 * Group Manager
 * Handles group creation, editing, and management functionality
 */

// Ensure Base URL is set (checks existing window.baseUrl first)
if (!window.baseUrl) {
    // Fallback only if not set by sidebar.php
    window.baseUrl = '/'; 
}
if (!window.baseUrl.endsWith('/')) {
    window.baseUrl += '/';
}

document.addEventListener("DOMContentLoaded", () => {
    initGroupManager();
});

function initGroupManager() {
    if (window.groupManagerInitialized) return;
    try {
        // Create the modal structure immediately
        createGroupModal();
        
        // Setup triggers to open it
        // We use a delegated listener on document to catch buttons added dynamically
        document.body.addEventListener("click", (e) => {
            if (e.target.closest(".create-group-btn") || e.target.closest("#create-group-btn")) {
                e.preventDefault();
                showCreateGroupModal();
            }
        });

        window.groupManagerInitialized = true;
    } catch (error) {
        console.error("Group manager init failed:", error);
    }
}

function createGroupModal() {
    // 1. Clean up existing modal to prevent duplicates
    const existingModal = document.getElementById("create-group-modal");
    if (existingModal) existingModal.remove();

    // 2. The HTML
    const modalHTML = `
    <div id="create-group-modal" class="modal hidden"> 
      <div class="modal-backdrop"></div>
      <div class="modal-content">
        
        <div class="modal-header">
          <div class="modal-title-section">
            <div class="modal-icon"><i class="fas fa-users"></i></div>
            <div class="modal-title-text">
              <h2>Create New Group</h2>
              <p>Start a conversation with multiple people</p>
            </div>
          </div>
          <button class="modal-close" id="close-group-modal"><i class="fas fa-times"></i></button>
        </div>

        <div class="modal-body">
          <form id="create-group-form">
            
            <div class="group-image-section">
              <div class="group-image-upload">
                <div class="image-preview-container" id="group-image-preview-wrapper">
                   <div class="default-group-icon" id="default-group-icon">
                      <i class="fas fa-camera"></i>
                   </div>
                   <img id="group-image-preview" src="#" alt="Preview" style="display: none; width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                </div>
                <label for="group-image" class="image-upload-btn">
                  <i class="fas fa-camera"></i> <span>Add Photo</span>
                </label>
                <input type="file" id="group-image" name="image" accept="image/*" hidden>
              </div>
            </div>

            <div class="group-details-section">
              <div class="form-group">
                  <label for="group-name">Group Name</label>
                  <input type="text" id="group-name" name="name" required placeholder="Enter group name" maxlength="50">
              </div>
            </div>

            <div class="members-section">
              <div class="section-header">
                <h3>Add Members</h3>
                <span class="member-count"><span id="selected-count">0</span> selected</span>
              </div>
              
              <div class="members-search-container">
                  <div class="search-input-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="members-search" placeholder="Search contacts...">
                  </div>
              </div>

              <div class="selected-members-preview" id="selected-members-preview"></div>

              <div class="members-list-container">
                <div class="members-list" id="members-list">
                    <div style="text-align:center; padding: 20px; color: #666;">
                        <i class="fas fa-spinner fa-spin"></i> Loading contacts...
                    </div>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" id="cancel-group-btn">Cancel</button>
              <button type="submit" class="btn btn-primary" id="create-group-submit">Create Group</button>
            </div>
          </form>
        </div>
      </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // 3. IMAGE PREVIEW LOGIC
    const imageInput = document.getElementById("group-image");
    const imagePreview = document.getElementById("group-image-preview");
    const defaultIcon = document.getElementById("default-group-icon");

    if (imageInput) {
        imageInput.addEventListener("change", function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    imagePreview.src = evt.target.result;
                    imagePreview.style.display = "block";
                    if(defaultIcon) defaultIcon.style.display = "none";
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // 4. EVENT BINDINGS
    const searchInput = document.getElementById("members-search");
    if(searchInput) {
        searchInput.addEventListener("input", (e) => searchUsers(e.target.value));
    }

    // Use onclick instead of addEventListener for single elements to avoid stacking listeners if re-initialized
    const closeBtn = document.getElementById("close-group-modal");
    if(closeBtn) closeBtn.onclick = hideCreateGroupModal;

    const cancelBtn = document.getElementById("cancel-group-btn");
    if(cancelBtn) cancelBtn.onclick = hideCreateGroupModal;
    
    const form = document.getElementById("create-group-form");
    if(form) form.onsubmit = handleCreateGroupSubmit;
}

// 5. DATA LOADING
function loadUsers() {
    const membersList = document.getElementById("members-list");
    if (!membersList) return;

    // Use api/users endpoint
    const apiUrl = window.baseUrl + "api/users"; 

    fetch(apiUrl)
    .then(r => r.json())
    .then(users => {
        membersList.innerHTML = "";
        
        if (!Array.isArray(users)) {
             membersList.innerHTML = `<div style="text-align:center; padding:20px;">No contacts found.</div>`;
             return;
        }

        // Filter out current user
        if(window.userId) {
            users = users.filter(u => u.id != window.userId);
        }

        users.forEach(user => {
            const avatarUrl = user.avatar 
                ? `${window.baseUrl}uploads/avatars/${user.avatar}` 
                : `${window.baseUrl}uploads/avatars/default-avatar.png`;

            const userItem = document.createElement("div");
            userItem.className = "member-item";
            userItem.dataset.name = (user.username || user.nickname || '').toLowerCase(); 
            
            userItem.innerHTML = `
                <input type="checkbox" id="user-${user.id}" class="member-select" style="display:none"> 
                <div class="member-checkbox-visual">
                    <div class="checkbox-circle"></div>
                </div>
                <img src="${avatarUrl}" class="mini-avatar" onerror="this.src='${window.baseUrl}uploads/avatars/default-avatar.png'">
                <div class="member-info">
                    <strong class="user-name"></strong>
                </div>`;

            // Safe text insertion
            userItem.querySelector(".user-name").textContent = user.nickname || user.username;

            // Row click handling
            userItem.addEventListener("click", (e) => {
                 const checkbox = userItem.querySelector(".member-select");
                 checkbox.checked = !checkbox.checked;
                 userItem.classList.toggle("selected", checkbox.checked);
                 checkbox.checked ? addMemberToSelection(user) : removeMemberFromSelection(user.id);
            });

            membersList.appendChild(userItem);
        });
    })
    .catch(err => {
        console.error("Load users failed:", err);
        membersList.innerHTML = `<div style="text-align:center; padding:20px; color:red;">Failed to load contacts.</div>`;
    });
}

// --- HELPER FUNCTIONS ---

function searchUsers(query) {
    const q = query.toLowerCase();
    document.querySelectorAll(".member-item").forEach(item => {
        const name = item.dataset.name || "";
        item.style.display = name.includes(q) ? "flex" : "none";
    });
}

function addMemberToSelection(user) {
    const preview = document.getElementById("selected-members-preview");
    if (preview.querySelector(`[data-id="${user.id}"]`)) return;

    const chip = document.createElement("div");
    chip.className = "selected-chip";
    chip.dataset.id = user.id;
    chip.innerHTML = `<span></span> <i class="fas fa-times"></i>`;
    chip.querySelector("span").textContent = user.nickname || user.username;
    
    chip.onclick = (e) => {
        e.stopPropagation();
        removeMemberFromSelection(user.id);
    };
    
    preview.appendChild(chip);
    updateSelectedCount();
}

function removeMemberFromSelection(id) {
    document.querySelector(`#selected-members-preview [data-id="${id}"]`)?.remove();
    
    const cb = document.getElementById(`user-${id}`);
    if (cb) {
        cb.checked = false;
        cb.closest('.member-item').classList.remove("selected");
    }
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = document.querySelectorAll("#selected-members-preview .selected-chip").length;
    document.getElementById("selected-count").innerText = count;
}

function showCreateGroupModal() {
    const modal = document.getElementById("create-group-modal");
    if(modal) {
        modal.classList.remove("hidden");
        loadUsers(); 
    }
}

function hideCreateGroupModal() {
    const modal = document.getElementById("create-group-modal");
    if(modal) modal.classList.add("hidden");
}

function handleCreateGroupSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    const selectedIds = Array.from(document.querySelectorAll("#selected-members-preview .selected-chip"))
                             .map(chip => chip.dataset.id);
    
    selectedIds.forEach(id => formData.append("members[]", id));
    
    createGroup(formData);
}

function createGroup(formData) {
    const btn = document.getElementById("create-group-submit");
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = "Creating...";
    
    fetch(window.baseUrl + "chat/createGroup", {
        method: "POST",
        body: formData,
        headers: { "X-Requested-With": "XMLHttpRequest" }
    })
    .then(r => r.json())
    .then(data => {
        // --- FIX FOR 404/UNDEFINED BUG ---
        // We strictly check if we have a valid ID before redirecting.
        // We check 'id' and 'group_id' to account for common backend variations.
        const newGroupId = data.id || data.group_id;

        if (data.success && newGroupId) {
             window.location.href = `${window.baseUrl}chat/group/${newGroupId}`;
        } else if (data.redirect) {
             window.location.href = data.redirect;
        } else {
             // If success is true but ID is missing, we reload or alert
             if (data.success) {
                 console.warn("Group created, but no ID returned. Reloading.");
                 window.location.reload();
             } else {
                 alert("Error: " + (data.message || "Unknown error"));
                 btn.disabled = false;
                 btn.innerText = originalText;
             }
        }
    })
    .catch((err) => {
        console.error(err);
        alert("Failed to create group. Check console.");
        btn.disabled = false;
        btn.innerText = originalText;
    });
}