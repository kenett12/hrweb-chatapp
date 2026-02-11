<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TSR Chat - Ticket #<?= $ticket['id'] ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #a78bfa;
            --primary-dark: #8b5cf6;
            --primary-darker: #7c3aed;
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --border: #334155;
            --border-light: #475569;
            
            --status-open-bg: rgba(245, 158, 11, 0.15);
            --status-open-text: #fbbf24;
            --status-progress-bg: rgba(167, 139, 250, 0.15);
            --status-progress-text: #c4b5fd;
            --status-resolved-bg: rgba(16, 185, 129, 0.15);
            --status-resolved-text: #6ee7b7;
            --status-closed-bg: rgba(156, 163, 175, 0.15);
            --status-closed-text: #d1d5db;
            
            --priority-urgent-bg: rgba(239, 68, 68, 0.15);
            --priority-urgent-text: #fca5a5;
            --priority-high-bg: rgba(245, 158, 11, 0.15);
            --priority-high-text: #fbbf24;
            --priority-medium-bg: rgba(59, 130, 246, 0.15);
            --priority-medium-text: #93c5fd;
            --priority-low-bg: rgba(16, 185, 129, 0.15);
            --priority-low-text: #6ee7b7;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
            height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        #app-container {
            display: flex;
            height: 100vh;
            background: var(--bg-primary);
            gap: 1px;
        }
        
        /* ===== SIDEBAR ===== */
        .chat-sidebar {
            width: 360px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .sidebar-header {
            padding: 20px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .back-btn:hover {
            color: var(--primary-dark);
            gap: 12px;
        }
        
        .back-btn i {
            font-size: 16px;
        }
        
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        .sidebar-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar-content::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .sidebar-content::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.2);
            border-radius: 3px;
        }
        .sidebar-content::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.3);
        }
        
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 12px;
            padding: 0 8px;
        }
        
        .customer-card {
            background: rgba(167, 139, 250, 0.08);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid rgba(167, 139, 250, 0.15);
        }
        
        .customer-card:hover {
            background: rgba(167, 139, 250, 0.12);
            border-color: rgba(167, 139, 250, 0.25);
        }
        
        .customer-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-darker) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 20px;
            font-weight: 600;
            color: white;
            position: relative;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
        }
        
        .customer-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .ticket-details {
            padding: 0 8px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-size: 12px;
            color: var(--text-tertiary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .detail-value {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .status-badge {
            padding: 5px 11px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: inline-block;
        }
        
        .status-open { 
            background: var(--status-open-bg); 
            color: var(--status-open-text); 
        }
        .status-in-progress { 
            background: var(--status-progress-bg);
            color: var(--status-progress-text); 
        }
        .status-resolved { 
            background: var(--status-resolved-bg); 
            color: var(--status-resolved-text); 
        }
        .status-closed { 
            background: var(--status-closed-bg); 
            color: var(--status-closed-text); 
        }
        
        .priority-urgent { 
            background: var(--priority-urgent-bg); 
            color: var(--priority-urgent-text); 
        }
        .priority-high { 
            background: var(--priority-high-bg); 
            color: var(--priority-high-text); 
        }
        .priority-medium { 
            background: var(--priority-medium-bg); 
            color: var(--priority-medium-text); 
        }
        .priority-low { 
            background: var(--priority-low-bg); 
            color: var(--priority-low-text); 
        }
        
        .action-buttons {
            padding: 16px 8px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .action-btn {
            padding: 11px 16px;
            border: 1.5px solid var(--border-light);
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
            background: transparent;
        }
        
        .btn-resolve {
            color: var(--status-resolved-text);
            border-color: rgba(16, 185, 129, 0.3);
            background: var(--status-resolved-bg);
        }
        
        .btn-resolve:hover {
            background: rgba(16, 185, 129, 0.25);
            border-color: rgba(16, 185, 129, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }
        
        .btn-close {
            color: var(--status-closed-text);
            border-color: rgba(156, 163, 175, 0.3);
            background: var(--status-closed-bg);
        }
        
        .btn-close:hover {
            background: rgba(156, 163, 175, 0.25);
            border-color: rgba(156, 163, 175, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(156, 163, 175, 0.15);
        }
        
        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
            position: relative;
            min-width: 0;
        }
        
        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(167, 139, 250, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(167, 139, 250, 0.02) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        .chat-header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 10;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }
        
        .header-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-darker) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
        }
        
        .chat-header-text {
            flex: 1;
            min-width: 0;
        }
        
        .chat-header-text h2 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 4px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .chat-subtitle {
            font-size: 12px;
            color: var(--text-tertiary);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin: 0;
        }
        
        .chat-header-actions {
            display: flex;
            gap: 4px;
        }
        
        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: transparent;
            border: none;
            color: var(--text-tertiary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 18px;
        }
        
        .icon-btn:hover {
            background: rgba(167, 139, 250, 0.15);
            color: var(--primary);
        }
        
        /* ===== MESSAGES ===== */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
            z-index: 1;
        }
        
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }
        
        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.2);
            border-radius: 4px;
        }
        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.3);
        }
        
        .message {
            display: flex;
            align-items: flex-end;
            max-width: 75%;
            animation: messageSlideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* TSR messages (right side - purple gradient) */
        .message-tsr {
            align-self: flex-end;
            justify-content: flex-end;
        }
        
        .message-tsr .message-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: flex-end;
        }
        
        .message-tsr .message-bubble {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-darker) 100%);
            color: white;
            border-radius: 18px 4px 18px 18px;
            padding: 10px 14px;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.2);
        }
        
        /* Customer messages (left side - dark bubbles) */
        .message-customer {
            align-self: flex-start;
        }
        
        .message-customer .message-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: flex-start;
        }
        
        .message-customer .message-bubble {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border-radius: 4px 18px 18px 18px;
            padding: 10px 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-light);
        }
        
        /* System messages */
        .message-system {
            align-self: center;
            max-width: 80%;
            margin: 12px 0;
        }
        
        .message-system .message-bubble {
            background: rgba(167, 139, 250, 0.1);
            color: var(--text-tertiary);
            border-radius: 12px;
            padding: 8px 14px;
            font-size: 12px;
            text-align: center;
            border: 1px solid rgba(167, 139, 250, 0.2);
        }
        
        .message-sender {
            font-size: 11px;
            color: var(--text-tertiary);
            font-weight: 600;
            padding: 0 4px;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .message-text {
            font-size: 15px;
            line-height: 1.5;
            word-wrap: break-word;
        }
        
        .message-time {
            font-size: 10px;
            color: rgba(241, 245, 249, 0.5);
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
            justify-content: flex-end;
        }
        
        .message-customer .message-time {
            color: var(--text-tertiary);
            justify-content: flex-start;
        }
        
        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-tertiary);
            text-align: center;
            padding: 40px;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.2;
        }
        
        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        
        .empty-state p {
            font-size: 13px;
            color: var(--text-tertiary);
        }
        
        /* ===== MESSAGE COMPOSER ===== */
        .message-composer {
            background: var(--bg-secondary);
            padding: 16px 24px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: flex-end;
            gap: 12px;
        }
        
        .message-input-container {
            flex: 1;
            background: var(--bg-tertiary);
            border-radius: 24px;
            padding: 10px 16px;
            display: flex;
            align-items: flex-end;
            border: 1.5px solid transparent;
            transition: all 0.2s ease;
        }
        
        .message-input-container:focus-within {
            background: rgba(167, 139, 250, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.15);
        }
        
        #messageInput {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-primary);
            padding: 10px 0;
            font-size: 15px;
            line-height: 1.5;
            resize: none;
            max-height: 100px;
            font-family: inherit;
        }
        
        #messageInput::placeholder {
            color: var(--text-tertiary);
        }
        
        .send-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-darker) 100%);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }
        
        .send-btn:hover:not(:disabled) {
            transform: scale(1.08);
            box-shadow: 0 6px 16px rgba(124, 58, 237, 0.4);
        }
        
        .send-btn:active:not(:disabled) {
            transform: scale(0.95);
        }
        
        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* ===== CLOSED BANNER ===== */
        .closed-ticket-banner {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-left: 3px solid #f87171;
            border-radius: 10px;
            padding: 12px 16px;
            margin: 0 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-tertiary);
            font-size: 13px;
        }
        
        .closed-ticket-banner i {
            color: #f87171;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        /* ===== NOTIFICATIONS ===== */
        .notification,
        .error-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--bg-secondary);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            padding: 16px 20px;
            min-width: 300px;
            z-index: 1000;
            animation: slideInRight 0.3s ease-out;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
        }
        
        .notification.success { border-left: 4px solid var(--status-resolved-text); }
        .notification.error,
        .error-toast { 
            border-left: 4px solid #f87171;
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }
        .notification.info { border-left: 4px solid var(--primary); }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .chat-sidebar {
                display: none;
            }
            
            #app-container {
                flex-direction: column;
                gap: 0;
            }
            
            .message {
                max-width: 85%;
            }
        }
        
        .hidden { display: none !important; }
    </style>
