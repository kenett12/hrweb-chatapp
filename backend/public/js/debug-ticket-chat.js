// Add this file to help debug the ticket chat transition issue
document.addEventListener("DOMContentLoaded", () => {
  console.log("Debug script loaded for ticket chat")

  // Force transition to chat interface after 2 seconds
  setTimeout(() => {
    const waitingScreen = document.getElementById("waitingScreen")
    const chatMessages = document.getElementById("chatMessages")
    const chatInput = document.getElementById("chatInput")

    if (waitingScreen) {
      console.log("Forcing waiting screen to hide")
      waitingScreen.style.display = "none"
    }

    if (chatMessages) {
      console.log("Forcing chat messages to show")
      chatMessages.style.display = "block"
      chatMessages.classList.add("active")
    }

    if (chatInput) {
      console.log("Forcing chat input to show")
      chatInput.style.display = "block"
      chatInput.classList.add("active")
    }

    console.log("Transition forced by debug script")
  }, 2000)
})
