<div id="image-viewer-modal" class="image-viewer-modal">
  <button class="image-viewer-close" onclick="closeImageViewer()" aria-label="Close viewer">
    <i class="fas fa-times"></i>
  </button>

  <div class="image-viewer-content">
    <div class="image-viewer-toolbar">
      <button class="image-control-btn" onclick="zoomOutViewer()" title="Zoom Out">
        <i class="fas fa-search-minus"></i>
      </button>
      <span id="zoom-level-display">100%</span>
      <button class="image-control-btn" onclick="zoomInViewer()" title="Zoom In">
        <i class="fas fa-search-plus"></i>
      </button>
      <div class="toolbar-divider"></div>
      <button class="image-control-btn" onclick="downloadViewerImage()" title="Download">
        <i class="fas fa-download"></i>
      </button>
    </div>

    <div class="image-stage">
      <img id="image-viewer-img" class="image-viewer-img" src="" alt="Preview" onload="imageLoaded()" onerror="imageError()">
      
      <div id="image-loading" class="image-status-overlay">
        <i class="fas fa-circle-notch fa-spin"></i>
        <span>Loading...</span>
      </div>

      <div id="image-error" class="image-status-overlay hidden">
        <i class="fas fa-exclamation-circle"></i>
        <span>Unable to load image</span>
      </div>
    </div>

    <div class="image-viewer-footer">
      <span id="image-viewer-filename">image.jpg</span>
    </div>
  </div>
</div>

<style>
/* Base Modal - Solid Messenger Dark */
.image-viewer-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: #000000;
    z-index: 9999;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.2s ease-in-out;
}

.image-viewer-modal.active {
    display: flex;
    opacity: 1;
}

.image-viewer-content {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* The "Stage" where the image sits */
.image-stage {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
    width: 100%;
    overflow: hidden;
    padding: 60px 20px;
}

.image-viewer-img {
    max-width: 95%;
    max-height: 85vh;
    object-fit: contain;
    transition: transform 0.25s cubic-bezier(0.15, 0, 0.2, 1);
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    user-select: none;
}

/* Floating Messenger Toolbar */
.image-viewer-toolbar {
    position: absolute;
    top: 25px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 8px;
    background: #242526; /* Messenger dark mode surface color */
    padding: 6px 16px;
    border-radius: 50px;
    border: 1px solid #3e4042;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

.image-control-btn {
    background: transparent;
    border: none;
    color: #e4e6eb;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
    font-size: 15px;
}

.image-control-btn:hover {
    background: #3a3b3c;
}

#zoom-level-display {
    color: #e4e6eb;
    font-size: 13px;
    font-weight: 500;
    min-width: 45px;
    text-align: center;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
}

.toolbar-divider {
    width: 1px;
    height: 20px;
    background: #3e4042;
    margin: 0 4px;
}

/* Navigation & Interaction Buttons */
.image-viewer-close {
    position: fixed;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #242526;
    border: 1px solid #3e4042;
    color: #e4e6eb;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1001;
    transition: all 0.2s;
}

.image-viewer-close:hover {
    background: #3a3b3c;
    color: #ffffff;
}

.image-viewer-footer {
    position: absolute;
    bottom: 25px;
    color: #b0b3b8;
    font-size: 14px;
    background: rgba(36, 37, 38, 0.8);
    padding: 6px 18px;
    border-radius: 20px;
    max-width: 80%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Status Overlays */
.image-status-overlay {
    position: absolute;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: #e4e6eb;
}

.image-status-overlay i {
    font-size: 24px;
    color: #0084ff; /* Messenger blue */
}

.hidden { display: none; }

/* Mobile Optimizations */
@media (max-width: 768px) {
    .image-viewer-toolbar {
        top: auto;
        bottom: 80px;
        background: #242526;
    }
    .image-viewer-close {
        top: 15px;
        right: 15px;
    }
}
</style>