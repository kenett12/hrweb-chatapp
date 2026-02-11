<?= view('partials/admin-header') ?>
<?= view('partials/admin-sidebar', ['active_page' => 'tsr']) ?>

<main class="main-content">
    <header class="content-header">
        <div class="breadcrumb">
            <span class="breadcrumb-root">SuperAdmin</span>
            <i class="fas fa-chevron-right breadcrumb-sep"></i>
            <span class="breadcrumb-current">TSR Management</span>
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
                <h1 class="page-title">Technical Support Representatives</h1>
                <p class="page-subtitle">Manage and monitor system access for support agents</p>
            </div>
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="toggleModal('createTsrModal')">
                    <i class="fas fa-user-plus"></i>
                    <span>Create New TSR</span>
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
                <h3 class="card-title">Active Agents</h3>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Technical ID</th>
                            <th>Agent Details</th>
                            <th>Email Address</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tsrs)): foreach($tsrs as $tsr): ?>
                        <tr>
                            <td class="id-cell">#<?= str_pad($tsr['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <div class="agent-info">
                                    <div class="agent-avatar" style="background: linear-gradient(135deg, #1f6feb 0%, #a371f7 100%); font-size: 14px;">
                                        <?= strtoupper(substr($tsr['nickname'] ?: $tsr['username'], 0, 2)) ?>
                                    </div>
                                    <div class="agent-text">
                                        <p class="agent-name"><?= esc($tsr['nickname'] ?: $tsr['username']) ?></p>
                                        <p class="agent-uid">@<?= esc($tsr['username']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="email-cell"><?= esc($tsr['email']) ?></td>
                            <td style="text-align: center;">
                                <?php 
                                    $status = strtolower($tsr['status'] ?? 'offline');
                                    $badgeClass = ($status === 'online') ? 'badge-online' : 'badge-offline';
                                ?>
                                <span class="badge <?= $badgeClass ?>">
                                    <i class="fas fa-circle" style="font-size: 6px;"></i>
                                    <?= strtoupper($status) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <button class="tool-btn" title="Edit Agent"><i class="fas fa-edit"></i></button>
                                <button class="tool-btn" title="More Options"><i class="fas fa-ellipsis-v"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i class="fas fa-users"></i>
                                <p style="font-weight: 600;">No TSR accounts found</p>
                                <span style="color: var(--text-tertiary); font-size: 13px;">Create your first agent to get started</span>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="createTsrModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px;" class="modal-overlay">
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);">
            <div style="padding: 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0 0 4px 0;">Create TSR Account</h3>
                    <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0;">Add a new technical support representative to your team</p>
                </div>
                <button onclick="toggleModal('createTsrModal')" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5rem; padding: 0;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="<?= site_url('sa/create-tsr') ?>" method="post" style="padding: 24px;">
                <?= csrf_field() ?>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">
                        Username / Nickname <span style="color: var(--accent-red);">*</span>
                    </label>
                    <div style="position: relative;">
                        <i class="fas fa-user" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-tertiary);"></i>
                        <input type="text" name="username" required placeholder="e.g., juandc" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px 10px 36px; color: var(--text-primary); font-size: 13px; transition: all 0.3s;" autocomplete="off" value="<?= old('username') ?>">
                    </div>
                    <span style="font-size: 12px; color: var(--text-tertiary); display: block; margin-top: 6px;">Unique system login identifier (4+ chars)</span>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">
                        Full Name <span style="color: var(--accent-red);">*</span>
                    </label>
                    <div style="position: relative;">
                        <i class="fas fa-id-badge" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-tertiary);"></i>
                        <input type="text" name="name" required placeholder="First Name Last Name" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px 10px 36px; color: var(--text-primary); font-size: 13px; transition: all 0.3s;" value="<?= old('name') ?>">
                    </div>
                    <span style="font-size: 12px; color: var(--text-tertiary); display: block; margin-top: 6px;">Agent's display name in the system</span>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">
                        Password <span style="color: var(--accent-red);">*</span>
                    </label>
                    <div style="position: relative;">
                        <i class="fas fa-lock" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-tertiary);"></i>
                        <input type="password" id="passwordField" name="password" required placeholder="••••••••" minlength="8" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px 10px 36px; color: var(--text-primary); font-size: 13px; transition: all 0.3s;">
                        <button type="button" onclick="togglePassword(event)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-tertiary); cursor: pointer;">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <span style="font-size: 12px; color: var(--text-tertiary); display: block; margin-top: 6px;">Min 8 chars, 1 Upper, 1 Lower, 1 Number, 1 Special Char</span>
                </div>

                <div style="background: rgba(31, 111, 235, 0.1); border: 1px solid rgba(31, 111, 235, 0.2); border-radius: 6px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px;">
                    <i class="fas fa-info-circle" style="color: var(--accent-blue); margin-top: 2px; flex-shrink: 0;"></i>
                    <span style="font-size: 12px; color: rgba(230, 237, 243, 0.8); line-height: 1.4;">The agent will receive an email notification with login credentials</span>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="toggleModal('createTsrModal')" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        <span>Create Account</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

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
            const modal = document.querySelector('.modal-overlay[style*="display: flex"]');
            if (modal) toggleModal(modal.id);
        }
    });

    <?php if (session()->getFlashdata('error') && strpos(session()->getFlashdata('error'), 'Validation') !== false): ?>
    document.addEventListener('DOMContentLoaded', function() {
        toggleModal('createTsrModal');
    });
    <?php endif; ?>
</script>

</body>
</html>