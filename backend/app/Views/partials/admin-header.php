<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($page_title ?? 'HRWeb Pro - Admin') ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed: 80px;
            --bg-primary: #0d1117;
            --bg-secondary: #161b22;
            --bg-tertiary: #1c2128;
            --bg-hover: #21262d;
            --border-color: #30363d;
            --text-primary: #e6edf3;
            --text-secondary: #8b949e;
            --text-tertiary: #6e7681;
            --accent-blue: #1f6feb;
            --accent-blue-hover: #388bfd;
            --accent-green: #3fb950;
            --accent-red: #f85149;
            --accent-yellow: #d29922;
            --accent-purple: #a371f7;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.5);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ===== LAYOUT ===== */
        body {
            display: flex;
            flex-direction: column;
        }

        .dashboard-wrapper {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: var(--transition);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-shrink: 0;
            min-height: 70px;
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-primary);
            flex: 1;
            min-width: 0;
        }

        .brand-icon {
            color: var(--accent-blue);
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .brand-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: var(--transition);
        }

        .brand-highlight {
            color: var(--accent-blue);
            font-weight: 800;
        }

        .sidebar.collapsed .brand-text {
            display: none;
        }

        .toggle-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .toggle-btn:hover {
            color: var(--text-primary);
            background-color: var(--bg-hover);
        }

        .sidebar-content {
            flex: 1;
            padding: 16px 0;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .nav-group {
            margin-bottom: 24px;
        }

        .nav-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--text-tertiary);
            font-weight: 700;
            padding: 0 20px;
            margin-bottom: 12px;
            letter-spacing: 0.08em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar.collapsed .nav-label {
            padding: 0 20px;
            font-size: 0;
        }

        .sidebar.collapsed .nav-label span {
            display: none;
        }

        .nav-list {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 0 8px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 12px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 6px;
            transition: var(--transition);
            border-left: 3px solid transparent;
            margin: 0 4px;
            font-weight: 500;
        }

        .sidebar-link:hover {
            background-color: var(--bg-hover);
            color: var(--text-primary);
        }

        .sidebar-link.active {
            background-color: rgba(31, 111, 235, 0.15);
            color: var(--accent-blue);
            font-weight: 600;
        }

        .sidebar-link i {
            min-width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .link-text {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar.collapsed .link-text {
            display: none;
        }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid var(--border-color);
            background-color: var(--bg-secondary);
            flex-shrink: 0;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            border-radius: 6px;
            transition: var(--transition);
        }

        .user-profile:hover {
            background-color: var(--bg-hover);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--accent-purple) 100%);
            color: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .user-info {
            flex: 1;
            min-width: 0;
            transition: var(--transition);
        }

        .user-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-email {
            font-size: 0.75rem;
            color: var(--text-tertiary);
            margin: 2px 0 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar.collapsed .user-info {
            display: none;
        }

        .sidebar.collapsed .user-profile {
            justify-content: center;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            background-color: var(--bg-primary);
            overflow-y: auto;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed);
        }

        .main-content::-webkit-scrollbar {
            width: 8px;
        }

        .main-content::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }

        .main-content::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        .main-content::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        /* ===== CONTENT HEADER ===== */
        .content-header {
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-shrink: 0;
            background-color: var(--bg-secondary);
            min-height: 70px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .breadcrumb-root {
            color: var(--text-primary);
            font-weight: 600;
        }

        .breadcrumb-sep {
            color: var(--text-tertiary);
            font-size: 10px;
        }

        .breadcrumb-current {
            color: var(--text-secondary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
        }

        .search-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            color: var(--text-tertiary);
            font-size: 13px;
            pointer-events: none;
        }

        .search-input {
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 8px 12px 8px 36px;
            color: var(--text-primary);
            font-size: 13px;
            width: 240px;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-blue);
            background-color: var(--bg-hover);
            box-shadow: 0 0 0 3px rgba(31, 111, 235, 0.1);
        }

        .search-input::placeholder {
            color: var(--text-tertiary);
        }

        .icon-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border-radius: 6px;
            transition: var(--transition);
        }

        .icon-btn:hover {
            color: var(--text-primary);
            background-color: var(--bg-hover);
        }

        .notification-dot {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            background-color: var(--accent-red);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* ===== PAGE BODY ===== */
        .page-body {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .page-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 30px;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 8px 0;
        }

        .page-subtitle {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary {
            background-color: var(--accent-blue);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background-color: var(--accent-blue-hover);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .btn-secondary:hover {
            background-color: var(--bg-hover);
            border-color: var(--text-secondary);
        }

        .tool-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1rem;
            padding: 6px 8px;
            border-radius: 4px;
            transition: var(--transition);
        }

        .tool-btn:hover {
            color: var(--accent-blue);
            background-color: var(--bg-hover);
        }

        /* ===== CARDS ===== */
        .admin-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .admin-card:hover {
            border-color: rgba(31, 111, 235, 0.2);
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            background-color: rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        /* ===== STATS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stats-card {
            padding: 24px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            border-left: 4px solid;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .stats-card:hover {
            border-color: rgba(31, 111, 235, 0.2);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .brand-border {
            border-left-color: var(--accent-blue);
        }

        .emerald-border {
            border-left-color: var(--accent-green);
        }

        .amber-border {
            border-left-color: var(--accent-yellow);
        }

        .violet-border {
            border-left-color: var(--accent-purple);
        }

        .stats-label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stats-value-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
        }

        .text-emerald {
            color: var(--accent-green);
        }

        .text-violet {
            color: var(--accent-purple);
        }

        .stats-icon {
            font-size: 1.8rem;
            color: var(--text-secondary);
        }

        .pulse-dot {
            width: 10px;
            height: 10px;
            background-color: var(--accent-green);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        /* ===== TABLES ===== */
        .table-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table-responsive::-webkit-scrollbar {
            height: 6px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: transparent;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .admin-table thead {
            background-color: rgba(0, 0, 0, 0.3);
            border-bottom: 2px solid var(--border-color);
        }

        .admin-table th {
            padding: 16px 24px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .admin-table td {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .admin-table tbody tr {
            transition: var(--transition);
        }

        .admin-table tbody tr:hover {
            background-color: rgba(31, 111, 235, 0.05);
        }

        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }

        .id-cell {
            font-weight: 700;
            color: var(--accent-blue);
            font-family: 'Courier New', monospace;
        }

        .email-cell {
            color: var(--text-secondary);
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 16px;
            opacity: 0.4;
        }

        .empty-state p {
            margin: 8px 0;
        }

        /* ===== AGENT INFO ===== */
        .agent-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .agent-avatar {
            width: 36px;
            height: 36px;
            background-color: var(--border-color);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            color: var(--text-primary);
            flex-shrink: 0;
        }

        .agent-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .agent-name {
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            font-size: 14px;
        }

        .agent-uid {
            color: var(--text-secondary);
            margin: 0;
            font-size: 12px;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-online {
            background-color: rgba(63, 185, 80, 0.15);
            color: var(--accent-green);
        }

        .badge-offline {
            background-color: rgba(110, 118, 129, 0.15);
            color: var(--text-secondary);
        }

        .system-tag {
            background-color: rgba(31, 111, 235, 0.15);
            color: var(--accent-blue);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        /* ===== DASHBOARD GRID ===== */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .chart-card, .feed-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .chart-container {
            margin-top: 20px;
        }

        .feed-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .feed-item {
            padding: 12px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 6px;
            border-left: 3px solid;
            transition: var(--transition);
        }

        .feed-item:hover {
            background-color: rgba(0, 0, 0, 0.3);
        }

        .brand-item {
            border-left-color: var(--accent-blue);
        }

        .feed-time {
            font-size: 0.75rem;
            color: var(--text-tertiary);
            font-weight: 600;
        }

        .feed-text {
            margin: 6px 0 0 0;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .feed-user {
            color: var(--text-primary);
            font-weight: 600;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .page-heading {
                flex-direction: column;
                gap: 16px;
            }

            .action-buttons {
                width: 100%;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            :root {
                --sidebar-width: 0;
                --sidebar-collapsed: 0;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 260px;
                z-index: 2000;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-header {
                padding: 16px 20px;
            }

            .page-body {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .search-input {
                width: 160px;
            }
        }
    </style>
</head>
<body>