<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?= session()->get('id') ?>">
    <title>Chat App</title>
    <link rel="stylesheet" href="<?= base_url('css/theme.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/notifications.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/image-viewer.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/sidebar.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script>
        // Set global socket URL that will be used by all scripts
        window.socketUrl = "http://localhost:3001";
        
        // Store user IDs for socket connection
        window.userId = <?= session()->get('id') ?>;
    </script>
    <style>
    /* ============================================
       BEAUTIFUL SETTINGS MODAL STYLES
       ============================================ */

    /* Modal Overlay */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .modal:not(.hidden) {
        opacity: 1;
        visibility: visible;
    }

    /* Modal Content Container */
    .modal-content {
        background: linear-gradient(135deg, #1e1e2e 0%, #2a2a3e 100%);
        border-radius: 20px;
        width: 90%;
        max-width: 900px;
        max-height: 85vh;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
                    0 0 0 1px rgba(255, 255, 255, 0.1);
        transform: scale(0.9) translateY(20px);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: flex;
        flex-direction: column;
    }

    .modal:not(.hidden) .modal-content {
        transform: scale(1) translateY(0);
    }

    /* Modal Header */
    .modal-header {
        padding: 24px 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    .modal-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-20px, -20px); }
    }

    .modal-header h3 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #fff;
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .modal-header h3::before {
        content: '⚙️';
        font-size: 28px;
    }

    .modal-header .close-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: #fff;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        font-size: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    }

    .modal-header .close-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }

    /* Modal Body */
    .modal-body {
        flex: 1;
        overflow: hidden;
        display: flex;
        background: #1a1a2e;
    }

    /* Settings Tabs */
    .settings-tabs {
        width: 200px;
        background: linear-gradient(180deg, #16172a 0%, #1a1a2e 100%);
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        padding: 20px 0;
        overflow-y: auto;
    }

    .settings-tab {
        width: 100%;
        padding: 14px 24px;
        background: transparent;
        border: none;
        color: #a0a0b0;
        text-align: left;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .settings-tab::before {
        content: '';
        width: 4px;
        height: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        border-radius: 0 4px 4px 0;
        transition: height 0.3s ease;
    }

    .settings-tab:hover {
        background: rgba(102, 126, 234, 0.1);
        color: #e0e0f0;
    }

    .settings-tab.active {
        color: #fff;
        background: rgba(102, 126, 234, 0.15);
    }

    .settings-tab.active::before {
        height: 70%;
    }

    /* Add icons to tabs */
    .settings-tab[data-tab="profile"]::after { content: '👤'; }
    .settings-tab[data-tab="general"]::after { content: '⚡'; }
    .settings-tab[data-tab="appearance"]::after { content: '🎨'; }
    .settings-tab[data-tab="notifications"]::after { content: '🔔'; }
    .settings-tab[data-tab="privacy"]::after { content: '🔒'; }

    /* Settings Content Area */
    .settings-content {
        flex: 1;
        padding: 30px 40px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(102, 126, 234, 0.5) transparent;
    }

    .settings-content::-webkit-scrollbar {
        width: 8px;
    }

    .settings-content::-webkit-scrollbar-track {
        background: transparent;
    }

    .settings-content::-webkit-scrollbar-thumb {
        background: rgba(102, 126, 234, 0.5);
        border-radius: 4px;
    }

    .settings-content::-webkit-scrollbar-thumb:hover {
        background: rgba(102, 126, 234, 0.7);
    }

    .settings-content h4 {
        margin: 0 0 24px 0;
        font-size: 20px;
        color: #fff;
        font-weight: 600;
    }

    /* Profile Avatar Section */
    .profile-avatar-section, .group-avatar-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 32px;
        padding: 30px;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .profile-avatar-section img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(102, 126, 234, 0.5);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        margin-bottom: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .profile-avatar-section img:hover {
        transform: scale(1.05);
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
    }

    .group-avatar-section img {
        width: 100px;
        height: 100px;
        border-radius: 16px;
        object-fit: cover;
        border: 3px solid rgba(102, 126, 234, 0.5);
        margin-bottom: 16px;
    }

    .avatar-actions {
        display: flex;
        gap: 12px;
    }

    /* Form Groups */
    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #e0e0f0;
        font-weight: 500;
        font-size: 14px;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        color: #fff;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="email"]:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.1);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
        font-family: inherit;
    }

    /* Setting Description */
    .setting-description {
        margin: 8px 0 0 0;
        font-size: 13px;
        color: #a0a0b0;
    }

    /* Switch Toggle */
    .switch-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .switch-label:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 52px;
        height: 28px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.2);
        transition: 0.4s;
        border-radius: 28px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    input:checked + .slider {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    input:checked + .slider:before {
        transform: translateX(24px);
    }

    /* Radio Groups */
    .radio-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .radio-group label {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .radio-group label:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .radio-group input[type="radio"] {
        margin-right: 12px;
        width: 18px;
        height: 18px;
        accent-color: #667eea;
    }

    /* Theme Options */
    .theme-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 16px;
        margin-top: 12px;
    }

    .theme-option {
        text-align: center;
        cursor: pointer;
        padding: 16px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 12px;
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }

    .theme-option:hover {
        background: rgba(255, 255, 255, 0.05);
        transform: translateY(-2px);
    }

    .theme-option.active {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.1);
    }

    .theme-preview {
        width: 100%;
        height: 80px;
        border-radius: 8px;
        margin-bottom: 12px;
        position: relative;
        overflow: hidden;
    }

    .theme-preview.light {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }

    .theme-preview.dark {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    }

    .theme-preview.system {
        background: linear-gradient(135deg, #f5f7fa 0%, #2c3e50 100%);
    }

    .theme-option span {
        color: #e0e0f0;
        font-weight: 500;
        font-size: 14px;
    }

    /* Range Slider */
    .range-slider {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 20px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
    }

    .range-slider input[type="range"] {
        flex: 1;
        height: 6px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
        outline: none;
        -webkit-appearance: none;
    }

    .range-slider input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
        transition: transform 0.2s ease;
    }

    .range-slider input[type="range"]::-webkit-slider-thumb:hover {
        transform: scale(1.2);
    }

    .range-slider input[type="range"]::-moz-range-thumb {
        width: 18px;
        height: 18px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
    }

    .range-slider span {
        color: #667eea;
        font-weight: 600;
        min-width: 50px;
        text-align: right;
    }

    /* Buttons */
    .btn {
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .primary-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .primary-btn:hover {
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        transform: translateY(-2px);
    }

    .secondary-btn {
        background: rgba(255, 255, 255, 0.1);
        color: #e0e0f0;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .secondary-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.3);
    }

    /* Form Actions */
    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Search Input */
    .search-input-wrapper {
        position: relative;
        margin-bottom: 16px;
    }

    .search-input-wrapper .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #a0a0b0;
    }

    .search-input-wrapper input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        color: #fff;
        font-size: 15px;
    }

    .search-input-wrapper input:focus {
        outline: none;
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.1);
    }

    /* Members List */
    .members-list {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.03);
    }

    /* Selected Members */
    .selected-members {
        margin-top: 16px;
        padding: 16px;
        background: rgba(102, 126, 234, 0.1);
        border-radius: 10px;
        border: 1px solid rgba(102, 126, 234, 0.3);
    }

    .selected-members h5 {
        margin: 0 0 12px 0;
        color: #e0e0f0;
        font-size: 14px;
    }

    .selected-members-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    /* Custom Select Styling */
    select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23e0e0f0' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 40px;
    }

    /* Animation for content switching */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .settings-content:not(.hidden) {
        animation: fadeInUp 0.4s ease;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            max-height: 90vh;
        }
        
        .modal-body {
            flex-direction: column;
        }
        
        .settings-tabs {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            overflow-x: auto;
            padding: 0;
        }
        
        .settings-tab {
            white-space: nowrap;
            padding: 12px 20px;
        }
        
        .settings-tab::before {
            display: none;
        }
        
        .settings-content {
            padding: 20px;
        }
    }

    /* Connection status styles */
    .connection-status {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        padding: 10px;
        text-align: center;
        z-index: 9999;
        font-weight: bold;
        transition: all 0.3s ease;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }
   
    .connection-status.connected {
        background-color: #4CAF50;
        color: white;
    }
   
    .connection-status.connecting {
        background-color: #FFC107;
        color: black;
    }
   
    .connection-status.disconnected,
    .connection-status.error {
        background-color: #F44336;
        color: white;
    }
   
    .connection-status .retry-btn {
        margin-left: 10px;
        padding: 5px 10px;
        border-radius: 4px;
        border: none;
        background-color: white;
        color: #333;
        cursor: pointer;
        font-weight: bold;
    }
    
    .connection-status .retry-btn:hover {
        background-color: #f1f1f1;
    }

    /* Fix for empty state */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        padding: 2rem;
        text-align: center;
    }

    .empty-state-icon {
        font-size: 4rem;
        color: #ccc;
        margin-bottom: 1rem;
    }

    .empty-state h2 {
        margin-bottom: 0.5rem;
        color: #333;
    }

    .empty-state p {
        margin-bottom: 1.5rem;
        color: #666;
    }

    /* App container */
    #app-container {
        display: flex;
        height: 100vh;
        width: 100%;
        overflow: hidden;
    }

    /* Main content */
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100vh;
        overflow: hidden;
        position: relative;
    }

    /* Chat interface */
    .chat-interface {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .chat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #e0e0e0;
        background-color: #f8f9fa;
    }

    .chat-header-info {
        display: flex;
        align-items: center;
    }

    .chat-header-text {
        margin-left: 1rem;
    }

    .chat-header-actions {
        display: flex;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    .message-composer {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-top: 1px solid #e0e0e0;
        background-color: #f8f9fa;
    }

    .message-input-container {
        flex: 1;
        margin: 0 1rem;
    }

    #message-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }

    .hidden {
        display: none !important;
    }

    /* User avatar */
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Status indicator */
    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        position: absolute;
        bottom: 0;
        right: 0;
        border: 2px solid #fff;
    }

    .status-indicator.online {
        background-color: #4CAF50;
    }

    .status-indicator.offline {
        background-color: #9e9e9e;
    }

    .status-indicator.away {
        background-color: #FFC107;
    }

    .status-indicator.busy {
        background-color: #F44336;
    }

    /* Avatar container */
    .avatar-container {
        position: relative;
    }

    /* Icon buttons */
    .icon-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.2rem;
        color: #6c757d;
        padding: 0.5rem;
        border-radius: 50%;
        transition: background-color 0.3s;
    }

    .icon-btn:hover {
        background-color: rgba(108, 117, 125, 0.1);
    }

    .send-btn {
        color: #007bff;
    }

    /* Typing indicator */
    .typing-indicator {
        display: flex;
        align-items: center;
        padding: 0.5rem 1rem;
        color: #6c757d;
    }

    .typing-dots {
        display: flex;
        margin-right: 0.5rem;
    }

    .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #6c757d;
        margin-right: 4px;
        animation: typing-dot 1.4s infinite ease-in-out;
    }

    .dot:nth-child(1) {
        animation-delay: 0s;
    }

    .dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing-dot {
        0%, 60%, 100% {
            transform: translateY(0);
        }
        30% {
            transform: translateY(-5px);
        }
    }
    </style>
