<script>
window.openImageViewer = window.openImageViewer || function(src, filename) {
  console.log("Using fallback image viewer");
  
  const existingViewer = document.querySelector(".image-viewer-modal");
  if (existingViewer) {
    existingViewer.remove();
  }

  const modal = document.createElement("div");
  modal.className = "image-viewer-modal active";
  modal.innerHTML = `
    <div class="image-viewer-content">
      <button class="image-viewer-close">&times;</button>
      <img src="${src}" alt="${filename || "Image"}" class="image-viewer-img">
    </div>
  `;


  document.body.appendChild(modal);

  const closeBtn = modal.querySelector(".image-viewer-close");
  closeBtn.addEventListener("click", () => {
    modal.remove();
    document.body.style.overflow = "";
  });

  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.remove();
      document.body.style.overflow = "";
    }
  });

  document.body.style.overflow = "hidden";
};


window.setupImageViewers = window.setupImageViewers || function() {
  console.log("Using fallback setupImageViewers");
  document.querySelectorAll(".image-container").forEach(container => {
    container.addEventListener("click", function() {
      const img = this.querySelector("img");
      if (img && img.src) {
        const src = img.src;
        const filename = img.getAttribute("data-filename") || "";
        window.openImageViewer(src, filename);
      }
    });
  });
};

document.addEventListener("DOMContentLoaded", function() {
  if (window.setupImageViewers) {
    window.setupImageViewers();
  }
});
</script>
