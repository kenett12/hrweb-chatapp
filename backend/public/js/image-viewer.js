// Lightweight image viewer - performance optimized version
;(() => {
  // Track if the script has already been initialized to prevent duplicate initialization
  if (window._imageViewerInitialized) {
    console.log("Image viewer already initialized, skipping")
    return
  }

  // Mark as initialized
  window._imageViewerInitialized = true

  // Simple image viewer function
  function openImageViewer(src, filename) {
    // Remove any existing viewer
    const existingViewer = document.querySelector(".image-viewer-modal")
    if (existingViewer) {
      existingViewer.remove()
    }

    // Create minimal modal
    const modal = document.createElement("div")
    modal.className = "image-viewer-modal"
    modal.innerHTML = `
      <div class="image-viewer-content">
        <button class="image-viewer-close">&times;</button>
        <img src="${src}" alt="${filename || "Image"}" class="image-viewer-img">
      </div>
    `

    // Add to DOM
    document.body.appendChild(modal)

    // Add minimal event listeners
    modal.querySelector(".image-viewer-close").addEventListener("click", () => {
      modal.remove()
      document.body.style.overflow = ""
    })

    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.remove()
        document.body.style.overflow = ""
      }
    })

    // Prevent scrolling
    document.body.style.overflow = "hidden"
  }

  // Make function available globally
  window.openImageViewer = openImageViewer

  // Simple one-time setup function - no MutationObserver
  function setupImageViewers() {
    // Use event delegation instead of adding listeners to each image
    document.addEventListener("click", (e) => {
      // Find closest image container if clicked on image or container
      const container = e.target.closest(".image-container")
      if (!container) return

      // Find the image
      const img = container.querySelector("img")
      if (!img || !img.src) return

      // Open viewer
      openImageViewer(img.src, img.getAttribute("data-filename") || "")

      // Prevent default and stop propagation
      e.preventDefault()
      e.stopPropagation()
    })
  }

  // Set up once when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", setupImageViewers, { once: true })
  } else {
    setupImageViewers()
  }

  // Add minimal CSS if needed
  if (!document.querySelector('style[data-id="image-viewer-css"]')) {
    const style = document.createElement("style")
    style.setAttribute("data-id", "image-viewer-css")
    style.textContent = `
      .image-viewer-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
      }
      .image-viewer-content {
        position: relative;
        max-width: 90%;
        max-height: 90%;
      }
      .image-viewer-close {
        position: absolute;
        top: -30px;
        right: -30px;
        background: #fff;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        font-size: 20px;
        cursor: pointer;
      }
      .image-viewer-img {
        max-width: 100%;
        max-height: 90vh;
        display: block;
      }
    `
    document.head.appendChild(style)
  }
})()
