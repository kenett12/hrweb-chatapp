<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audio Call</title>
    <script src="https://cdn.socket.io/4.4.1/socket.io.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            color: white; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .avatar { 
            width: 120px; 
            height: 120px; 
            border-radius: 50%; 
            border: 4px solid #2d2d44; 
            object-fit: cover; 
            margin-bottom: 20px; 
            animation: pulse 2s infinite;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        .user-name { 
            font-size: 1.5rem; 
            font-weight: 600; 
            margin-bottom: 8px; 
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .status { 
            font-size: 0.9rem; 
            color: #a3a3a3; 
            margin-bottom: 40px; 
        }
        .controls { 
            display: flex; 
            gap: 32px; 
        }
        .btn { 
            width: 64px; 
            height: 64px; 
            border-radius: 50%; 
            border: none; 
            font-size: 1.4rem; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
        }
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn:hover::before {
            width: 100%;
            height: 100%;
        }
        .btn:hover { 
            transform: scale(1.1); 
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }
        .btn:active {
            transform: scale(0.95);
        }
        .btn i {
            position: relative;
            z-index: 1;
        }
        .btn-mute { 
            background: linear-gradient(135deg, #3b3b3b 0%, #2a2a2a 100%);
        }
        .btn-mute:hover {
            background: linear-gradient(135deg, #4a4a4a 0%, #3a3a3a 100%);
        }
        .btn-mute.muted {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .btn-end { 
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .btn-end:hover {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
        }
        @keyframes pulse { 
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); } 
            70% { box-shadow: 0 0 0 20px rgba(59, 130, 246, 0); } 
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); } 
        }
    </style>
</head>
<body>
    <img src="<?= base_url('public/uploads/avatars/' . ($receiver['avatar'] ?? 'default-avatar.png')) ?>" class="avatar">
    <div class="user-name"><?= esc($receiver['nickname'] ?? $receiver['username']) ?></div>
    <div class="status" id="status-text">Connecting...</div>

    <div class="controls">
        <button class="btn btn-mute" id="muteBtn"><i class="fas fa-microphone"></i></button>
        <button class="btn btn-end" onclick="window.close()"><i class="fas fa-phone-slash"></i></button>
    </div>

    <script>
        const socketUrl = "http://localhost:3001";
        const userId = <?= $user_id ?>;
        const receiverId = <?= $receiver['id'] ?>;
        let socket;
        let isMuted = false;

        // Mute toggle functionality
        const muteBtn = document.getElementById('muteBtn');
        muteBtn.addEventListener('click', () => {
            isMuted = !isMuted;
            muteBtn.classList.toggle('muted');
            muteBtn.querySelector('i').className = isMuted ? 'fas fa-microphone-slash' : 'fas fa-microphone';
        });

        // 1. Mic Permission Check
        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(stream => {
                document.getElementById('status-text').innerText = "Calling...";
                initSocket();
            })
            .catch(err => {
                document.getElementById('status-text').innerText = "Microphone Access Denied";
                document.getElementById('status-text').style.color = "#ef4444";
            });

        function initSocket() {
            try {
                socket = io(socketUrl, { path: "/socket.io", transports: ['websocket'] });
                
                socket.on('connect', () => {
                    console.log("Call Window Connected to Socket");
                    // We just emit 'user_connected' so the server knows we are alive
                    socket.emit('user_connected', userId);
                });

            } catch(e) {
                console.error(e);
            }
        }
    </script>
</body>
</html>