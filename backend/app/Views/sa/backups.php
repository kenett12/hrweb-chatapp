<?= view('partials/admin-header') ?>
<?= view('partials/admin-sidebar', ['active_page' => 'backups']) ?>

<main class="main-content">
    <header class="content-header">
        <div class="breadcrumb">
            <span class="breadcrumb-root">SuperAdmin</span>
            <i class="fas fa-chevron-right breadcrumb-sep"></i>
            <span class="breadcrumb-current">Database Backups</span>
        </div>
        <div class="header-actions">
            <button class="icon-btn">
                <i class="far fa-bell"></i>
                <span class="notification-dot"></span>
            </button>
        </div>
    </header>

    <div class="page-body">
        <div class="page-heading">
            <div>
                <h1 class="page-title">Backup & Restore</h1>
                <p class="page-subtitle">Manage system recovery points and automated database exports.</p>
            </div>
            <div class="action-buttons">
                <button class="btn btn-primary"><i class="fas fa-plus-circle"></i> Create Manual Backup</button>
            </div>
        </div>

        <div class="admin-card table-card">
            <div class="card-header">
                <h3 class="card-title">Available Recovery Points</h3>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Created By</th>
                            <th>Date Created</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($backups)): foreach($backups as $backup): ?>
                        <tr>
                            <td class="id-cell" style="font-family: monospace; font-size: 13px;"><?= esc($backup['filename']) ?></td>
                            <td style="font-size: 13px;"><?= esc($backup['size']) ?> MB</td>
                            <td class="email-cell"><?= esc($backup['created_by']) ?></td>
                            <td class="email-cell"><?= date('M d, Y H:i', strtotime($backup['created_at'])) ?></td>
                            <td class="text-right">
                                <button class="tool-btn" title="Download"><i class="fas fa-download"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" class="empty-state"><i class="fas fa-database"></i><p>No backup files found.</p></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>