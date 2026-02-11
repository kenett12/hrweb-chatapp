// Test script to diagnose message saving issues
document.addEventListener("DOMContentLoaded", () => {
  const testForm = document.getElementById("test-form")
  const testResult = document.getElementById("test-result")
  const testApiBtn = document.getElementById("test-api-btn")
  const testSaveBtn = document.getElementById("test-save-btn")

  // Test the API endpoint
  testApiBtn.addEventListener("click", async () => {
    try {
      testResult.innerHTML = "Testing API endpoint..."

      const response = await fetch("/api/test")
      const data = await response.json()

      testResult.innerHTML = `
                <div class="success">API Test Successful!</div>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `
    } catch (error) {
      testResult.innerHTML = `
                <div class="error">API Test Failed!</div>
                <pre>${error.message}</pre>
            `
    }
  })

  // Test saving a message
  testSaveBtn.addEventListener("click", async () => {
    try {
      testResult.innerHTML = "Testing message save..."

      const response = await fetch("/api/test/save", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          content: "Test message from test.js",
          sender_id: 1,
          type: "text",
          is_group: 0,
        }),
      })

      const responseText = await response.text()
      let data

      try {
        data = JSON.parse(responseText)
      } catch (e) {
        throw new Error(`Invalid JSON response: ${responseText}`)
      }

      if (response.ok) {
        testResult.innerHTML = `
                    <div class="success">Message Save Test Successful!</div>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `
      } else {
        testResult.innerHTML = `
                    <div class="error">Message Save Test Failed!</div>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `
      }
    } catch (error) {
      testResult.innerHTML = `
                <div class="error">Message Save Test Failed!</div>
                <pre>${error.message}</pre>
            `
    }
  })
})
