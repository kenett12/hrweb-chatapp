<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar - Modern</title>
</head>
<body>

<div class="sidebar-wrapper">
    <link rel="stylesheet" href="<?= base_url('css/group-modal.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/ticket-sidebar.css') ?>">
    <script src="https://cdn.socket.io/4.4.1/socket.io.min.js"></script>

    <?php
    $db = \Config\Database::connect();
    if ($db->tableExists('ticket_categories')) {
        $categories = $db->table('ticket_categories')
                         ->where('is_active', 1)
                         ->get()
                         ->getResultArray();
    } else {
        $categories = []; 
    }
    ?>
    
    <script>
    window.baseUrl = '<?= base_url() ?>';
    if (!window.baseUrl.endsWith('/')) window.baseUrl += '/';
    window.userId = '<?= session()->get('id') ?>';
    window.userRole = '<?= session()->get('role') ?>';
    window.socketUrl = "http://localhost:3001"; 

    if (!window.socket) {
        try {
            window.socket = io(window.socketUrl, { 
                path: "/socket.io", 
                transports: ['websocket']
            });
            window.socket.on('connect', () => {
                if(window.userId) window.socket.emit('user_connected', window.userId);
            });
        } catch (e) { console.error("Socket init failed:", e); }
    }
    </script>

    <div class="nav-rail">
        <div class="nav-rail-avatar">
            <div class="avatar-container">
                <img src="<?= base_url('uploads/avatars/' . (session()->get('avatar') ?? 'default-avatar.png')) ?>" class="user-avatar">
                <span class="status-indicator <?= session()->get('status') ?? 'online' ?>"></span>
            </div>
        </div>

        <div class="nav-rail-items">
            <button class="nav-rail-btn active" data-tab="chats" title="Chats">
                <i class="fas fa-comment-dots"></i>
            </button>
            <button class="nav-rail-btn" data-tab="calls" title="Calls">
                <i class="fas fa-phone-alt"></i>
            </button>
            
            <?php if (session()->get('role') === 'tsr'): ?>
                <a href="<?= base_url('tsr/dashboard') ?>" class="nav-rail-btn" title="Dashboard">
                    <i class="fas fa-chart-line"></i>
                </a>
            <?php endif; ?>

            <button class="nav-rail-btn" data-tab="ticketing" title="Tickets">
                <i class="fas fa-headset"></i>
            </button>
            <button class="nav-rail-btn" data-tab="contacts" title="Contacts">
                <i class="fas fa-address-book"></i>
            </button>
            <button class="nav-rail-btn" data-tab="notifications" title="Notifications">
                <i class="fas fa-bell"></i>
                <span id="notifications-badge" class="nav-badge hidden">0</span>
            </button>
        </div>

        <div class="nav-rail-actions">
            <button id="settings-btn-action" class="nav-rail-btn" title="Settings" 
                    onclick="document.getElementById('settings-overlay').classList.remove('hidden');">
                <i class="fas fa-cog"></i>
            </button>
            
            <button id="edit-profile-btn-action" class="nav-rail-btn" title="Edit Profile"
                    onclick="document.getElementById('edit-profile-modal').classList.remove('hidden');">
                <i class="fas fa-user-edit"></i>
            </button>
            
            <a href="<?= base_url('/logout') ?>" class="nav-rail-btn" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <div class="content-panel">
        <div class="content-header">
            <div class="header-top">
                <h2 class="header-title" id="active-tab-title">Chats</h2>
                <button id="create-ticket-btn" class="header-action-btn hidden" title="Create ticket">
                    <i class="fas fa-plus"></i>
                </button>
                <button id="new-chat-btn" class="header-action-btn" title="New chat">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" placeholder="Search...">
            </div>
        </div>

        <div class="status-bar" data-user-id="<?= session()->get('id') ?>">
            <div class="status-info">
                <span class="status-dot <?= session()->get('status') ?? 'online' ?>"></span>
                <span class="status-username"><?= session()->get('nickname') ?? session()->get('username') ?></span>
            </div>
            <button id="status-dropdown-btn" class="status-toggle">
                <span id="status-text"><?= ucfirst(session()->get('status') ?? 'online') ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <ul id="status-dropdown" class="status-dropdown hidden">
                <li data-status="online"><span class="status-dot online"></span> Online</li>
                <li data-status="away"><span class="status-dot away"></span> Away</li>
                <li data-status="busy"><span class="status-dot busy"></span> Busy</li>
                <li data-status="offline"><span class="status-dot offline"></span> Offline</li>
            </ul>
        </div>

        <div class="tab-content-area">
            <div class="tab-pane active" id="chats-content">
                <div class="content-section">
                    <div class="section-header">
                        <h3>Direct</h3>
                        <button class="section-action-btn" title="New chat">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div id="direct-messages-list" class="content-list">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-comment-dots"></i>
                            </div>
                            <h4>No messages yet</h4>
                            <p>Start a conversation</p>
                        </div>
                    </div>
                </div>

                <div class="content-section">
                    <div class="section-header">
                        <h3>Groups</h3>
                        <button id="create-group-btn" class="section-action-btn" title="Create group">
                            <i class="fas fa-users"></i>
                        </button>
                    </div>
                    <div id="groups-list" class="content-list">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h4>No groups</h4>
                            <p>Create or join a group</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane" id="calls-content">
                <div id="calls-list" class="content-list">
                    <div class="empty-state large">
                        <div class="empty-icon large">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h4>No recent calls</h4>
                        <p>Your call history will appear here</p>
                    </div>
                </div>
            </div>

            <div class="tab-pane" id="ticketing-content">
                <div class="content-section">
                    <div class="section-header">
                        <h3>My Tickets (<span id="my-tickets-count">0</span>)</h3>
                    </div>
                    <div id="my-tickets-list" class="content-list">
                        <div class="empty-state large">
                            <div class="empty-icon large">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h4>No active tickets</h4>
                            <p>Create a ticket to get help</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane" id="contacts-content">
                <div id="contacts-list" class="content-list">
                    <div class="empty-state large">
                        <div class="empty-icon large">
                            <i class="fas fa-address-book"></i>
                        </div>
                        <h4>No contacts</h4>
                        <p>Add contacts to stay connected</p>
                    </div>
                </div>
            </div>

            <link rel="stylesheet" type="text/css" href="<?= base_url('css/notifications.css') ?>">
            <div class="tab-pane" id="notifications-content">
                <div id="notifications-list" class="content-list">
                    <div class="empty-state large">
                        <div class="empty-icon large">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h4>All caught up</h4>
                        <p>No new notifications</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="create-ticket-modal" class="overlay-fixed hidden">
        <div class="overlay-container">
            <div class="overlay-header">
                <h3>Create Ticket</h3>
                <button class="close-overlay" data-target="create-ticket-modal">&times;</button>
            </div>
            <div class="overlay-body">
                <form id="create-ticket-form">
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Brief description" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control dropdown-fix" required>
                            <option value="" disabled selected>Select category</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= esc($cat['name']) ?>">
                                        <?= esc($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="General">General</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-control dropdown-fix">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="5" placeholder="Provide details about your issue..." required></textarea>
                    </div>

                    <button type="submit" class="save-btn">
                        <i class="fas fa-paper-plane"></i> Submit Ticket
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="settings-overlay" class="overlay-fixed hidden">
        <div class="overlay-container">
            <div class="overlay-header">
                <h3>Settings</h3>
                <button class="close-overlay" data-target="settings-overlay">&times;</button>
            </div>
            <div class="overlay-body">
                <div class="overlay-row">
                    <span>Dark Mode</span>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div id="edit-profile-modal" class="overlay-fixed hidden">
        <div class="overlay-container">
            <div class="overlay-header">
                <h3>Edit Profile</h3>
                <button class="close-overlay" data-target="edit-profile-modal">&times;</button>
            </div>
            <div class="overlay-body">
                <form id="edit-profile-form">
                    <div class="form-group">
                        <label class="form-label">Nickname</label>
                        <input type="text" name="nickname" class="form-control" value="<?= session()->get('nickname') ?? session()->get('username') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Message</label>
                        <input type="text" name="status_message" class="form-control" placeholder="What's on your mind?">
                    </div>
                    <button type="submit" class="save-btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* === MODERN COLOR SYSTEM === */
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: #818cf8;
            
            --bg-primary: #0f111a;
            --bg-secondary: #1a1d29;
            --bg-tertiary: #14161f;
            --bg-elevated: #1f2433;
            --bg-hover: #252d3d;
            
            --border-subtle: rgba(255, 255, 255, 0.1);
            --border-medium: #334155;
            --border-strong: #475569;
            
            --text-primary: #f8fafc;
            --text-secondary: #e2e8f0;
            --text-tertiary: #94a3b8;
            --text-muted: #64748b;
            
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.25);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-xl: 18px;
        }

        /* === LAYOUT === */
        .sidebar-wrapper {
            display: flex;
            height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', 'Helvetica Neue', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* === VERTICAL NAVIGATION RAIL === */
        .nav-rail {
            width: 80px;
            background: var(--bg-primary);
            border-right: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 0;
            flex-shrink: 0;
        }

        .nav-rail-avatar {
            margin-bottom: 32px;
        }

        .avatar-container {
            position: relative;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 16px;
            object-fit: cover;
            border: 2px solid var(--border-medium);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .user-avatar:hover {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }

        .status-indicator {
            position: absolute;
            bottom: -4px;
            right: -4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 3px solid var(--bg-primary);
        }

        .status-indicator.online { background: var(--success); }
        .status-indicator.away { background: var(--warning); }
        .status-indicator.busy { background: var(--danger); }
        .status-indicator.offline { background: var(--text-muted); }

        .nav-rail-items {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            align-items: center;
        }

        .nav-rail-btn {
            position: relative;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .nav-rail-btn i {
            font-size: 20px;
        }

        .nav-rail-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
        }

        .nav-rail-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .nav-rail-btn:hover::after {
            content: attr(title);
            position: absolute;
            left: 100%;
            margin-left: 12px;
            background: var(--bg-elevated);
            color: var(--text-primary);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            box-shadow: var(--shadow-xl);
            pointer-events: none;
            z-index: 1000;
        }

        .nav-rail-btn.active:hover::after {
            display: none;
        }

        .nav-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--danger);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
            border: 2px solid var(--bg-primary);
        }

        .nav-rail-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            border-top: 1px solid var(--border-subtle);
            padding-top: 24px;
            width: 100%;
            align-items: center;
        }

        /* === CONTENT PANEL === */
        .content-panel {
            width: 320px;
            background: var(--bg-secondary);
            display: flex;
            flex-direction: column;
            color: var(--text-primary);
        }

        .content-header {
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-subtle);
            padding: 16px 20px;
            flex-shrink: 0;
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .header-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .header-action-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid var(--border-medium);
            background: transparent;
            color: var(--text-tertiary);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .header-action-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--primary);
            border-color: var(--primary);
        }

        .header-action-btn.hidden {
            display: none;
        }
        
        #create-ticket-btn.hidden {
            display: none !important;
        }

        .search-container {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-medium);
            border-radius: 10px;
            padding: 10px 14px;
            transition: all 0.2s ease;
        }

        .search-container:focus-within {
            background: var(--bg-elevated);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-container i {
            color: var(--text-muted);
            font-size: 16px;
        }

        .search-container input {
            background: none;
            border: none;
            width: 100%;
            color: var(--text-primary);
            outline: none;
            font-size: 14px;
            font-weight: 500;
        }

        .search-container input::placeholder {
            color: var(--text-muted);
        }

        /* === STATUS BAR === */
        .status-bar {
            position: relative;
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-subtle);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 12px;
            overflow: hidden;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-dot.online { background: var(--success); }
        .status-dot.away { background: var(--warning); }
        .status-dot.busy { background: var(--danger); }
        .status-dot.offline { background: var(--text-muted); }

        .status-username {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .status-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.2s ease;
        }

        .status-toggle:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--primary);
        }

        .status-toggle i {
            font-size: 12px;
        }

        .status-dropdown {
            position: absolute;
            top: 100%;
            left: 20px;
            right: 20px;
            margin-top: 8px;
            background: var(--bg-elevated);
            border: 1px solid var(--border-medium);
            border-radius: 12px;
            list-style: none;
            padding: 6px;
            z-index: 1000;
            box-shadow: var(--shadow-xl);
        }

        .status-dropdown li {
            padding: 11px 12px;
            cursor: pointer;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .status-dropdown li:hover {
            background: var(--bg-hover);
            color: var(--primary);
        }

        /* === TAB CONTENT === */
        .tab-content-area {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .tab-content-area::-webkit-scrollbar {
            width: 6px;
        }

        .tab-content-area::-webkit-scrollbar-track {
            background: transparent;
        }

        .tab-content-area::-webkit-scrollbar-thumb {
            background: var(--border-medium);
            border-radius: 3px;
        }

        .tab-pane {
            display: none;
            flex-direction: column;
            height: 100%;
        }

        .tab-pane.active {
            display: flex;
        }

        /* === CONTENT SECTIONS === */
        .content-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            border-bottom: 1px solid var(--border-subtle);
        }

        .content-section:last-child {
            border-bottom: none;
        }

        .section-header {
            background: var(--bg-tertiary);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .section-header h3 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin: 0;
        }

        .section-action-btn {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .section-action-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--primary);
        }

        .section-action-btn i {
            font-size: 14px;
        }

        /* === CONTENT LISTS === */
        .content-list {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
        }

        .content-list::-webkit-scrollbar {
            width: 6px;
        }

        .content-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .content-list::-webkit-scrollbar-thumb {
            background: var(--border-medium);
            border-radius: 3px;
        }

        /* === EMPTY STATE === */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state.large {
            padding: 64px 24px;
        }

        .empty-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            background: rgba(100, 116, 139, 0.1);
            margin-bottom: 16px;
        }

        .empty-icon.large {
            width: 80px;
            height: 80px;
            border-radius: 24px;
        }

        .empty-icon i {
            font-size: 32px;
            color: var(--text-muted);
            opacity: 0.4;
        }

        .empty-icon.large i {
            font-size: 40px;
        }

        .empty-state h4 {
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 4px;
            color: var(--text-secondary);
        }

        .empty-state p {
            font-size: 12px;
            margin: 0;
            color: var(--text-muted);
        }

        /* === MODALS === */
        .overlay-fixed {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.75);
            z-index: 2147483647 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }

        .overlay-container {
            background: var(--bg-elevated);
            width: 460px;
            max-width: 90%;
            border-radius: var(--radius-xl);
            overflow: hidden;
            color: white;
            border: 1px solid var(--border-medium);
            box-shadow: var(--shadow-xl);
            animation: modalSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .overlay-header {
            padding: 20px 24px;
            background: var(--bg-tertiary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-subtle);
        }

        .overlay-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }

        .close-overlay {
            background: transparent;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-tertiary);
            padding: 0;
            line-height: 1;
            transition: all 0.2s ease;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .close-overlay:hover {
            color: var(--danger);
            background: rgba(239, 68, 68, 0.1);
        }

        .overlay-body {
            padding: 24px;
        }

        /* === FORM ELEMENTS === */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-medium);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
            transition: all 0.2s ease;
            font-family: inherit;
            font-weight: 500;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: var(--bg-secondary);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        textarea.form-control {
            resize: vertical;
            line-height: 1.6;
        }

        .form-control.dropdown-fix {
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: calc(100% - 16px) center !important;
            background-size: 14px !important;
            padding-right: 44px !important;
            cursor: pointer;
        }

        .save-btn {
            width: 100%;
            padding: 12px 18px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .save-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* === SETTINGS === */
        .overlay-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
        }

        .overlay-row span {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 600;
        }

        /* === TOGGLE SWITCH === */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
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
            background-color: var(--bg-hover);
            transition: 0.3s;
            border-radius: 28px;
            border: 2px solid var(--border-medium);
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        input:checked + .slider:before {
            transform: translateX(20px);
        }

        .hidden {
            display: none !important;
        }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .nav-rail {
                width: 64px;
            }

            .content-panel {
                width: 280px;
            }
        }
    </style>

    <script src="<?= base_url('js/group-manager.js') ?>"></script>
    <script src="<?= base_url('js/ticket-manager.js') ?>"></script>
    <script src="<?= base_url('js/sidebar-init.js') ?>"></script>
    
    <script>
        // Only run this if the external script fails or to force correct initial state
        document.addEventListener("DOMContentLoaded", function() {
            // FIX: Force correct initial state to prevent button bleeding into other tabs on load
            const activeBtn = document.querySelector('.nav-rail-btn.active');
            if (activeBtn) {
                const tab = activeBtn.dataset.tab;
                const ticketBtn = document.getElementById('create-ticket-btn');
                const chatBtn = document.getElementById('new-chat-btn');
                if(ticketBtn) ticketBtn.classList.toggle('hidden', tab !== 'ticketing');
                if(chatBtn) chatBtn.classList.toggle('hidden', tab !== 'chats');
            }

            // Close modals when clicking outside
            document.querySelectorAll('.overlay-fixed').forEach(overlay => {
                overlay.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.add('hidden');
                    }
                });
            });

            // Close buttons
            document.querySelectorAll('.close-overlay').forEach(btn => {
                btn.onclick = function() {
                    const target = this.dataset.target;
                    document.getElementById(target).classList.add('hidden');
                };
            });
        });
    </script>
</div>

</body>
</html>