// Debug script for sidebar
document.addEventListener("DOMContentLoaded", () => {
    console.log("Sidebar debug script loaded")
    
    // Test API endpoints directly
    testApiEndpoints()
  })
  
  // Dummy function for loadChatList, replace with actual implementation
  function loadChatList() {
    console.log("loadChatList function called (dummy implementation)")
    // Add your actual implementation here
  }
  
  function testApiEndpoints() {
    console.log("Testing API endpoints...")
  
    // Test users endpoint
    fetch("/api/users")
      .then((response) => {
        console.log("Users API status:", response.status)
        return response.text()
      })
      .then((text) => {
        try {
          const data = JSON.parse(text)
          console.log("Users API response:", data)
        } catch (e) {
          console.error("Failed to parse users API response:", e)
          console.log("Raw response:", text)
        }
      })
      .catch((error) => {
        console.error("Error testing users API:", error)
      })
  
    // Test groups endpoint
    fetch("/api/getUserGroups")
      .then((response) => {
        console.log("Groups API status:", response.status)
        return response.text()
      })
      .then((text) => {
        try {
          const data = JSON.parse(text)
          console.log("Groups API response:", data)
        } catch (e) {
          console.error("Failed to parse groups API response:", e)
          console.log("Raw response:", text)
        }
      })
      .catch((error) => {
        console.error("Error testing groups API:", error)
      })
  }
  