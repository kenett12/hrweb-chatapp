/**
 * Theme Manager
 * Handles theme switching and persistence
 */

document.addEventListener("DOMContentLoaded", () => {
  // Initialize theme
  initTheme();

  // Add event listener for theme toggle button
  const themeToggleBtn = document.getElementById("theme-toggle");
  if (themeToggleBtn) {
    themeToggleBtn.addEventListener("click", toggleTheme);
  }

  // Detect system theme preference
  detectSystemTheme();

  // Handle storage changes (theme changes from other tabs/windows)
  window.addEventListener("storage", handleStorageChange);
});

function initTheme() {
  let savedTheme = localStorage.getItem("theme");
  if (!savedTheme) {
    savedTheme = detectSystemThemePreference();
  }
  savedTheme = savedTheme || "dark";
  applyTheme(savedTheme);
  updateThemeToggleButton(savedTheme);
}

function detectSystemTheme() {
  const systemTheme = detectSystemThemePreference();
  if (systemTheme) {
    applyTheme(systemTheme);
    updateThemeToggleButton(systemTheme);
  }
}

function detectSystemThemePreference() {
  if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
    return "dark";
  } else if (window.matchMedia && window.matchMedia("(prefers-color-scheme: light)").matches) {
    return "light";
  }
  return null;
}

function handleStorageChange(event) {
  if (event.key === "theme") {
    applyTheme(event.newValue);
    updateThemeToggleButton(event.newValue);
  }
}

/**
 * Apply theme to document
 */
function applyTheme(theme) {
  if (!theme) theme = "dark";
  
  document.body.classList.remove("light-theme", "dark-theme");
  document.body.classList.add(`${theme}-theme`);
  document.body.classList.add("theme-transition");

  localStorage.setItem("theme", theme);

  const metaThemeColor = document.querySelector('meta[name="theme-color"]');
  if (metaThemeColor) {
    metaThemeColor.setAttribute("content", theme === "dark" ? "#2d2c2c" : "#f5f5f5");
  }

  // Global event for other scripts to listen to
  document.dispatchEvent(new CustomEvent("themechange", { detail: { theme } }));

  // Run updates with "guards" to prevent errors if elements don't exist
  updateTicketInterfaceColors(theme);
  updateMessageBubbleTheming(theme);
  updateStatusBadgeColors(theme);

  setTimeout(() => {
    document.body.classList.remove("theme-transition");
  }, 500);
}

function toggleTheme() {
  const currentTheme = localStorage.getItem("theme") || "dark";
  const newTheme = currentTheme === "dark" ? "light" : "dark";
  applyTheme(newTheme);
  updateThemeToggleButton(newTheme);
}

function updateThemeToggleButton(theme) {
  const themeToggleBtn = document.getElementById("theme-toggle");
  const themeText = document.getElementById("theme-text");
  if (!themeToggleBtn) return;

  if (theme === "dark") {
    themeToggleBtn.querySelector('i').className = "fas fa-sun";
    if (themeText) themeText.innerText = "Light Mode";
  } else {
    themeToggleBtn.querySelector('i').className = "fas fa-moon";
    if (themeText) themeText.innerText = "Dark Mode";
  }
}

/**
 * These functions now check if elements exist before styling them
 */
function updateTicketInterfaceColors(theme) {
  const ticketItems = document.querySelectorAll(".ticket-item");
  if (ticketItems.length === 0) return; // Guard
  
  ticketItems.forEach((item) => {
    item.style.backgroundColor = (theme === "dark") ? "#333" : "#fff";
    item.style.color = (theme === "dark") ? "#fff" : "#000";
  });
}

function updateMessageBubbleTheming(theme) {
  const messageBubbles = document.querySelectorAll(".message-bubble");
  if (messageBubbles.length === 0) return; // Guard

  messageBubbles.forEach((bubble) => {
    bubble.style.backgroundColor = (theme === "dark") ? "#444" : "#eee";
    bubble.style.color = (theme === "dark") ? "#fff" : "#000";
  });
}

function updateStatusBadgeColors(theme) {
  const statusBadges = document.querySelectorAll(".status-badge");
  if (statusBadges.length === 0) return; // Guard

  statusBadges.forEach((badge) => {
    badge.style.backgroundColor = (theme === "dark") ? "#555" : "#ddd";
    badge.style.color = (theme === "dark") ? "#fff" : "#000";
  });
}

window.themeManager = {
  applyTheme,
  toggleTheme,
  getCurrentTheme: () => localStorage.getItem("theme") || "dark",
};