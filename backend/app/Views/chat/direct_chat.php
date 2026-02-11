<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?= session()->get('id') ?>">
    <meta name="user-status" content="<?= session()->get('status') ?? 'online' ?>">
    <meta name="theme-color" content="#0f172a">
    <title>Chat with <?= $otherUser['username'] ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= base_url('css/theme.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/styles.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/seen-indicator.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/notifications.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/image-viewer.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/file-attachments.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/sidebar.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/performance-optimizations.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/call-modal.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/group-modal.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* Palette */
            --bg-body: #0f172a;
            --bg-sidebar: #1e293b;
            --bg-chat: #0f172a;
            --bg-card: #1e293b;
            --bg-input: #334155;
            
            /* Accents */
            --primary: #6366f1; /* Indigo */
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary: #64748b;
            
            /* Text */
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            
            /* Borders & Separators */
            --border-color: #334155;
            
            /* Status */
            --online: #22c55e;
            --busy: #ef4444;
            --away: #eab308;
            --offline: #64748b;

            /* Spacing */
            --header-height: 70px;
        }

        /* --- [FIX] Call Button Z-Index & Clickability --- */
        .call-controls-floating {
            z-index: 100002 !important; 
            position: relative; 
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        .call-modal-content {
            z-index: 100001 !important;
        }
        .call-glass-overlay {
            z-index: 100000 !important;
            pointer-events: none; /* Crucial: lets clicks pass through */
        }
        .control-btn {
            display: flex !important;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        /* ----------------------------------------------- */

        * {
            box-sizing: border-box;
            outline: none;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100vh;
        }

        /* --- Layout --- */
        #app-container {
            display: flex;
            height: 100vh;
            width: 100vw;
            background: var(--bg-body);
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-chat);
            position: relative;
            height: 100%;
        }

        .chat-interface {
            display: flex;
            flex-direction: column;
            height: 100%;
            background-image: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.05) 0%, transparent 50%);
        }

        /* --- Header --- */
        .chat-header {
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            z-index: 10;
        }

        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chat-header .avatar-container {
            position: relative;
            width: 42px;
            height: 42px;
        }

        .chat-header .user-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.1);
        }

        .chat-header-text h2 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-main);
        }

        .status-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid var(--bg-body);
            box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
        }
        .status-indicator.online { background-color: var(--online); box-shadow: 0 0 8px rgba(34, 197, 94, 0.4); }
        .status-indicator.busy { background-color: var(--busy); }
        .status-indicator.away { background-color: var(--away); }
        .status-indicator.offline { background-color: var(--offline); }

        .chat-header-actions .icon-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .chat-header-actions .icon-btn:hover {
            background: rgba(255,255,255,0.05);
            color: var(--primary);
            transform: translateY(-1px);
        }

        /* --- Chat Messages Area --- */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            scrollbar-width: thin;
            scrollbar-color: var(--bg-input) transparent;
        }

        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }
        .chat-messages::-webkit-scrollbar-thumb {
            background-color: var(--bg-input);
            border-radius: 10px;
        }

        /* Date Separator */
        .date-separator {
            text-align: center;
            margin: 24px 0;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .date-separator span {
            background: rgba(30, 41, 59, 0.8);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
            border: 1px solid var(--border-color);
        }

        /* --- Message Layout (Messenger Style) --- */
        .message {
            display: flex;
            flex-direction: column;
            max-width: 75%;
            animation: fadeIn 0.3s ease;
            margin-bottom: 2px;
        }

        .message-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Incoming Messages */
        .message:not(.own-message) {
            align-self: flex-start;
        }
        .message:not(.own-message) .message-row {
            flex-direction: row;
        }
        .message:not(.own-message) .message-bubble {
            background: var(--bg-card);
            color: var(--text-main);
            border-radius: 4px 18px 18px 4px;
            padding: 12px 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }
        .message:not(.own-message) .message-bubble.image-bubble {
            padding: 4px;
            background: transparent;
            border: none;
            box-shadow: none;
        }

        .message:not(.own-message):first-child .message-bubble,
        .date-separator + .message:not(.own-message) .message-bubble {
            border-top-left-radius: 18px;
        }

        /* Outgoing Messages */
        .message.own-message {
            align-self: flex-end;
            align-items: flex-end;
        }
        .message.own-message .message-row {
            flex-direction: row-reverse;
        }
        .message.own-message .message-bubble {
            background: var(--primary-gradient);
            color: white;
            border-radius: 18px 4px 4px 18px;
            padding: 12px 16px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            border: none;
        }
        .message.own-message .message-bubble.image-bubble {
            padding: 4px;
            background: transparent;
            box-shadow: none;
        }

        /* Text & Time */
        .message-text {
            font-size: 0.95rem;
            line-height: 1.5;
            word-wrap: break-word;
        }
        .message-time {
            font-size: 0.65rem;
            margin-top: 4px;
            opacity: 0.7;
            text-align: right;
            display: block;
        }
        .image-bubble .message-time {
            display: none;
        }

        .message-sender {
            font-size: 0.75rem;
            color: var(--primary);
            margin-bottom: 4px;
            font-weight: 600;
        }

        /* --- Actions (Reply Button) --- */
        .message-actions {
            opacity: 0;
            transition: opacity 0.2s;
            display: flex;
            align-items: center;
        }
        .message:hover .message-actions { opacity: 1; }

        .message-action-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.85rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .message-action-btn:hover { color: var(--primary); background: #fff; }

        /* --- Status (Outside) --- */
        .message-status-container {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 4px;
            padding: 0 4px;
            height: 14px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .seen-indicator { font-size: 0.7rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px; font-weight: 500; }
        .sent-indicator { font-size: 0.7rem; color: var(--text-muted); opacity: 0.6; }

        /* --- Images & Files --- */
        .image-container {
            border-radius: 12px;
            overflow: hidden;
            max-width: 300px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .image-container:hover { 
            transform: scale(1.02); 
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .message-image { 
            width: 100%; 
            display: block; 
            border-radius: 12px;
        }
        
        .file-attachment-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
            gap: 12px;
            min-width: 220px;
            max-width: 300px;
            transition: background 0.2s;
        }
        .file-attachment-card:hover {
            background: rgba(255,255,255,0.1);
        }
        .file-icon-wrapper {
            background: rgba(99, 102, 241, 0.2);
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.2rem;
        }
        .file-card-details {
            flex: 1;
            overflow: hidden;
        }
        .file-card-name { 
            font-weight: 500; 
            font-size: 0.9rem; 
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }
        .file-card-info { font-size: 0.75rem; opacity: 0.7; }

        /* --- Message Composer & Reply Area --- */
        .message-composer {
            background: var(--bg-card);
            padding: 16px 24px;
            display: flex;
            align-items: flex-end;
            gap: 12px;
            border-top: 1px solid var(--border-color);
            position: relative;
            z-index: 20;
        }

        .reply-container {
            position: absolute;
            bottom: 100%;
            left: 0;
            width: 100%;
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 15;
            box-shadow: 0 -4px 10px rgba(0,0,0,0.1);
            transform: translateY(10px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
        }

        .reply-container:not(.hidden) {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        .reply-content-wrapper {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            border-left: 4px solid var(--primary);
            padding-left: 12px;
            margin-right: 12px;
        }

        .reply-header {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary);
        }

        .reply-text {
            font-size: 0.85rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 80vw;
        }

        .reply-close {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: var(--text-muted);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.2s;
        }
        .reply-close:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--text-main);
        }

        .composer-actions {
            display: flex;
            gap: 8px;
            padding-bottom: 8px;
        }

        .message-input-container {
            flex: 1;
            background: var(--bg-input);
            border-radius: 24px;
            padding: 2px;
            transition: ring 0.2s;
            border: 1px solid transparent;
        }

        .message-input-container:focus-within {
            border-color: var(--primary);
            background: rgba(51, 65, 85, 0.8);
        }

        #message-input {
            width: 100%;
            background: transparent;
            border: none;
            color: var(--text-main);
            padding: 12px 16px;
            resize: none;
            min-height: 44px;
            max-height: 120px;
            font-family: inherit;
            font-size: 0.95rem;
        }

        #message-input::placeholder { color: var(--text-muted); }

        .icon-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .icon-btn:hover {
            background: rgba(255,255,255,0.1);
            color: var(--text-main);
        }

        .send-btn {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.4);
            margin-bottom: 2px;
        }
        .send-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.5);
            color: white;
        }

        /* --- Emoji Picker --- */
        .emoji-picker {
            position: fixed;
            bottom: 80px;
            left: 340px;
            width: 320px;
            height: 350px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            z-index: 50;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.2s;
        }
        
        @media (max-width: 768px) {
            .emoji-picker {
                left: 20px;
                width: calc(100% - 40px);
            }
        }

        .emoji-picker.hidden {
            display: none !important;
            transform: scale(0.9);
            opacity: 0;
        }

        .emoji-categories {
            display: flex;
            padding: 8px;
            background: rgba(0,0,0,0.2);
            gap: 4px;
            overflow-x: auto;
        }
        
        .emoji-category {
            background: transparent;
            border: none;
            color: var(--text-muted);
            padding: 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        
        .emoji-category:hover, .emoji-category.active {
            background: rgba(255,255,255,0.1);
            color: var(--primary);
        }

        .emoji-search {
            padding: 8px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .emoji-search input {
            width: 100%;
            background: var(--bg-input);
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            color: var(--text-main);
            font-size: 0.9rem;
        }

        .emoji-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 4px;
            padding: 12px;
            overflow-y: auto;
            flex: 1;
        }
        
        .emoji {
            cursor: pointer;
            font-size: 1.2rem;
            text-align: center;
            padding: 6px;
            border-radius: 4px;
            transition: background 0.1s;
        }
        
        .emoji:hover {
            background: rgba(255,255,255,0.1);
            transform: scale(1.2);
        }

        /* --- Beautiful Image Viewer Modal (SOLID BACKGROUND) --- */
        .image-viewer-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000000; /* Solid opaque black */
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeInViewer 0.3s ease;
        }

        @keyframes fadeInViewer {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .image-viewer-content {
            position: relative;
            max-width: 85vw;
            max-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .image-viewer-img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            animation: scaleInImage 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes scaleInImage {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .image-viewer-close {
            position: absolute;
            top: -45px;
            right: 0;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-viewer-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .image-viewer-download {
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            color: black;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }

        .image-viewer-download:hover {
            transform: translateX(-50%) translateY(-2px);
        }

        /* --- Typing Indicator --- */
        .typing-indicator {
            padding: 8px 24px;
            font-size: 0.75rem;
            color: var(--primary);
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .typing-dots { display: flex; gap: 4px; }
        .dot {
            width: 4px; height: 4px; background: var(--primary); border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }
        .dot:nth-child(1) { animation-delay: -0.32s; }
        .dot:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }

        /* Utility */
        .hidden { display: none !important; }
    </style>

    <script>
        // Make socket URL available to the client
        window.socketUrl = "<?= base_url('socket.io') ?>";
        
        // Store user IDs for socket connection
        window.userId = <?= session()->get('id') ?>;
        window.otherUserId = <?= $otherUser['id'] ?>;
        
        // Set current user info globally
        window.currentUser = {
            id: <?= session()->get('id') ?>,
            username: "<?= session()->get('username') ?>",
            nickname: "<?= session()->get('nickname') ?? session()->get('username') ?>",
            avatar: "<?= session()->get('avatar') ?? 'default-avatar.png' ?>"
        };
        
        // Store the other user info
        window.otherUser = {
            id: <?= $otherUser['id'] ?>,
            username: "<?= $otherUser['username'] ?>",
            nickname: "<?= $otherUser['nickname'] ?? $otherUser['username'] ?>",
            avatar: "<?= $otherUser['avatar'] ?? 'default-avatar.png' ?>"
        };
    </script>
    <script>
        // Set global socket URL that will be used by all scripts
        window.socketUrl = "http://localhost:3001";
        
        // Add a debug function to help troubleshoot WebSocket connection issues
        function debugSocketConnection() {
            console.log("Debug: Testing socket connection to " + window.socketUrl);
            
            // Test if the server is reachable at all
            fetch(window.socketUrl + '/health')
                .then(response => {
                    console.log("Debug: Server health check response status:", response.status);
                    return response.json();
                })
                .then(data => {
                    console.log("Debug: Server health check data:", data);
                })
                .catch(error => {
                    console.error("Debug: Server health check error:", error);
                });
           
            // Log the actual URL being used
            console.log("Debug: Socket.IO URL:", window.socketUrl);
            console.log("Debug: Socket.IO path:", "/socket.io");
            console.log("Debug: Full Socket.IO endpoint:", window.socketUrl + "/socket.io");
           
            // Check if Socket.IO client is loaded
            if (typeof io === 'undefined') {
                console.error("Debug: Socket.IO client library is not loaded!");
            } else {
                console.log("Debug: Socket.IO client library is loaded");
            }
        }
       
        // Run the debug function when the page loads
        window.addEventListener('DOMContentLoaded', debugSocketConnection);
       
        // Add a function to check if the socket server is available
        function checkSocketServer() {
            fetch(window.socketUrl + '/health')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Socket server health check failed');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Socket server health check:', data);
                    if (data.status === 'ok') {
                        console.log('Socket server is available');
                    }
                })
                .catch(error => {
                    console.error('Socket server health check error:', error);
                    // Show error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'connection-error';
                    errorDiv.innerHTML = `Chat server appears to be offline. Please try again later or contact support. <button id="retry-connection" class="retry-btn">Retry</button>`;
                   
                    // Insert at the top of chat messages
                    const chatMessages = document.getElementById('chat-messages');
                    if (chatMessages) {
                        chatMessages.insertBefore(errorDiv, chatMessages.firstChild);
                       
                        // Add event listener to retry button
                        document.getElementById('retry-connection').addEventListener('click', function() {
                            errorDiv.textContent = 'Checking connection...';
                            checkSocketServer();
                        });
                    }
                });
        }
       
        // Check socket server when page loads
        window.addEventListener('DOMContentLoaded', checkSocketServer);
    </script>
    <link rel="stylesheet" href="<?= base_url('css/call-modal.css') ?>">

    <script>
        // Preload sound files
        window.addEventListener('DOMContentLoaded', function() {
            // Preload message sound
            const messageSound = new Audio('/assets/sounds/message.mp3');
            messageSound.preload = 'auto';
            
            // Preload ringtone
            const ringtone = new Audio('/assets/ringtone.mp3');
            ringtone.preload = 'auto';
            
            console.log('Sound files preloaded');
        });
    </script>
    <script>
        // Set global socket URL that will be used by all scripts
        window.socketUrl = "http://localhost:3001";
    </script>
    <script>
    // Format time for Philippines timezone (GMT+8)
    function formatTime(timestamp) {
        if (!timestamp) return "";
        
        // Create date object from timestamp
        const date = new Date(timestamp);
        
        // Check if the timestamp already includes timezone information
        // If not, assume it's in UTC and convert to Philippines time (GMT+8)
        if (!timestamp.includes('+08:00') && !timestamp.includes('+0800') && !timestamp.includes('+08')) {
            // Add 8 hours to convert from UTC to Philippines time
            date.setTime(date.getTime() + (8 * 60 * 60 * 1000));
        }
        
        // Format the time in 12-hour format with AM/PM
        return date.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
    }

    // Format date for grouping
    function formatDate(date) {
        // Ensure date is properly adjusted for Philippines time
        if (!(date instanceof Date)) {
            date = new Date(date);
            // Add 8 hours to convert from UTC to Philippines time if needed
            if (!date.toString().includes('+08')) {
                date.setTime(date.getTime() + (8 * 60 * 60 * 1000));
            }
        }
        
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        
        if (date.toDateString() === today.toDateString()) {
            return 'Today';
        } else if (date.toDateString() === yesterday.toDateString()) {
            return 'Yesterday';
        } else {
            return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
    }
    </script>
</head>
<body class="dark-theme">
    <div id="app-container">
        <?= $this->include('partials/sidebar') ?>
        <div class="main-content">
            <div class="chat-interface">
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="avatar-container">
                            <img src="<?= base_url('public/uploads/avatars/' . ($otherUser['avatar'] ?? 'default-avatar.png')) ?>" alt="<?= $otherUser['username'] ?>" class="user-avatar">
                            <span class="status-indicator <?= $otherUser['status'] ?? 'online' ?>" id="other-user-status" data-user-id="<?= $otherUser['id'] ?>"></span>
                        </div>
                        <div class="chat-header-text">
                            <h2><?= $otherUser['nickname'] ?? $otherUser['username'] ?></h2>
                        </div>
                    </div>
                    <div class="chat-header-actions">
                        <button id="audio-call-btn" class="icon-btn" title="Audio Call"><i class="fas fa-phone-alt"></i></button>
                        <button id="video-call-btn" class="icon-btn" title="Video Call"><i class="fas fa-video"></i></button>
                        <button id="chat-info-btn" class="icon-btn" title="Chat Info"><i class="fas fa-info-circle"></i></button>
                    </div>
                </div>
               
                <div class="chat-messages" id="chat-messages">
                    </div>
               
                <div class="typing-indicator hidden" id="typing-indicator">
                    <div class="typing-dots">
                        <div class="dot"></div>
                        <div class="dot"></div>
                        <div class="dot"></div>
                    </div>
                    <span id="typing-text">Someone is typing...</span>
                </div>
               
                <div class="message-composer">
                    <div class="reply-container hidden" id="reply-container">
                        <div class="reply-content-wrapper">
                            <div class="reply-header" id="reply-header-text">Replying to...</div>
                            <div class="reply-text" id="reply-body-text"></div>
                        </div>
                        <button class="reply-close" onclick="cancelReply()">&times;</button>
                    </div>

                    <div class="composer-actions">
                        <button class="icon-btn" id="emoji-btn" title="Emoji"><i class="far fa-smile"></i></button>
                        <button class="icon-btn" id="attach-btn" title="Attach File"><i class="fas fa-paperclip"></i></button>
                        <input type="file" id="file-input" class="hidden">
                    </div>
                    <div class="message-input-container">
                        <textarea id="message-input" placeholder="Type a message..." rows="1"></textarea>
                    </div>
                    <button class="icon-btn send-btn" id="send-btn" title="Send Message"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="emoji-picker hidden" id="emoji-picker">
        <div class="emoji-categories">
            <button class="emoji-category active"><i class="far fa-smile"></i></button>
            <button class="emoji-category"><i class="far fa-hand-point-right"></i></button>
            <button class="emoji-category"><i class="fas fa-utensils"></i></button>
            <button class="emoji-category"><i class="fas fa-futbol"></i></button>
            <button class="emoji-category"><i class="far fa-lightbulb"></i></button>
            <button class="emoji-category"><i class="fas fa-flag"></i></button>
        </div>
        <div class="emoji-search">
            <input type="text" id="emoji-search" placeholder="Search emojis...">
        </div>
        <div class="emoji-grid" id="emoji-grid">
            </div>
    </div>
   
    <div class="status-dropdown hidden" id="status-dropdown">
        <ul>
            <li data-status="online"><span class="status-dot online"></span> Online</li>
            <li data-status="away"><span class="status-dot away"></span> Away</li>
            <li data-status="busy"><span class="status-dot busy"></span> Busy</li>
            <li data-status="offline"><span class="status-dot offline"></span> Appear Offline</li>
            <li class="divider"></li>
            <li data-status="custom"><i class="fas fa-pen"></i> Set a status message</li>
        </ul>
    </div>
   
   
    <div class="modal hidden" id="settings-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Settings</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="settings-tabs">
                    <button class="settings-tab active" data-tab="profile">Profile</button>
                    <button class="settings-tab" data-tab="appearance">Appearance</button>
                    <button class="settings-tab" data-tab="notifications">Notifications</button>
                    <button class="settings-tab" data-tab="privacy">Privacy</button>
                </div>
               
                <div class="settings-content active" id="profile-settings">
                    <form id="profile-form">
                        <div class="profile-avatar-section">
                            <img src="<?= base_url('public/uploads/avatars/' . (session()->get('avatar') ?? 'default-avatar.png')) ?>" alt="Avatar Preview" id="avatar-preview">
                            <div class="avatar-actions">
                                <label for="avatar" class="btn primary-btn">Change Picture</label>
                                <input type="file" id="avatar" name="avatar" class="hidden">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nickname">Display Name</label>
                            <input type="text" id="nickname" name="nickname" value="<?= session()->get('nickname') ?? session()->get('username') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= session()->get('email') ?>">
                        </div>
                        <div class="form-group">
                            <label for="status-message">Status Message</label>
                            <input type="text" id="status-message" name="status_message" placeholder="What's happening?">
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn secondary-btn close-modal">Cancel</button>
                            <button type="submit" class="btn primary-btn">Save Changes</button>
                        </div>
                    </form>
                </div>
               
                <div class="settings-content" id="appearance-settings" style="display: none;">
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
                            <span>A</span>
                            <input type="range" id="font-size-slider" min="12" max="20" value="14">
                            <span>A</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Chat Background</label>
                        <button class="btn secondary-btn" id="choose-background">Choose Image</button>
                        <input type="file" id="background-input" class="hidden">
                    </div>
                </div>
               
                <div class="settings-content" id="notifications-settings" style="display: none;">
                    <div class="form-group">
                        <label class="switch-label">
                            <span>Enable Notifications</span>
                            <label class="switch">
                                <input type="checkbox" id="enable-notifications" checked>
                                <span class="slider round"></span>
                            </label>
                        </label>
                        <p class="setting-description">Receive notifications for new messages and calls</p>
                    </div>
                    <div class="form-group">
                        <label class="switch-label">
                            <span>Sound Notifications</span>
                            <label class="switch">
                                <input type="checkbox" id="sound-notifications" checked>
                                <span class="slider round"></span>
                            </label>
                        </label>
                        <p class="setting-description">Play sound when receiving notifications</p>
                    </div>
                    <div class="form-group">
                        <label>Notification Preview</label>
                        <select id="notification-preview">
                            <option value="full">Show sender and message</option>
                            <option value="sender">Show sender only</option>
                            <option value="none">No preview</option>
                        </select>
                    </div>
                </div>
               
                <div class="settings-content" id="privacy-settings" style="display: none;">
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
                            <span>Show Online Status</span>
                            <label class="switch">
                                <input type="checkbox" id="online-status" checked>
                                <span class="slider round"></span>
                            </label>
                        </label>
                        <p class="setting-description">Let others see when you're online</p>
                    </div>
                    <div class="form-group">
                        <label>Who can contact me</label>
                        <select id="contact-permission">
                            <option value="everyone">Everyone</option>
                            <option value="contacts">Contacts only</option>
                            <option value="none">No one (appear offline)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
   
    <div class="modal hidden" id="create-group-modal">
        <div class="global-modal-content">
            <div class="modal-header">
                <h3>Create New Group</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="create-group-form">
                    <div class="group-avatar-section">
                        <img src="<?= base_url('uploads/groups/default-group.png') ?>" alt="Group Image Preview" id="group-image-preview">
                        <div class="avatar-actions">
                            <label for="group-image" class="btn primary-btn">Choose Image</label>
                            <input type="file" id="group-image" name="image" class="hidden">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="group-name">Group Name</label>
                        <input type="text" id="group-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="group-description">Description</label>
                        <textarea id="group-description" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Add Members</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="member-search" placeholder="Search contacts...">
                        </div>
                        <div class="members-list" id="available-members">
                            </div>
                    </div>
                    <div class="selected-members">
                        <h5>Selected Members</h5>
                        <div class="selected-members-list" id="selected-members-list">
                            </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn secondary-btn close-modal">Cancel</button>
                        <button type="submit" class="btn primary-btn">Create Group</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
   
<div class="call-modal hidden" id="call-modal">
    <div class="call-glass-overlay"></div>
    
    <div class="call-modal-content">
        <div class="call-info-overlay">
            <div class="caller-avatar-wrapper">
                <img src="<?= base_url('public/uploads/avatars/' . ($otherUser['avatar'] ?? 'default-avatar.png')) ?>" alt="Avatar" class="caller-img">
                <div class="pulse-ring"></div>
            </div>
            <h2 id="call-recipient-name"><?= $otherUser['nickname'] ?? $otherUser['username'] ?></h2>
            <p id="call-status-text" class="status-shimmer">Calling...</p>
             <div id="call-timer" style="margin-top: 5px; font-weight: bold;">00:00</div>
        </div>

        <div class="video-grid">
            <div class="remote-video-wrapper">
                <video id="call-remote-video" autoplay playsinline></video>
                <div class="audio-only-placeholder hidden" id="audio-placeholder">
                    <i class="fas fa-user-circle"></i>
                </div>
            </div>
            
            <div class="local-video-wrapper" id="local-video-container">
                <video id="call-local-video" autoplay playsinline muted></video>
            </div>
        </div>

        <div class="call-controls-floating">
            <button id="call-toggle-audio" class="control-btn" title="Mute/Unmute">
                <i class="fas fa-microphone"></i>
            </button>
            <button id="call-toggle-video" class="control-btn" title="Camera On/Off">
                <i class="fas fa-video"></i>
            </button>
            <button id="call-toggle-speaker" class="control-btn" title="Speaker">
                <i class="fas fa-volume-up"></i>
            </button>
            <button id="call-end" class="control-btn end-call" title="End Call">
                <i class="fas fa-phone-slash"></i>
            </button>
        </div>
    </div>
</div>

<div id="incoming-call-modal" class="modal hidden" style="z-index: 99999; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center;">
    <div class="modal-content" style="max-width: 350px; text-align: center; background: #1e293b; border: 1px solid #334155; padding: 30px; border-radius: 16px;">
        <div style="margin: 0 auto 20px; width: 80px; height: 80px; border-radius: 50%; overflow: hidden; border: 3px solid #6366f1;">
            <img id="incoming-call-avatar" src="" alt="Caller" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <h3 id="incoming-caller-name" style="color: #f8fafc; margin-bottom: 5px;">Unknown</h3>
        <p id="incoming-call-type" style="color: #94a3b8; margin-bottom: 25px;">Incoming Audio Call...</p>
        <div style="display: flex; justify-content: center; gap: 30px;">
            <button id="btn-reject-call" style="background: #ef4444; border: none; width: 50px; height: 50px; border-radius: 50%; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; transition: transform 0.2s;"><i class="fas fa-phone-slash"></i></button>
            <button id="btn-accept-call" style="background: #22c55e; border: none; width: 50px; height: 50px; border-radius: 50%; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; transition: transform 0.2s;"><i class="fas fa-phone"></i></button>
        </div>
    </div>
</div>
   
    <div class="notification-toast hidden" id="notification-toast">
        <div class="notification-icon">
            <i class="fas fa-info-circle"></i>
        </div>
        <div class="notification-content">
            <h4 id="notification-title">Notification</h4>
            <p id="notification-message">This is a notification message.</p>
        </div>
        <button class="notification-close" id="notification-close">&times;</button>
    </div>
   
    <script src="https://cdn.socket.io/4.4.1/socket.io.min.js"></script>
    <script>
        // --- [FIX] NEW CallHandler Module (Replaced with WebRTC Logic) ---
const CallHandler = (() => {
    let localStream = null;
    let peerConnection = null;
    let currentCallParams = null;
    let callTimer = null;
    let callDuration = 0;
    let isInitialized = false;

    const ringtone = new Audio('<?= base_url("assets/sounds/ringtone.mp3") ?>');
    ringtone.loop = true;
    const dialtone = new Audio('<?= base_url("assets/sounds/dialtone.mp3") ?>');
    dialtone.loop = true;

    const rtcConfig = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
        ]
    };

    const getUI = () => ({
        callModal: document.getElementById('call-modal'),
        incomingModal: document.getElementById('incoming-call-modal'),
        status: document.getElementById('call-status-text'),
        timerText: document.getElementById('call-timer'),
        recipientName: document.getElementById('call-recipient-name'),
        remoteVideo: document.getElementById('call-remote-video'),
        localVideo: document.getElementById('call-local-video'),
        audioPlaceholder: document.getElementById('audio-placeholder'),
        localContainer: document.getElementById('local-video-container'),
        btnMute: document.getElementById('call-toggle-audio'),
        btnCamera: document.getElementById('call-toggle-video'),
        btnEnd: document.getElementById('call-end'),
        incomingName: document.getElementById('incoming-caller-name'),
        incomingAvatar: document.getElementById('incoming-call-avatar'),
        incomingType: document.getElementById('incoming-call-type'),
        btnAccept: document.getElementById('btn-accept-call'),
        btnReject: document.getElementById('btn-reject-call')
    });

    const init = () => {
        if (isInitialized) return;
        const ui = getUI();
        
        document.getElementById('audio-call-btn')?.addEventListener('click', (e) => { e.preventDefault(); startCall('audio'); });
        document.getElementById('video-call-btn')?.addEventListener('click', (e) => { e.preventDefault(); startCall('video'); });

        if(ui.btnEnd) ui.btnEnd.onclick = (e) => { e.preventDefault(); endCall(true); };
        if(ui.btnMute) ui.btnMute.onclick = (e) => { e.preventDefault(); toggleAudio(); };
        if(ui.btnCamera) ui.btnCamera.onclick = (e) => { e.preventDefault(); toggleVideo(); };
        if(ui.btnAccept) ui.btnAccept.onclick = (e) => { e.preventDefault(); acceptIncomingCall(); };
        if(ui.btnReject) ui.btnReject.onclick = (e) => { e.preventDefault(); rejectIncomingCall(); };

        const checkSocket = setInterval(() => {
            if (window.socket && window.socket.connected) {
                setupSocketListeners();
                isInitialized = true;
                clearInterval(checkSocket);
            }
        }, 500);
    };

    const setupSocketListeners = () => {
        const socket = window.socket;
        ['call_request', 'call_accepted', 'call_rejected', 'call_ended', 'ice_candidate'].forEach(ev => socket.off(ev));

        socket.on('call_request', onIncomingCall);
        socket.on('call_accepted', onCallAccepted);
        socket.on('call_rejected', onCallRejected);
        socket.on('call_ended', onCallEnded);
        socket.on('ice_candidate', onNewICECandidate);
    };

    const startCall = async (type) => {
        const ui = getUI();
        ui.callModal.classList.remove('hidden');
        ui.timerText.classList.add('hidden'); 
        ui.status.textContent = "Calling...";
        ui.status.style.color = "#fff";
        ui.recipientName.textContent = window.otherUser.nickname;

        const isAudioOnly = (type === 'audio');
        isAudioOnly ? ui.localContainer.classList.add('hidden') : ui.localContainer.classList.remove('hidden');

        try {
            localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: !isAudioOnly });
            ui.localVideo.srcObject = localStream;
            ui.localVideo.muted = true;

            createPeerConnection(window.otherUserId);
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            window.socket.emit('call_request', {
                caller_id: String(window.userId),
                target_id: String(window.otherUserId),
                caller_name: window.currentUser.nickname,
                caller_avatar: window.currentUser.avatar,
                type: type,
                sdp: offer
            });

            dialtone.play().catch(() => {});
        } catch (err) {
            endCall(false);
        }
    };

    const onIncomingCall = (data) => {
        if (String(data.caller_id) === String(window.userId)) return;
        currentCallParams = data;
        const ui = getUI();
        ui.incomingName.textContent = data.caller_name;
        ui.incomingAvatar.src = `<?= base_url('public/uploads/avatars/') ?>${data.caller_avatar || 'default-avatar.png'}`;
        ui.incomingModal.classList.remove('hidden');
        ringtone.play().catch(() => {});
    };

    const onCallAccepted = async (data) => {
        const ui = getUI();
        dialtone.pause(); dialtone.currentTime = 0;
        
        ui.status.textContent = "Connected";
        ui.status.style.color = "#22c55e";
        ui.timerText.classList.remove('hidden'); 

        if (data.sdp && peerConnection) {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(data.sdp));
        }
        startTimer();
    };

    const acceptIncomingCall = async () => {
        ringtone.pause(); ringtone.currentTime = 0;
        const ui = getUI();
        ui.incomingModal.classList.add('hidden');
        ui.callModal.classList.remove('hidden');

        try {
            const isAudioOnly = currentCallParams.type === 'audio';
            localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: !isAudioOnly });
            ui.localVideo.srcObject = localStream;
            ui.localVideo.muted = true;

            createPeerConnection(currentCallParams.caller_id);
            await peerConnection.setRemoteDescription(new RTCSessionDescription(currentCallParams.sdp));
            
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);

            window.socket.emit('call_accepted', {
                target_id: String(currentCallParams.caller_id),
                accepter_id: String(window.userId),
                sdp: answer
            });

            ui.status.textContent = "Connected";
            ui.status.style.color = "#22c55e";
            ui.timerText.classList.remove('hidden');
            startTimer();
        } catch (err) {
            endCall(true);
        }
    };

    const onCallRejected = () => {
        const ui = getUI();
        dialtone.pause(); dialtone.currentTime = 0;
        ui.status.textContent = "Declined";
        ui.status.style.color = "#ef4444";
        setTimeout(() => endCall(false), 2000);
    };

    const rejectIncomingCall = () => {
        ringtone.pause(); ringtone.currentTime = 0;
        getUI().incomingModal.classList.add('hidden');
        if (currentCallParams) {
            window.socket.emit('call_rejected', { target_id: String(currentCallParams.caller_id) });
        }
        currentCallParams = null;
    };

    const createPeerConnection = (targetId) => {
        const ui = getUI();
        peerConnection = new RTCPeerConnection(rtcConfig);
        localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                window.socket.emit('ice_candidate', { target_id: String(targetId), candidate: event.candidate });
            }
        };

        peerConnection.ontrack = (event) => {
            ui.remoteVideo.srcObject = event.streams[0];
            ui.audioPlaceholder.classList.add('hidden');
        };
    };

    const onNewICECandidate = async (data) => {
        if (peerConnection && data) {
            try { await peerConnection.addIceCandidate(new RTCIceCandidate(data)); } 
            catch (e) {}
        }
    };

    const startTimer = () => {
        const ui = getUI();
        if (callTimer) clearInterval(callTimer);
        callDuration = 0;
        ui.timerText.textContent = "00:00";
        callTimer = setInterval(() => {
            callDuration++;
            const m = Math.floor(callDuration / 60).toString().padStart(2, '0');
            const s = (callDuration % 60).toString().padStart(2, '0');
            ui.timerText.textContent = `${m}:${s}`;
        }, 1000);
    };

    const endCall = (notifyServer = true) => {
    const ui = getUI();
    if (localStream) { localStream.getTracks().forEach(t => t.stop()); localStream = null; }
    if (peerConnection) { peerConnection.close(); peerConnection = null; }
    
    const finalDuration = callDuration; // Capture duration before clearing
    if (callTimer) clearInterval(callTimer);
    
    ringtone.pause(); ringtone.currentTime = 0;
    dialtone.pause(); dialtone.currentTime = 0;
    
    ui.callModal.classList.add('hidden');
    
    if (notifyServer) {
        const target = currentCallParams ? currentCallParams.caller_id : window.otherUserId;
        window.socket.emit('call_ended', { 
            target_id: String(target),
            duration: finalDuration,
            type: currentCallParams ? currentCallParams.type : 'audio'
        });
    }
    currentCallParams = null;
    callDuration = 0;
};

    const onCallEnded = () => {
        const ui = getUI();
        ui.status.textContent = "Call Ended";
        ui.status.style.color = "#ef4444";
        setTimeout(() => endCall(false), 1500);
    };

    const toggleAudio = () => {
        const track = localStream?.getAudioTracks()[0];
        if(track) {
            track.enabled = !track.enabled;
            document.getElementById('call-toggle-audio').innerHTML = track.enabled ? '<i class="fas fa-microphone"></i>' : '<i class="fas fa-microphone-slash"></i>';
        }
    };

    const toggleVideo = () => {
        const track = localStream?.getVideoTracks()[0];
        if (track) {
            track.enabled = !track.enabled;
            document.getElementById('call-toggle-video').innerHTML = track.enabled ? '<i class="fas fa-video"></i>' : '<i class="fas fa-video-slash"></i>';
        }
    };

    return { init };
})();
        // ----------------------------------------------------------------

        document.addEventListener('DOMContentLoaded', function() {
            // [FIX] Initialize Call Handler
            CallHandler.init();

            const userId = <?= session()->get('id') ?>;
            const otherUserId = <?= $otherUser['id'] ?>;
            const username = "<?= session()->get('username') ?>";
            const socketUrl = "http://localhost:3001";
           
            let socket;
            let selectedFile = null;
            let typingTimeout = null;
            let currentTheme = 'dark';
           
            // Initialize socket connection
            function connectSocket() {
                console.log('Connecting to socket server at:', socketUrl);
                updateConnectionStatus('connecting');
               
                try {
                    socket = io(socketUrl, {
                        path: "/socket.io", // Explicitly set the Socket.IO path
                        transports: ['websocket', 'polling'],
                        reconnection: true,
                        reconnectionAttempts: 10,
                        reconnectionDelay: 1000,
                        timeout: 20000,
                        forceNew: true,
                        autoConnect: true
                    });
                   
                    socket.on('connect', function() {
                        console.log('Connected to Socket.IO server with ID:', socket.id);
                       
                        // Update connection status
                        const statusElement = document.getElementById('connection-status');
                        if (statusElement) {
                            statusElement.className = 'connection-status connected';
                            statusElement.innerHTML = 'Connected to chat server';
                           
                            // Hide the status after 3 seconds
                            setTimeout(() => {
                                statusElement.style.opacity = '0';
                                setTimeout(() => {
                                    statusElement.style.display = 'none';
                                }, 300);
                            }, 3000);
                        }
                       
                        // Clear any existing error messages
                        const errorMessages = document.querySelectorAll('.connection-error');
                        errorMessages.forEach(el => el.remove());
                       
                        // Emit user connected event
                        socket.emit('user_connected', userId);
                       
                        // Join direct chat room - use consistent room naming
                        const roomName = [userId, otherUserId].sort().join('-');
                        socket.emit('join_direct_chat', {
                            user_id: userId,
                            other_user_id: otherUserId,
                            room_name: roomName
                        });
                       
                        console.log(`Joined direct chat room: ${roomName}`);
                       
                        // Update UI to show connected status
                        const currentStatus = '<?= session()->get('status') ?? 'online' ?>';
                        updateUserStatus(currentStatus);
                       
                        // Load messages to ensure we have the latest
                        loadMessages();
                       
                        // Show success notification
                        showNotification('success', 'Connected', 'Successfully connected to chat server');
                    });
                   
                    socket.on('connect_error', function(error) {
                        console.error('Socket connection error:', error);
                        const statusElement = document.getElementById('connection-status');
                        if (statusElement) {
                            statusElement.className = 'connection-status disconnected';
                            statusElement.innerHTML = `Failed to connect: ${error.message} <button id="connection-retry" class="retry-btn">Retry</button>`;
                            statusElement.style.display = 'block';
                            statusElement.style.opacity = '1';
                           
                            document.getElementById('connection-retry').addEventListener('click', function() {
                                statusElement.className = 'connection-status connecting';
                                statusElement.innerHTML = 'Reconnecting to chat server...';
                                socket.connect();
                            });
                        }
                       
                        updateConnectionStatus('disconnected');
                        showNotification('error', 'Connection Error', 'Failed to connect to chat server');
                    });
                   
                    // Listen for new messages
                    socket.on('receive_message', function(message) {
                        console.log('Received message via socket:', message);
                        if ((message.sender_id == userId && message.receiver_id == otherUserId) || 
                            (message.sender_id == otherUserId && message.receiver_id == userId)) {
                           
                            const existingMessage = document.querySelector(`.message[data-message-id="${message.id}"]`);
                            if (existingMessage) return;
                           
                            appendMessage(message);
                           
                            if (message.sender_id == otherUserId) {
                                playMessageSound();
                            }
                        }
                    });
                   
                    // Listen for typing status
                    socket.on('typing', function(data) {
                        if (data.user_id == otherUserId && data.receiver_id == userId) {
                            if (data.is_typing) {
                                showTypingIndicator(data.username || 'Someone');
                            } else {
                                hideTypingIndicator();
                            }
                        }
                    });
                   
                    // Listen for user status changes
                    socket.on('user_status_change', function(data) {
                        if (data.user_id == otherUserId) {
                            updateUserStatus(data.status);
                        }
                    });
                   
                    // Make socket available globally
                    window.socket = socket;
                   
                    socket.on('disconnect', function() {
                        const statusElement = document.getElementById('connection-status');
                        if (statusElement) {
                            statusElement.className = 'connection-status disconnected';
                            statusElement.innerHTML = 'Disconnected from chat server <button id="connection-retry" class="retry-btn">Reconnect</button>';
                            statusElement.style.display = 'block';
                            statusElement.style.opacity = '1';
                           
                            document.getElementById('connection-retry').addEventListener('click', function() {
                                statusElement.className = 'connection-status connecting';
                                statusElement.innerHTML = 'Reconnecting to chat server...';
                                socket.connect();
                            });
                        }
                       
                        updateConnectionStatus('disconnected');
                        updateUserStatus('offline');
                        showNotification('warning', 'Disconnected', 'Connection to chat server lost');
                    });
                } catch (error) {
                    console.error('Error initializing socket connection:', error);
                    updateConnectionStatus('disconnected');
                    showNotification('error', 'Connection Error', 'Failed to initialize chat connection');
                }
            }
           
            // Function to update connection status
            function updateConnectionStatus(status) {
                const connectionStatusDiv = document.getElementById('connection-status');
                if (!connectionStatusDiv) return;
               
                connectionStatusDiv.classList.remove('connected', 'connecting', 'disconnected');
                connectionStatusDiv.classList.add(`connection-status`, status);
               
                if (status === 'connected') {
                    connectionStatusDiv.textContent = 'Connected to chat server';
                } else if (status === 'connecting') {
                    connectionStatusDiv.textContent = 'Connecting to chat server...';
                } else if (status === 'disconnected') {
                    connectionStatusDiv.textContent = 'Disconnected from chat server. ';
                    const retryButton = document.createElement('button');
                    retryButton.textContent = 'Retry';
                    retryButton.className = 'retry-btn';
                    retryButton.addEventListener('click', connectSocket);
                    connectionStatusDiv.appendChild(retryButton);
                }
            }
           
            // Load users list
            function loadUsers() {
                fetch('<?= base_url('api/users') ?>')
                    .then(response => response.json())
                    .then(data => {
                        const users = Array.isArray(data) ? data : [];
                        const directMessagesList = document.getElementById('direct-messages-list');
                        directMessagesList.innerHTML = '';
                        
                        if (users.length === 0) {
                            directMessagesList.innerHTML = '<div class="empty-state">No contacts found</div>';
                            return;
                        }
                        
                        users.forEach(user => {
                            if (user.id == userId) return;
                            
                            const isActive = user.id == otherUserId;
                            const div = document.createElement('div');
                            div.className = `conversation-item${isActive ? ' active' : ''}`;
                            div.dataset.userId = user.id;
                            
                            const lastMessage = user.last_message ? user.last_message.content : 'Click to start chatting';
                            const lastMessageTime = user.last_message ? formatTime(user.last_message.created_at) : '';
                            const unreadCount = user.unread_count || 0;
                
                            div.innerHTML = `
                                <div class="avatar-container">
                                    <img src="<?= base_url('public/uploads/avatars') ?>/${user.avatar || 'default-avatar.png'}" alt="${user.username}" class="user-avatar">
                                    <span class="status-indicator ${user.online ? 'online' : 'offline'}"></span>
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-name">${user.nickname || user.username}</div>
                                    <div class="conversation-preview">${lastMessage}</div>
                                </div>
                                <div class="conversation-meta">
                                    ${lastMessageTime ? `<div class="conversation-time">${lastMessageTime}</div>` : ''}
                                    ${unreadCount > 0 ? `<div class="conversation-badge">${unreadCount}</div>` : ''}
                                </div>
                            `;
                            
                            div.addEventListener('click', function() {
                                window.location.href = `<?= base_url('chat/direct/') ?>${user.id}`;
                            });
                            
                            directMessagesList.appendChild(div);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading users:', error);
                        loadGroups();
                    });
            }

            function loadGroups() {
                fetch('<?= base_url('api/getUserGroups') ?>')
                    .then(response => response.json())
                    .then(data => {
                        const groups = Array.isArray(data) ? data : [];
                        const directMessagesList = document.getElementById('direct-messages-list');
                        
                        if (groups.length === 0) return;
                        
                        const existingHeaders = directMessagesList.querySelectorAll('.list-section-header h3');
                        let existingHeader = null;
                        for (let i = 0; i < existingHeaders.length; i++) {
                            if (existingHeaders[i].textContent === 'Groups') {
                                existingHeader = existingHeaders[i].parentElement;
                                break;
                            }
                        }
                        
                        if (!existingHeader) {
                            const groupsHeader = document.createElement('div');
                            groupsHeader.className = 'list-section-header';
                            groupsHeader.innerHTML = '<h3>Groups</h3>';
                            directMessagesList.appendChild(groupsHeader);
                        }
                        
                        groups.forEach(group => {
                            if (directMessagesList.querySelector(`.conversation-item[data-group-id="${group.id}"]`)) return;
                            
                            const isActive = false;
                            const lastMessage = group.last_message ? group.last_message.content : 'No messages yet';
                            const lastMessageTime = group.last_message ? formatTime(group.last_message.created_at) : '';
                            const unreadCount = group.unread_count || 0;
                            
                            const groupItem = document.createElement('div');
                            groupItem.className = `conversation-item${isActive ? ' active' : ''}`;
                            groupItem.dataset.groupId = group.id;
                            
                            groupItem.innerHTML = `
                                <div class="avatar-container">
                                    <img src="<?= base_url('uploads/groups') ?>/${group.image || 'default-group.png'}" alt="${group.name}" class="user-avatar">
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-name">${group.name}</div>
                                    <div class="conversation-preview">${lastMessage}</div>
                                </div>
                                <div class="conversation-meta">
                                    ${lastMessageTime ? `<div class="conversation-time">${lastMessageTime}</div>` : ''}
                                    ${unreadCount > 0 ? `<div class="conversation-badge">${unreadCount}</div>` : ''}
                                </div>
                            `;
                            
                            groupItem.addEventListener('click', function() {
                                const groupId = this.dataset.groupId;
                                window.location.href = `<?= base_url('chat/group') ?>/${groupId}`;
                            });
                            
                            directMessagesList.appendChild(groupItem);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading groups:', error);
                    });
            }
           
            // Load chat messages
            function loadMessages() {
                fetch(`<?= base_url('api/getDirectMessages') ?>/${otherUserId}`)
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                        return response.json();
                    })
                    .then(messages => {
                        const chatMessages = document.getElementById('chat-messages');
                        chatMessages.innerHTML = '';
                       
                        if (messages.length === 0) {
                            const emptyState = document.createElement('div');
                            emptyState.className = 'empty-state';
                            emptyState.innerHTML = `
                                <div class="empty-state-icon"><i class="far fa-comment-alt"></i></div>
                                <h2>No messages yet</h2>
                                <p>Start a conversation with <?= $otherUser['nickname'] ?? $otherUser['username'] ?></p>
                            `;
                            chatMessages.appendChild(emptyState);
                            return;
                        }
                       
                        const messagesByDate = groupMessagesByDate(messages);
                        Object.keys(messagesByDate).forEach(date => {
                            const dateSeparator = document.createElement('div');
                            dateSeparator.className = 'date-separator';
                            dateSeparator.innerHTML = `<span>${date}</span>`;
                            chatMessages.appendChild(dateSeparator);
                            messagesByDate[date].forEach(message => appendMessage(message));
                        });
                        scrollToBottom();
                    })
                    .catch(error => {
                        console.error('Error loading messages:', error);
                    });
            }
           
            function groupMessagesByDate(messages) {
                const groups = {};
                messages.forEach(message => {
                    const date = new Date(message.created_at);
                    const dateString = formatDate(date);
                    if (!groups[dateString]) groups[dateString] = [];
                    groups[dateString].push(message);
                });
                return groups;
            }
           
            function formatDate(date) {
                const today = new Date();
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
               
                if (date.toDateString() === today.toDateString()) return 'Today';
                else if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';
                else return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
           
            function getFileTypeLabel(extension) {
                const fileTypes = {
                    pdf: "PDF Document", doc: "Word Document", docx: "Word Document",
                    xls: "MS Excel", xlsx: "MS Excel", ppt: "PowerPoint", pptx: "PowerPoint",
                    jpg: "JPEG Image", jpeg: "JPEG Image", png: "PNG Image", gif: "GIF Image",
                    zip: "ZIP Archive", rar: "RAR Archive", txt: "Text File", mp3: "MP3 Audio", mp4: "MP4 Video",
                };
                return fileTypes[extension.toLowerCase()] || "File";
            }
            
            function getFileIconClass(extension) {
                const iconMap = {
                    pdf: "fas fa-file-pdf", doc: "fas fa-file-word", docx: "fas fa-file-word",
                    xls: "fas fa-file-excel", xlsx: "fas fa-file-excel", ppt: "fas fa-file-powerpoint", pptx: "fas fa-file-powerpoint",
                    jpg: "fas fa-file-image", jpeg: "fas fa-file-image", png: "fas fa-file-image", gif: "fas fa-file-image",
                    zip: "fas fa-file-archive", rar: "fas fa-file-archive", txt: "fas fa-file-alt", mp3: "fas fa-file-audio", mp4: "fas fa-file-video",
                };
                return iconMap[extension.toLowerCase()] || "fas fa-file";
            }
            
            function formatFileSize(bytes) {
                if (!bytes || isNaN(bytes)) return "18 KB"; 
                const sizes = ["Bytes", "KB", "MB", "GB", "TB"];
                if (bytes === 0) return "0 Bytes";
                const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                return Math.round(bytes / Math.pow(1024, i)) + " " + sizes[i];
            }
            
            function getOriginalFilename(fileUrl, originalName) {
                if (originalName && originalName !== "null" && originalName !== "undefined") return originalName;
                if (fileUrl) {
                    if (fileUrl.startsWith("File: ")) return fileUrl.substring(6);
                    const parts = fileUrl.split('/');
                    const filename = parts[parts.length - 1];
                    if (/^[a-f0-9]{20,}\.[a-z0-9]+$/i.test(filename)) {
                        const ext = filename.split('.').pop();
                        return `Untitled spreadsheet.${ext}`;
                    }
                    return filename;
                }
                return "Untitled file";
            }
           
            function appendMessage(message) {
                const chatMessages = document.getElementById('chat-messages');
                const messageDiv = document.createElement('div');

                const isCurrentUser = message.sender_id == userId;
                messageDiv.className = `message ${isCurrentUser ? 'own-message' : ''}`;
                messageDiv.dataset.messageId = message.id;

                let html = `<div class="message-row">`;
                const isImage = message.type === 'image';
                const bubbleClass = isImage ? 'message-bubble image-bubble' : 'message-bubble';
                html += `<div class="${bubbleClass}">`;

                if (!isCurrentUser) {
                    html += `<div class="message-sender">${message.nickname || message.username || 'Unknown'}</div>`;
                }

                if (message.reply_to_id) {
                    let replySender = message.reply_to_sender_name || 'Unknown User';
                    let replyContent = message.reply_to_content || '';
                    if (!replyContent || replyContent === 'undefined' || replyContent === 'Original message') {
                        const original = document.querySelector(`.message[data-message-id="${message.reply_to_id}"] .message-text`);
                        replyContent = original ? original.textContent.trim() : 'Message';
                    }
                    html += `
                        <div class="replied-message" style="
                            background: rgba(108,92,231,.3);
                            padding:8px 12px;
                            border-radius:8px 8px 0 0;
                            font-size:.85em;
                            margin-bottom:4px;
                        ">
                            <div style="font-weight:600;color:#fff">Replying to ${replySender}</div>
                            <div style="color:#fff;opacity:.9">${replyContent}</div>
                        </div>
                    `;
                }

                if (message.type === 'image') {
                    const src = '<?= base_url("uploads/messages/") ?>/' + (message.file_url || message.content);
                    const name = message.original_filename || 'image.jpg';
                    html += `
                        <div class="image-container" onclick="openImageViewer('${src}','${name}')">
                            <img src="${src}" class="message-image" alt="Shared Image">
                        </div>
                        `;
                }
                else if (message.type === 'file') {
                    const fileUrl = '<?= base_url("uploads/messages/") ?>/' + (message.file_url || message.content);
                    const name = message.original_filename || getOriginalFilename(message.file_url, message.original_filename) || message.content;
                    const size = message.file_size ? formatFileSize(message.file_size) : '';
                    let extension = name.split('.').pop();
                    let iconClass = getFileIconClass(extension);

                    html += `
                        <a href="${fileUrl}" class="file-attachment-card" download="${name}" target="_blank">
                            <div class="file-icon-wrapper"><i class="${iconClass}"></i></div>
                            <div class="file-card-details">
                                <div class="file-card-name">${name}</div>
                                <div class="file-card-info">${size} • ${extension.toUpperCase()}</div>
                            </div>
                            <div class="file-download-icon"><i class="fas fa-download"></i></div>
                        </a>
                    `;
                }
                else {
                    html += `<div class="message-text">${message.content}</div>`;
                }

                if (!isImage) {
                    html += `<div class="message-time">${formatTime(message.created_at)}</div>`;
                }
                html += `</div>`; // Close message-bubble

                html += `
                    <div class="message-actions">
                        <button class="message-action-btn reply-btn"
                            onclick="handleReplyClick(${message.id}, '${message.sender_id}')" title="Reply">
                            <i class="fas fa-reply"></i>
                        </button>
                    </div>
                `;
                html += `</div>`; // Close message-row

                if (isCurrentUser) {
                    html += `
                        <div class="message-status-container">
                            ${(message.is_read === 1 || message.status === 'seen')
                                ? `<div class="seen-indicator" title="Seen"><i class="fas fa-check-circle"></i> Seen</div>`
                                : `<div class="sent-indicator" title="Sent"><i class="far fa-check-circle"></i> Sent</div>`
                            }
                        </div>
                    `;
                }

                messageDiv.innerHTML = html;
                chatMessages.appendChild(messageDiv);
                scrollToBottom();
            }

            window.handleReplyClick = function(messageId, senderId) {
                const el = document.querySelector(`.message[data-message-id="${messageId}"]`);
                if (!el) return;

                let content = 'Message';
                if (el.querySelector('.message-text')) content = el.querySelector('.message-text').textContent;
                else if (el.querySelector('.image-container')) content = "Photo";
                else if (el.querySelector('.file-card-name')) content = "File: " + el.querySelector('.file-card-name').textContent;

                const sender = el.querySelector('.message-sender')?.textContent || (senderId == userId ? 'You' : window.otherUser.nickname || window.otherUser.username);

                const replyContainer = document.getElementById('reply-container');
                const replyHeader = document.getElementById('reply-header-text');
                const replyBody = document.getElementById('reply-body-text');

                if (replyContainer && replyHeader && replyBody) {
                    replyHeader.textContent = `Replying to ${sender}`;
                    replyBody.textContent = content;
                    replyContainer.classList.remove('hidden');
                    const input = document.getElementById('message-input');
                    if (input) input.focus();
                }

                window.replyHandler?.startReply({ id: messageId, senderId, senderName: sender, content });
            }

            window.cancelReply = function() {
                const replyContainer = document.getElementById('reply-container');
                if (replyContainer) replyContainer.classList.add('hidden');
                window.replyHandler?.cancelReply();
            }

            function scrollToBottom() {
                const chatMessages = document.getElementById('chat-messages');
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
           
            function showTypingIndicator(username) {
                const typingIndicator = document.getElementById('typing-indicator');
                const typingText = document.getElementById('typing-text');
                typingText.textContent = `${username} is typing...`;
                typingIndicator.classList.remove('hidden');
            }
           
            function hideTypingIndicator() {
                document.getElementById('typing-indicator').classList.add('hidden');
            }
           
            function updateUserStatus(status) {
                const statusIndicator = document.getElementById('other-user-status');
                if (statusIndicator) {
                    statusIndicator.className = 'status-indicator ' + status;
                }
            }
           
            window.showNotification = function(type, title, message) {
                const toast = document.getElementById('notification-toast');
                const toastTitle = document.getElementById('notification-title');
                const toastMessage = document.getElementById('notification-message');
                const toastIcon = toast.querySelector('.notification-icon i');
               
                toastTitle.textContent = title;
                toastMessage.textContent = message;
                toast.className = 'notification-toast';
                toast.classList.add(type);
               
                if (type === 'success') toastIcon.className = 'fas fa-check-circle';
                else if (type === 'error') toastIcon.className = 'fas fa-exclamation-circle';
                else if (type === 'warning') toastIcon.className = 'fas fa-exclamation-triangle';
                else toastIcon.className = 'fas fa-info-circle';
               
                toast.classList.remove('hidden');
                setTimeout(() => { toast.classList.add('hidden'); }, 3000);
            }
           
            function playMessageSound() {
                const soundEnabled = document.getElementById('sound-notifications')?.checked ?? true;
                if (!soundEnabled) return;
                const audio = new Audio('<?= base_url('assets/sounds/message.mp3') ?>');
                audio.play().catch(error => { console.error('Error playing notification sound:', error); });
            }
           
            function initEmojiPicker() {
                const emojiGrid = document.getElementById('emoji-grid');
                const targetElement = emojiGrid || document.querySelector('.emoji-grid');
                if (!targetElement) return;
                
                const commonEmojis = [
                    '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇',
                    '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚',
                    '😋', '😛', '😝', '😜', '😜', '🤨', '🤨', '🧐', '🤓', '😎', '🤩',
                    '👍', '👎', '👏', '🙌', '👌', '✌️', '🤞', '🤟', '🤘', '👊',
                    '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '💔', '❣️', '💕'
                ];
               
                targetElement.innerHTML = ''; 
                commonEmojis.forEach(emoji => {
                    const emojiElement = document.createElement('div');
                    emojiElement.className = 'emoji';
                    emojiElement.textContent = emoji;
                    emojiElement.addEventListener('click', () => {
                        insertEmoji(emoji);
                    });
                    targetElement.appendChild(emojiElement);
                });
            }
           
            function insertEmoji(emoji) {
                const messageInput = document.getElementById('message-input');
                messageInput.value += emoji;
                messageInput.focus();
                document.getElementById('emoji-picker').classList.add('hidden');
            }
           
            document.getElementById('attach-btn').addEventListener('click', function() {
                document.getElementById('file-input').click();
            });
           
            document.getElementById('file-input').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;
                selectedFile = file;
                const messageInput = document.getElementById('message-input');
                messageInput.value = `File: ${file.name}`;
                messageInput.disabled = true;
                showNotification('info', 'File Selected', `${file.name} ready to send`);
            });
           
            document.getElementById('send-btn').addEventListener('click', sendMessage);
           
            document.getElementById('message-input').addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
           
            function sendMessage() {
                const messageInput = document.getElementById('message-input');
                const content = messageInput.value.trim();
                
                if (!content && !selectedFile) return;
                
                let type = 'text';
                if (selectedFile) type = selectedFile.type.startsWith('image/') ? 'image' : 'file';
                
                const formData = new FormData();
                formData.append('content', content);
                formData.append('is_group', 0);
                formData.append('receiver_id', otherUserId);
                formData.append('type', type);
                
                const activeReply = window.replyHandler?.getActiveReply();
                if (activeReply) {
                    formData.append('reply_to_id', activeReply.id);
                    formData.append('reply_to_sender_id', activeReply.senderId);
                    formData.append('reply_to_content', activeReply.content);
                    formData.append('reply_to_sender_name', activeReply.senderName);
                    window.replyHandler.cancelReply();
                }
                
                if (selectedFile) {
                    formData.append('file', selectedFile);
                    formData.append('original_filename', selectedFile.name);
                    formData.append('file_size', selectedFile.size);
                }
                
                const csrfToken = document.querySelector('input[name="<?= csrf_token() ?>"]')?.value;
                if (csrfToken) formData.append('<?= csrf_token() ?>', csrfToken);
                
                messageInput.value = '';
                messageInput.disabled = false;
                selectedFile = null;
                document.getElementById('file-input').value = '';
                
                cancelReply();
                
                fetch('<?= base_url('api/saveMessage') ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Failed to send message: ' + response.statusText);
                    return response.json();
                })
                .then(message => {
                    if (socket && socket.connected) {
                        socket.emit('new_message', message);
                    } else {
                        appendMessage(message);
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    showNotification('error', 'Error', 'Failed to send message');
                });
            }
           
            document.getElementById('message-input').addEventListener('input', function() {
                if (typingTimeout) clearTimeout(typingTimeout);
                if (socket && socket.connected) {
                    socket.emit('typing', {
                        user_id: userId,
                        receiver_id: otherUserId,
                        is_typing: 1,
                        is_group: 0,
                        username: username
                    });
                }
                typingTimeout = setTimeout(() => {
                    if (socket && socket.connected) {
                        socket.emit('typing', {
                            user_id: userId,
                            receiver_id: otherUserId,
                            is_typing: 0,
                            is_group: 0,
                            username: username
                        });
                    }
                }, 2000);
            });
           
            function initUI() {
                document.querySelectorAll('.nav-tab').forEach(tab => {
                    tab.addEventListener('click', function() {
                        const tabName = this.dataset.tab;
                        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                        document.querySelectorAll('.chats-content, .calls-content, .contacts-content, .notifications-content').forEach(content => {
                            content.style.display = 'none';
                        });
                        document.getElementById(`${tabName}-content`).style.display = 'block';
                    });
                });
               
                document.getElementById('status-dropdown-btn').addEventListener('click', function(e) {
                    e.stopPropagation();
                    document.getElementById('status-dropdown').classList.toggle('hidden');
                });
               
                document.addEventListener('click', function() {
                    document.getElementById('status-dropdown').classList.add('hidden');
                });
               
                document.querySelectorAll('#status-dropdown li').forEach(item => {
                    item.addEventListener('click', function() {
                        const status = this.dataset.status;
                        const statusIndicator = document.querySelector('.user-profile .status-indicator');
                        statusIndicator.className = 'status-indicator ' + status;
                        if (socket && socket.connected) {
                            socket.emit('status_change', { user_id: userId, status: status });
                        }
                        document.getElementById('status-dropdown').classList.add('hidden');
                    });
                });
               
                document.getElementById('emoji-btn').addEventListener('click', function() {
                    document.getElementById('emoji-picker').classList.toggle('hidden');
                });
               
                document.getElementById('settings-btn').addEventListener('click', function() {
                    document.getElementById('settings-modal').classList.remove('hidden');
                });
               
                document.querySelectorAll('.settings-tab').forEach(tab => {
                    tab.addEventListener('click', function() {
                        const tabName = this.dataset.tab;
                        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                        document.querySelectorAll('.settings-content').forEach(content => {
                            content.style.display = 'none';
                        });
                        document.getElementById(`${tabName}-settings`).style.display = 'block';
                    });
                });
               
                document.querySelectorAll('.theme-option').forEach(option => {
                    option.addEventListener('click', function() {
                        const theme = this.dataset.theme;
                        document.querySelectorAll('.theme-option').forEach(o => o.classList.remove('active'));
                        this.classList.add('active');
                        applyTheme(theme);
                    });
                });
               
                document.querySelectorAll('.close-btn, .close-modal').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.querySelectorAll('.modal').forEach(modal => modal.classList.add('hidden'));
                        document.getElementById('emoji-picker').classList.add('hidden');
                    });
                });
               
                initEmojiPicker();
            }
           
            function applyTheme(theme) {
                if (theme === 'light') {
                    document.body.classList.remove('dark-theme');
                    document.body.classList.add('light-theme');
                    currentTheme = 'light';
                } else if (theme === 'dark') {
                    document.body.classList.remove('light-theme');
                    document.body.classList.add('dark-theme');
                    currentTheme = 'dark';
                } else if (theme === 'system') {
                    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.body.classList.remove('light-theme');
                        document.body.classList.add('dark-theme');
                        currentTheme = 'dark';
                    } else {
                        document.body.classList.remove('dark-theme');
                        document.body.classList.add('light-theme');
                        currentTheme = 'light';
                    }
                }
            }
           
            connectSocket();
            loadUsers();
            loadGroups();
            initUI();
           
            window.activeChat = {
                id: otherUserId,
                type: 'private',
                name: '<?= $otherUser['nickname'] ?? $otherUser['username'] ?>'
            };
           
            setInterval(() => {
                if (!socket || !socket.connected) {
                    loadMessages();
                }
            }, 10000); 
            
            window.openImageViewer = function(src, filename) {
                const modal = document.createElement('div');
                modal.className = 'image-viewer-modal active';
                modal.innerHTML = `
                    <div class="image-viewer-content">
                        <button class="image-viewer-close">&times;</button>
                        <img src="${src}" alt="${filename || 'Image'}" class="image-viewer-img">
                        <a href="${src}" download="${filename || 'image'}" class="image-viewer-download">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                `;
                document.body.appendChild(modal);
                modal.querySelector('.image-viewer-close').addEventListener('click', () => { modal.remove(); });
                modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
            };
        });
    </script>
    <div id="notifications-content" class="tab-content" style="display: none;">
        <div class="notifications-header">
            <h3>Notifications</h3>
            <button id="clear-notifications-btn" class="btn btn-sm btn-outline-danger">Clear All</button>
        </div>
        <div id="notifications-list" class="notifications-list">
            </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof socket !== 'undefined' && socket) {
            window.socket = socket;
        }
        
        if (typeof window.notificationManager === 'undefined') {
            window.notificationManager = new NotificationManager();
        }
        
        window.openChatFromNotification = function(chatType, chatId) {
            if (chatType === "private") {
                window.location.href = `<?= base_url('chat/direct/') ?>${chatId}`;
            } else if (chatType === "group") {
                window.location.href = `<?= base_url('chat/group/') ?>${chatId}`;
            }
        };
        
        if (window.socket) {
            window.socket.on("receive_message", function(message) {
                if (window.notificationManager) {
                    const currentUserId = <?= session()->get('id') ?>;
                    const activeChatId = <?= $otherUser['id'] ?>;
                    window.notificationManager.processNewMessage(message, currentUserId, activeChatId, "private");
                }
            });
        }
    });
