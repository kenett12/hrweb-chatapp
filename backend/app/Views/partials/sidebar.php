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
    <script src="https://cdn.socket.io/4.4.1/socket.io.min.js"></script>

    <?php
    $db = \Config\Database::connect();
    // Fetch Categories
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
    // System Configuration
    window.baseUrl = '<?= base_url() ?>';
    if (!window.baseUrl.endsWith('/')) window.baseUrl += '/';
    window.userId = '<?= session()->get('id') ?>';
    window.userRole = '<?= session()->get('role') ?>';
    window.socketUrl = "http://localhost:3001"; 

    // Socket Initialization
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
                <img src="<?= base_url('uploads/avatars/' . (session()->get('avatar') ?? 'default-avatar.png')) ?>" class="user-avatar" alt="User">
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
                    <i class="fas fa-edit"></i>
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
                            <button class="btn-secondary trigger-create-ticket" style="margin-top: 16px;">
                                Create New Ticket
                            </button>
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
    <div class="overlay-container sap-dialog">
        <div class="overlay-header sap-bar header">
            <h3 class="sap-title">Create Ticket</h3>
            <button class="close-overlay sap-icon-btn" data-target="create-ticket-modal">
                &times;
            </button>
        </div>
        
        <form id="create-ticket-form" enctype="multipart/form-data" class="sap-form-layout">
            <div class="overlay-body sap-content">
                
                <div class="sap-form-group">
                    <label class="sap-label">Subject</label>
                    <input type="text" name="subject" class="sap-input" placeholder="Enter a brief summary..." required>
                </div>
                
                <div class="sap-grid-row">
                    <div class="sap-form-group">
                        <label class="sap-label">Category</label>
                        <div class="sap-select-wrapper">
                            <select name="category" class="sap-select" required>
                                <option value="" disabled selected>Select Category</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= esc($cat['name']) ?>"><?= esc($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="General">General</option>
                                <?php endif; ?>
                            </select>
                            <i class="fas fa-chevron-down sap-select-arrow"></i>
                        </div>
                    </div>

                    <div class="sap-form-group">
                        <label class="sap-label">Priority</label>
                        <div class="sap-select-wrapper">
                            <select name="priority" class="sap-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                            <i class="fas fa-chevron-down sap-select-arrow"></i>
                        </div>
                    </div>
                </div>

                <div class="sap-form-group">
                    <label class="sap-label">Description</label>
                    <textarea name="description" class="sap-textarea" rows="6" placeholder="Describe the issue in detail..." required></textarea>
                </div>

                <div class="sap-form-group">
                    <label class="sap-label">Attachments</label>
                    <div class="sap-file-uploader">
                        <input type="file" name="attachment" id="ticket-attachment" class="file-input-hidden">
                        <label for="ticket-attachment" class="sap-file-trigger">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Upload File</span>
                        </label>
                        <span id="file-name-display" class="sap-file-name">No file chosen</span>
                    </div>
                </div>
            </div>

            <div class="overlay-footer sap-bar footer">
                <button type="button" class="sap-btn sap-btn-ghost close-overlay" data-target="create-ticket-modal">
                    Cancel
                </button>
                <button type="submit" class="sap-btn sap-btn-emphasized">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>

    <div id="settings-overlay" class="overlay-fixed hidden">
        <div class="overlay-container">
            <div class="overlay-header">
                <h3>App Settings</h3>
                <button class="close-overlay" data-target="settings-overlay">&times;</button>
            </div>
            <div class="overlay-body">
                <h4 class="settings-section-title">Profile & Availability</h4>
                <div class="overlay-row">
                    <span>Current Status</span>
                    <select class="settings-select">
                        <option value="online" selected>Online</option>
                        <option value="away">Away</option>
                        <option value="dnd">Do Not Disturb</option>
                        <option value="offline">Invisible</option>
                    </select>
                </div>
                <div class="overlay-row">
                    <span>Auto-Reply Message</span>
                    <input type="text" class="settings-input" placeholder="Be right back...">
                </div>

                <hr class="settings-divider">
                <h4 class="settings-section-title">Notifications</h4>
                <div class="overlay-row">
                    <span>New Ticket Alert (Sound)</span>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="overlay-row">
                    <span>New Message Alert (Sound)</span>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="overlay-row">
                    <span>Desktop Push Notifications</span>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="overlay-row">
                    <span>Email Digest for Missed Tickets</span>
                    <label class="switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>

                <hr class="settings-divider">
                <h4 class="settings-section-title">Interface & Chat</h4>
                <div class="overlay-row">
                    <span>Dark Mode</span>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="overlay-row">
                    <span>Ticket Sort Order</span>
                    <select class="settings-select">
                        <option value="priority">Priority (High to Low)</option>
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="unanswered">Unanswered First</option>
                    </select>
                </div>
                <div class="overlay-row">
                    <span>Press Enter to Send</span>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="overlay-row">
                    <span>Show Typing Indicators</span>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="overlay-row">
                    <span>Send Read Receipts</span>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="overlay-row">
                    <span>Font Size</span>
                    <div class="range-container">
                        <input type="range" min="12" max="20" value="14" class="settings-range">
                    </div>
                </div>

                <hr class="settings-divider">
                <h4 class="settings-section-title">Security</h4>
                <div class="overlay-row">
                    <span>Two-Factor Authentication</span>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="overlay-row button-row">
                    <button class="btn-secondary">View Login History</button>
                    <button class="btn-secondary">Export Chat Logs</button>
                </div>
            </div>
            <div class="overlay-footer">
                <button class="btn-cancel close-overlay" data-target="settings-overlay">Discard</button>
                <button class="btn-save">Save Configuration</button>
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
        --primary-darker: #4338ca;
        
        --bg-primary: #0f111a;
        --bg-secondary: #1a1d29;
        --bg-tertiary: #14161f;
        --bg-elevated: #1f2433;
        --bg-hover: #252d3d;
        --bg-accent: #2a2d3a;
        
        --border-subtle: rgba(255, 255, 255, 0.08);
        --border-medium: #334155;
        --border-strong: #475569;
        --border-accent: rgba(99, 102, 241, 0.3);
        
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
        --shadow-inset: inset 0 1px 0 0 rgba(255, 255, 255, 0.1);
        
        --radius-sm: 6px;
        --radius-md: 10px;
        --radius-lg: 14px;
        --radius-xl: 18px;
        
        --transition-fast: 0.15s ease;
        --transition-normal: 0.2s ease;
        --transition-smooth: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        transition: all var(--transition-normal);
        cursor: pointer;
    }

    .user-avatar:hover {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        transform: scale(1.05);
    }

    .status-indicator {
        position: absolute;
        bottom: -4px;
        right: -4px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 3px solid var(--bg-primary);
        box-shadow: 0 0 0 1px var(--border-medium);
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
        border: 1px solid transparent;
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        transition: all var(--transition-normal);
        text-decoration: none;
    }

    .nav-rail-btn i {
        font-size: 20px;
    }

    .nav-rail-btn:hover {
        background: rgba(255, 255, 255, 0.08);
        color: var(--text-secondary);
        border-color: var(--border-subtle);
    }

    .nav-rail-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary-hover);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4), var(--shadow-inset);
    }

    .nav-rail-btn:hover::after {
        content: attr(title);
        position: absolute;
        left: 100%;
        margin-left: 12px;
        background: var(--bg-elevated);
        color: var(--text-primary);
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        box-shadow: var(--shadow-xl);
        pointer-events: none;
        z-index: 1000;
        border: 1px solid var(--border-subtle);
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
        padding: 3px 6px;
        border-radius: 10px;
        min-width: 20px;
        text-align: center;
        border: 2px solid var(--bg-primary);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
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
        border-right: 1px solid var(--border-subtle);
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
        transition: all var(--transition-normal);
    }

    .header-action-btn:hover {
        background: rgba(99, 102, 241, 0.1);
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
        transition: all var(--transition-normal);
    }

    .search-container:focus-within {
        background: var(--bg-elevated);
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
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
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.3);
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
        border: 1px solid var(--border-subtle);
        color: var(--text-tertiary);
        cursor: pointer;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        transition: all var(--transition-normal);
    }

    .status-toggle:hover {
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary);
        border-color: var(--primary);
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
        padding: 8px;
        z-index: 1000;
        box-shadow: var(--shadow-xl);
    }

    .status-dropdown li {
        padding: 11px 14px;
        cursor: pointer;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        border-radius: 8px;
        transition: all var(--transition-normal);
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
        transition: all var(--transition-normal);
    }

    .section-action-btn:hover {
        background: rgba(99, 102, 241, 0.1);
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
        background: rgba(99, 102, 241, 0.08);
        margin-bottom: 16px;
        border: 1px solid var(--border-accent);
    }

    .empty-icon.large {
        width: 80px;
        height: 80px;
        border-radius: 24px;
    }

    .empty-icon i {
        font-size: 32px;
        color: var(--text-muted);
        opacity: 0.5;
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
        background: rgba(0, 0, 0, 0.85);
        z-index: 2147483647 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(12px);
    }

    .overlay-fixed.hidden {
        display: none !important;
    }

    .overlay-container {
        background: var(--bg-elevated);
        width: 500px;
        max-width: 90vw;
        max-height: 85vh;
        border-radius: var(--radius-xl);
        overflow: hidden;
        color: white;
        border: 1px solid var(--border-medium);
        box-shadow: var(--shadow-xl), 0 0 60px rgba(99, 102, 241, 0.15);
        animation: modalSlideIn var(--transition-smooth);
        display: flex;
        flex-direction: column;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(24px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .overlay-header {
        padding: 24px 28px;
        background: var(--bg-tertiary);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-subtle);
        flex-shrink: 0;
    }

    .overlay-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        letter-spacing: -0.01em;
    }

    .close-overlay {
        background: transparent;
        border: 1px solid var(--border-subtle);
        font-size: 24px;
        cursor: pointer;
        color: var(--text-tertiary);
        padding: 0;
        line-height: 1;
        transition: all var(--transition-normal);
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        flex-shrink: 0;
    }

    .close-overlay:hover {
        color: var(--danger);
        background: rgba(239, 68, 68, 0.1);
        border-color: var(--danger);
    }

    .overlay-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
    }

    .overlay-body::-webkit-scrollbar {
        width: 6px;
    }

    .overlay-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .overlay-body::-webkit-scrollbar-thumb {
        background: var(--border-medium);
        border-radius: 3px;
    }

    .overlay-footer {
        padding: 20px 24px;
        background: var(--bg-tertiary);
        border-top: 1px solid var(--border-subtle);
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        flex-shrink: 0;
    }

    /* === SETTINGS SECTION === */
    .settings-section-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-tertiary);
        margin: 24px 0 16px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .settings-section-title:first-child {
        margin-top: 0;
    }

    .settings-section-title::before {
        content: '';
        width: 3px;
        height: 3px;
        background: var(--primary);
        border-radius: 50%;
        flex-shrink: 0;
    }

    .settings-divider {
        border: none;
        border-top: 1px solid var(--border-subtle);
        margin: 16px 0;
    }

    .overlay-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 16px;
        background: var(--bg-accent);
        border-radius: 10px;
        margin-bottom: 12px;
        border: 1px solid transparent;
        transition: all var(--transition-normal);
    }

    .overlay-row:hover {
        border-color: var(--border-subtle);
        background: var(--bg-hover);
    }

    .overlay-row:last-of-type {
        margin-bottom: 0;
    }

    .overlay-row span {
        font-size: 14px;
        color: var(--text-primary);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .overlay-row.button-row {
        flex-direction: column;
        gap: 10px;
        background: transparent;
        border: none;
        padding: 12px 0;
    }

    .overlay-row.button-row button {
        width: 100%;
    }

    /* === SETTINGS SELECT === */
    .settings-select {
        padding: 10px 14px;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-medium);
        border-radius: 8px;
        color: var(--text-primary);
        font-size: 14px;
        font-weight: 500;
        outline: none;
        cursor: pointer;
        transition: all var(--transition-normal);
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: calc(100% - 12px) center;
        background-size: 14px;
        padding-right: 40px;
    }

    .settings-select:hover {
        border-color: var(--primary);
        background-color: var(--bg-elevated);
    }

    .settings-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .settings-select option {
        background: var(--bg-elevated);
        color: var(--text-primary);
        padding: 8px;
    }

    /* === SETTINGS INPUT === */
    .settings-input {
        padding: 10px 14px;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-medium);
        border-radius: 8px;
        color: var(--text-primary);
        font-size: 14px;
        font-weight: 500;
        outline: none;
        transition: all var(--transition-normal);
        width: 100%;
        max-width: 200px;
        font-family: inherit;
    }

    .settings-input:hover {
        border-color: var(--border-strong);
        background-color: var(--bg-elevated);
    }

    .settings-input:focus {
        border-color: var(--primary);
        background-color: var(--bg-secondary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .settings-input::placeholder {
        color: var(--text-muted);
    }

    /* === RANGE INPUT === */
    .range-container {
        width: 100%;
        max-width: 160px;
    }

    .settings-range {
        width: 100%;
        height: 4px;
        border-radius: 2px;
        background: var(--bg-tertiary);
        outline: none;
        -webkit-appearance: none;
        appearance: none;
    }

    .settings-range::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--primary);
        cursor: pointer;
        border: 2px solid var(--bg-elevated);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        transition: all var(--transition-normal);
    }

    .settings-range::-webkit-slider-thumb:hover {
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.5);
        transform: scale(1.1);
    }

    .settings-range::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--primary);
        cursor: pointer;
        border: 2px solid var(--bg-elevated);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        transition: all var(--transition-normal);
    }

    .settings-range::-moz-range-thumb:hover {
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.5);
        transform: scale(1.1);
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
        transition: all var(--transition-normal);
        font-family: inherit;
        font-weight: 500;
    }

    .form-control:hover {
        border-color: var(--border-strong);
        background-color: var(--bg-elevated);
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        background: var(--bg-secondary);
    }

    .form-control::placeholder {
        color: var(--text-muted);
    }

    textarea.form-control {
        resize: vertical;
        line-height: 1.6;
    }

    /* === BUTTONS === */
    .btn-save {
        padding: 12px 24px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: var(--radius-md);
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: all var(--transition-normal);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .btn-save:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
    }

    .btn-save:active {
        transform: translateY(0);
    }

    .btn-cancel {
        padding: 12px 24px;
        background: transparent;
        color: var(--text-secondary);
        border: 1px solid var(--border-medium);
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all var(--transition-normal);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-primary);
        border-color: var(--border-strong);
    }

    .btn-secondary {
        padding: 12px 20px;
        background: transparent;
        color: var(--primary);
        border: 1px solid var(--primary);
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all var(--transition-normal);
        width: 100%;
    }

    .btn-secondary:hover {
        background: rgba(99, 102, 241, 0.1);
        border-color: var(--primary-hover);
        color: var(--primary-light);
    }

    /* === TOGGLE SWITCH === */
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
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
        transition: all var(--transition-normal);
        border-radius: 28px;
        border: 1.5px solid var(--border-medium);
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 2px;
        background-color: white;
        transition: all var(--transition-normal);
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    input:checked + .slider {
        background-color: var(--primary);
        border-color: var(--primary-hover);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1);
    }

    input:checked + .slider:before {
        transform: translateX(22px);
        box-shadow: 0 2px 6px rgba(99, 102, 241, 0.3);
    }
    
    /* === FILE UPLOAD ZONE (NEW) === */
    .file-upload-zone { position: relative; width: 100%; }
    .file-input-hidden { position: absolute; width: 0.1px; height: 0.1px; opacity: 0; overflow: hidden; z-index: -1; }
    .file-upload-label { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; border: 2px dashed var(--border-medium); border-radius: var(--radius-md); background: var(--bg-tertiary); color: var(--text-tertiary); cursor: pointer; transition: all var(--transition-normal); text-align: center; gap: 8px; }
    .file-upload-label:hover { border-color: var(--primary); color: var(--primary); background: rgba(99, 102, 241, 0.05); }
    .file-upload-label i { font-size: 24px; margin-bottom: 4px; }
    .file-upload-label span { font-size: 13px; font-weight: 500; }
    .file-name { margin-top: 8px; font-size: 13px; color: var(--success); display: flex; align-items: center; gap: 6px; }
    .file-name::before { content: '\f00c'; font-family: 'Font Awesome 5 Free'; font-weight: 900; }

    /* === SAP UI STYLE OVERRIDES === */
    .sap-form-layout { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
    .sap-form-layout .overlay-body { flex: 1; overflow-y: auto; padding: 24px; }
    .sap-form-layout .overlay-footer { flex-shrink: 0; padding: 16px 24px; background: var(--bg-tertiary); border-top: 1px solid var(--border-subtle); display: flex; justify-content: flex-end; gap: 12px; }
    .grid-2-col { display: flex; gap: 16px; margin-bottom: 20px; }
    .grid-2-col .form-group { flex: 1; margin-bottom: 0; }
    .upload-content { display: flex; align-items: center; gap: 10px; }

    .hidden { display: none !important; }
    
    @media (max-width: 768px) {
        .nav-rail { width: 64px; }
        .content-panel { width: 280px; }
        .overlay-container { width: 95vw; }
    }

    /* === SAP / ENTERPRISE MODAL THEME === */

/* Container Logic */
.sap-dialog {
    display: flex;
    flex-direction: column;
    width: 600px; /* Slightly wider for enterprise feel */
    max-width: 95vw;
    max-height: 85vh;
    background-color: var(--bg-secondary); /* Your dark theme background */
    border-radius: 6px; /* SAP uses smaller radius usually */
    box-shadow: 0 0 0 1px rgba(255,255,255,0.1), 0 20px 40px rgba(0,0,0,0.6);
    font-family: '72', 'Inter', sans-serif; /* '72' is SAP's font, fallback to Inter */
}

/* Bars (Header & Footer) */
.sap-bar {
    display: flex;
    align-items: center;
    padding: 0 1rem;
    height: 3rem; /* Fixed height headers */
    flex-shrink: 0;
    background-color: var(--bg-tertiary);
    border-bottom: 1px solid var(--border-subtle);
}

.sap-bar.header {
    justify-content: space-between;
}

.sap-bar.footer {
    justify-content: flex-end; /* Right aligned actions */
    gap: 0.5rem;
    border-top: 1px solid var(--border-subtle);
    border-bottom: none;
    height: 3.5rem; /* Footers are slightly taller */
}

.sap-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

/* Content Area */
.sap-form-layout {
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow: hidden;
}

.sap-content {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

/* Form Elements */
.sap-form-group {
    margin-bottom: 1.25rem;
    display: flex;
    flex-direction: column;
}

.sap-label {
    font-size: 0.875rem;
    color: var(--text-tertiary);
    margin-bottom: 0.5rem;
    font-weight: 500;
}

/* Inputs & Selects */
.sap-input, 
.sap-select, 
.sap-textarea {
    background-color: var(--bg-primary); /* Darker input background */
    border: 1px solid var(--border-medium);
    border-radius: 4px;
    padding: 0 0.75rem;
    font-size: 0.875rem;
    color: var(--text-primary);
    transition: all 0.2s ease;
    width: 100%;
    box-sizing: border-box;
}

.sap-input { height: 36px; } /* SAP Cozy height */
.sap-select { height: 36px; appearance: none; cursor: pointer; }
.sap-textarea { padding: 0.75rem; line-height: 1.5; resize: vertical; min-height: 100px; }

.sap-input:focus, 
.sap-select:focus, 
.sap-textarea:focus {
    border-color: var(--primary); /* User Brand Color */
    box-shadow: inset 0 0 0 1px var(--primary); /* Inner glow focus state */
    outline: none;
}

/* Grid Layout */
.sap-grid-row {
    display: flex;
    gap: 1rem;
}
.sap-grid-row .sap-form-group {
    flex: 1;
}

/* Select Wrapper for Icon */
.sap-select-wrapper {
    position: relative;
}
.sap-select-arrow {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    pointer-events: none;
    font-size: 0.75rem;
}

/* Buttons (Fiori Style) */
.sap-btn {
    height: 36px;
    padding: 0 1rem;
    border-radius: 4px; /* Slightly rounded */
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.1s ease;
}

.sap-btn-emphasized {
    background-color: var(--primary);
    color: white;
    border-color: var(--primary);
}
.sap-btn-emphasized:hover {
    box-shadow: inset 0 0 0 100px rgba(255,255,255,0.1); /* Lighten on hover */
}

.sap-btn-ghost {
    background-color: transparent;
    color: var(--text-primary);
    border-color: var(--border-medium);
}
.sap-btn-ghost:hover {
    background-color: rgba(255,255,255,0.05);
    border-color: var(--text-secondary);
}

.sap-icon-btn {
    background: transparent;
    border: none;
    color: var(--text-muted);
    font-size: 1.25rem;
    cursor: pointer;
}
.sap-icon-btn:hover { color: var(--text-primary); }

/* File Uploader (Enterprise Look) */
.sap-file-uploader {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid var(--border-medium);
    background-color: var(--bg-primary);
    border-radius: 4px;
    padding: 4px;
}

.sap-file-trigger {
    background-color: var(--bg-tertiary);
    border: 1px solid var(--border-subtle);
    color: var(--text-primary);
    height: 28px;
    padding: 0 12px;
    border-radius: 3px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
}
.sap-file-trigger:hover { background-color: var(--bg-elevated); }

.sap-file-name {
    font-size: 0.875rem;
    color: var(--text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
    </style>

    <script src="<?= base_url('js/group-manager.js') ?>"></script>
    <script src="<?= base_url('js/ticket-manager.js') ?>"></script>
    <script src="<?= base_url('js/sidebar-init.js') ?>"></script>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Core Elements
        const navBtns = document.querySelectorAll('.nav-rail-btn[data-tab]');
        const tabPanes = document.querySelectorAll('.tab-pane');
        const headerTitle = document.getElementById('active-tab-title');
        
        // 2. Header Buttons
        const ticketBtn = document.getElementById('create-ticket-btn');
        const chatBtn = document.getElementById('new-chat-btn');
        
        // 3. UI Update Logic (Fixes the "static header" issue)
        function updateUI(tabId, title) {
            // A. Update Active Sidebar Button
            navBtns.forEach(btn => {
                if(btn.dataset.tab === tabId) btn.classList.add('active');
                else btn.classList.remove('active');
            });

            // B. Show Correct Content Pane
            tabPanes.forEach(pane => {
                if(pane.id === tabId + '-content') pane.classList.add('active');
                else pane.classList.remove('active');
            });

            // C. Update Header Title Text
            if(headerTitle) headerTitle.textContent = title;

            // D. Toggle Header Action Buttons (Crucial Fix)
            if(ticketBtn) ticketBtn.classList.add('hidden');
            if(chatBtn) chatBtn.classList.add('hidden');

            if(tabId === 'ticketing') {
                if(ticketBtn) ticketBtn.classList.remove('hidden');
            } else if (tabId === 'chats') {
                if(chatBtn) chatBtn.classList.remove('hidden');
            }
        }

        // 4. Attach Click Event Listeners
        navBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Prevent default behavior if it's an anchor tag acting as a button
                if(this.tagName !== 'A') e.preventDefault();
                
                const tab = this.dataset.tab;
                const title = this.getAttribute('title');
                
                // Call update function
                if(tab) updateUI(tab, title);
            });
        });

        // 5. Set Initial State based on active class in HTML
        const active = document.querySelector('.nav-rail-btn.active');
        if(active) {
            updateUI(active.dataset.tab, active.getAttribute('title'));
        } else {
            // Fallback default
            updateUI('chats', 'Chats');
        }

        // 6. Modal Logic (Openers & Closers)
        // Header Button Trigger
        if(ticketBtn) {
            ticketBtn.addEventListener('click', () => {
                document.getElementById('create-ticket-modal').classList.remove('hidden');
            });
        }
        
        // Empty State Button Trigger
        document.querySelectorAll('.trigger-create-ticket').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('create-ticket-modal').classList.remove('hidden');
            });
        });

        // Close via 'X' or Cancel button
        document.querySelectorAll('.close-overlay').forEach(btn => {
            btn.addEventListener('click', function() {
                // Find the closest modal container ID via data-target attribute
                const targetId = this.dataset.target;
                if(targetId) {
                    document.getElementById(targetId).classList.add('hidden');
                }
            });
        });

        // Close via Overlay Background Click
        document.querySelectorAll('.overlay-fixed').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) this.classList.add('hidden');
            });
        });

        // 7. File Upload Helper (Display filename on selection)
        const fileInput = document.getElementById('ticket-attachment');
        if(fileInput) {
            fileInput.addEventListener('change', function(e) {
                const name = e.target.files[0] ? e.target.files[0].name : '';
                const display = document.getElementById('file-name-display');
                if(name) {
                    display.textContent = name;
                    display.classList.remove('hidden');
                } else {
                    display.classList.add('hidden');
                }
            });
        }
    });
    </script>
</div>

</body>
</html>