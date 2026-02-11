<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?= session()->get('id') ?>">
    <meta name="theme-color" content="#121212">
    <title><?= esc($group['name']) ?> - Group Chat</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="<?= base_url('css/theme.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/styles.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/sidebar.css') ?>"> 
    <link rel="stylesheet" href="<?= base_url('css/call-modal.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/group-modal.css') ?>">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.socket.io/4.4.1/socket.io.min.js"></script>

    <style>
        :root {
            --v-bg-main: #121212;
            --v-bg-panel: #1e1e1e;
            --v-purple: #665cac;
            --v-purple-hover: #7b6fd0;
            --v-incoming-bg: #2a2a2a;
            --v-outgoing-bg: #665cac;
            --v-text-main: #ffffff;
            --v-text-sub: #a0a0a0;
            --v-border: #000000;
            --header-height: 60px;
        }

        * { 
            box-sizing: border-box; 
            outline: none; 
            margin: 0;
            padding: 0;
        }
        
        body { 
            font-family: 'Roboto', sans-serif; 
            background: var(--v-bg-main); 
            color: var(--v-text-main); 
            overflow: hidden; 
            height: 100vh; 
        }

        #app-container { 
            display: flex; 
            height: 100vh; 
            width: 100vw; 
            background: var(--v-bg-main);
        }

        .sidebar { 
            flex-shrink: 0; 
            width: 340px;
        }

        .main-content { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            background: var(--v-bg-main); 
            min-width: 0;
            height: 100%;
        }

        /* === HEADER === */
        .chat-header { 
            height: var(--header-height); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 0 16px; 
            background: var(--v-bg-panel); 
            border-bottom: 1px solid var(--v-border);
            z-index: 10; 
            flex-shrink: 0;
        }
        
        .header-info { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            min-width: 0;
            flex: 1;
        }
        
        .group-avatar { 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            background: var(--v-purple); 
            border: 2px solid var(--v-bg-panel);
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: bold; 
            font-size: 1rem; 
            color: white; 
            flex-shrink: 0;
            overflow: hidden;
        }

        .group-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .header-text { 
            min-width: 0;
            flex: 1;
        }

        .header-text h2 { 
            font-size: 0.95rem; 
            font-weight: 600; 
            margin: 0; 
            color: #fff; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }

        .header-text p { 
            font-size: 0.75rem; 
            color: var(--v-purple); 
            margin: 2px 0 0 0; 
        }

        .header-actions { 
            display: flex; 
            gap: 6px; 
            flex-shrink: 0;
        }

        .btn-icon { 
            background: transparent; 
            border: none; 
            color: var(--v-text-sub); 
            width: 36px; 
            height: 36px; 
            border-radius: 50%; 
            cursor: pointer; 
            font-size: 1rem; 
            transition: 0.2s; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }

        .btn-icon:hover { 
            background: rgba(255,255,255,0.08); 
            color: var(--v-purple); 
        }

        .btn-icon.danger:hover { 
            color: #ff4d4d; 
        }

        /* === CHAT AREA === */
        .chat-interface { 
            display: flex; 
            flex-direction: column; 
            flex: 1;
            background-color: var(--v-bg-main);
            background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 24px 24px;
            min-height: 0;
        }

        .chat-messages { 
            flex: 1; 
            overflow-y: auto; 
            overflow-x: hidden;
            padding: 18px 28px; 
            display: flex; 
            flex-direction: column; 
            gap: 3px;
            min-height: 0;
        }
        
        .chat-messages::-webkit-scrollbar { 
            width: 6px; 
        }

        .chat-messages::-webkit-scrollbar-thumb { 
            background: #333; 
            border-radius: 3px; 
        }

        .chat-messages::-webkit-scrollbar-track { 
            background: transparent; 
        }

        /* === MESSAGES === */
        .message { 
            display: flex; 
            flex-direction: column; 
            max-width: 68%; 
            min-width: 0;
            margin-bottom: 10px; 
            position: relative;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn { 
            from { 
                opacity: 0; 
                transform: translateY(5px); 
            } 
            to { 
                opacity: 1; 
                transform: translateY(0); 
            } 
        }

        .message-row { 
            display: flex; 
            align-items: flex-end; 
            gap: 9px; 
            max-width: 100%;
            min-width: 0;
        }

        .msg-avatar { 
            width: 30px; 
            height: 30px; 
            border-radius: 50%; 
            object-fit: cover; 
            margin-bottom: 2px; 
            flex-shrink: 0; 
            border: 1px solid #333;
        }

        .bubble {
            padding: 9px 13px;
            position: relative;
            font-size: 0.92rem;
            line-height: 1.42;
            box-shadow: 0 1px 2px rgba(0,0,0,0.3);
            word-wrap: break-word;
            word-break: break-word;
            max-width: 100%;
            min-width: 0;
        }

        /* Incoming */
        .message:not(.own) { 
            align-self: flex-start; 
        }

        .message:not(.own) .message-row { 
            flex-direction: row; 
        }

        .message:not(.own) .bubble { 
            background: var(--v-incoming-bg); 
            color: var(--v-text-main); 
            border-radius: 12px 12px 12px 0;
        }

        .message:not(.own) .bubble::before { 
            content: ""; 
            position: absolute; 
            bottom: 0; 
            left: -8px;
            width: 10px; 
            height: 10px;
            background: radial-gradient(circle at top left, transparent 65%, var(--v-incoming-bg) 65%);
        }

        .sender-name { 
            font-size: 0.72rem; 
            color: var(--v-purple); 
            font-weight: 600; 
            margin-bottom: 3px; 
        }

        /* Outgoing */
        .message.own { 
            align-self: flex-end; 
        }

        .message.own .message-row { 
            flex-direction: row-reverse; 
        }

        .message.own .bubble { 
            background: var(--v-outgoing-bg); 
            color: #fff; 
            border-radius: 12px 12px 0 12px;
        }

        .message.own .bubble::before { 
            content: ""; 
            position: absolute; 
            bottom: 0; 
            right: -8px;
            width: 10px; 
            height: 10px;
            background: radial-gradient(circle at top right, transparent 65%, var(--v-outgoing-bg) 65%);
        }

        /* Meta */
        .meta{
            display: flex; 
            align-items: center; 
            justify-content: flex-end; 
            gap: 4px;
            margin-top: 3px;
            float: right;
            padding-left: 8px;
            font-size: 0.65rem;
        }

        .time { 
            color: rgba(255,255,255,0.55); 
        }

        .checks { 
            font-weight: bold; 
            letter-spacing: -2px; 
        }

        .checks.seen { 
            color: #fff; 
        }

        .checks.sent { 
            color: rgba(255,255,255,0.45); 
        }

        /* Reply */
        .reply-block{
            border-left: 2px solid rgba(255,255,255,0.3);
            padding-left: 7px;
            margin-bottom: 4px;
            background: rgba(0,0,0,0.1);
            border-radius: 3px;
            padding: 4px 7px;
            font-size: 0.72rem;
        }

        .reply-sender{
            font-weight: bold;
            margin-bottom: 1px;
        }

        .reply-content{
            opacity: 0.8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.8rem;
        }

        /* System Messages */
        .system-msg { 
            width: 100%; 
            text-align: center; 
            margin: 12px 0; 
            opacity: 0.8; 
        }

        .system-msg span { 
            font-size: 0.72rem; 
            color: #aaa; 
            background: rgba(255,255,255,0.05); 
            padding: 5px 14px; 
            border-radius: 20px; 
        }

        /* === IMAGES & FILES === */
        .image-container {
            max-width: 300px;
            width: 100%;
            border-radius: 7px;
            overflow: hidden;
            cursor: pointer;
            margin-top: 4px;
        }

        .message-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .file-card {
            background: rgba(0,0,0,0.2); 
            border-radius: 7px; 
            padding: 9px;
            display: flex; 
            align-items: center; 
            gap: 11px; 
            margin-top: 4px;
            border: 1px solid rgba(255,255,255,0.1); 
            text-decoration: none; 
            color: inherit;
            max-width: 300px;
            transition: all 0.2s;
            font-size: 0.87rem;
        }

        .file-card:hover {
            background: rgba(0,0,0,0.3);
            border-color: rgba(255,255,255,0.2);
        }

        .file-icon { 
            width: 38px; 
            height: 38px; 
            background: rgba(255,255,255,0.1); 
            border-radius: 6px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: #fff; 
            flex-shrink: 0; 
            font-size: 1.05rem;
        }

        .file-info { 
            flex: 1; 
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .file-name { 
            font-size: 0.82rem; 
            font-weight: 500; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }

        .file-meta { 
            font-size: 0.72rem; 
            opacity: 0.65; 
        }

        /* === TYPING === */
        .typing { 
            font-size: 0.72rem; 
            color: var(--v-purple); 
            padding: 0 28px 8px; 
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
            height: 24px;
        }

        .typing-dots{
            display: flex;
            gap: 3px;
        }

        .typing-dot {
            width: 4px;
            height: 4px;
            background: var(--v-purple);
            border-radius: 50%;
            animation: bounce 1.4s infinite;
        }

        .typing-dot:nth-child(1) { animation-delay: 0s; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes bounce {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
            30% { transform: translateY(-5px); opacity: 1; }
        }

        /* === COMPOSER === */
        .composer { 
            background: var(--v-bg-panel); 
            padding: 12px 16px; 
            display: flex; 
            align-items: flex-end; 
            gap: 10px; 
            border-top: 1px solid var(--v-border);
            z-index: 20; 
            position: relative;
            flex-shrink: 0;
            min-height: 60px;
        }

        .reply-box {
            position: absolute; 
            bottom: 100%; 
            left: 0; 
            right: 0;
            width: 100%; 
            background: rgba(30, 30, 30, 0.95); 
            backdrop-filter: blur(5px);
            border-top: 1px solid #333; 
            padding: 9px 16px;
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            z-index: 5;
        }

        .reply-content { 
            border-left: 2px solid var(--v-purple); 
            padding-left: 9px; 
            flex: 1; 
            min-width: 0; 
        }

        .reply-to { 
            color: var(--v-purple); 
            font-size: 0.76rem; 
            font-weight: bold; 
        }

        .reply-text { 
            font-size: 0.82rem; 
            color: #ccc; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }

        .composer-actions { 
            display: flex; 
            gap: 2px; 
            flex-shrink: 0;
        }

        .action-btn { 
            color: var(--v-text-sub); 
            background: none; 
            border: none;
            width: 36px; 
            height: 36px; 
            border-radius: 50%; 
            font-size: 1rem;
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            transition: 0.2s;
            flex-shrink: 0;
        }

        .action-btn:hover { 
            color: var(--v-purple); 
            background: rgba(255,255,255,0.05); 
        }

        .input-wrapper { 
            flex: 1; 
            background: #000; 
            border-radius: 22px; 
            border: 1px solid #333; 
            padding: 3px 14px; 
            display: flex; 
            align-items: center; 
            transition: border 0.2s;
            min-width: 100px;
            height: 36px;
        }

        .input-wrapper:focus-within { 
            border-color: var(--v-purple); 
        }
        
        .input-wrapper {
    display: flex;
    align-items: center;
    width: 100%;
}

#msg-input {
    width: 100%;
    background: transparent;
    border: none;
    color: #fff;
    resize: none;

    font-family: inherit;
    font-size: 0.88rem;

    line-height: 20px;   /* text height */
    height: 20px;        /* SAME as line-height */
    min-height: 20px;
    max-height: 90px;

    padding: 0 4px;      /* remove vertical padding */
    overflow-y: auto;
}

#msg-input::placeholder {
    color: #555;
}


        .send-btn { 
            width: 36px; 
            height: 36px; 
            border-radius: 50%; 
            border: none;
            background: transparent; 
            color: var(--v-purple); 
            font-size: 1.05rem; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            transition: 0.2s;
            flex-shrink: 0;
        }

        .send-btn:hover { 
            background: var(--v-purple); 
            color: #fff; 
        }

        .send-btn:active {
            transform: scale(0.95);
        }

        /* === EMOJI PICKER === */
        .emoji-picker {
            position: fixed; 
            bottom: 78px; 
            left: 360px; 
            background: #2a2a2a; 
            border: 1px solid #444; 
            border-radius: 8px; 
            width: 270px; 
            max-height: 300px; 
            z-index: 100; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); 
            display: flex; 
            flex-direction: column;
        }

        #emoji-grid {
            display: grid; 
            grid-template-columns: repeat(8,1fr); 
            padding: 9px; 
            gap: 5px; 
            overflow-y: auto; 
            flex: 1;
        }

        .emoji-item {
            cursor: pointer;
            font-size: 1.15rem;
            text-align: center;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
            user-select: none;
        }

        .emoji-item:hover {
            background: rgba(255,255,255,0.1);
            transform: scale(1.2);
        }

        /* === IMAGE VIEWER === */
        .img-viewer { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.95); 
            z-index: 9999; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }

        .img-viewer img { 
            max-width: 90%; 
            max-height: 90vh; 
            border-radius: 4px; 
            box-shadow: 0 0 50px rgba(0,0,0,0.5); 
        }

        .img-close { 
            position: absolute; 
            top: 20px; 
            right: 30px; 
            font-size: 1.8rem; 
            color: #fff; 
            cursor: pointer;
            background: rgba(255,255,255,0.1);
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .img-close:hover {
            background: rgba(255,255,255,0.2);
        }

        /* === UTILITY === */
        .hidden { 
            display: none !important; 
        }

        .reply-btn {
            opacity: 0;
            transition: opacity 0.2s;
            cursor: pointer;
            flex-shrink: 0;
            color: #666;
            font-size: 0.92rem;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 1024px) {
            .sidebar { 
                width: 280px;
            }

            .emoji-picker {
                left: 300px;
                width: 250px;
            }
        }
    </style>

    <script>
        window.socketUrl = "http://localhost:3001";
        window.userId = <?= session()->get('id') ?>;
        window.groupId = <?= $group['id'] ?>;
    </script>
</head>

<body>
    <div id="app-container">
        <?= $this->include('partials/sidebar') ?>
        
        <div class="main-content">
            <div class="chat-header">
                <div class="header-info">
                    <div class="group-avatar">
                        <?php if(!empty($group['image'])): ?>
                            <img src="<?= base_url('uploads/groups/' . $group['image']) ?>">
                        <?php else: ?>
                            <?= strtoupper(substr($group['name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="header-text">
                        <h2><?= esc($group['name']) ?></h2>
                        <p><?= isset($group['member_count']) ? $group['member_count'] . ' participants' : 'Group' ?></p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn-icon" id="audio-call-btn" title="Audio Call"><i class="fas fa-phone-alt"></i></button>
                    <button class="btn-icon" id="video-call-btn" title="Video"><i class="fas fa-video"></i></button>
                    <button class="btn-icon" title="Info"><i class="fas fa-info-circle"></i></button>
                    <button class="btn-icon danger" id="leave-group-btn" title="Leave"><i class="fas fa-sign-out-alt"></i></button>
                </div>
            </div>
            
            <div class="chat-interface">
                <div class="chat-messages" id="chat-messages">
                    <div style="text-align:center; padding:40px; color:#555;">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>
                
                <div id="typing-indicator" class="typing hidden">
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                    <span id="typing-text">typing...</span>
                </div>

                <div class="composer">
                    <div id="reply-box" class="reply-box hidden">
                        <div class="reply-content">
                            <div class="reply-to" id="reply-sender">User</div>
                            <div class="reply-text" id="reply-text">Message</div>
                        </div>
                        <button onclick="cancelReply()" style="background:none; border:none; color:#666; cursor:pointer; font-size:1.1rem; width:24px; height:24px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">&times;</button>
                    </div>

                    <div class="composer-actions">
                        <button class="action-btn" onclick="document.getElementById('file-input').click()" title="Attach"><i class="fas fa-paperclip"></i></button>
                        <input type="file" id="file-input" class="hidden">
                        <button class="action-btn" id="emoji-btn" title="Emoji"><i class="far fa-smile"></i></button>
                    </div>
                    
                    <div class="input-wrapper">
                        <textarea id="msg-input" placeholder="Type a message..."></textarea>
                    </div>
                    
                    <button class="send-btn" id="send-btn"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="emoji-picker hidden" id="emoji-picker">
        <div id="emoji-grid"></div>
    </div>

    <div class="call-modal hidden" id="call-modal">
        <div class="call-glass-overlay"></div>
        <div class="call-modal-content">
            <div class="call-info-overlay">
                <div class="caller-avatar-wrapper">
                    <img src="<?= base_url('uploads/groups/' . ($group['image'] ?? 'default-group.png')) ?>" class="caller-img">
                    <div class="pulse-ring"></div>
                </div>
                <h2><?= esc($group['name']) ?></h2>
                <p id="call-status-text">Calling group members...</p>
            </div>
            <div class="call-controls-floating">
                <button id="call-end" style="width:50px; height:50px; border-radius:50%; background:#ef4444; border:none; color:white; font-size:1.2rem; cursor:pointer;"><i class="fas fa-phone-slash"></i></button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userId = <?= session()->get('id') ?>;
            const groupId = <?= $group['id'] ?>;
            const socketUrl = "http://localhost:3001";
            let socket;
            let selectedFile = null;
            let typingTimeout = null;
            let replyData = null;

            // === EMOJI PICKER ===
            const emojis = ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','👍','👎','❤️','💜','🔥','🎉','👋','🙏','👀','🧠','✨'];
            const grid = document.getElementById('emoji-grid');
            emojis.forEach(e => {
                const el = document.createElement('div');
                el.className = 'emoji-item';
                el.textContent = e;
                el.onclick = () => {
                    const input = document.getElementById('msg-input');
                    input.value += e;
                    input.focus();
                    document.getElementById('emoji-picker').classList.add('hidden');
                };
                grid.appendChild(el);
            });

            document.getElementById('emoji-btn').onclick = (e) => {
                e.stopPropagation();
                document.getElementById('emoji-picker').classList.toggle('hidden');
            };

            document.addEventListener('click', (e) => {
                if (!e.target.closest('#emoji-picker') && !e.target.closest('#emoji-btn')) {
                    document.getElementById('emoji-picker').classList.add('hidden');
                }
            });

            // === SOCKET ===
            function connectSocket() {
                socket = io(socketUrl, { path: "/socket.io", transports: ['websocket'] });
                
                socket.on('connect', () => {
                    socket.emit('user_connected', userId);
                    socket.emit('join_group', groupId);
                    loadMessages();
                });

                socket.on('receive_message', (msg) => {
                    if (msg.is_group && msg.group_id == groupId) {
                        appendMessage(msg);
                        if(msg.sender_id != userId) {
                            new Audio('<?= base_url('assets/sounds/message.mp3') ?>').play().catch(()=>{});
                        }
                    }
                });

                socket.on('typing', (data) => {
                    if(data.group_id == groupId && data.user_id != userId) {
                        const div = document.getElementById('typing-indicator');
                        if(data.is_typing) {
                            document.getElementById('typing-text').textContent = `${data.username} is typing...`;
                            div.classList.remove('hidden');
                        } else {
                            div.classList.add('hidden');
                        }
                    }
                });
            }

            // === MESSAGES ===
            function loadMessages() {
                const container = document.getElementById('chat-messages');
                fetch(`<?= base_url('api/getGroupMessages') ?>/${groupId}`)
                    .then(r => r.json())
                    .then(msgs => {
                        container.innerHTML = '';
                        if(msgs.length === 0) container.innerHTML = '<div class="system-msg"><span>No messages yet</span></div>';
                        msgs.forEach(appendMessage);
                        scrollToBottom();
                    })
                    .catch(e => container.innerHTML = '<div style="text-align:center;color:#ef4444;font-size:0.9rem">Failed to load</div>');
            }

            function appendMessage(msg) {
                const container = document.getElementById('chat-messages');
                if(document.querySelector(`[data-id="${msg.id}"]`)) return;

                if (msg.type === 'system') {
                    container.innerHTML += `<div class="system-msg" data-id="${msg.id}"><span>${msg.content}</span></div>`;
                    scrollToBottom();
                    return;
                }

                const isMe = msg.sender_id == userId;
                const time = new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                const checkIcon = isMe ? `<span class="checks ${msg.is_read ? 'seen' : 'sent'}">✓✓</span>` : '';

                let content = `<div class="message-text">${msg.content}</div>`;
                
                if(msg.type === 'image') {
                    const src = `<?= base_url('uploads/messages/') ?>${msg.file_url || msg.content}`;
                    content = `<div class="image-container" onclick="viewImage('${src}')"><img src="${src}" class="message-image"></div>`;
                } 
                else if(msg.type === 'file') {
                    const fname = msg.original_filename || 'Attachment';
                    content = `<a href="<?= base_url('uploads/messages/') ?>${msg.file_url || msg.content}" target="_blank" class="file-card">
                        <div class="file-icon"><i class="fas fa-file"></i></div>
                        <div class="file-info">
                            <div class="file-name">${fname}</div>
                            <div class="file-meta">Download</div>
                        </div>
                    </a>`;
                }

                let replyHtml = '';
                if(msg.reply_to_id) {
                    replyHtml = `<div class="reply-block">
                        <div class="reply-sender" style="color:${isMe ? '#eee' : 'var(--v-purple)'}">${msg.reply_to_sender_name}</div>
                        <div class="reply-content">${msg.reply_to_content || 'Message'}</div>
                    </div>`;
                }

                const html = `<div class="message ${isMe ? 'own' : ''}" data-id="${msg.id}">
                    <div class="message-row">
                        ${!isMe ? `<img src="<?= base_url('uploads/avatars/') ?>${msg.avatar||'default-avatar.png'}" class="msg-avatar">` : ''}
                        <div class="bubble">
                            ${!isMe ? `<div class="sender-name">${msg.nickname||msg.username}</div>` : ''}
                            ${replyHtml}
                            ${content}
                            <div class="meta"><span class="time">${time}</span> ${checkIcon}</div>
                        </div>
                        <div class="reply-btn" onclick="reply('${msg.id}', '${msg.nickname||msg.username}', '${msg.content.replace(/'/g, "\\'")}')">
                            <i class="fas fa-reply"></i>
                        </div>
                    </div>
                </div>`;

                container.insertAdjacentHTML('beforeend', html);
                const lastMsg = container.lastElementChild;
                lastMsg.onmouseenter = () => lastMsg.querySelector('.reply-btn').style.opacity = '1';
                lastMsg.onmouseleave = () => lastMsg.querySelector('.reply-btn').style.opacity = '0';
                scrollToBottom();
            }

            function scrollToBottom() {
                const c = document.getElementById('chat-messages');
                c.scrollTop = c.scrollHeight;
            }

            // === SEND ===
            document.getElementById('send-btn').onclick = sendMessage;
            document.getElementById('msg-input').onkeydown = (e) => {
                if(e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
            };
            
            document.getElementById('msg-input').oninput = () => {
                if(typingTimeout) clearTimeout(typingTimeout);
                socket.emit('typing', { group_id: groupId, user_id: userId, username: '<?= session()->get('username') ?>', is_typing: true });
                typingTimeout = setTimeout(() => socket.emit('typing', { group_id: groupId, user_id: userId, is_typing: false }), 1500);
            };

            document.getElementById('file-input').onchange = (e) => {
                selectedFile = e.target.files[0];
            };

            function sendMessage() {
                const input = document.getElementById('msg-input');
                const text = input.value.trim();
                if(!text && !selectedFile) return;

                const formData = new FormData();
                formData.append('content', text);
                formData.append('group_id', groupId);
                formData.append('is_group', 1);
                
                if(selectedFile) {
                    formData.append('file', selectedFile);
                    formData.append('type', selectedFile.type.startsWith('image') ? 'image' : 'file');
                    formData.append('original_filename', selectedFile.name);
                } else {
                    formData.append('type', 'text');
                }

                if(replyData) {
                    formData.append('reply_to_id', replyData.id);
                    formData.append('reply_to_sender_name', replyData.sender);
                    formData.append('reply_to_content', replyData.content);
                }

                input.value = '';
                selectedFile = null;
                cancelReply();

                fetch('<?= base_url('api/saveMessage') ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(msg => {
                    socket.emit('new_message', msg);
                    appendMessage(msg);
                });
            }

            // === REPLY ===
            window.reply = (id, sender, text) => {
                replyData = { id, sender, content: text };
                const box = document.getElementById('reply-box');
                box.classList.remove('hidden');
                document.getElementById('reply-sender').textContent = sender;
                document.getElementById('reply-text').textContent = text.substring(0, 45);
                document.getElementById('msg-input').focus();
            };
            
            window.cancelReply = () => {
                replyData = null;
                document.getElementById('reply-box').classList.add('hidden');
            };

            // === IMAGE VIEWER ===
            window.viewImage = (src) => {
                const div = document.createElement('div');
                div.className = 'img-viewer';
                div.innerHTML = `<button class="img-close" onclick="this.parentElement.remove()">&times;</button><img src="${src}">`;
                document.body.appendChild(div);
            };

            // === CALLS ===
            document.getElementById('audio-call-btn').onclick = () => document.getElementById('call-modal').classList.remove('hidden');
            document.getElementById('video-call-btn').onclick = () => document.getElementById('call-modal').classList.remove('hidden');
            document.getElementById('call-end').onclick = () => document.getElementById('call-modal').classList.add('hidden');
            
            document.getElementById('leave-group-btn').onclick = () => {
                if(confirm('Leave group?')) {
                    fetch(`<?= base_url('api/leaveGroup') ?>/${groupId}`, {method:'POST'})
                    .then(() => window.location.href = '<?= base_url('chat') ?>');
                }
            };

            connectSocket();
        });
    </script>
</body>
</html>