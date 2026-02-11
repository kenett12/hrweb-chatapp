<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark light">
    <meta name="theme-color" content="#2d2c2c">
    <title><?= $title ?? 'Chat Application' ?></title>
    
    <script>
        window.baseUrl = "<?= base_url() ?>/";
        window.currentUser = {
            id: <?= session()->get('id') ?? 'null' ?>,
            username: "<?= session()->get('username') ?? '' ?>",
            nickname: "<?= session()->get('nickname') ?? '' ?>",
            avatar: "<?= session()->get('avatar') ?? '' ?>",
            status: "<?= session()->get('status') ?? 'online' ?>",
            role: "<?= session()->get('role') ?? 'user' ?>"
        };
    </script>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('public/css/styles.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/css/sidebar.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/css/notifications.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/css/image-viewer.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/css/seen-indicator.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/css/group-modal.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/css/theme.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/css/file-attachments.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/css/call-modal.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/css/ticket-notifications.css') ?>">

    <?= $this->renderSection('styles') ?>

    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.7.2/socket.io.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= base_url('public/js/theme-manager.js') ?>"></script>
    <script src="<?= base_url('public/js/notification-handler.js') ?>"></script>

    <style>
        body { min-height: 100vh; display: flex; flex-direction: column; }
        .app-container { flex: 1; }
        .sidebar { height: calc(100vh - 56px); overflow-y: auto; }
        .avatar-xs { width: 24px; height: 24px; border-radius: 50%; object-fit: cover; }
        body.dark-theme .navbar-dark { background-color: #2d2c2c !important; }
        body.dark-theme .modal-content { background-color: #2d2c2c; color: #ffffff; }
        body.dark-theme .form-control { background-color: #3b3a39; color: #ffffff; border-color: #484644; }
    </style>
</head>

<body <?= session()->get('id') ? 'data-user-id="' . session()->get('id') . '"' : '' ?> class="dark-theme">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= base_url('chat') ?>">Chat App</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('chat') ?>">Home</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="app-container">
        <?= $this->renderSection('content') ?>
    </div>
    
    <script src="<?= base_url('public/js/notifications.js') ?>"></script>
    <?php if(session()->get('isLoggedIn')): ?>
        <script src="<?= base_url('public/js/socket-manager.js') ?>"></script>
    <?php endif; ?>
    <script src="<?= base_url('public/js/image-viewer.js') ?>"></script>

    <?= $this->renderSection('scripts') ?>
    
    <?= $this->include('partials/call_overlay') ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const initGlobalCallSystem = () => {
                if (window.socket && window.currentUser && window.currentUser.id) {
                    const registerUser = () => window.socket.emit('user_connected', window.currentUser.id);
                    if (window.socket.connected) registerUser();
                    window.socket.on('connect', registerUser);

                    window.socket.on('incoming_call', (data) => {
                        if (String(data.receiver_id) === String(window.currentUser.id)) {
                            if (typeof CallManager !== 'undefined') {
                                CallManager.showIncoming(data);
                                window.socket.emit('call_received', { caller_id: data.caller_id, receiver_id: window.currentUser.id });
                            }
                        }
                    });

                    window.socket.on('call_is_ringing', () => {
                        if (typeof CallManager !== 'undefined') CallManager.updateStatus("Ringing...");
                    });

                    window.socket.on('call_rejected', () => {
                        if (typeof CallManager !== 'undefined') CallManager.resetUI('Declined');
                    });

                    window.socket.on('call_ended', () => {
                        if (typeof CallManager !== 'undefined') CallManager.resetUI('Ended');
                    });
                } else {
                    setTimeout(initGlobalCallSystem, 500);
                }
            };
            initGlobalCallSystem();
        });
    </script>
</body>
</html>