</head>
<body>
    <div id="app-container">
        <!-- SIDEBAR -->
        <aside class="chat-sidebar">
            <div class="sidebar-header">
                <a href="<?= base_url('tsr/dashboard') ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to tickets</span>
                </a>
            </div>
            
            <div class="sidebar-content">
                <!-- Customer Info -->
                <div class="section-title">Customer</div>
                <div class="customer-card">
                    <div class="customer-avatar">
                        <?= strtoupper(substr($ticket['creator_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="customer-name"><?= $ticket['creator_name'] ?></div>
                    </div>
                </div>
                
                <!-- Ticket Details -->
                <div class="section-title">Ticket Details</div>
                <div class="ticket-details">
                    <div class="detail-item">
                        <span class="detail-label">Ticket ID</span>
                        <span class="detail-value">#<?= $ticket['id'] ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Subject</span>
                        <span class="detail-value"><?= $ticket['subject'] ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Category</span>
                        <span class="detail-value"><?= ucfirst($ticket['category']) ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Priority</span>
                        <span class="status-badge priority-<?= strtolower($ticket['priority']) ?>">
                            <?= ucfirst($ticket['priority']) ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="status-badge status-<?= str_replace('-', '-', strtolower($ticket['status'])) ?>" id="ticketStatus">
                            <?= ucfirst(str_replace('-', ' ', $ticket['status'])) ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Created</span>
                        <span class="detail-value">
                            <?php
                            $createdDate = new DateTime($ticket['created_at']);
                            $createdDate->setTimezone(new DateTimeZone('Asia/Manila'));
                            echo $createdDate->format('M j, Y');
                            ?>
                        </span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="action-btn btn-resolve update-status" data-status="resolved">
                        <i class="fas fa-check-circle"></i>
                        Mark as Resolved
                    </button>
                    <button class="action-btn btn-close update-status" data-status="closed">
                        <i class="fas fa-times-circle"></i>
                        Close Ticket
                    </button>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <!-- HEADER -->
            <header class="chat-header">
                <div class="chat-header-info">
                    <div class="header-avatar">
                        <?= strtoupper(substr($ticket['creator_name'], 0, 1)) ?>
                    </div>
                    <div class="chat-header-text">
                        <h2><?= $ticket['subject'] ?></h2>
                        <div class="chat-subtitle">
                            <span>#<?= $ticket['id'] ?></span>
                            <span class="status-badge status-<?= str_replace('-', '-', strtolower($ticket['status'])) ?>">
                                <?= ucfirst(str_replace('-', ' ', $ticket['status'])) ?>
                            </span>
                            <span class="status-badge priority-<?= strtolower($ticket['priority']) ?>">
                                <?= ucfirst($ticket['priority']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="chat-header-actions">
                    <button class="icon-btn" title="More options">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                </div>
            </header>

            <!-- MESSAGES -->
            <div class="chat-messages" id="chatMessages" data-ticket-id="<?= $ticket['id'] ?>" data-user-id="<?= $user['id'] ?>">
                <?php if (empty($messages)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h3>No messages yet</h3>
                        <p>Start the conversation with the customer</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <?php if ($message['sender_type'] === 'system' || $message['is_system'] == 1): ?>
                            <div class="message message-system" id="message-<?= $message['id'] ?>">
                                <div class="message-bubble">
                                    <i class="fas fa-info-circle"></i> <?= $message['content'] ?? $message['message'] ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php $isTSR = $message['sender_id'] == $user['id']; ?>
                            <div class="message <?= $isTSR ? 'message-tsr' : 'message-customer' ?>" id="message-<?= $message['id'] ?>">
                                <div class="message-content">
                                    <?php if (!$isTSR): ?>
                                        <div class="message-sender"><?= $ticket['creator_name'] ?></div>
                                    <?php endif; ?>
                                    <div class="message-bubble">
                                        <div class="message-text">
                                            <?= nl2br(esc($message['content'] ?? $message['message'])) ?>
                                        </div>
                                    </div>
                                    <div class="message-time">
                                        <span>
                                            <?php
                                            $messageDate = new DateTime($message['created_at']);
                                            $messageDate->setTimezone(new DateTimeZone('Asia/Manila'));
                                            echo $messageDate->format('g:i A');
                                            ?>
                                        </span>
                                        <?php if ($isTSR): ?>
                                            <i class="fas fa-check"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- COMPOSER -->
            <div class="message-composer">
                <form id="messageForm" style="display: contents;">
                    <div class="message-input-container">
                        <textarea 
                            id="messageInput" 
                            placeholder="Type a message..." 
                            rows="1"
                        ></textarea>
                    </div>
                    <button type="submit" class="send-btn" id="sendBtn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const ticketId = <?= $ticket['id'] ?>;
    const currentUserId = <?= $user['id'] ?>;
    const baseUrl = '<?= base_url() ?>';
    
    const chatMessages = document.getElementById('chatMessages');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const ticketStatusBadge = document.getElementById('ticketStatus');
    
    let lastMessageTimestamp = 0;
    let pollingInterval = null;
    let isTicketClosed = false;
    const displayedMessageIds = new Set();

    console.log('TSR Chat initialized - Ticket:', ticketId, 'User:', currentUserId);

    initializeExistingMessages();
    initChat();

    function initializeExistingMessages() {
        const existingMessages = chatMessages.querySelectorAll('.message[id^="message-"]');
        existingMessages.forEach(messageEl => {
            const messageId = messageEl.id.replace('message-', '');
            if (messageId) {
                displayedMessageIds.add(parseInt(messageId));
            }
        });
        console.log('Initialized with', displayedMessageIds.size, 'existing messages');
    }

    function initChat() {
        checkTicketStatus();
        setupPolling();
        setupMessageForm();
        loadMessages();
        scrollToBottom();
    }

    function setupPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(() => {
            loadMessages(true);
            checkTicketStatus();
        }, 3000);
    }

    function loadMessages(isPolling = false) {
        let url = `${baseUrl}/api/tickets/tsr/${ticketId}/messages`;
        if (isPolling && lastMessageTimestamp > 0) {
            url += `?since=${lastMessageTimestamp}`;
        }

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (Array.isArray(data)) {
                    processMessages(data, isPolling);
                } else if (data.success && data.messages) {
                    processMessages(data.messages, isPolling);
                }
            })
            .catch(error => {
                console.error('Error loading messages:', error);
                if (!isPolling) showError('Failed to load messages');
            });
    }

    function processMessages(messages, isPolling) {
        if (!Array.isArray(messages)) return;

        let newMessagesAdded = 0;
        messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

        messages.forEach(message => {
            if (!displayedMessageIds.has(message.id)) {
                addMessageToChat(message);
                displayedMessageIds.add(message.id);
                newMessagesAdded++;

                const messageTime = new Date(message.created_at).getTime();
                if (messageTime > lastMessageTimestamp) {
                    lastMessageTimestamp = messageTime;
                }
            }
        });

        if (newMessagesAdded > 0) {
            if (isPolling) console.log(`Received ${newMessagesAdded} new messages`);
            scrollToBottom();
        }
    }

    function addMessageToChat(message) {
        if (document.getElementById(`message-${message.id}`)) return;

        const messageElement = document.createElement('div');
        messageElement.id = `message-${message.id}`;

        if (message.sender_type === 'system' || message.is_system == 1) {
            messageElement.className = 'message message-system';
            messageElement.innerHTML = `
                <div class="message-bubble">
                    <i class="fas fa-info-circle"></i> ${message.content || message.message || ''}
                </div>`;
        } else {
            const isTSR = message.sender_id == currentUserId;
            const senderName = isTSR ? '' : (message.sender_name || '<?= $ticket['creator_name'] ?>');
            
            messageElement.className = `message ${isTSR ? 'message-tsr' : 'message-customer'}`;
            messageElement.innerHTML = `
                <div class="message-content">
                    ${!isTSR ? `<div class="message-sender">${senderName}</div>` : ''}
                    <div class="message-bubble">
                        <div class="message-text">
                            ${(message.content || message.message || '').replace(/\n/g, '<br>')}
                        </div>
                    </div>
                    <div class="message-time">
                        <span>${formatTime(message.created_at)}</span>
                        ${isTSR ? '<i class="fas fa-check"></i>' : ''}
                    </div>
                </div>`;
        }

        chatMessages.appendChild(messageElement);
    }

    function setupMessageForm() {
        if (!messageForm) return;

        messageForm.addEventListener('submit', e => {
            e.preventDefault();
            if (isTicketClosed) {
                showError('This ticket is closed');
                return;
            }
            const message = messageInput.value.trim();
            if (message) sendMessage(message);
        });

        if (messageInput) {
            messageInput.addEventListener('keypress', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    const message = messageInput.value.trim();
                    if (message) sendMessage(message);
                }
            });

            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });
        }
    }

    function sendMessage(message) {
        if (sendBtn) sendBtn.disabled = true;
        if (messageInput) messageInput.disabled = true;

        const formData = new FormData();
        formData.append('ticket_id', ticketId);
        formData.append('message', message);
        formData.append('sender_type', 'tsr');

        fetch(`${baseUrl}/api/tickets/tsr/send-message`, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error(`Parse error: ${text}`);
            }
        }))
        .then(data => {
            if (data.success) {
                if (messageInput) messageInput.value = '';

                if (data.data && !displayedMessageIds.has(data.data.id)) {
                    addMessageToChat(data.data);
                    displayedMessageIds.add(data.data.id);

                    const messageTime = new Date(data.data.created_at).getTime();
                    if (messageTime > lastMessageTimestamp) {
                        lastMessageTimestamp = messageTime;
                    }
                    scrollToBottom();
                }

                setTimeout(() => loadMessages(true), 1000);
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            showError('Failed to send message: ' + error.message);
        })
        .finally(() => {
            if (sendBtn) sendBtn.disabled = false;
            if (messageInput) {
                messageInput.disabled = isTicketClosed;
                if (!isTicketClosed) messageInput.focus();
            }
        });
    }

    function checkTicketStatus() {
        fetch(`${baseUrl}/api/tickets/${ticketId}/status`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.ticket) {
                    updateTicketStatus(data.ticket.status);
                }
            })
            .catch(error => console.error('Error checking status:', error));
    }

    function updateTicketStatus(status) {
        if (ticketStatusBadge) {
            ticketStatusBadge.textContent = status.toUpperCase();
            ticketStatusBadge.className = `status-badge status-${status.toLowerCase()}`;
        }

        isTicketClosed = status.toLowerCase() === 'closed' || status.toLowerCase() === 'resolved';

        if (isTicketClosed) {
            if (messageInput) messageInput.disabled = true;
            if (sendBtn) sendBtn.disabled = true;

            if (!document.getElementById('closed-ticket-banner')) {
                const banner = document.createElement('div');
                banner.id = 'closed-ticket-banner';
                banner.className = 'closed-ticket-banner';
                banner.innerHTML = `
                    <i class="fas fa-lock"></i>
                    <span>This ticket is ${status.toLowerCase()}. No new messages allowed.</span>`;

                const composer = document.querySelector('.message-composer');
                if (composer && composer.parentNode) {
                    composer.parentNode.insertBefore(banner, composer);
                }
            }
        } else {
            if (messageInput) messageInput.disabled = false;
            if (sendBtn) sendBtn.disabled = false;

            const banner = document.getElementById('closed-ticket-banner');
            if (banner) banner.remove();
        }
    }

    // Status update buttons
    document.querySelectorAll('.update-status').forEach(button => {
        button.addEventListener('click', function() {
            const status = this.getAttribute('data-status');

            if (confirm(`Mark this ticket as ${status}?`)) {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                this.disabled = true;

                fetch(`${baseUrl}/api/tickets/${ticketId}/status`, {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ status: status }),
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const systemMessage = {
                            id: Date.now(),
                            content: `Ticket marked as ${status}`,
                            sender_type: 'system',
                            is_system: 1,
                            created_at: new Date().toISOString()
                        };

                        addMessageToChat(systemMessage);
                        displayedMessageIds.add(systemMessage.id);

                        document.querySelectorAll('.status-badge').forEach(badge => {
                            badge.className = `status-badge status-${status}`;
                            badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        });

                        scrollToBottom();

                        if (status === 'closed') {
                            showNotification('Ticket closed successfully', 'success');
                            setTimeout(() => {
                                window.location.href = `${baseUrl}/tsr/dashboard`;
                            }, 2000);
                        } else {
                            showNotification(`Status updated to ${status}`, 'success');
                        }
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error updating status: ' + error.message, 'error');
                })
                .finally(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            }
        });
    });

    function formatTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    function scrollToBottom() {
        if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showError(message) {
        const toast = document.createElement('div');
        toast.className = 'error-toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => { if (toast.parentNode) toast.remove(); }, 5000);
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => { if (notification.parentNode) notification.remove(); }, 5000);
    }

    window.addEventListener('beforeunload', () => {
        if (pollingInterval) clearInterval(pollingInterval);
    });
});
</script>
</body>
</html>