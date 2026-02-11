document.addEventListener("DOMContentLoaded", () => {
    // We use a self-invoking function to avoid global scope pollution
    (function() {
        const statusBtn = document.getElementById("status-dropdown-btn");
        const statusDropdown = document.getElementById("status-dropdown");
        
        if (!statusBtn || !statusDropdown) return;

        // Determine the Base URL automatically
        const getBaseUrl = () => {
            const path = window.location.pathname;
            const segments = path.split('/');
            // If running in /chat-app/ subfolder
            return window.location.origin + '/' + segments[1];
        };

        const updateStatus = (status) => {
    const userId = document.querySelector(".user-profile").getAttribute("data-user-id");
    
    // We target the root of the domain + project folder + index.php
    const apiUrl = window.location.origin + "/chat-app/index.php/api/updateUserStatus";

    fetch(apiUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: new URLSearchParams({ 
            "status": status, 
            "user_id": userId 
        })
    })
    .then(res => {
        if (!res.ok) throw new Error(`Server returned ${res.status}`);
        return res.json();
    })
    .then(data => {
        console.log("DB Update Success:", data);
    })
    .catch(err => console.error("Update failed:", err));
};

        // Re-bind events to the clones
        const newBtn = statusBtn.cloneNode(true);
        statusBtn.parentNode.replaceChild(newBtn, statusBtn);

        newBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            statusDropdown.classList.toggle("hidden");
        });

        document.querySelectorAll("#status-dropdown li").forEach(li => {
            li.addEventListener("click", function() {
                const status = this.getAttribute("data-status");
                const label = this.textContent.trim();
                
                document.getElementById("status-text").textContent = label;
                const indicator = document.querySelector(".user-profile .status-indicator");
                if (indicator) indicator.className = "status-indicator " + status;
                
                updateStatus(status);
                statusDropdown.classList.add("hidden");
            });
        });
    })();
});