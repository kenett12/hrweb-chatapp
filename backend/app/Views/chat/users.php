<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="content-container">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2>Users</h2>
                <p class="text-muted">Start a conversation with other users</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" onclick="globalGroupManager.showCreateGroupModal()">
                    <i class="bi bi-plus-circle"></i> Create Group
                </button>
            </div>
        </div>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php if (empty($users)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <p class="mb-0">No users found.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="col">
                        <div class="card user-card">
                            <div class="user-info">
                                <img src="public/uploads/avatars/<?= $user['avatar'] ?>" alt="<?= $user['nickname'] ?>" class="user-avatar">
                                <h5 class="card-title"><?= $user['nickname'] ?></h5>
                                <p class="card-text text-muted">@<?= $user['username'] ?></p>
                                <div class="user-status mb-2">
                                    <span class="online-indicator" id="user-status-<?= $user['id'] ?>"></span>
                                    <span class="status-text" id="user-status-text-<?= $user['id'] ?>">Offline</span>
                                </div>
                            </div>
                            <div class="user-actions">
                                <a href="/chat/direct/<?= $user['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-chat-dots"></i> Message
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    body {
        background-color: #f8f9fa;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    .content-container {
        flex: 1;
        padding: 20px;
    }
    .user-card {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
        border: none;
    }
    .user-card:hover {
        transform: translateY(-5px);
    }
    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin: 20px auto;
    }
    .user-info {
        padding: 15px;
        text-align: center;
    }
    .user-actions {
        padding: 10px;
        border-top: 1px solid #dee2e6;
        text-align: center;
    }
    .online-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #6c757d;
        margin-right: 5px;
    }
    .online-indicator.online {
        background-color: #28a745;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Connect to Socket.IO server
    const socket = io('http://localhost:3000', {
        withCredentials: true
    });
    
    // User data
    const currentUser = {
        id: <?= session()->get('id') ?>,
        username: '<?= session()->get('username') ?>',
        nickname: '<?= session()->get('nickname') ?>',
        avatar: '<?= session()->get('avatar') ?>'
    };
    
    // Initialize
    function init() {
        // Authenticate with Socket.IO server
        socket.emit('authenticate', currentUser);
        
        // Set up event listeners
        socket.on('active_users', handleActiveUsers);
        socket.on('user_status', handleUserStatus);
    }
    
    // Handle active users list
    function handleActiveUsers(users) {
        users.forEach(user => {
            updateUserStatus(user.id, true);
        });
    }
    
    // Handle user status update
    function handleUserStatus(statusData) {
        updateUserStatus(statusData.userId, statusData.status === 'online');
    }
    
    // Update user status indicator
    function updateUserStatus(userId, isOnline) {
        const statusIndicator = document.getElementById(`user-status-${userId}`);
        const statusText = document.getElementById(`user-status-text-${userId}`);
        
        if (statusIndicator && statusText) {
            if (isOnline) {
                statusIndicator.classList.add('online');
                statusText.textContent = 'Online';
            } else {
                statusIndicator.classList.remove('online');
                statusText.textContent = 'Offline';
            }
        }
    }
    
    // Initialize when page loads
    window.addEventListener('load', init);
</script>
<?= $this->endSection() ?>
