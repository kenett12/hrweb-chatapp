<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?= session()->get('id') ?>">
    <title>Chat Manager - Premium</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.socket.io/4.4.1/socket.io.min.js"></script>

    <style>
        :root { 
            --bg-primary: #0a0e1a;
            --bg-secondary: #111827;
            --bg-tertiary: #1a1f35;
            --bg-elevated: #1f2937;
            --bg-hover: #2d3548;
            
            --border-primary: #1e293b;
            --border-secondary: #334155;
            --border-accent: #4f46e5;
            
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: rgba(99, 102, 241, 0.1);
            --primary-glow: rgba(99, 102, 241, 0.3);
            
            --accent: #8b5cf6;
            --accent-secondary: #ec4899;
            
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --text-muted: #64748b;
            
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.6);
            --shadow-glow: 0 0 20px rgba(99, 102, 241, 0.4);
            
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-full: 9999px;
            
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 250ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-smooth: 350ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            outline: none;
        }

        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        #app-container { 
            display: flex; 
            height: 100%; 
            width: 100%;
            position: relative;
        }

        /* === ANIMATED BACKGROUND === */
        #app-container::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(236, 72, 153, 0.02) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        /* === SIDEBAR === */
        .chat-sidebar { 
            width: 380px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-primary);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            z-index: 10;
            position: relative;
            box-shadow: var(--shadow-lg);
        }
        
        .sidebar-header { 
            padding: 24px;
            height: 88px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-primary);
            gap: 14px;
            background: var(--bg-tertiary);
            position: relative;
        }

        .sidebar-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            opacity: 0.3;
        }
        
        .btn-back { 
            color: var(--text-tertiary);
            font-size: 18px;
            text-decoration: none;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            transition: var(--transition-base);
            background: var(--bg-hover);
            border: 1px solid var(--border-secondary);
            position: relative;
            overflow: hidden;
        }

        .btn-back::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--primary-light), transparent);
            opacity: 0;
            transition: var(--transition-base);
        }

        .btn-back:hover {
            color: var(--primary);
            border-color: var(--primary);
            transform: translateX(-2px);
        }

        .btn-back:hover::before {
            opacity: 1;
        }
        
        .sidebar-header h2 { 
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            flex: 1;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, var(--text-primary), var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .btn-icon-add { 
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition-base);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md), 0 0 0 0 var(--primary-glow);
        }

        .btn-icon-add::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
            opacity: 0;
            transition: var(--transition-base);
        }

        .btn-icon-add:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: var(--shadow-lg), 0 0 20px var(--primary-glow);
        }

        .btn-icon-add:hover::before {
            opacity: 1;
        }

        .btn-icon-add:active {
            transform: translateY(0) scale(0.98);
        }

        .search-bar { 
            padding: 20px 24px;
            background: var(--bg-secondary);
        }

        .search-bar input { 
            width: 100%;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-secondary);
            padding: 14px 18px 14px 46px;
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition-base);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 16px center;
            background-size: 18px;
        }

        .search-bar input::placeholder {
            color: var(--text-muted);
        }

        .search-bar input:focus { 
            border-color: var(--primary);
            background-color: var(--bg-elevated);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .group-list { 
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }

        .group-list::-webkit-scrollbar {
            width: 8px;
        }

        .group-list::-webkit-scrollbar-track {
            background: transparent;
            margin: 8px 0;
        }

        .group-list::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.2);
            border-radius: 100px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .group-list::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.4);
            border-radius: 100px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .group-item { 
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            cursor: pointer;
            border-radius: var(--radius-md);
            transition: var(--transition-base);
            position: relative;
            margin-bottom: 6px;
            border: 1px solid transparent;
        }

        .group-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 0;
            background: linear-gradient(180deg, var(--primary), var(--accent));
            border-radius: 0 4px 4px 0;
            transition: var(--transition-smooth);
            opacity: 0;
        }

        .group-item:hover {
            background: var(--bg-hover);
            border-color: var(--border-secondary);
        }

        .group-item.active {
            background: var(--primary-light);
            border-color: var(--border-accent);
        }

        .group-item.active::before {
            height: 60%;
            opacity: 1;
        }

        .g-avatar { 
            width: 52px;
            height: 52px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .g-avatar::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.3), transparent);
            opacity: 0;
            transition: var(--transition-base);
        }

        .group-item:hover .g-avatar::before {
            opacity: 1;
        }

        .g-info { 
            flex: 1;
            min-width: 0;
        }

        .g-name { 
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
        }

        .g-desc { 
            font-size: 13px;
            color: var(--text-tertiary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* === CHAT MAIN AREA === */
        .chat-main { 
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
            position: relative;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-muted);
            gap: 20px;
        }

        .empty-state i {
            font-size: 80px;
            opacity: 0.3;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .empty-state h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-secondary);
        }

        .empty-state p {
            font-size: 15px;
            color: var(--text-tertiary);
        }

        .active-chat-container { 
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .chat-header { 
            height: 88px;
            padding: 0 32px;
            background: var(--bg-tertiary);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 5;
            box-shadow: var(--shadow-sm);
        }

        .chat-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            opacity: 0.3;
        }

        .chat-meta h3 { 
            margin: 0;
            font-size: 19px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.3px;
        }

        .chat-meta p { 
            margin: 6px 0 0;
            font-size: 13px;
            color: var(--primary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .chat-meta p::before {
            content: '';
            width: 6px;
            height: 6px;
            background: var(--success);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--success);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .chat-actions { 
            display: flex;
            gap: 12px;
        }

        .chat-actions button { 
            background: var(--bg-hover);
            border: 1px solid var(--border-secondary);
            width: 46px;
            height: 46px;
            border-radius: var(--radius-md);
            color: var(--text-tertiary);
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition-base);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .chat-actions button::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--primary-light), transparent);
            opacity: 0;
            transition: var(--transition-base);
        }

        .chat-actions button:hover {
            background: var(--bg-elevated);
            color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .chat-actions button:hover::before {
            opacity: 1;
        }

        .chat-actions button:active {
            transform: translateY(0);
        }

        .chat-actions button:last-child:hover {
            color: var(--danger);
            border-color: var(--danger);
        }

        .messages-container { 
            flex: 1;
            overflow-y: auto;
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            scroll-behavior: smooth;
            background: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.02) 0%, transparent 50%);
        }

        .messages-container::-webkit-scrollbar {
            width: 8px;
        }

        .messages-container::-webkit-scrollbar-track {
            background: transparent;
            margin: 12px 0;
        }

        .messages-container::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.2);
            border-radius: 100px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .messages-container::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.5);
            border-radius: 100px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .msg { 
            max-width: 70%;
            display: flex;
            flex-direction: column;
            animation: messageSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            opacity: 0;
        }

        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .msg.own { 
            align-self: flex-end;
            align-items: flex-end;
        }

        .msg-sender { 
            font-size: 12px;
            color: var(--primary);
            margin-bottom: 6px;
            font-weight: 600;
            padding-left: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .msg-sender::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
        }

        .msg-bubble { 
            background: var(--bg-elevated);
            color: var(--text-primary);
            padding: 14px 20px;
            border-radius: var(--radius-lg);
            font-size: 15px;
            line-height: 1.6;
            box-shadow: var(--shadow-sm);
            position: relative;
            border: 1px solid var(--border-secondary);
            word-wrap: break-word;
            word-break: break-word;
            white-space: pre-wrap;
            overflow-wrap: break-word;
        }

        .msg.own .msg-bubble { 
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            border-bottom-right-radius: 4px;
            border: none;
            box-shadow: var(--shadow-md), 0 0 20px var(--primary-glow);
        }

        .msg:not(.own) .msg-bubble { 
            border-bottom-left-radius: 4px;
        }

        .msg-info { 
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        .msg.own .msg-info {
            justify-content: flex-end;
        }
        
        .system-msg { 
            align-self: center;
            text-align: center;
            margin: 20px 0;
            max-width: 90%;
        }

        .system-msg span { 
            background: var(--bg-elevated);
            color: var(--text-tertiary);
            font-size: 12px;
            padding: 8px 18px;
            border-radius: var(--radius-full);
            border: 1px solid var(--border-secondary);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .system-msg span::before {
            content: '•';
            color: var(--primary);
            font-size: 16px;
        }

        .chat-composer { 
            padding: 28px 32px;
            background: var(--bg-tertiary);
            border-top: 1px solid var(--border-primary);
            display: flex;
            gap: 16px;
            align-items: flex-end;
            position: relative;
        }

        .chat-composer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            opacity: 0.3;
        }

        .composer-input-wrapper {
            flex: 1;
            position: relative;
        }

        .chat-composer input[type="text"] { 
            width: 100%;
            background: var(--bg-secondary);
            border: 1px solid var(--border-secondary);
            padding: 16px 20px;
            border-radius: var(--radius-lg);
            color: var(--text-primary);
            font-size: 15px;
            font-family: inherit;
            height: 56px;
            transition: var(--transition-base);
            line-height: 1.5;
        }

        .chat-composer input[type="text"]::placeholder {
            color: var(--text-muted);
        }

        .chat-composer input[type="text"]:focus { 
            border-color: var(--primary);
            background: var(--bg-elevated);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .btn-send { 
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            width: 56px;
            height: 56px;
            border-radius: var(--radius-lg);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: var(--transition-base);
            box-shadow: var(--shadow-md), 0 0 0 0 var(--primary-glow);
            position: relative;
            overflow: hidden;
        }

        .btn-send::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.3), transparent);
            opacity: 0;
            transition: var(--transition-base);
        }

        .btn-send:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: var(--shadow-lg), 0 0 30px var(--primary-glow);
        }

        .btn-send:hover::before {
            opacity: 1;
        }

        .btn-send:active {
            transform: translateY(0) scale(0.98);
        }

        /* === PREMIUM MODALS === */
        .modal-overlay { 
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-smooth);
            backdrop-filter: blur(8px);
        }

        .modal-overlay.active { 
            opacity: 1;
            visibility: visible;
        }

        .modal-box { 
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 520px;
            box-shadow: var(--shadow-xl), 0 0 80px rgba(99, 102, 241, 0.2);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 85vh;
            transform: scale(0.9) translateY(20px);
            transition: var(--transition-smooth);
            position: relative;
        }

        .modal-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }

        .modal-overlay.active .modal-box { 
            transform: scale(1) translateY(0);
        }

        .modal-top { 
            padding: 24px 28px;
            border-bottom: 1px solid var(--border-primary);
            display: flex;
            justify-content: center;
            position: relative;
            background: var(--bg-elevated);
        }

        .modal-top h3 { 
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            letter-spacing: -0.3px;
        }

        .btn-close { 
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--bg-hover);
            border: 1px solid var(--border-secondary);
            width: 36px;
            height: 36px;
            border-radius: var(--radius-md);
            color: var(--text-tertiary);
            cursor: pointer;
            font-size: 20px;
            transition: var(--transition-base);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-close:hover { 
            color: var(--danger);
            border-color: var(--danger);
            background: rgba(239, 68, 68, 0.1);
            transform: translateY(-50%) rotate(90deg);
        }

        .modal-content { 
            padding: 28px;
            overflow-y: auto;
        }

        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: transparent;
            margin: 8px 0;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.2);
            border-radius: 100px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.4);
            border-radius: 100px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .chip-container { 
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
            padding: 14px;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-secondary);
            min-height: 58px;
        }

        .user-chip { 
            background: linear-gradient(135deg, var(--primary-light), rgba(139, 92, 246, 0.1));
            border: 1px solid var(--primary);
            padding: 6px 16px;
            border-radius: var(--radius-full);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
            font-weight: 500;
            box-shadow: var(--shadow-sm);
            animation: chipSlideIn 0.2s ease-out;
        }

        @keyframes chipSlideIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .user-select-list { 
            border: 1px solid var(--border-secondary);
            border-radius: var(--radius-lg);
            background: var(--bg-secondary);
            max-height: 320px;
            overflow-y: auto;
        }

        .user-select-list::-webkit-scrollbar {
            width: 8px;
        }

        .user-select-list::-webkit-scrollbar-track {
            background: transparent;
            margin: 8px 0;
        }

        .user-select-list::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.2);
            border-radius: 100px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .user-select-list::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.4);
            border-radius: 100px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .selectable-user { 
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: var(--transition-base);
            border-bottom: 1px solid var(--border-primary);
        }

        .selectable-user:last-child {
            border-bottom: none;
        }

        .selectable-user:hover { 
            background: var(--bg-hover);
        }

        .selectable-user.selected { 
            background: var(--primary-light);
        }
        
        .check-circle { 
            width: 24px;
            height: 24px;
            border: 2px solid var(--border-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-base);
        }

        .selectable-user.selected .check-circle { 
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-color: var(--primary);
            box-shadow: 0 0 12px var(--primary-glow);
        }

        .check-circle i { 
            font-size: 12px;
            color: #fff;
            display: none;
        }

        .selectable-user.selected .check-circle i { 
            display: block;
        }
        
        .badge { 
            font-size: 10px;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            text-transform: uppercase;
            font-weight: 700;
            margin-left: 10px;
            letter-spacing: 0.5px;
        }

        .badge.admin { 
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge.tsr { 
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge.user { 
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .btn-modal-primary { 
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: var(--radius-md);
            width: 100%;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: var(--transition-base);
            margin-top: 20px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .btn-modal-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
            opacity: 0;
            transition: var(--transition-base);
        }

        .btn-modal-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg), 0 0 30px var(--primary-glow);
        }

        .btn-modal-primary:hover::before {
            opacity: 1;
        }

        .btn-modal-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-remove {
            background: transparent;
            border: 1px solid var(--border-secondary);
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            color: var(--text-tertiary);
            cursor: pointer;
            transition: var(--transition-base);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-remove:hover {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger);
            color: var(--danger);
        }

        .member-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-primary);
            transition: var(--transition-base);
        }

        .member-list-item:hover {
            background: var(--bg-hover);
        }

        .member-list-item:last-child {
            border-bottom: none;
        }

        /* Form Inputs */
        input[type="text"], input[type="email"] {
            width: 100%;
            background: var(--bg-secondary);
            border: 1px solid var(--border-secondary);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            color: var(--text-primary);
            font-size: 15px;
            transition: var(--transition-base);
            font-family: inherit;
        }

        input[type="text"]:focus, input[type="email"]:focus {
            border-color: var(--primary);
            background: var(--bg-elevated);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        label {
            display: block;
            font-size: 13px;
            color: var(--text-tertiary);
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        
        .hidden { 
            display: none !important;
        }

        /* Loading State */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-secondary);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .chat-sidebar {
                width: 100%;
                max-width: 380px;
            }

            .msg {
                max-width: 85%;
            }
        }
    </style>