</script>

<script src="<?= base_url('js/notifications.js') ?>"></script>
<script src="<?= base_url('js/user-status.js') ?>"></script>
<script src="<?= base_url('js/status-sync.js') ?>"></script>
<div id="notifications-container" class="notifications-container"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        if (window.notificationManager) {
            window.notificationManager.setupSocketListeners();
            window.notificationManager.renderNotifications();
        } else if (typeof NotificationManager !== 'undefined') {
            window.notificationManager = new NotificationManager();
        } else {
            const script = document.createElement('script');
            script.src = '<?= base_url('js/notifications.js') ?>';
            script.onload = function() {
                if (typeof NotificationManager !== 'undefined') {
                    window.notificationManager = new NotificationManager();
                }
            };
            document.head.appendChild(script);
        }
    }, 1000);
});
</script>
<script src="<?= base_url('js/reply-handler.js') ?>"></script>
<script src="<?= base_url('js/sidebar-init.js') ?>"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  const statusBtn = document.getElementById("status-dropdown-btn");
  const statusDropdown = document.getElementById("status-dropdown");
  
  if (statusBtn && statusDropdown) {
    const newStatusBtn = statusBtn.cloneNode(true);
    statusBtn.parentNode.replaceChild(newStatusBtn, statusBtn);
    
    newStatusBtn.addEventListener("click", function(e) {
      e.preventDefault();
      e.stopPropagation();
      statusDropdown.classList.toggle("hidden");
    });
    
    const statusItems = document.querySelectorAll("#status-dropdown li");
    statusItems.forEach(item => {
      item.addEventListener("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const status = this.getAttribute("data-status");
        const statusText = document.getElementById("status-text");
        const statusIndicator = document.querySelector(".user-profile .status-indicator");
        
        if (statusText) statusText.textContent = this.textContent.trim();
        if (statusIndicator) statusIndicator.className = "status-indicator " + status;
        
        if (window.updateUserStatus) {
          window.updateUserStatus(status);
        } else {
          fetch("/api/updateUserStatus", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ status: status }),
          });
        }
        statusDropdown.classList.add("hidden");
      });
    });
    
    document.addEventListener("click", function(e) {
      if (!newStatusBtn.contains(e.target) && !statusDropdown.contains(e.target)) {
        statusDropdown.classList.add("hidden");
      }
    });
  }
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const serverMessages = <?= json_encode($messages ?? []) ?>;
    if (serverMessages && serverMessages.length > 0) {
        const chatMessages = document.getElementById("chat-messages");
        chatMessages.innerHTML = "";
        serverMessages.forEach(message => {
            if (typeof appendMessage === 'function') {
                appendMessage(message);
            }
        });
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});
</script>
<script src="<?= base_url('js/status-dropdown-fix.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof window.openImageViewer !== 'function') {
    const script = document.createElement('script');
    script.src = '<?= base_url('js/image-viewer.js') ?>';
    document.head.appendChild(script);
    script.onload = function() {
      if (typeof window.setupImageViewers === 'function') window.setupImageViewers();
    };
  } else {
    if (typeof window.setupImageViewers === 'function') window.setupImageViewers();
  }
  
  window.openImageViewer = window.openImageViewer || function(src, filename) {
    const modal = document.createElement('div');
    modal.className = 'image-viewer-modal active';
    modal.innerHTML = `
      <div class="image-viewer-content">
        <button class="image-viewer-close">&times;</button>
        <img src="${src}" alt="${filename || 'Image'}" class="image-viewer-img">
      </div>
    `;
    document.body.appendChild(modal);
    modal.querySelector('.image-viewer-close').addEventListener('click', function() {
      modal.remove();
      document.body.style.overflow = '';
    });
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        modal.remove();
        document.body.style.overflow = '';
      }
    });
    document.body.style.overflow = 'hidden';
  };
});

