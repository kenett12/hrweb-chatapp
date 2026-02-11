<?= view('partials/admin-header') ?>
<?= view('partials/admin-sidebar', ['active_page' => 'dashboard']) ?>

<main class="main-content">
    <header class="content-header">
        <div class="breadcrumb">
            <span class="breadcrumb-root">SuperAdmin</span>
            <i class="fas fa-chevron-right breadcrumb-sep"></i>
            <span class="breadcrumb-current">System Overview</span>
        </div>
        <div class="header-actions">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" placeholder="Search data..." class="search-input">
            </div>
            <button class="icon-btn" title="Notifications">
                <i class="far fa-bell"></i>
                <span class="notification-dot"></span>
            </button>
        </div>
    </header>

    <div class="page-body">
        <div class="page-heading">
            <div>
                <h1 class="page-title">Enterprise Performance</h1>
                <p class="page-subtitle">Real-time overview of your support infrastructure.</p>
            </div>
            <div class="action-buttons">
                <button class="btn btn-secondary"><i class="fas fa-file-export"></i> Report</button>
                <button class="btn btn-primary" onclick="location.href='<?= base_url('sa/tsr-accounts') ?>'"><i class="fas fa-user-plus"></i> Create TSR</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="admin-card stats-card brand-border">
                <span class="stats-label">Total Agents</span>
                <div class="stats-value-container">
                    <h3 class="stats-number"><?= $stats['total_tsrs'] ?? 0 ?></h3>
                    <i class="fas fa-users stats-icon"></i>
                </div>
            </div>
            <div class="admin-card stats-card emerald-border">
                <span class="stats-label">Online Now</span>
                <div class="stats-value-container">
                    <h3 class="stats-number text-emerald"><?= $stats['active_now'] ?? 0 ?></h3>
                    <div class="pulse-dot"></div>
                </div>
            </div>
            <div class="admin-card stats-card amber-border">
                <span class="stats-label">Tickets Today</span>
                <div class="stats-value-container">
                    <h3 class="stats-number"><?= $stats['tickets_today'] ?? 0 ?></h3>
                    <i class="fas fa-ticket-alt stats-icon"></i>
                </div>
            </div>
            <div class="admin-card stats-card violet-border">
                <span class="stats-label">Avg Response</span>
                <div class="stats-value-container">
                    <h3 class="stats-number"><?= $stats['avg_response'] ?? 'N/A' ?></h3>
                    <i class="fas fa-bolt stats-icon text-violet"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="admin-card chart-card">
                <div class="card-header"><h3 class="card-title">Agent Utilization Trend</h3></div>
                <div class="chart-container" style="height:320px;"><canvas id="utilizationChart"></canvas></div>
            </div>
            <div class="admin-card feed-card">
                <h3 class="card-title">Live System Feed</h3>
                <div class="feed-container">
                    <?php if(!empty($feed)): foreach($feed as $item): ?>
                    <div class="feed-item brand-item">
                        <span class="feed-time"><?= $item['time'] ?></span>
                        <p class="feed-text"><span class="feed-user"><?= $item['user'] ?></span> <?= $item['text'] ?></p>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="feed-item brand-item">
                        <span class="feed-time"><?= date('H:i') ?></span>
                        <p class="feed-text"><span class="feed-user">System</span> Monitoring active.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('utilizationChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '23:59'],
            datasets: [{
                label: 'Agent Utilization',
                data: [15, 10, 45, 80, 75, 50, 30],
                borderColor: '#1f6feb',
                backgroundColor: 'rgba(31, 111, 235, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointBackgroundColor: '#1f6feb',
                pointBorderColor: '#0d1117',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                filler: { propagate: true }
            },
            scales: {
                x: { 
                    grid: { display: false },
                    ticks: { color: '#8b949e', font: { size: 12, weight: '500' } }
                },
                y: { 
                    grid: { color: 'rgba(48, 54, 61, 0.5)' },
                    ticks: { color: '#8b949e', font: { size: 12, weight: '500' } },
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
</script>
</body>
</html>