<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TSR Dashboard</title>
    <link rel="stylesheet" href="<?= base_url('css/styles.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/theme.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/tsr-dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="min-height: 100vh;">
    <div class="dashboard-container">
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= esc($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="dashboard-header">
            <div class="header-left">
                <h1 class="header-title">TSR Dashboard</h1>
            </div>
            <div class="header-actions">
                <button onclick="openQueueViewer()" class="btn btn-secondary" style="margin-right: 10px;" title="Open Live Queue Window">
                    <i class="fas fa-columns"></i> Live Queue
                </button>

                <button id="theme-toggle" class="theme-toggle" title="Toggle Dark Mode">
                    <i class="fas fa-moon"></i>
                </button>
                
                <a href="<?= base_url('/logout') ?>" class="btn btn-primary">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
                <div class="logo-placeholder">
                    <i class="fas fa-headset"></i>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Open Tickets</div>
                        <div class="stat-number"><?= $openTickets ?? 0 ?></div>
                        <div class="stat-description">
                            <i class="fas fa-clock"></i>
                            Awaiting response
                        </div>
                    </div>
                    <div class="stat-icon open">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">In Progress</div>
                        <div class="stat-number"><?= $inProgressTickets ?? 0 ?></div>
                        <div class="stat-description">
                            <i class="fas fa-tools"></i>
                            Being handled
                        </div>
                    </div>
                    <div class="stat-icon progress">
                        <i class="fas fa-spinner"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Resolved</div>
                        <div class="stat-number"><?= $resolvedTickets ?? 0 ?></div>
                        <div class="stat-description">
                            <i class="fas fa-thumbs-up"></i>
                            Completed today
                        </div>
                    </div>
                    <div class="stat-icon resolved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-inbox"></i>
                    Unassigned Tickets
                </div>
                <?php if (!empty($unassignedTickets)): ?>
                    <span class="section-badge"><?= count($unassignedTickets) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="filter-controls">
                <div class="filter-group">
                    <label class="filter-label">Priority:</label>
                    <select class="filter-select" id="unassigned-priority-filter">
                        <option value="">All Priorities</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Category:</label>
                    <select class="filter-select" id="unassigned-category-filter">
                        <option value="">All Categories</option>
                        <option value="technical">Technical</option>
                        <option value="billing">Billing</option>
                        <option value="feature">Feature</option>
                        <option value="general">General</option>
                    </select>
                </div>
                <button class="filter-button" id="unassigned-reset-filters">
                    <i class="fas fa-undo"></i> Reset Filters
                </button>
            </div>
            
            <?php if (empty($unassignedTickets)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>All tickets are assigned!</h3>
                    <p>Great job! There are no unassigned tickets at the moment.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table-modern" id="unassigned-tickets-table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Subject</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unassignedTickets as $ticket): ?>
                            <tr data-priority="<?= strtolower($ticket['priority']) ?>" data-category="<?= strtolower($ticket['category']) ?>">
                                <td>
                                    <span class="ticket-id">#<?= $ticket['id'] ?></span>
                                </td>
                                <td>
                                    <strong><?= esc($ticket['subject']) ?></strong>
                                </td>
                                <td>
                                    <i class="fas fa-tag" style="color: #d1d5db; margin-right: 0.5rem;"></i>
                                    <?= ucfirst(esc($ticket['category'])) ?>
                                </td>
                                <td>
                                    <span class="priority-badge priority-<?= strtolower($ticket['priority']) ?>">
                                        <?= ucfirst($ticket['priority']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="time-ago"><?= date('M j, H:i', strtotime($ticket['created_at'])) ?></span>
                                </td>
                                <td>
                                    <button class="btn-action btn-claim claim-ticket" data-ticket-id="<?= $ticket['id'] ?>">
                                        <i class="fas fa-hand-paper"></i>
                                        Claim
                                    </button>
                                    <a href="<?= base_url('tsr/ticket/' . $ticket['id']) ?>" class="btn-action btn-view">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-user-check"></i>
                    My Assigned Tickets
                </div>
                <div class="section-actions">
                    <?php if (!empty($assignedTickets)): ?>
                        <span class="section-badge"><?= count($assignedTickets) ?></span>
                    <?php endif; ?>
                    <button class="btn-secondary" id="refresh-assigned" title="Refresh Tickets">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <div class="filter-controls">
                <div class="filter-group">
                    <label class="filter-label">Status:</label>
                    <select class="filter-select" id="assigned-status-filter">
                        <option value="">All Statuses</option>
                        <option value="in-progress">In Progress</option>
                        <option value="pending">Pending</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Priority:</label>
                    <select class="filter-select" id="assigned-priority-filter">
                        <option value="">All Priorities</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Category:</label>
                    <select class="filter-select" id="assigned-category-filter">
                        <option value="">All Categories</option>
                        <option value="technical">Technical</option>
                        <option value="billing">Billing</option>
                        <option value="feature">Feature</option>
                        <option value="general">General</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Show:</label>
                    <div class="filter-buttons">
                        <button class="filter-button active" data-filter="all">All</button>
                        <button class="filter-button" data-filter="active">Active</button>
                        <button class="filter-button" data-filter="closed">Closed</button>
                    </div>
                </div>
                <button class="filter-button" id="assigned-reset-filters">
                    <i class="fas fa-undo"></i> Reset Filters
                </button>
            </div>
            
            <?php if (empty($assignedTickets)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No assigned tickets</h3>
                    <p>You don't have any tickets assigned to you yet. Claim some tickets above to get started!</p>
                </div>
            <?php else: ?>
                <div class="tickets-grid" id="assigned-tickets-grid">
                    <?php foreach ($assignedTickets as $ticket): ?>
                        <?php 
                            $isActive = in_array($ticket['status'], ['open', 'in-progress', 'pending']);
                            $isClosed = in_array($ticket['status'], ['resolved', 'closed']);
                        ?>
                        <div class="ticket-card <?= $isActive ? 'ticket-active' : ($isClosed ? 'ticket-closed' : '') ?>" 
                             data-status="<?= strtolower($ticket['status']) ?>" 
                             data-priority="<?= strtolower($ticket['priority']) ?>" 
                             data-category="<?= strtolower($ticket['category']) ?>"
                             data-ticket-id="<?= $ticket['id'] ?>">
                            
                            <div class="ticket-header">
                                <div class="ticket-id-section">
                                    <span class="ticket-id">#<?= $ticket['id'] ?></span>
                                    <span class="ticket-time"><?= date('M j, H:i', strtotime($ticket['updated_at'])) ?></span>
                                </div>
                                <div class="ticket-status-section">
                                    <span class="status-badge status-<?= str_replace('-', '-', strtolower($ticket['status'])) ?>">
                                        <?= ucfirst(str_replace('-', ' ', $ticket['status'])) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="ticket-content <?= $isActive ? 'clickable-ticket' : '' ?>" 
                                 <?= $isActive ? 'onclick="openTicketChat(' . $ticket['id'] . ')"' : '' ?>>
                                <h4 class="ticket-subject"><?= esc($ticket['subject']) ?></h4>
                                <div class="ticket-meta">
                                    <span class="ticket-customer">
                                        <i class="fas fa-user"></i>
                                        <?= esc($ticket['customer_name']) ?>
                                    </span>
                                    <span class="ticket-category">
                                        <i class="fas fa-tag"></i>
                                        <?= ucfirst(esc($ticket['category'])) ?>
                                    </span>
                                    <span class="priority-badge priority-<?= strtolower($ticket['priority']) ?>">
                                        <?= ucfirst($ticket['priority']) ?>
                                    </span>
                                </div>
                                
                            </div>
                            
                            <div class="ticket-actions">
                                <?php if ($isActive): ?>
                                    <button class="btn-action btn-chat" onclick="openTicketChat(<?= $ticket['id'] ?>)">
                                        <i class="fas fa-comments"></i>
                                        Open Chat
                                    </button>
                                <?php else: ?>
                                    <a href="<?= base_url('tsr/ticket/' . $ticket['id']) ?>" class="btn-action btn-view">
                                        <i class="fas fa-eye"></i>
                                        View Details
                                    </a>
                                <?php endif; ?>
                                
                                <div class="ticket-actions-menu">
                                    <button class="btn-action btn-menu" onclick="toggleTicketMenu(<?= $ticket['id'] ?>)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="ticket-menu" id="ticket-menu-<?= $ticket['id'] ?>">
                                        <a href="<?= base_url('tsr/ticket/' . $ticket['id']) ?>">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <?php if ($isActive): ?>
                                            <button onclick="updateTicketStatus(<?= $ticket['id'] ?>, 'resolved')">
                                                <i class="fas fa-check"></i> Mark Resolved
                                            </button>
                                            <button onclick="updateTicketStatus(<?= $ticket['id'] ?>, 'pending')">
                                                <i class="fas fa-pause"></i> Set Pending
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($ticket['status'] === 'resolved'): ?>
                                            <button onclick="updateTicketStatus(<?= $ticket['id'] ?>, 'closed')">
                                                <i class="fas fa-times"></i> Close Ticket
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="view-toggle">
                    <button class="view-toggle-btn active" data-view="grid">
                        <i class="fas fa-th-large"></i> Card View
                    </button>
                    <button class="view-toggle-btn" data-view="table">
                        <i class="fas fa-table"></i> Table View
                    </button>
                </div>
                
                <div class="table-container" id="assigned-tickets-table-container" style="display: none;">
                    <table class="table-modern" id="assigned-tickets-table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Subject</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignedTickets as $ticket): ?>
                                <?php 
                                    $isActive = in_array($ticket['status'], ['open', 'in-progress', 'pending']);
                                    $isClosed = in_array($ticket['status'], ['resolved', 'closed']);
                                ?>
                                <tr class="<?= $isActive ? 'table-row-active' : ($isClosed ? 'table-row-closed' : '') ?>"
                                    data-status="<?= strtolower($ticket['status']) ?>" 
                                    data-priority="<?= strtolower($ticket['priority']) ?>" 
                                    data-category="<?= strtolower($ticket['category']) ?>">
                                    <td>
                                        <span class="ticket-id">#<?= $ticket['id'] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <a href="<?= base_url('tsr/ticket/' . $ticket['id'] . '/chat') ?>" class="ticket-subject-link">
                                                <strong><?= esc($ticket['subject']) ?></strong>
                                            </a>
                                        <?php else: ?>
                                            <strong><?= esc($ticket['subject']) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($ticket['customer_name']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= str_replace('-', '-', strtolower($ticket['status'])) ?>">
                                            <?= ucfirst(str_replace('-', ' ', $ticket['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="priority-badge priority-<?= strtolower($ticket['priority']) ?>">
                                            <?= ucfirst($ticket['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="time-ago"><?= date('M j, H:i', strtotime($ticket['updated_at'])) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <a href="<?= base_url('tsr/ticket/' . $ticket['id'] . '/chat') ?>" class="btn-action btn-chat">
                                                <i class="fas fa-comments"></i>
                                                Chat
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= base_url('tsr/ticket/' . $ticket['id']) ?>" class="btn-action btn-view">
                                                <i class="fas fa-eye"></i>
                                                View
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Open the Standalone Queue Monitor
        function openQueueViewer() {
            window.open('<?= base_url('tsr/queue') ?>', 'QueueViewer', 'width=420,height=900,scrollbars=yes,resizable=yes');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Remove any existing image viewer modals
            const imageModals = document.querySelectorAll('#imageModal, .image-viewer-modal, .modal');
            imageModals.forEach(modal => {
                if (modal) {
                    modal.style.display = 'none';
                    modal.remove();
                }
            });

            // Override any image viewer functions
            window.openImageViewer = function() { return false; };
            
            // Claim ticket functionality
            document.querySelectorAll('.claim-ticket').forEach(button => {
                button.addEventListener('click', function() {
                    const ticketId = this.getAttribute('data-ticket-id');
                    const originalText = this.innerHTML;
                    
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Claiming...';
                    this.disabled = true;
                    
                    fetch('<?= base_url('tsr/claimTicket') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `ticket_id=${ticketId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('success', 'Ticket claimed successfully!');
                            
                            this.innerHTML = '<i class="fas fa-check"></i> Claimed!';
                            this.style.background = 'rgba(16, 185, 129, 1)';
                            
                            if (data.redirect) {
                                setTimeout(() => {
                                    window.location.href = data.redirect;
                                }, 1000);
                            } else {
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            }
                        } else {
                            showNotification('error', data.message || 'Failed to claim ticket');
                            this.innerHTML = originalText;
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('error', 'An error occurred while claiming the ticket');
                        this.innerHTML = originalText;
                        this.disabled = false;
                    });
                });
            });
            
            // Filter functionality for unassigned tickets
            const unassignedPriorityFilter = document.getElementById('unassigned-priority-filter');
            const unassignedCategoryFilter = document.getElementById('unassigned-category-filter');
            const unassignedResetFilters = document.getElementById('unassigned-reset-filters');
            const unassignedTicketsTable = document.getElementById('unassigned-tickets-table');
            
            if (unassignedPriorityFilter && unassignedCategoryFilter && unassignedResetFilters && unassignedTicketsTable) {
                const filterUnassignedTickets = () => {
                    const priorityValue = unassignedPriorityFilter.value;
                    const categoryValue = unassignedCategoryFilter.value;
                    
                    const rows = unassignedTicketsTable.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        const rowPriority = row.getAttribute('data-priority');
                        const rowCategory = row.getAttribute('data-category');
                        
                        const priorityMatch = !priorityValue || rowPriority === priorityValue;
                        const categoryMatch = !categoryValue || rowCategory === categoryValue;
                        
                        if (priorityMatch && categoryMatch) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                };
                
                unassignedPriorityFilter.addEventListener('change', filterUnassignedTickets);
                unassignedCategoryFilter.addEventListener('change', filterUnassignedTickets);
                
                unassignedResetFilters.addEventListener('click', () => {
                    unassignedPriorityFilter.value = '';
                    unassignedCategoryFilter.value = '';
                    filterUnassignedTickets();
                });
            }
            
            // Global functions for ticket actions
            window.toggleTicketMenu = function(ticketId) {
                const menuId = 'ticket-menu-' + ticketId;
                const currentMenu = document.getElementById(menuId);

                if (!currentMenu) return;

                const allMenus = document.querySelectorAll('.ticket-menu');
                allMenus.forEach(menu => {
                    if (menu !== currentMenu) {
                        menu.style.display = 'none';
                    }
                });

                const isVisible = currentMenu.style.display === 'block';
                currentMenu.style.display = isVisible ? 'none' : 'block';

                if (!isVisible) {
                    const closeMenu = (e) => {
                        const button = document.querySelector(`[onclick="toggleTicketMenu(${ticketId})"]`);
                        if (!currentMenu.contains(e.target) && (!button || !button.contains(e.target))) {
                            currentMenu.style.display = 'none';
                            document.removeEventListener('click', closeMenu);
                        }
                    };
                    setTimeout(() => {
                        document.addEventListener('click', closeMenu);
                    }, 10);
                }
            };

            window.updateTicketStatus = function(ticketId, status) {
                if (confirm(`Are you sure you want to mark this ticket as ${status}?`)) {
                    fetch(`<?= base_url('api/tickets/') ?>${ticketId}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ status: status })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('success', `Ticket marked as ${status}`);
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showNotification('error', data.message || 'Failed to update ticket status');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('error', 'An error occurred while updating the ticket');
                    });
                }
            };

            window.openTicketChat = function(ticketId) {
                window.location.href = `<?= base_url('tsr/ticket/') ?>${ticketId}/chat`;
            };

            // Enhanced filter functionality for assigned tickets
            const assignedStatusFilter = document.getElementById('assigned-status-filter');
            const assignedPriorityFilter = document.getElementById('assigned-priority-filter');
            const assignedCategoryFilter = document.getElementById('assigned-category-filter');
            const assignedResetFilters = document.getElementById('assigned-reset-filters');
            const assignedTicketsGrid = document.getElementById('assigned-tickets-grid');
            const assignedTicketsTable = document.getElementById('assigned-tickets-table');

            const filterAssignedTickets = () => {
                const statusValue = assignedStatusFilter ? assignedStatusFilter.value : '';
                const priorityValue = assignedPriorityFilter ? assignedPriorityFilter.value : '';
                const categoryValue = assignedCategoryFilter ? assignedCategoryFilter.value : '';
                const showFilter = document.querySelector('.filter-buttons .filter-button.active')?.getAttribute('data-filter') || 'all';
                
                if (assignedTicketsGrid) {
                    const gridCards = assignedTicketsGrid.querySelectorAll('.ticket-card');
                    gridCards.forEach(card => {
                        const cardStatus = card.getAttribute('data-status');
                        const cardPriority = card.getAttribute('data-priority');
                        const cardCategory = card.getAttribute('data-category');
                        
                        const statusMatch = !statusValue || cardStatus === statusValue;
                        const priorityMatch = !priorityValue || cardPriority === priorityValue;
                        const categoryMatch = !categoryValue || cardCategory === categoryValue;
                        
                        let showMatch = true;
                        if (showFilter === 'active') {
                            showMatch = ['open', 'in-progress', 'pending'].includes(cardStatus);
                        } else if (showFilter === 'closed') {
                            showMatch = ['resolved', 'closed'].includes(cardStatus);
                        }
                        
                        if (statusMatch && priorityMatch && categoryMatch && showMatch) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                }
                
                if (assignedTicketsTable) {
                    const tableRows = assignedTicketsTable.querySelectorAll('tbody tr');
                    tableRows.forEach(row => {
                        const rowStatus = row.getAttribute('data-status');
                        const rowPriority = row.getAttribute('data-priority');
                        const rowCategory = row.getAttribute('data-category');
                        
                        const statusMatch = !statusValue || rowStatus === statusValue;
                        const priorityMatch = !priorityValue || rowPriority === priorityValue;
                        const categoryMatch = !categoryValue || rowCategory === categoryValue;
                        
                        let showMatch = true;
                        if (showFilter === 'active') {
                            showMatch = ['open', 'in-progress', 'pending'].includes(rowStatus);
                        } else if (showFilter === 'closed') {
                            showMatch = ['resolved', 'closed'].includes(rowStatus);
                        }
                        
                        if (statusMatch && priorityMatch && categoryMatch && showMatch) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }
            };

            if (assignedStatusFilter) assignedStatusFilter.addEventListener('change', filterAssignedTickets);
            if (assignedPriorityFilter) assignedPriorityFilter.addEventListener('change', filterAssignedTickets);
            if (assignedCategoryFilter) assignedCategoryFilter.addEventListener('change', filterAssignedTickets);

            if (assignedResetFilters) {
                assignedResetFilters.addEventListener('click', () => {
                    if (assignedStatusFilter) assignedStatusFilter.value = '';
                    if (assignedPriorityFilter) assignedPriorityFilter.value = '';
                    if (assignedCategoryFilter) assignedCategoryFilter.value = '';
                    
                    document.querySelectorAll('.filter-buttons .filter-button').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    document.querySelector('.filter-buttons .filter-button[data-filter="all"]')?.classList.add('active');
                    
                    filterAssignedTickets();
                });
            }

            const assignedFilterButtons = document.querySelectorAll('.filter-buttons .filter-button');
            assignedFilterButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    assignedFilterButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    filterAssignedTickets();
                });
            });
            
            function showNotification(type, message) {
                const existingNotifications = document.querySelectorAll('.notification');
                existingNotifications.forEach(notification => {
                    notification.remove();
                });
                
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                
                let icon = 'info-circle';
                if (type === 'success') icon = 'check-circle';
                if (type === 'error') icon = 'exclamation-circle';
                
                notification.innerHTML = `
                    <div class="notification-content">
                        <i class="fas fa-${icon}" style="color: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'}; margin-right: 8px;"></i>
                        <span>${message}</span>
                    </div>
                    <button class="notification-close">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                document.body.appendChild(notification);
                
                const closeButton = notification.querySelector('.notification-close');
                closeButton.addEventListener('click', () => {
                    notification.remove();
                });
                
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 5000);
            }
            
            document.querySelectorAll('.view-toggle-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const view = this.getAttribute('data-view');
                    const gridView = document.getElementById('assigned-tickets-grid');
                    const tableView = document.getElementById('assigned-tickets-table-container');
                    
                    document.querySelectorAll('.view-toggle-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    if (view === 'grid') {
                        gridView.style.display = 'grid';
                        tableView.style.display = 'none';
                    } else {
                        gridView.style.display = 'none';
                        tableView.style.display = 'block';
                    }
                });
            });
            
            document.getElementById('refresh-assigned')?.addEventListener('click', function() {
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            });
        });

        // Theme Management
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            applyTheme(savedTheme);
            updateThemeToggleButton(savedTheme);
        }

        function applyTheme(theme) {
            document.body.classList.remove('light-theme', 'dark-theme');
            document.body.classList.add(`${theme}-theme`);
            localStorage.setItem('theme', theme);
        }

        function toggleTheme() {
            const currentTheme = localStorage.getItem('theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
            updateThemeToggleButton(newTheme);
        }

        function updateThemeToggleButton(theme) {
            const themeToggleBtn = document.getElementById('theme-toggle');
            if (themeToggleBtn) {
                if (theme === 'dark') {
                    themeToggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
                    themeToggleBtn.title = 'Switch to Light Mode';
                } else {
                    themeToggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
                    themeToggleBtn.title = 'Switch to Dark Mode';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();
            document.getElementById('theme-toggle')?.addEventListener('click', toggleTheme);
        });
    </script>
</body>
</html>