function updateMessageSeenStatus(messageId) {
    const messageElement = document.querySelector(`.message[data-message-id="${messageId}"]`);
    if (!messageElement) return;
    const messageStatus = messageElement.querySelector(".message-status-container"); 
    if (!messageStatus) return;
    messageStatus.innerHTML = `
        <div class="seen-indicator" title="Seen">
            <i class="fas fa-check-circle"></i> Seen
        </div>
    `;
}

socket.on('message_seen', function(data) {
    if (data.other_user_id == userId) {
        updateMessageSeenStatus(data.message_id);
    }
});

function trackVisibleMessages() {
    const chatMessages = document.getElementById('chat-messages');
    if (!chatMessages) return;
    const messageElements = chatMessages.querySelectorAll('.message:not(.own-message)');
    if (messageElements.length === 0) return;
    const containerRect = chatMessages.getBoundingClientRect();
    messageElements.forEach((message) => {
        const messageRect = message.getBoundingClientRect();
        const isVisible = messageRect.top < containerRect.bottom && messageRect.bottom > containerRect.top;
        if (isVisible && message.dataset.messageId && !message.classList.contains('seen-by-me')) {
            markMessageAsSeen(message.dataset.messageId);
            message.classList.add('seen-by-me'); 
        }
    });
}

function markMessageAsSeen(messageId) {
    const seenData = {
        message_id: messageId,
        user_id: userId,
        other_user_id: otherUserId,
        is_group: false
    };
    fetch('<?= base_url('api/markMessageAsSeen') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(seenData)
    })
    .catch(error => { console.error("Error marking message as seen via API:", error); });
    
    if (socket && socket.connected) {
        socket.emit('message_seen', seenData);
    }
}

