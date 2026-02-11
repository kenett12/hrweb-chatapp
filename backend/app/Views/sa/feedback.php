<?= view('partials/admin-header') ?>
<?= view('partials/admin-sidebar', ['active_page' => 'feedback']) ?>

<main class="main-content">
    <header class="content-header">
        <div class="breadcrumb">
            <span class="breadcrumb-root">SuperAdmin</span>
            <i class="fas fa-chevron-right breadcrumb-sep"></i>
            <span class="breadcrumb-current">Knowledge Base Manager</span>
        </div>
        <div class="header-actions">
            <button class="icon-btn" title="Refresh Data" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </header>

    <div class="page-body">
        <div class="page-heading">
            <div>
                <h1 class="page-title">Knowledge Base</h1>
                <p class="page-subtitle">Manage chatbot answers, intents, and training data.</p>
            </div>
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Add New Entry
                </button>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #34d399; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle"></i>
                <span><?= session()->getFlashdata('success') ?></span>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="admin-card stats-card brand-border">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 56px; height: 56px; background: rgba(31, 111, 235, 0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #3b82f6;">
                        <i class="fas fa-book"></i>
                    </div>
                    <div>
                        <span class="stats-label">Total Entries</span>
                        <h3 class="stats-number"><?= $stats['total'] ?? 0 ?></h3>
                    </div>
                </div>
            </div>

            <div class="admin-card stats-card emerald-border">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 56px; height: 56px; background: rgba(63, 185, 80, 0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #10b981;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <span class="stats-label">Active Answers</span>
                        <h3 class="stats-number" style="color: #10b981;"><?= $stats['active'] ?? 0 ?></h3>
                    </div>
                </div>
            </div>

            <div class="admin-card stats-card amber-border">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 56px; height: 56px; background: rgba(245, 158, 11, 0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #f59e0b;">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <span class="stats-label">Drafts / Hidden</span>
                        <h3 class="stats-number" style="color: #f59e0b;"><?= $stats['drafts'] ?? 0 ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card table-card">
            <div class="card-header">
                <h3 class="card-title">Q&A Repository</h3>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 25%;">Question / Keyword</th>
                            <th style="width: 35%;">Bot Answer</th>
                            <th style="width: 15%;">Intent</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 10%; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($entries)): ?>
                            <tr>
                                <td colspan="6" class="empty-state" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-search" style="font-size: 32px; opacity: 0.5; margin-bottom: 10px;"></i>
                                    <p style="font-weight: 600; color: #9ca3af;">No entries found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($entries as $row): ?>
                            <tr>
                                <td>
                                    <span style="font-family: monospace; color: #3b82f6;">#<?= $row['id'] ?></span>
                                </td>
                                <td>
                                    <div style="font-weight: 500; color: #fff;"><?= esc($row['question']) ?></div>
                                </td>
                                <td>
                                    <div style="font-size: 13px; color: #9ca3af; max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= esc($row['answer']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 4px; font-size: 12px; color: #d1d5db;">
                                        <?= esc($row['intent']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['approved']): ?>
                                        <span style="background: rgba(16, 185, 129, 0.15); color: #34d399; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid rgba(16, 185, 129, 0.2);">
                                            ACTIVE
                                        </span>
                                    <?php else: ?>
                                        <span style="background: rgba(107, 114, 128, 0.15); color: #9ca3af; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid rgba(107, 114, 128, 0.2);">
                                            DRAFT
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button onclick='editKb(<?= json_encode($row) ?>)' style="width: 32px; height: 32px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.03); color: #9ca3af; cursor: pointer;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="<?= base_url('sa/knowledge-base/delete/'.$row['id']) ?>" onclick="return confirm('Delete this entry?')" style="width: 32px; height: 32px; border-radius: 6px; border: 1px solid rgba(248, 81, 73, 0.3); background: rgba(248, 81, 73, 0.1); color: #f85149; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="kbModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(2px);" onclick="closeModal()"></div>
        <div style="position: relative; margin: 5% auto; background: #1a1d24; width: 90%; max-width: 500px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
            
            <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: #fff; font-size: 18px;" id="modalTitle">Add New Entry</h3>
                <button onclick="closeModal()" style="background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 16px;"><i class="fas fa-times"></i></button>
            </div>

            <form action="<?= base_url('sa/knowledge-base/save') ?>" method="post">
                <input type="hidden" name="id" id="kbId">
                <div style="padding: 20px;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; color: #d1d5db; font-size: 13px; margin-bottom: 8px;">Question / Trigger Phrase</label>
                        <input type="text" name="question" id="kbQuestion" required placeholder="e.g. I forgot my password" 
                               style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 10px; border-radius: 6px; color: #fff; outline: none;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; color: #d1d5db; font-size: 13px; margin-bottom: 8px;">Intent (Category)</label>
                        <input type="text" name="intent" id="kbIntent" required placeholder="e.g. password_reset" 
                               style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 10px; border-radius: 6px; color: #fff; outline: none;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; color: #d1d5db; font-size: 13px; margin-bottom: 8px;">Bot Answer</label>
                        <textarea name="answer" id="kbAnswer" rows="5" required placeholder="Type the response..." 
                                  style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 10px; border-radius: 6px; color: #fff; outline: none;"></textarea>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <label style="display: block; color: #d1d5db; font-size: 13px; margin-bottom: 8px;">Status</label>
                        <select name="approved" id="kbApproved" style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 10px; border-radius: 6px; color: #fff; outline: none;">
                            <option value="1">Active</option>
                            <option value="0">Draft</option>
                        </select>
                    </div>
                </div>

                <div style="padding: 20px; border-top: 1px solid rgba(255,255,255,0.08); text-align: right;">
                    <button type="button" onclick="closeModal()" style="padding: 8px 16px; background: transparent; border: 1px solid rgba(255,255,255,0.1); color: #d1d5db; border-radius: 6px; margin-right: 8px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 8px 16px; background: #2563eb; border: none; color: white; border-radius: 6px; cursor: pointer;">Save Entry</button>
                </div>
            </form>
        </div>
    </div>

</main>

<script>
    function openModal() {
        document.getElementById('kbModal').style.display = 'block';
        document.getElementById('modalTitle').innerText = 'Add New Entry';
        document.getElementById('kbId').value = '';
        document.getElementById('kbQuestion').value = '';
        document.getElementById('kbIntent').value = '';
        document.getElementById('kbAnswer').value = '';
        document.getElementById('kbApproved').value = '1';
    }

    function closeModal() {
        document.getElementById('kbModal').style.display = 'none';
    }

    function editKb(data) {
        document.getElementById('kbModal').style.display = 'block';
        document.getElementById('modalTitle').innerText = 'Edit Entry #' + data.id;
        document.getElementById('kbId').value = data.id;
        document.getElementById('kbQuestion').value = data.question;
        document.getElementById('kbIntent').value = data.intent;
        document.getElementById('kbAnswer').value = data.answer;
        document.getElementById('kbApproved').value = data.approved;
    }
</script>

</body>
</html>