</head>
<body class="dark-theme">
    <div id="app-container">
        <!-- Connection Status Indicator -->
        <div id="connection-status" class="connection-status connecting">
            Connecting to chat server...
            <button id="connection-retry" class="retry-btn">
                Retry
            </button>
        </div>   
        <!-- Sidebar -->
        <?= $this->include('partials/sidebar') ?>
       
        <!-- Main Content -->
        <div class="main-content">
            <!-- Empty State -->
            <div class="empty-state" id="empty-state">
                <div class="empty-state-icon">
                    <div class="app-logo">
                    <img src="<?= base_url('uploads/logo/cropped.png') ?>" alt="Logo" width="300">
                    <p>Select a conversation to start chatting</p>
                </div>
            </div>
            
            <!-- Chat Interface (Hidden initially) -->
            <div class="chat-interface hidden" id="chat-interface">
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="avatar-container">
                            <img id="chat-header-avatar" src="<?= base_url('public/uploads/avatars/default-avatar.png') ?>" alt="Contact" class="user-avatar">
                            <span id="chat-header-status" class="status-indicator offline"></span>
                        </div>
                        <div class="chat-header-text">
                            <h2 id="chat-header-title">Contact Name</h2>
                            <p id="chat-header-subtitle" class="text-muted">Offline</p>
                        </div>
                    </div>
                    <div class="chat-header-actions">
                        <button id="audio-call-btn" class="icon-btn" title="Audio Call"><i class="fas fa-phone-alt"></i></button>
                        <button id="video-call-btn" class="icon-btn" title="Video Call"><i class="fas fa-video"></i></button>
                        <button id="add-people-btn" class="icon-btn" title="Add People"><i class="fas fa-user-plus"></i></button>
                        <button id="chat-info-btn" class="icon-btn" title="Chat Info"><i class="fas fa-info-circle"></i></button>
                    </div>
                </div>
                
                <div class="chat-messages" id="chat-messages">
                    <!-- Messages will be loaded here -->
                </div>
                
                <div class="typing-indicator hidden" id="typing-indicator">
                    <div class="typing-dots">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                    <span id="typing-text">Someone is typing...</span>
                </div>
                
                <div class="message-composer">
                    <div class="composer-actions">
                        <button id="emoji-btn" class="icon-btn" title="Emoji"><i class="far fa-smile"></i></button>
                        <button id="gif-btn" class="icon-btn" title="GIF"><i class="fas fa-film"></i></button>
                        <button id="attach-btn" class="icon-btn" title="Attach File"><i class="fas fa-paperclip"></i></button>
                    </div>
                    
                    <div class="message-input-container">
                        <input type="text" id="message-input" placeholder="Type a message..." autocomplete="off">
                        <input type="file" id="file-input" class="hidden">
                    </div>
                    
                    <button type="submit" id="send-message-btn" class="icon-btn send-btn" title="Send Message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status Dropdown Menu -->
    <div class="status-dropdown hidden" id="status-dropdown">
        <ul>
            <li data-status="online"><span class="status-dot online"></span> Available</li>
            <li data-status="away"><span class="status-dot away"></span> Away</li>
            <li data-status="busy"><span class="status-dot busy"></span> Busy</li>
            <li data-status="offline"><span class="status-dot offline"></span> Appear Offline</li>
            <li class="divider"></li>
            <li data-status="custom"><i class="fas fa-pen"></i> Set a custom status...</li>
        </ul>
    </div>

    <!-- Include all the modals and other components -->
    <?= $this->include('partials/image-viewer') ?>
    
    <!-- Beautiful User Settings Modal -->
    <div class="modal hidden" id="settings-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Settings</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="settings-tabs">
                    <button class="settings-tab active" data-tab="profile">Profile</button>
                    <button class="settings-tab" data-tab="general">General</button>
                    <button class="settings-tab" data-tab="appearance">Appearance</button>
                    <button class="settings-tab" data-tab="notifications">Notifications</button>
                    <button class="settings-tab" data-tab="privacy">Privacy</button>
                </div>
                
                <div class="settings-content" id="profile-settings">
                    <form id="profile-form" action="<?= base_url('chat/profile/update') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="profile-avatar-section">
                            <img src="<?= base_url('public/uploads/avatars/' . (session()->get('avatar') ?? 'default-avatar.png')) ?>" alt="Avatar Preview" id="avatar-preview">
                            <div class="avatar-actions">
                                <label for="avatar" class="btn secondary-btn">
                                    <i class="fas fa-camera"></i> Change Picture
                                </label>
                                <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="nickname">Display Name</label>
                            <input type="text" id="nickname" name="nickname" value="<?= session()->get('nickname') ?? session()->get('username') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= session()->get('email') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" rows="3" placeholder="Tell us about yourself..."><?= session()->get('bio') ?? '' ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn secondary-btn close-modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn primary-btn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="settings-content hidden" id="general-settings">
                    <h4>General Settings</h4>
                    
                    <div class="form-group">
                        <label>Language</label>
                        <select id="language-setting">
                            <option value="en">English</option>
                            <option value="es">Español</option>
                            <option value="fr">Français</option>
                            <option value="de">Deutsch</option>
                            <option value="ja">日本語</option>
                            <option value="zh">中文</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Time Format</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="time-format" value="12h" checked> 12-hour (1:30 PM)
                            </label>
                            <label>
                                <input type="radio" name="time-format" value="24h"> 24-hour (13:30)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="switch-label">
                            <span>Auto-start</span>
                            <label class="switch">
                                <input type="checkbox" id="autostart-setting">
                                <span class="slider round"></span>
                            </label>
                        </label>
                        <p class="setting-description">Start app automatically when you log in</p>
                    </div>
                </div>
                
                <div class="settings-content hidden" id="appearance-settings">
                    <h4>Appearance Settings</h4>
                    
                    <div class="form-group">
                        <label>Theme</label>
                        <div class="theme-options">
                            <div class="theme-option" data-theme="light">
                                <div class="theme-preview light"></div>
                                <span>Light</span>
                            </div>
                            <div class="theme-option active" data-theme="dark">
                                <div class="theme-preview dark"></div>
                                <span>Dark</span>
                            </div>
                            <div class="theme-option" data-theme="system">
                                <div class="theme-preview system"></div>
                                <span>System</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Font Size</label>
                        <div class="range-slider">
                            <input type="range" min="12" max="20" value="14" id="font-size-slider">
                            <span id="font-size-value">14px</span>
                        </div>
                    </div>
                </div>
                
                <div class="settings-content hidden" id="notifications-settings">
                    <h4>Notification Settings</h4>
                    
                    <div class="form-group">
                        <label class="switch-label">
                            <span>Message Notifications</span>
                            <label class="switch">
                                <input type="checkbox" id="message-notifications" checked>
                                <span class="slider round"></span>
                            </label>
                        </label>
                        <p class="setting-description">Get notified when you receive new messages</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="switch-label">
                            <span>Call Notifications</span>
                            <label class="switch">
                                <input type="checkbox" id="call-notifications" checked>
                                <span class="slider round"></span>
                            </label>
                        </label>
                        <p class="setting-description">Get notified for incoming calls</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="switch-label">
                            <span>Sound</span>
                            <label class="switch">
                                <input type="checkbox" id="notification-sound" checked>
                                <span class="slider round"></span>
                            </label>
                        </label>
                        <p class="setting-description">Play sound for notifications</p>
                    </div>
                </div>
                
                <div class="settings-content hidden" id="privacy-settings">
                    <h4>Privacy Settings</h4>
                    
                    <div class="form-group">
                        <label class="switch-label">
                            <span>Read Receipts</span>
                            <label class="switch">
                                <input type="checkbox" id="read-receipts" checked>
                                <span class="slider round"></span>
                            </label>
                        </label>
                        <p class="setting-description">Let others know when you've read their messages</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="switch-label">
                            <span>Last Seen</span>
                            <label class="switch">
                                <input type="checkbox" id="last-seen" checked>
                                <span class="slider round"></span>
                            </label>
                        </label>
                        <p class="setting-description">Show others when you were last online</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Who can contact me</label>
                        <select id="contact-permission">
                            <option value="everyone">Everyone</option>
                            <option value="contacts">Contacts only</option>
                            <option value="nobody">Nobody</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Group Modal -->
    <div class="modal hidden" id="create-group-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Group</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="create-group-form" action="<?= base_url('chat/groups/create') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="group-avatar-section">
                        <img src="<?= base_url('uploads/groups/default-group.png') ?>" alt="Group Image Preview" id="group-image-preview">
                        <div class="avatar-actions">
                            <label for="group-image" class="btn secondary-btn">
                                <i class="fas fa-upload"></i> Upload Image
                            </label>
                            <input type="file" id="group-image" name="image" accept="image/*" class="hidden">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="group-name">Group Name</label>
                        <input type="text" id="group-name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="group-description">Description</label>
                        <textarea id="group-description" name="description" rows="3" placeholder="What's this group about?"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Add Members</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="member-search" placeholder="Search contacts...">
                        </div>
                        <div class="members-list" id="available-members">
                            <!-- Available users will be loaded here -->
                        </div>
                        <div class="selected-members" id="selected-members">
                            <h5>Selected: <span id="selected-count">0</span></h5>
                            <div class="selected-members-list" id="selected-members-list"></div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn secondary-btn close-modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn primary-btn">
                            <i class="fas fa-users"></i> Create Group
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Call Modal -->
    <div class="call-modal hidden" id="call-modal">
        <div class="call-header">
            <div class="call-info">
                <h3 id="call-status">Calling...</h3>
                <p id="call-duration">00:00</p>
            </div>
            <div class="call-actions">
                <button id="toggle-mic-btn" class="call-btn active" title="Mute"><i class="fas fa-microphone"></i></button>
                <button id="toggle-video-btn" class="call-btn active" title="Turn off video"><i class="fas fa-video"></i></button>
                <button id="toggle-speaker-btn" class="call-btn" title="Speaker"><i class="fas fa-volume-up"></i></button>
                <button id="end-call-btn" class="call-btn end-call" title="End call"><i class="fas fa-phone-slash"></i></button>
            </div>
        </div>
        <div class="call-content">
            <div class="remote-video-container">
                <video id="remote-video" autoplay playsinline></video>
                <div class="remote-user-info">
                    <div class="avatar-container large">
                        <img id="remote-user-avatar" src="<?= base_url('uploads/default-avatar.png') ?>" alt="Remote User">
                    </div>
                    <h3 id="remote-user-name">User Name</h3>
                </div>
            </div>
            <div class="local-video-container">
                <video id="local-video" autoplay playsinline muted></video>
            </div>
        </div>
    </div>

    <!-- Emoji Picker -->
    <div class="emoji-picker hidden" id="emoji-picker">
        <div class="emoji-categories">
            <button class="emoji-category active" data-category="recent"><i class="far fa-clock"></i></button>
            <button class="emoji-category" data-category="smileys"><i class="far fa-smile"></i></button>
            <button class="emoji-category" data-category="people"><i class="far fa-user"></i></button>
            <button class="emoji-category" data-category="animals"><i class="fas fa-paw"></i></button>
            <button class="emoji-category" data-category="food"><i class="fas fa-pizza-slice"></i></button>
            <button class="emoji-category" data-category="travel"><i class="fas fa-plane"></i></button>
            <button class="emoji-category" data-category="activities"><i class="fas fa-basketball-ball"></i></button>
            <button class="emoji-category" data-category="objects"><i class="fas fa-lightbulb"></i></button>
            <button class="emoji-category" data-category="symbols"><i class="fas fa-heart"></i></button>
            <button class="emoji-category" data-category="flags"><i class="fas fa-flag"></i></button>
        </div>
        <div class="emoji-search">
            <input type="text" placeholder="Search emojis..." id="emoji-search">
        </div>
        <div class="emoji-grid" id="emoji-grid">
            <!-- Emojis will be loaded here -->
        </div>
    </div>

    <!-- Notification Toast -->
    <div class="notification-toast hidden" id="notification-toast">
        <div class="notification-icon">
            <i class="fas fa-bell"></i>
        </div>
        <div class="notification-content">
            <h4 id="notification-title">Notification Title</h4>
            <p id="notification-message">Notification message goes here</p>
        </div>
        <button class="notification-close">&times;</button>
    </div>
    
    <div id="notifications-content" class="tab-content" style="display: none;">
        <div class="notifications-header">
            <h3>Notifications</h3>
            <button id="clear-notifications-btn" class="btn btn-sm btn-outline-danger">Clear All</button>
        </div>
        <div id="notifications-list" class="notifications-list">
            <!-- Notifications will be loaded here -->
        </div>
        <!-- Create Ticket Modal -->
        <div id="create-ticket-modal" class="hidden">
            <div id="create-ticket-backdrop" class="modal-backdrop"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Create Support Ticket</h3>
                    <button type="button" id="close-create-ticket-modal" class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="create-ticket-form" class="modal-body">
                    <div class="form-group">
                        <label for="ticket-subject">
                            Subject <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="ticket-subject" 
                            name="subject" 
                            placeholder="Brief description of your issue"
                            required
                            maxlength="200"
                        >
                        <div class="error-message" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ticket-category">Category</label>
                        <select id="ticket-category" name="category" required>
                            <option value="">Select a category</option>
                            <option value="technical">Technical Issue</option>
                            <option value="billing">Billing & Account</option>
                            <option value="general">General Support</option>
                            <option value="feature">Feature Request</option>
                            <option value="bug">Bug Report</option>
                            <option value="other">Other</option>
                        </select>
                        <div class="error-message" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ticket-priority">Priority</label>
                        <select id="ticket-priority" name="priority" required>
                            <option value="">Select priority level</option>
                            <option value="low">Low - General inquiry</option>
                            <option value="medium">Medium - Standard issue</option>
                            <option value="high">High - Urgent issue</option>
                            <option value="urgent">Urgent - Critical problem</option>
                        </select>
                        <div class="error-message" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ticket-description">
                            Description <span class="required">*</span>
                        </label>
                        <textarea 
                            id="ticket-description" 
                            name="description" 
                            placeholder="Please provide detailed information about your issue, including steps to reproduce if applicable..."
                            required
                            maxlength="2000"
                        ></textarea>
                        <div class="error-message" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ticket-attachments">Attachments (optional)</label>
                        <div class="file-upload-container">
                            <div class="file-upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="file-upload-text">
                                <strong>Click to upload</strong> or drag and drop files here
                            </div>
                            <div class="file-upload-hint">
                                Max 5 files, 10MB each (PNG, JPG, PDF, DOC, TXT)
                            </div>
                            <input 
                                type="file" 
                                id="ticket-attachments" 
                                name="attachments[]" 
                                multiple 
                                accept=".png,.jpg,.jpeg,.pdf,.doc,.docx,.txt"
                            >
                        </div>
                        <div class="selected-files" id="selected-files"></div>
                    </div>
                </form>

                <div class="modal-footer">
                    <button type="button" id="cancel-create-ticket" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" form="create-ticket-form" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Create Ticket
                    </button>
                </div>
            </div>
        </div>

    </div>
    