setInterval(trackVisibleMessages, 2000);
chatMessages.addEventListener('scroll', trackVisibleMessages);
document.addEventListener('DOMContentLoaded', function() { setTimeout(trackVisibleMessages, 1000); });
</script>
<script>
function checkForUnseenMessages() {
    const chatMessages = document.getElementById('chat-messages');
    if (!chatMessages) return;
    const messageElements = chatMessages.querySelectorAll('.message:not(.own-message):not(.seen-by-me)');
    if (messageElements.length === 0) return;
    const containerRect = chatMessages.getBoundingClientRect();
    messageElements.forEach((message) => {
        const messageRect = message.getBoundingClientRect();
        const isVisible = messageRect.top < containerRect.bottom && messageRect.bottom > containerRect.top;
        if (isVisible && message.dataset.messageId) {
            markMessageAsSeen(message.dataset.messageId);
            message.classList.add('seen-by-me');
        }
    });
}
setInterval(checkForUnseenMessages, 1000);
document.getElementById('chat-messages').addEventListener('scroll', checkForUnseenMessages);
document.addEventListener('DOMContentLoaded', function() { setTimeout(checkForUnseenMessages, 1000); });
</script>
<script src="<?= base_url('js/performance-optimizations.js') ?>"></script>
<script src="<?= base_url('js/message-virtualization.js') ?>"></script>
<script src="<?= base_url('js/optimized-notifications.js') ?>"></script>
<script src="<?= base_url('js/batch-seen-handler.js') ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  if (window.performanceOptimizations) {
    window.performanceOptimizations.initPerformanceOptimizations();
  }
  
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          const src = img.getAttribute('data-src');
          if (src) {
            img.src = src;
            img.removeAttribute('data-src');
          }
          observer.unobserve(img);
        }
      });
    });
    document.querySelectorAll('img[data-src]').forEach(img => {
      imageObserver.observe(img);
    });
  }
  
  const notificationClose = document.getElementById('notification-close');
  if (notificationClose) {
    const newCloseBtn = notificationClose.cloneNode(true);
    notificationClose.parentNode.replaceChild(newCloseBtn, notificationClose);
    newCloseBtn.addEventListener('click', function(e) {
      e.preventDefault();
      requestAnimationFrame(() => {
        const toast = document.getElementById('notification-toast');
        if (toast) toast.classList.add('hidden');
      });
    });
  }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateMessageStatusVisibility() {
        const ownMessages = document.querySelectorAll('.message.own-message');
        if (ownMessages.length === 0) return;
        ownMessages.forEach(message => {
            const statusElement = message.querySelector('.message-status-container');
            if (statusElement) statusElement.style.display = 'none';
        });
        const lastMessage = ownMessages[ownMessages.length - 1];
        if (lastMessage) {
            const statusElement = lastMessage.querySelector('.message-status-container');
            if (statusElement) {
                statusElement.style.display = 'block'; 
                statusElement.style.display = 'flex';
            }
        }
    }
    
    setTimeout(updateMessageStatusVisibility, 1000);
    const chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        const observer = new MutationObserver(function(mutations) {
            updateMessageStatusVisibility();
        });
        observer.observe(chatMessages, { childList: true });
    }
    
    const originalAppendMessage = window.appendMessage;
    if (typeof originalAppendMessage === 'function') {
        window.appendMessage = function(message) {
            if (message.status === 'seen') message.is_read = 1;
            originalAppendMessage(message);
            updateMessageStatusVisibility();
        };
    }
});
</script>
<script src="<?= base_url('js/seen-avatars.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.currentUserId = <?= session()->get('id') ?>;
    window.userId = <?= session()->get('id') ?>;
    window.otherUserId = <?= $otherUser['id'] ?>;
    
    window.currentUser = {
        id: <?= session()->get('id') ?>,
        username: "<?= session()->get('username') ?>",
        nickname: "<?= session()->get('nickname') ?? session()->get('username') ?>",
        avatar: "<?= session()->get('avatar') ?? 'default-avatar.png' ?>"
    };
    
    window.otherUser = {
        id: <?= $otherUser['id'] ?>,
        username: "<?= $otherUser['username'] ?>",
        nickname: "<?= $otherUser['nickname'] ?? $otherUser['username'] ?>",
        avatar: "<?= $otherUser['avatar'] ?? 'default-avatar.png' ?>"
    };
    
    if (window.replyHandler) {
        window.replyHandler.currentUser = window.currentUser;
        window.replyHandler.otherUser = window.otherUser;
    }
});
</script>

</body>
</html>