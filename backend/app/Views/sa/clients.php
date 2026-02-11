<?= view('partials/admin-header') ?>
<?= view('partials/admin-sidebar', ['active_page' => 'clients']) ?>

<main class="main-content">
    <header class="content-header">
        <div class="breadcrumb">
            <span class="breadcrumb-root">SuperAdmin</span>
            <i class="fas fa-chevron-right breadcrumb-sep"></i>
            <span class="breadcrumb-current">Client Management</span>
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
                <h1 class="page-title">Client Accounts</h1>
                <p class="page-subtitle">Manage and monitor client access and subscriptions</p>
            </div>
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="toggleModal('createClientModal')">
                    <i class="fas fa-user-plus"></i>
                    <span>Create New Client</span>
                </button>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div style="background: rgba(63, 185, 80, 0.1); border: 1px solid rgba(63, 185, 80, 0.3); border-radius: 6px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: #3fb950;">
                <i class="fas fa-check-circle"></i>
                <span><?= session()->getFlashdata('success') ?></span>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div style="background: rgba(248, 81, 73, 0.1); border: 1px solid rgba(248, 81, 73, 0.3); border-radius: 6px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: #f85149;">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= session()->getFlashdata('error') ?></span>
            </div>
        <?php endif; ?>

        <div class="admin-card table-card">
            <div class="card-header">
                <h3 class="card-title">Active Clients</h3>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Client Details</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($clients)): foreach($clients as $client): ?>
                        <tr>
                            <td class="id-cell">#C-<?= str_pad($client['client_number'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <div class="agent-info">
                                    <div class="agent-avatar" style="background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); font-size: 14px;">
                                        <?= strtoupper(substr($client['nickname'] ?: $client['username'], 0, 2)) ?>
                                    </div>
                                    <div class="agent-text">
                                        <p class="agent-name"><?= esc($client['nickname'] ?: $client['username']) ?></p>
                                        <p class="agent-uid">@<?= esc($client['username']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <?php 
                                    $status = strtolower($client['status'] ?? 'offline');
                                    $badgeClass = ($status === 'active' || $status === 'online') ? 'badge-online' : 'badge-offline';
                                ?>
                                <span class="badge <?= $badgeClass ?>">
                                    <i class="fas fa-circle" style="font-size: 6px;"></i>
                                    <?= strtoupper($status) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <button class="tool-btn" title="Edit Client"><i class="fas fa-edit"></i></button>
                                
                                <button class="tool-btn" 
                                        title="Delete Client" 
                                        style="color: #ef4444;"
                                        onclick="openDeleteModal(<?= $client['id'] ?>, '<?= esc($client['username']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <p style="font-weight: 600;">No Client accounts found</p>
                                <span style="color: var(--text-tertiary); font-size: 13px;">Add your first client to get started</span>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="createClientModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px;" class="modal-overlay">
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);">
            <div style="padding: 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0 0 4px 0;">Register New Client</h3>
                    <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0;">Add a new client account to the system</p>
                </div>
                <button onclick="toggleModal('createClientModal')" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5rem; padding: 0;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="<?= site_url('sa/create-client') ?>" method="post" style="padding: 24px;">
                <?= csrf_field() ?>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">Username <span style="color: var(--accent-red);">*</span></label>
                    <div style="position: relative;">
                        <i class="fas fa-user" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-tertiary);"></i>
                        <input type="text" name="username" required placeholder="e.g., client_company" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px 10px 36px; color: var(--text-primary); font-size: 13px; transition: all 0.3s;" autocomplete="off" value="<?= old('username') ?>">
                    </div>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">Client Name <span style="color: var(--accent-red);">*</span></label>
                    <div style="position: relative;">
                        <i class="fas fa-building" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-tertiary);"></i>
                        <input type="text" name="name" required placeholder="Company Name" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px 10px 36px; color: var(--text-primary); font-size: 13px; transition: all 0.3s;" value="<?= old('name') ?>">
                    </div>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">Initial Password <span style="color: var(--accent-red);">*</span></label>
                    <div style="position: relative;">
                        <i class="fas fa-lock" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-tertiary);"></i>
                        <input type="password" id="passwordField" name="password" required placeholder="••••••••" minlength="8" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px 10px 36px; color: var(--text-primary); font-size: 13px; transition: all 0.3s;">
                        <button type="button" onclick="togglePassword(event)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-tertiary); cursor: pointer;">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                <div style="background: rgba(31, 111, 235, 0.1); border: 1px solid rgba(31, 111, 235, 0.2); border-radius: 6px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px;">
                    <i class="fas fa-info-circle" style="color: var(--accent-blue); margin-top: 2px; flex-shrink: 0;"></i>
                    <span style="font-size: 12px; color: rgba(230, 237, 243, 0.8); line-height: 1.4;">An onboarding email with credentials will be sent to the client.</span>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="toggleModal('createClientModal')" class="btn btn-secondary"><i class="fas fa-times"></i> <span>Cancel</span></button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> <span>Register Client</span></button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 2100; padding: 20px;" class="modal-overlay">
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; max-width: 400px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7); overflow: hidden; transform: scale(1); transition: all 0.2s;">
            <div style="padding: 32px 24px; text-align: center;">
                
                <div style="width: 72px; height: 72px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 24px;">
                    <i class="fas fa-trash-alt"></i>
                </div>
                
                <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0 0 12px 0;">Delete Client Account?</h3>
                
                <p style="font-size: 0.95rem; color: var(--text-secondary); margin: 0 0 24px 0; line-height: 1.6;">
                    You are about to delete the account for <strong id="delUsername" style="color: var(--text-primary);"></strong>.
                    <br><span style="font-size: 0.85rem; color: #ef4444; margin-top: 8px; display: block;">This action is permanent and cannot be undone.</span>
                </p>
                
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button onclick="toggleModal('deleteModal')" class="btn btn-secondary" style="width: 100%; padding: 12px; font-weight: 600;">
                        Cancel
                    </button>
                    <a href="#" id="confirmDeleteLink" class="btn btn-danger" style="background: #ef4444; color: white; border: none; width: 100%; padding: 12px; font-weight: 600; text-decoration: none; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                        Yes, Delete It
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                if (modal.style.display === 'none' || !modal.style.display) {
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                } else {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            }
        }

        // === NEW JS FOR DELETE MODAL ===
        function openDeleteModal(clientId, username) {
            // Set the username in the modal text
            document.getElementById('delUsername').textContent = "@" + username;
            
            // Set the href for the confirm button dynamically
            // Note: site_url is printed by PHP, we append the ID in JS
            const baseUrl = "<?= site_url('sa/client/delete/') ?>";
            document.getElementById('confirmDeleteLink').href = baseUrl + clientId;
            
            toggleModal('deleteModal');
        }

        function togglePassword(event) {
            event.preventDefault();
            const field = document.getElementById('passwordField');
            const icon = document.getElementById('toggleIcon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal-overlay');
                modals.forEach(modal => {
                    if (modal.style.display === 'flex') {
                        toggleModal(modal.id);
                    }
                });
            }
        });

        <?php if (session()->getFlashdata('error') && strpos(session()->getFlashdata('error'), 'Validation') !== false): ?>
        document.addEventListener('DOMContentLoaded', function() {
            toggleModal('createClientModal');
        });
        <?php endif; ?>
    </script>
</main>