</head>

<body>
    <div id="app-container">
        
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <a href="<?= base_url('sa/dashboard') ?>" class="btn-back" title="Back to Dashboard">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2>Messages</h2>
                <button class="btn-icon-add" onclick="openModal('createModal')" title="Create Group">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="search-bar">
                <input type="text" id="group-search" placeholder="Search conversations..." onkeyup="filterGroups()">
            </div>
            <div class="group-list" id="group-list-container">
                <?php if(!empty($groups)): foreach($groups as $g): ?>
                    <div class="group-item" onclick="loadGroup(<?= $g['id'] ?>, '<?= esc($g['name']) ?>', '<?= esc($g['description'] ?? '') ?>')">
                        <div class="g-avatar"><?= strtoupper(substr($g['name'], 0, 1)) ?></div>
                        <div class="g-info">
                            <div class="g-name"><?= esc($g['name']) ?></div>
                            <div class="g-desc"><?= esc($g['description'] ?? 'No description available') ?></div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div style="padding:40px; text-align:center; color:var(--text-muted); font-size:14px;">
                        <i class="fas fa-inbox" style="font-size:48px; opacity:0.3; margin-bottom:16px; display:block;"></i>
                        No groups found
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-main">
            <div id="empty-state" class="empty-state">
                <i class="fas fa-comments"></i>
                <h3>Select a Conversation</h3>
                <p>Choose a group from the sidebar to start messaging</p>
            </div>

            <div id="active-chat" class="active-chat-container hidden">
                <div class="chat-header">
                    <div class="chat-meta">
                        <h3 id="header-name">Group Name</h3>
                        <p id="header-desc">Active now</p>
                    </div>
                    <div class="chat-actions">
                        <button onclick="openAddMemberModal()" title="Add Members">
                            <i class="fas fa-user-plus"></i>
                        </button>
                        <button onclick="openManageMembersModal()" title="View Members">
                            <i class="fas fa-users"></i>
                        </button>
                        <button onclick="deleteGroup()" title="Delete Group">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="messages-container" id="messages-container"></div>

                <div class="chat-composer">
                    <div class="composer-input-wrapper">
                        <input type="text" id="msg-input" placeholder="Type your message..." onkeydown="handleEnter(event)">
                    </div>
                    <button class="btn-send" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div id="addMemberModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-top">
                <h3>Add Members</h3>
                <button class="btn-close" onclick="closeModal('addMemberModal')">&times;</button>
            </div>
            <div style="padding: 28px;">
                <div id="selected-chips" class="chip-container"></div>
                <input type="text" id="user-search-input" placeholder="Search users..." onkeyup="searchUsersToSelect()">
                
                <div class="user-select-list" id="user-select-list" style="margin-top: 16px;">
                    <div style="text-align:center; padding:40px; color:var(--text-muted); font-size:14px;">
                        Type to search users...
                    </div>
                </div>
                
                <button class="btn-modal-primary" id="btn-add-confirm" onclick="confirmAddMembers()">
                    Add Selected Members
                </button>
            </div>
        </div>
    </div>

    <!-- Manage Members Modal -->
    <div id="manageMembersModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-top">
                <h3>Group Members</h3>
                <button class="btn-close" onclick="closeModal('manageMembersModal')">&times;</button>
            </div>
            <div class="modal-content" id="manage-members-list">
                <div style="text-align:center; padding:40px; color:var(--text-muted);">
                    <div class="loading-spinner"></div>
                    <p style="margin-top:16px;">Loading members...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Group Modal -->
    <div id="createModal" class="modal-overlay">
        <div class="modal-box" style="height:auto;">
            <div class="modal-top">
                <h3>Create New Group</h3>
                <button class="btn-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <div style="padding:28px;">
                <form action="<?= base_url('sa/chat/create-group') ?>" method="post">
                    <div class="form-group">
                        <label>Group Name</label>
                        <input type="text" name="group_name" required placeholder="Enter group name">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" placeholder="What's this group about?">
                    </div>
                    <button type="submit" class="btn-modal-primary">Create Group</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentGroupId = null;
        let socket = null;
        let selectedUsers = new Set(); 

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: '#1e293b',
            color: '#f1f5f9',
            iconColor: '#6366f1',
            customClass: {
                popup: 'swal-toast-custom'
            }
        });

        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = '';
        }

        // === CORE LOGIC ===
        function loadGroup(id, name, desc) {
            currentGroupId = id;
            document.getElementById('empty-state').classList.add('hidden');
            document.getElementById('active-chat').classList.remove('hidden');
            document.getElementById('header-name').innerText = name;
            document.getElementById('header-desc').innerText = desc || 'Active now';
            
            document.querySelectorAll('.group-item').forEach(el => el.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            fetchMessages();
            
            if(socket && socket.connected) {
                socket.emit('join_group', id);
            }
        }

        function fetchMessages() {
            const container = document.getElementById('messages-container');
            container.innerHTML = '<div style="text-align:center; margin-top:40px; color:var(--text-muted)"><div class="loading-spinner"></div><p style="margin-top:16px;">Loading messages...</p></div>';
            
            fetch(`<?= base_url('api/getGroupMessages') ?>/${currentGroupId}`)
                .then(r => r.json())
                .then(msgs => {
                    container.innerHTML = '';
                    if(msgs.length === 0) {
                        container.innerHTML = '<div id="no-msg-placeholder" style="text-align:center; margin-top:80px; color:var(--text-muted)"><i class="fas fa-inbox" style="font-size:64px; opacity:0.2; margin-bottom:20px; display:block;"></i><p>No messages yet. Start the conversation!</p></div>';
                    } else {
                        msgs.forEach(appendMessage);
                        scrollToBottom();
                    }
                });
        }

        function appendMessage(msg) {
            const container = document.getElementById('messages-container');
            const placeholder = document.getElementById('no-msg-placeholder');
            if (placeholder) placeholder.remove();
            if(document.querySelector(`[data-id="${msg.id}"]`)) return;

            if(msg.type === 'system') {
                container.insertAdjacentHTML('beforeend', `
                    <div class="system-msg">
                        <span>${msg.content}</span>
                    </div>
                `);
                return;
            }

            const isMe = msg.sender_id == <?= session()->get('id') ?>;
            const html = `
                <div class="msg ${isMe ? 'own' : ''}" data-id="${msg.id}">
                    ${!isMe ? `<div class="msg-sender">${msg.nickname||msg.username}</div>` : ''}
                    <div class="msg-bubble">${(msg.content || '').replace(/\n/g, '<br>')}</div>
                    <div class="msg-info">
                        <i class="fas fa-check-double" style="font-size:10px;"></i>
                        ${new Date(msg.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        function sendMessage() {
            const input = document.getElementById('msg-input');
            const text = input.value.trim();
            if(!text || !currentGroupId) return;
            
            const fd = new FormData();
            fd.append('content', text);
            fd.append('group_id', currentGroupId);
            fd.append('is_group', 1);
            
            input.value = '';
            
            fetch('<?= base_url('api/saveMessage') ?>', {method:'POST', body:fd})
                .then(r => r.json())
                .then(msg => {
                    if(socket) socket.emit('new_message', msg);
                    appendMessage(msg);
                    scrollToBottom();
                });
        }

        function scrollToBottom() {
            const el = document.getElementById('messages-container');
            el.scrollTop = el.scrollHeight;
        }

        function handleEnter(e) {
            if(e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        }

        // === MEMBER LOGIC ===
        function openAddMemberModal() {
            if(!currentGroupId) return;
            selectedUsers.clear();
            updateSelectedUI();
            document.getElementById('user-search-input').value = '';
            document.getElementById('user-select-list').innerHTML = '<div style="text-align:center; padding:40px; color:var(--text-muted);">Type to search users...</div>';
            openModal('addMemberModal');
        }

        function searchUsersToSelect() {
            const q = document.getElementById('user-search-input').value;
            const list = document.getElementById('user-select-list');
            
            if(q.length < 2) {
                list.innerHTML = '<div style="text-align:center; padding:40px; color:var(--text-muted);">Type at least 2 characters...</div>';
                return;
            }
            
            list.innerHTML = '<div style="text-align:center; padding:40px; color:var(--text-muted)"><div class="loading-spinner"></div></div>';
            
            fetch(`<?= base_url('sa/chat/search-users') ?>?group_id=${currentGroupId}&q=${q}`)
                .then(r => r.json())
                .then(users => {
                    list.innerHTML = '';
                    if(users.length === 0) {
                        list.innerHTML = '<div style="text-align:center; padding:40px; color:var(--text-muted);">No users found</div>';
                        return;
                    }
                    users.forEach(u => {
                        const isSelected = selectedUsers.has(u.id);
                        list.insertAdjacentHTML('beforeend', `
                            <div class="selectable-user ${isSelected ? 'selected' : ''}" onclick="toggleUserSelection(this, ${u.id}, '${u.username}')">
                                <div style="display:flex; align-items:center; gap:14px;">
                                    <div style="width:42px; height:42px; background:linear-gradient(135deg, var(--primary), var(--accent)); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff; font-size:16px;">${u.username.charAt(0).toUpperCase()}</div>
                                    <div>
                                        <div style="font-size:15px; font-weight:600; color:var(--text-primary);">${u.nickname || u.username}</div>
                                        <div style="font-size:12px; color:var(--text-tertiary); text-transform:uppercase; margin-top:2px;">${u.role}</div>
                                    </div>
                                </div>
                                <div class="check-circle"><i class="fas fa-check"></i></div>
                            </div>
                        `);
                    });
                });
        }

        function toggleUserSelection(el, uid, username) {
            if(selectedUsers.has(uid)) {
                selectedUsers.delete(uid);
                el.classList.remove('selected');
            } else {
                selectedUsers.add(uid);
                el.classList.add('selected');
            }
            updateSelectedUI();
        }

        function updateSelectedUI() {
            const chipContainer = document.getElementById('selected-chips');
            chipContainer.innerHTML = '';
            
            document.querySelectorAll('.selectable-user.selected').forEach(el => {
                const name = el.querySelector('div > div > div').innerText;
                chipContainer.innerHTML += `<div class="user-chip"><i class="fas fa-user"></i> ${name}</div>`;
            });
            
            const btn = document.getElementById('btn-add-confirm');
            btn.innerText = selectedUsers.size > 0 ? `Add ${selectedUsers.size} Member${selectedUsers.size > 1 ? 's' : ''}` : 'Add Selected Members';
            btn.disabled = selectedUsers.size === 0;
        }

        function confirmAddMembers() {
            if(selectedUsers.size === 0) return;
            
            const fd = new FormData();
            fd.append('group_id', currentGroupId);
            fd.append('user_ids', Array.from(selectedUsers).join(','));
            
            fetch('<?= base_url('sa/chat/add-members-batch') ?>', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.success) {
                        closeModal('addMemberModal');
                        Toast.fire({ icon: 'success', title: 'Members added successfully!' });
                        if(res.system_message && socket) socket.emit('new_message', res.system_message);
                        if(res.system_message) appendMessage(res.system_message);
                    }
                });
        }

        function openManageMembersModal() {
            if(!currentGroupId) return;
            const list = document.getElementById('manage-members-list');
            list.innerHTML = '<div style="text-align:center; padding:40px; color:var(--text-muted)"><div class="loading-spinner"></div><p style="margin-top:16px;">Loading members...</p></div>';
            openModal('manageMembersModal');
            
            fetch(`<?= base_url('sa/chat/get-members') ?>/${currentGroupId}`)
                .then(r => r.json())
                .then(data => {
                    list.innerHTML = '';
                    if(data.length === 0) {
                        list.innerHTML = '<div style="text-align:center; padding:40px; color:var(--text-muted);">No members found</div>';
                        return;
                    }
                    data.forEach(u => {
                        let role = (u.role || 'user').toLowerCase();
                        list.insertAdjacentHTML('beforeend', `
                            <div class="member-list-item">
                                <div style="display:flex; align-items:center; gap:14px;">
                                    <div style="width:44px; height:44px; background:linear-gradient(135deg, var(--primary), var(--accent)); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff; font-size:17px;">${u.username.charAt(0).toUpperCase()}</div>
                                    <div>
                                        <div style="font-size:15px; font-weight:600; color:var(--text-primary);">${u.nickname || u.username}</div>
                                        <span class="badge ${role}">${role}</span>
                                    </div>
                                </div>
                                <button class="btn-remove" onclick="removeMember(${u.membership_id})" title="Remove member">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        `);
                    });
                });
        }

        function removeMember(mid) {
            Swal.fire({
                title: 'Remove Member?',
                text: 'This user will be removed from the group.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Remove',
                cancelButtonText: 'Cancel',
                background: '#1e293b',
                color: '#f1f5f9'
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('membership_id', mid);
                    fetch('<?= base_url('sa/chat/remove-member') ?>', {method:'POST', body:fd})
                        .then(r => r.json())
                        .then(res => {
                            if(res.success) {
                                openManageMembersModal();
                                Toast.fire({ icon: 'success', title: 'Member removed' });
                            }
                        });
                }
            });
        }

        function deleteGroup() {
            Swal.fire({
                title: 'Delete Group?',
                text: "This action cannot be undone. All messages will be permanently deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Delete Group',
                cancelButtonText: 'Cancel',
                background:'#1e293b',
                color:'#f1f5f9'
            }).then((r) => {
                if(r.isConfirmed) {
                    const fd = new FormData();
                    fd.append('id', currentGroupId);
                    fetch('<?= base_url('sa/chat/delete-group') ?>', {method:'POST', body:fd})
                        .then(() => {
                            Toast.fire({ icon: 'success', title: 'Group deleted' });
                            setTimeout(() => window.location.reload(), 1000);
                        });
                }
            });
        }

        function filterGroups() {
            const input = document.getElementById('group-search').value.toLowerCase();
            document.querySelectorAll('.group-item').forEach(item => {
                const name = item.querySelector('.g-name').innerText.toLowerCase();
                const desc = item.querySelector('.g-desc').innerText.toLowerCase();
                item.style.display = (name.includes(input) || desc.includes(input)) ? 'flex' : 'none';
            });
        }

        // === SOCKET INIT ===
        try {
            socket = io(window.socketUrl, { transports: ['websocket'], upgrade: false });
            
            socket.on('connect', () => {
                console.log('✅ Connected to chat server');
                socket.emit('user_connected', window.userId);
                if(currentGroupId) socket.emit('join_group', currentGroupId);
            });
            
            socket.on('receive_message', (msg) => {
                if(msg.group_id == currentGroupId) {
                    appendMessage(msg);
                    scrollToBottom();
                }
            });
            
            socket.on('connect_error', (err) => {
                console.error('❌ Socket Connection Error:', err);
            });
        } catch(e) {
            console.error('❌ Socket Server unavailable:', e);
        }

        // Close modals on ESC key
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });

        // Close modal on backdrop click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if(e.target === this) {
                    closeModal(this.id);
                }
            });
        });

    </script>
</body>
</html>