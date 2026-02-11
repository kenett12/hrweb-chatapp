document.addEventListener("DOMContentLoaded", () => {
    const API_BASE_URL = "http://localhost:8080/api" // CodeIgniter 4 API base URL
  
    // Login Form
    const loginForm = document.getElementById("login-form")
    if (loginForm) {
      loginForm.addEventListener("submit", (e) => {
        e.preventDefault()
  
        const username = document.getElementById("login-username").value
        const password = document.getElementById("login-password").value
  
        // Validate inputs
        if (!username || !password) {
          showNotification("Please fill in all fields", "error")
          return
        }
  
        // Send login request
        fetch(`${API_BASE_URL}/auth/login`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            username,
            password,
          }),
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error("Login failed")
            }
            return response.json()
          })
          .then((data) => {
            // Store auth token
            localStorage.setItem("auth_token", data.token)
  
            // Redirect to chat
            window.location.href = "/chat"
          })
          .catch((error) => {
            console.error("Login error:", error)
            showNotification("Invalid username or password", "error")
          })
      })
    }
  
    // Register Form
    const registerForm = document.getElementById("register-form")
    if (registerForm) {
      registerForm.addEventListener("submit", (e) => {
        e.preventDefault()
  
        const username = document.getElementById("register-username").value
        const email = document.getElementById("register-email").value
        const password = document.getElementById("register-password").value
        const confirmPassword = document.getElementById("register-confirm-password").value
  
        // Validate inputs
        if (!username || !email || !password || !confirmPassword) {
          showNotification("Please fill in all fields", "error")
          return
        }
  
        if (password !== confirmPassword) {
          showNotification("Passwords do not match", "error")
          return
        }
  
        // Send register request
        fetch(`${API_BASE_URL}/auth/register`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            username,
            email,
            password,
          }),
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error("Registration failed")
            }
            return response.json()
          })
          .then((data) => {
            showNotification("Registration successful! Please login.", "success")
  
            // Redirect to login
            setTimeout(() => {
              window.location.href = "/login"
            }, 2000)
          })
          .catch((error) => {
            console.error("Registration error:", error)
            showNotification("Registration failed. Please try again.", "error")
          })
      })
    }
  
    // Show notification
    function showNotification(message, type) {
      const notification = document.createElement("div")
      notification.className = `notification ${type}`
      notification.textContent = message
  
      document.body.appendChild(notification)
  
      setTimeout(() => {
        notification.classList.add("show")
      }, 10)
  
      setTimeout(() => {
        notification.classList.remove("show")
        setTimeout(() => {
          document.body.removeChild(notification)
        }, 300)
      }, 3000)
    }
  
    // Check if already logged in
    const token = localStorage.getItem("auth_token")
    if (token && window.location.pathname.includes("login")) {
      // Redirect to chat if already logged in
      window.location.href = "/chat"
    }
  })
  
  