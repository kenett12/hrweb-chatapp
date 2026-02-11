<?= view('partials/admin-header') ?>
<?= view('partials/admin-sidebar', ['active_page' => 'audit']) ?>

<main class="main-content">
    <header class="content-header">
        <div class="breadcrumb">
            <span class="breadcrumb-root">SuperAdmin</span>
            <i class="fas fa-chevron-right breadcrumb-sep"></i>
            <span class="breadcrumb-current">Audit Trail</span>
        </div>
        <div class="header-actions">
            <button class="icon-btn" title="Notifications">
                <i class="far fa-bell"></i>
                <span class="notification-dot"></span>
            </button>
        </div>
    </header>

    <div class="page-body">
        <div class="page-heading">
            <div>
                <h1 class="page-title">System Audit Trail</h1>
                <p class="page-subtitle">Security log of all administrative actions and system changes.</p>
            </div>
            <div class="action-buttons">
                <button class="btn btn-secondary"><i class="fas fa-download"></i> Export Logs</button>
            </div>
        </div>

        <div class="admin-card table-card">
            <div class="card-header">
                <h3 class="card-title">Security Events</h3>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>IP Address</th>
                            <th style="text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): foreach($logs as $log): ?>
                        <tr>
                            <td style="font-size: 12px;"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                            <td>
                                <div class="agent-info">
                                    <div class="agent-avatar"><?= strtoupper(substr($log['username'], 0, 2)) ?></div>
                                    <div class="agent-text">
                                        <p class="agent-name"><?= esc($log['username']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight: 600;"><?= esc($log['action']) ?></td>
                            <td><span class="system-tag"><?= esc($log['entity']) ?></span></td>
                            <td class="email-cell"><?= esc($log['ip_address']) ?></td>
                            <td style="text-align: right;"><span class="badge badge-online"><i class="fas fa-circle" style="font-size: 6px;"></i> SUCCESS</span></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-shield-alt"></i>
                                <p style="font-weight: 600;">No security events recorded</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                