<!-- Socket.IO library will be loaded dynamically by socket-manager.js if needed -->
<script src="<?= base_url('js/socket-manager.js') ?>"></script>
<script>
  // Global image viewer functions
  window.openImageViewer = function(src) {
    const modal = document.getElementById('image-viewer-modal');
    const img = document.getElementById('image-viewer-img');
    
    window.currentImageSrc = src;
    img.src = src;
    modal.classList.add('active');
    
    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', handleImageViewerKeydown);
  };
  
  window.closeImageViewer = function() {
    const modal = document.getElementById('image-viewer-modal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleImageViewerKeydown);
  };
  
  function handleImageViewerKeydown(e) {
    if (e.key === 'Escape') {
      closeImageViewer();
    }
  }
  
  window.downloadViewerImage = function() {
    if (!window.currentImageSrc) return;
    
    const link = document.createElement('a');
    link.href = window.currentImageSrc;
    link.download = window.currentImageSrc.split('/').pop() || 'image.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };
  
  document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('image-viewer-modal');
    if (modal) {
      modal.addEventListener('click', function(e) {
        if (e.target === this) {
          closeImageViewer();
        }
      });
    }
  });
</script>

<script>
/* ============================================
   SETTINGS MODAL ENHANCED INTERACTIONS
   ============================================ */
