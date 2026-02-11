<?= view('partials/admin-header') ?>
<?= view('partials/admin-sidebar', ['active_page' => 'tickets']) ?>

<main class="main-content">
    <header class="content-header">
        <div class="breadcrumb">
            <span class="breadcrumb-root">SuperAdmin</span>
            <i class="fas fa-chevron-right breadcrumb-sep"></i>
            <span class="breadcrumb-current">Ticket Logs</span>
        </div>
        <div class="header-actions">
            <button class="icon-btn" title="Notifications">
                <i class="far fa-bell"></i>
                <span class="notification-dot"></span>
            </button>
        </div>
    </header>

    <div class="page-body">
        
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i>
                    <span><?= session()->getFlashdata('success') ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-error">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= session()->getFlashdata('error') ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
            </div>
        <?php endif; ?>

        <div class="page-heading">
            <div>
                <h1 class="page-title">Support Ticket Logs</h1>
                <p class="page-subtitle">Historical record of all client inquiries.</p>
            </div>
            <div class="action-buttons">
                <button onclick="openCategoryManager()" class="btn btn-secondary" style="margin-right: 10px; background: #334155; color: white; border: 1px solid #475569;">
                    <i class="fas fa-tags" style="margin-right: 8px;"></i> Categories
                </button>

                <a href="<?= base_url('sa/tickets/export') ?>" class="btn btn-primary" style="text-decoration: none; color: white;">
                    <i class="fas fa-file-csv" style="margin-right: 8px;"></i> Export CSV
                </a>
            </div>
        </div>

        <div class="admin-card table-card">
            <div class="card-header">
                <h3 class="card-title">Ticket Registry</h3>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Subject & Client</th>
                            <th>Assigned TSR</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th style="text-align: right;">Last Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tickets)): foreach($tickets as $ticket): ?>
                        <tr>
                            <td class="id-cell">#<?= str_pad($ticket['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <div class="agent-text">
                                    <p class="agent-name"><?= esc($ticket['subject']) ?></p>
                                    <p class="agent-uid"><?= esc($ticket['client_nickname'] ?? $ticket['client_name'] ?? 'Unknown') ?></p>
                                </div>
                            </td>
                            <td><?= esc($ticket['tsr_name'] ?? 'Unassigned') ?></td>
                            <td>
                                <span style="font-weight: 600; text-transform: uppercase; font-size: 11px;">
                                    <?= esc($ticket['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= strtolower($ticket['status']) == 'closed' ? 'offline' : 'online' ?>">
                                    <?= strtoupper($ticket['status']) ?>
                                </span>
                            </td>
                            <td class="email-cell" style="text-align: right;"><?= date('M d, H:i', strtotime($ticket['updated_at'])) ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-ticket-alt"></i>
                                <p style="font-weight: 600;">No ticket logs found</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div id="categoryManagerModal" class="modal-overlay hidden">
    <div class="modal-content large-modal">
        <div class="modal-header">
            <h3>Manage Categories</h3>
            <button onclick="closeCategoryManager()" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            
            <form action="<?= base_url('sa/categories/save') ?>" method="post" class="add-cat-form">
                <input type="text" name="name" placeholder="New Category Name..." required class="form-input">
                <button type="submit" class="btn btn-primary btn-sm">Add</button>
            </form>

            <div class="category-list-container">
                <table class="simple-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($categories)): foreach($categories as $cat): ?>
                        <tr>
                            <td><?= esc($cat['name']) ?></td>
                            <td>
                                <span class="status-dot <?= $cat['is_active'] ? 'active' : 'inactive' ?>"></span>
                                <?= $cat['is_active'] ? 'Active' : 'Hidden' ?>
                            </td>
                            <td style="text-align:right;">
                                <form action="<?= base_url('sa/categories/toggle') ?>" method="post" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $cat['is_active'] ?>">
                                    <button type="submit" class="icon-action" title="Toggle Visibility">
                                        <i class="fas <?= $cat['is_active'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                    </button>
                                </form>
                                
                                <button type="button" class="icon-action delete" title="Delete" onclick="confirmDelete(<?= $cat['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding: 20px;">No categories found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="deleteModal" class="modal-overlay hidden">
    <div class="modal-content small-modal">
        <div class="modal-body text-center" style="padding: 30px;">
            <div style="width: 50px; height: 50px; background: rgba(239,68,68,0.2); color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px;"></i>
            </div>
            <h3 style="color: #fff; margin: 0 0 10px; font-size: 18px;">Delete Category?</h3>
            <p style="color: #94a3b8; margin: 0 0 25px; font-size: 14px;">
                Are you sure you want to delete this category? This action cannot be undone.
            </p>
            
            <form action="<?= base_url('sa/categories/delete') ?>" method="post">
                <input type="hidden" name="id" id="deleteInputId">
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()" style="background: transparent; border: 1px solid #475569; color: #cbd5e1;">Cancel</button>
                    <button type="submit" class="btn btn-danger" style="background: #ef4444; color: white; border: none;">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Alerts */
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; justify-content: space-between; animation: slideDown 0.3s ease-out; }
    .alert-success { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .alert-error { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    .alert-close { background: none; border: none; color: inherit; font-size: 18px; cursor: pointer; }
    @keyframes slideDown { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    /* Modal Styles */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
    .modal-overlay.hidden { display: none; }
    .modal-content { background: #1e293b; border: 1px solid #334155; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); overflow: hidden; }
    .large-modal { width: 100%; max-width: 500px; }
    .small-modal { width: 100%; max-width: 350px; }
    .modal-header { padding: 16px 24px; background: #0f172a; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
    .modal-header h3 { margin: 0; color: #fff; font-size: 16px; font-weight: 600; }
    .close-btn { background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; }
    .close-btn:hover { color: #fff; }
    .modal-body { padding: 24px; }
    
    /* Buttons & Forms */
    .add-cat-form { display: flex; gap: 10px; margin-bottom: 20px; }
    .form-input { flex: 1; background: #0f172a; border: 1px solid #334155; color: #fff; padding: 8px 12px; border-radius: 6px; outline: none; }
    .form-input:focus { border-color: #8b5cf6; }
    .btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-primary { background: #3b82f6; color: white; border: none; }
    .btn-primary:hover { background: #2563eb; }
    .btn-danger:hover { background: #dc2626; }
    .btn-secondary:hover { background: rgba(255,255,255,0.05); color: #fff; }

    /* Category List */
    .category-list-container { max-height: 300px; overflow-y: auto; border: 1px solid #334155; border-radius: 6px; }
    .simple-table { width: 100%; border-collapse: collapse; }
    .simple-table th, .simple-table td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #334155; color: #cbd5e1; font-size: 13px; }
    .simple-table th { background: #0f172a; font-weight: 600; color: #94a3b8; position: sticky; top: 0; }
    .simple-table tr:last-child td { border-bottom: none; }
    .status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; }
    .status-dot.active { background: #10b981; box-shadow: 0 0 5px rgba(16,185,129,0.4); }
    .status-dot.inactive { background: #64748b; }
    .icon-action { background: none; border: none; color: #94a3b8; cursor: pointer; padding: 6px; transition: 0.2s; border-radius: 4px; }
    .icon-action:hover { color: #fff; background: rgba(255,255,255,0.1); }
    .icon-action.delete:hover { color: #ef4444; background: rgba(239,68,68,0.1); }
    .text-center { text-align: center; }
</style>

<script>
    // Category Manager Modal
    function openCategoryManager() { document.getElementById('categoryManagerModal').classList.remove('hidden'); }
    function closeCategoryManager() { document.getElementById('categoryManagerModal').classList.add('hidden'); }

    // Delete Confirmation Modal
    function confirmDelete(id) {
        document.getElementById('deleteInputId').value = id;
        document.getElementById('deleteModal').classList.remove('hidden');
        // Close the manager modal temporarily so they don't overlap awkwardly
        document.getElementById('categoryManagerModal').classList.add('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        // Re-open the manager modal so user can continue managing
        document.getElementById('categoryManagerModal').classList.remove('hidden');
    }

    // Close on Outside Click
    window.onclick = function(event) {
        const catModal = document.getElementById('categoryManagerModal');
        const delModal = document.getElementById('deleteModal');
        
        if (event.target == catModal) closeCategoryManager();
        if (event.target == delModal) closeDeleteModal();
    }
    
    // Auto-Dismiss Alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 4000); // Disappears after 4 seconds
</script>

</body>
</html>