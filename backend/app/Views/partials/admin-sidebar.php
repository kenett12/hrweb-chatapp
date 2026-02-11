<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand-container">
            <div class="brand-icon"><i class="fas fa-layer-group"></i></div>
            <span class="brand-text">HRWeb <span class="brand-highlight">Pro</span></span>
        </div>
        <button type="button" class="toggle-btn" onclick="toggleSidebar()" title="Toggle sidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <div class="sidebar-content">
        <div class="nav-group">
            <h3 class="nav-label"><span>Management</span></h3>
            <nav class="nav-list">
                <a href="<?= base_url('sa/dashboard') ?>" class="sidebar-link <?= ($active_page == 'dashboard') ? 'active' : '' ?>" title="Dashboard">
                    <i class="fas fa-chart-line"></i>
                    <span class="link-text">Dashboard</span>
                </a>
                
                <a href="<?= base_url('sa/tsr-accounts') ?>" class="sidebar-link <?= ($active_page == 'tsr') ? 'active' : '' ?>" title="TSR Accounts">
                    <i class="fas fa-users-cog"></i>
                    <span class="link-text">TSR Accounts</span>
                </a>

                <a href="<?= base_url('sa/client-accounts') ?>" class="sidebar-link <?= ($active_page == 'clients') ? 'active' : '' ?>" title="Client Accounts">
                    <i class="fas fa-user-tie"></i>
                    <span class="link-text">Client Accounts</span>
                </a>
                
                <a href="<?= base_url('sa/ticket-logs') ?>" class="sidebar-link <?= ($active_page == 'tickets') ? 'active' : '' ?>" title="Ticket Logs">
                    <i class="fas fa-ticket-alt"></i>
                    <span class="link-text">Ticket Logs</span>
                </a>

                <a href="<?= base_url('sa/chat-manager') ?>" class="sidebar-link <?= ($active_page == 'chats') ? 'active' : '' ?>" title="Chat Manager">
                    <i class="fas fa-comment-dots"></i>
                    <span class="link-text">Chat Manager</span>
                </a>
                
                <a href="<?= base_url('sa/feedback') ?>" class="sidebar-link <?= ($active_page == 'feedback') ? 'active' : '' ?>" title="Feedback Manager">
                    <i class="fas fa-comments"></i>
                    <span class="link-text">Feedback Manager</span>
                </a>
            </nav>
        </div>

        <div class="nav-group">
            <h3 class="nav-label"><span>Security</span></h3>
            <nav class="nav-list">
                <a href="<?= base_url('sa/audit-trail') ?>" class="sidebar-link <?= ($active_page == 'audit') ? 'active' : '' ?>" title="Audit Trail">
                    <i class="fas fa-shield-alt"></i>
                    <span class="link-text">Audit Trail</span>
                </a>
                <a href="<?= base_url('sa/backups') ?>" class="sidebar-link <?= ($active_page == 'backups') ? 'active' : '' ?>" title="Backups">
                    <i class="fas fa-database"></i>
                    <span class="link-text">Backups</span>
                </a>
            </nav>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar"><?= strtoupper(substr(session()->get('name') ?? 'A', 0, 1)) ?></div>
            <div class="user-info">
                <p class="user-name"><?= esc(session()->get('name') ?? 'Administrator') ?></p>
                <p class="user-email"><?= esc(session()->get('email') ?? '') ?></p>
            </div>
            <a href="<?= base_url('logout') ?>" class="icon-btn" style="border: none; background: none;" title="Logout">
                <i class="fas fa-power-off"></i>
            </a>
        </div>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;
        
        sidebar.classList.toggle('collapsed');
        
        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sa-sidebar-collapsed', isCollapsed ? 'true' : 'false');
        
        // Update icon
        const icon = sidebar.querySelector('.toggle-btn i');
        if (icon) {
            icon.classList.toggle('fa-chevron-left');
            icon.classList.toggle('fa-chevron-right');
        }
    }

    // Apply saved state on page load
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const isCollapsed = localStorage.getItem('sa-sidebar-collapsed') === 'true';
        
        if (sidebar && isCollapsed) {
            sidebar.classList.add('collapsed');
            const icon = sidebar.querySelector('.toggle-btn i');
            if (icon) {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
            }
        }
    });

    // Close sidebar on mobile when link is clicked
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        });
    });
</script>