(function() {
    'use strict';
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initSettingsModal();
    });
    
    function initSettingsModal() {
        const modal = document.getElementById('settings-modal');
        if (!modal) return;
        
        // Tab switching
        initTabSwitching();
        
        // Avatar preview
        initAvatarPreview();
        
        // Group image preview
        initGroupImagePreview();
        
        // Theme selection
        initThemeSelection();
        
        // Font size slider
        initFontSizeSlider();
        
        // Close modal handlers
        initModalClose();
        
        // Form submission
        initFormSubmission();
        
        // Settings sync
        initSettingsSync();
    }
    
    // Tab Switching with Animation
    function initTabSwitching() {
        const tabs = document.querySelectorAll('.settings-tab');
        const contents = document.querySelectorAll('.settings-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all content sections
                contents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show target content with animation
                const targetContent = document.getElementById(targetTab + '-settings');
                if (targetContent) {
                    setTimeout(() => {
                        targetContent.classList.remove('hidden');
                    }, 50);
                }
                
                // Save last active tab
                localStorage.setItem('lastActiveSettingsTab', targetTab);
            });
        });
        
        // Restore last active tab
        const lastActiveTab = localStorage.getItem('lastActiveSettingsTab');
        if (lastActiveTab) {
            const tab = document.querySelector('[data-tab="' + lastActiveTab + '"]');
            if (tab) {
                tab.click();
            }
        }
    }
    
    // Avatar Preview
    function initAvatarPreview() {
        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('avatar-preview');
        
        if (avatarInput && avatarPreview) {
            avatarInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        avatarPreview.src = e.target.result;
                        avatarPreview.style.animation = 'fadeInScale 0.5s ease';
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
        }
    }
    
    // Group Image Preview
    function initGroupImagePreview() {
        const groupImageInput = document.getElementById('group-image');
        const groupImagePreview = document.getElementById('group-image-preview');
        
        if (groupImageInput && groupImagePreview) {
            groupImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        groupImagePreview.src = e.target.result;
                        groupImagePreview.style.animation = 'fadeInScale 0.5s ease';
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
        }
    }
    
    // Theme Selection
    function initThemeSelection() {
        const themeOptions = document.querySelectorAll('.theme-option');
        
        themeOptions.forEach(option => {
            option.addEventListener('click', function() {
                const theme = this.getAttribute('data-theme');
                
                // Remove active class from all options
                themeOptions.forEach(opt => opt.classList.remove('active'));
                
                // Add active class to selected option
                this.classList.add('active');
                
                // Apply theme
                applyTheme(theme);
                
                // Save preference
                localStorage.setItem('theme', theme);
                
                // Show confirmation
                showNotification('Theme changed successfully', 'success');
            });
        });
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            const themeOption = document.querySelector('[data-theme="' + savedTheme + '"]');
            if (themeOption) {
                themeOption.classList.add('active');
            }
        }
    }
    
    function applyTheme(theme) {
        const body = document.body;
        
        // Remove existing theme classes
        body.classList.remove('light-theme', 'dark-theme');
        
        if (theme === 'system') {
            // Detect system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            body.classList.add(prefersDark ? 'dark-theme' : 'light-theme');
        } else {
            body.classList.add(theme + '-theme');
        }
    }
    
    // Font Size Slider
    function initFontSizeSlider() {
        const slider = document.getElementById('font-size-slider');
        const value = document.getElementById('font-size-value');
        
        if (slider && value) {
            slider.addEventListener('input', function() {
                const fontSize = this.value;
                value.textContent = fontSize + 'px';
                
                // Apply font size to body
                document.body.style.fontSize = fontSize + 'px';
                
                // Save preference
                localStorage.setItem('fontSize', fontSize);
            });
            
            // Load saved font size
            const savedFontSize = localStorage.getItem('fontSize');
            if (savedFontSize) {
                slider.value = savedFontSize;
                value.textContent = savedFontSize + 'px';
                document.body.style.fontSize = savedFontSize + 'px';
            }
        }
    }
    
    // Modal Close Handlers
    function initModalClose() {
        const modal = document.getElementById('settings-modal');
        const closeBtns = modal.querySelectorAll('.close-btn, .close-modal');
        
        closeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                closeModal();
            });
        });
        
        // Close on outside click
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    }
    
    function closeModal() {
        const modal = document.getElementById('settings-modal');
        modal.classList.add('hidden');
    }
    
    // Form Submission
    function initFormSubmission() {
        const profileForm = document.getElementById('profile-form');
        
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Profile updated successfully', 'success');
                        
                        // Update session data if needed
                        if (data.user) {
                            updateSessionData(data.user);
                        }
                    } else {
                        showNotification(data.message || 'Failed to update profile', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        }
    }
    
    // Settings Sync
    function initSettingsSync() {
        // Auto-start setting
        const autostartCheckbox = document.getElementById('autostart-setting');
        if (autostartCheckbox) {
            autostartCheckbox.checked = localStorage.getItem('autostart') === 'true';
            autostartCheckbox.addEventListener('change', function() {
                localStorage.setItem('autostart', this.checked);
                showNotification('Auto-start ' + (this.checked ? 'enabled' : 'disabled'), 'success');
            });
        }
        
        // Notification settings
        const notificationCheckboxes = [
            'message-notifications',
            'call-notifications',
            'notification-sound'
        ];
        
        notificationCheckboxes.forEach(function(id) {
            const checkbox = document.getElementById(id);
            if (checkbox) {
                checkbox.checked = localStorage.getItem(id) !== 'false';
                checkbox.addEventListener('change', function() {
                    localStorage.setItem(id, this.checked);
                    showNotification('Notification settings updated', 'success');
                });
            }
        });
        
        // Privacy settings
        const privacyCheckboxes = ['read-receipts', 'last-seen'];
        privacyCheckboxes.forEach(function(id) {
            const checkbox = document.getElementById(id);
            if (checkbox) {
                checkbox.checked = localStorage.getItem(id) !== 'false';
                checkbox.addEventListener('change', function() {
                    localStorage.setItem(id, this.checked);
                    showNotification('Privacy settings updated', 'success');
                });
            }
        });
        
        // Language setting
        const languageSelect = document.getElementById('language-setting');
        if (languageSelect) {
            languageSelect.value = localStorage.getItem('language') || 'en';
            languageSelect.addEventListener('change', function() {
                localStorage.setItem('language', this.value);
                showNotification('Language preference saved', 'success');
            });
        }
        
        // Time format
        const timeFormatRadios = document.querySelectorAll('input[name="time-format"]');
        const savedTimeFormat = localStorage.getItem('timeFormat') || '12h';
        
        timeFormatRadios.forEach(function(radio) {
            if (radio.value === savedTimeFormat) {
                radio.checked = true;
            }
            
            radio.addEventListener('change', function() {
                localStorage.setItem('timeFormat', this.value);
                showNotification('Time format updated', 'success');
            });
        });
        
        // Contact permission
        const contactSelect = document.getElementById('contact-permission');
        if (contactSelect) {
            contactSelect.value = localStorage.getItem('contactPermission') || 'everyone';
            contactSelect.addEventListener('change', function() {
                localStorage.setItem('contactPermission', this.value);
                showNotification('Contact permissions updated', 'success');
            });
        }
    }
    
    // Helper function to show notifications
    function showNotification(message, type) {
        const toast = document.getElementById('notification-toast');
        if (!toast) return;
        
        const title = toast.querySelector('#notification-title');
        const messageEl = toast.querySelector('#notification-message');
        
        title.textContent = type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info';
        messageEl.textContent = message;
        
        toast.classList.remove('hidden');
        
        setTimeout(function() {
            toast.classList.add('hidden');
        }, 3000);
    }
    
    // Helper function to update session data
    function updateSessionData(user) {
        // Update UI elements with new user data
        const elements = {
            nickname: document.querySelectorAll('[data-user-nickname]'),
            email: document.querySelectorAll('[data-user-email]'),
            avatar: document.querySelectorAll('[data-user-avatar]')
        };
        
        if (user.nickname) {
            elements.nickname.forEach(function(el) {
                el.textContent = user.nickname;
            });
        }
        
        if (user.email) {
            elements.email.forEach(function(el) {
                el.textContent = user.email;
            });
        }
        
        if (user.avatar) {
            elements.avatar.forEach(function(el) {
                el.src = user.avatar;
            });
        }
    }
    
})();
</script>

<script src="<?= base_url('js/sidebar-init.js') ?>"></script>
<script src="<?= base_url('js/notifications.js') ?>"></script>
<script src="<?= base_url('js/chat-index.js') ?>"></script>
<script src="<?= base_url('js/socket-debug.js') ?>"></script>
<script src="<?= base_url('js/status-dropdown-fix.js') ?>"></script>
</body>
</html>