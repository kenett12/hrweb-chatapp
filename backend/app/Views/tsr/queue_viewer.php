<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRWeb Inc.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #121212;
            color: #fff;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Header Styling */
.header {
    background: #4d4d4dff;
    padding: 10px 30px;
    border-bottom: 2px solid #333;
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    height: 80px; /* Fixed height for stability */
}

.header-title { 
    display: flex;
    align-items: center;
    gap: 15px; /* Space between the red dot and the logo */
}

/* Logo Styling */
.header-logo {
    height: 50px; /* Adjust height to fit your logo */
    width: auto;  /* Maintain aspect ratio */
    object-fit: contain;
    vertical-align: middle;
}

.clock { 
    font-size: 24px; 
    font-weight: 700; 
    color: #ffffffff; 
    font-family: monospace; 
}

/* Blinking dot */
.live-dot { 
    height: 12px; 
    width: 12px; 
    background-color: #dc3545; 
    border-radius: 50%; 
    display: inline-block; 
    box-shadow: 0 0 10px #dc3545;
    animation: blink 2s infinite; 
}

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            gap: 20px;
            overflow: hidden;
        }

        /* NOW SERVING (In Progress) */
        .serving-section { flex: 0 0 auto; margin-bottom: 20px; }
        .section-title {
            font-size: 18px; font-weight: 700; color: #888; text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid #333; padding-bottom: 5px;
        }
        
        .serving-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .serving-card {
            background: #01158b62;
            border-radius: 12px;
            padding: 20px;
            color: white;
            box-shadow: 0 0 20px rgba(13, 110, 253, 0.4);
            display: flex; justify-content: space-between; align-items: center;
            border: 2px solid #fff;
            animation: pulse-border 2s infinite;
            transition: all 0.3s ease;
        }
        
        @keyframes pulse-border {
            0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
            100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
        }
        
        .serving-left { display: flex; flex-direction: column; }
        .serving-label { font-size: 12px; opacity: 0.9; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .serving-id { font-size: 48px; font-weight: 900; line-height: 1; margin: 5px 0; }
        .serving-customer { font-size: 18px; font-weight: 500; }
        
        .serving-right { text-align: right; }
        .serving-tsr-label { font-size: 11px; opacity: 0.8; text-transform: uppercase; }
        .serving-tsr { font-size: 18px; font-weight: 700; background: rgba(0,0,0,0.3); padding: 5px 12px; border-radius: 6px; margin-top: 5px; display: inline-block;}

        /* WAITING (Pending) */
        .waiting-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .waiting-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            overflow-y: auto;
            padding-right: 10px;
            padding-bottom: 20px;
        }

        .waiting-card {
            background: #252525;
            border-radius: 8px;
            padding: 15px;
            border-left: 5px solid #555;
            transition: transform 0.2s, background 0.2s;
            cursor: pointer;
            position: relative;
        }
        
        .waiting-card:hover { transform: translateY(-2px); background: #333; }
        
        /* Dynamic Priority Colors */
        .waiting-card.priority-urgent { border-left-color: #dc3545; }
        .waiting-card.priority-high { border-left-color: #fd7e14; }
        .waiting-card.priority-medium { border-left-color: #ffc107; }
        .waiting-card.priority-low { border-left-color: #198754; }

        .waiting-header { display: flex; justify-content: space-between; margin-bottom: 8px; align-items: center; }
        .waiting-id { font-size: 24px; font-weight: 700; color: #fff; }
        .waiting-badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 800; text-transform: uppercase; background: #444; color: #ccc;}
        .waiting-subject { font-size: 14px; color: #ddd; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px;}
        .waiting-meta { font-size: 12px; color: #777; display: flex; justify-content: space-between; }

        .empty-zone {
            background: #1a1a1a; border: 2px dashed #333; border-radius: 12px; height: 100px; display: flex; align-items: center; justify-content: center; color: #555; font-weight: 600;
        }
        
        .footer { background: #111; padding: 10px 30px; border-top: 1px solid #333; text-align: center; color: #444; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }

        .live-dot { height: 12px; width: 12px; background-color: #dc3545; border-radius: 50%; display: inline-block; margin-right: 8px; animation: blink 2s infinite; }
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

        /* --- Modern Scrollbar Styling --- */

/* 1. Standard Scrollbar (Firefox) */
* {
    scrollbar-width: thin;
    scrollbar-color: #444 #1a1a1a;
}

/* 2. Webkit Scrollbar (Chrome, Edge, Safari) */
::-webkit-scrollbar {
    width: 8px;  /* Vertical scrollbar width */
    height: 8px; /* Horizontal scrollbar height */
}

/* Track (Background) */
::-webkit-scrollbar-track {
    background: #1a1a1a; /* Matches your dark bg */
    border-radius: 4px;
}

/* Thumb (The moving part) */
::-webkit-scrollbar-thumb {
    background-color: #444; /* Subtle grey */
    border-radius: 4px;
    border: 2px solid #1a1a1a; /* Creates padding effect */
}

/* Thumb Hover State */
::-webkit-scrollbar-thumb:hover {
    background-color: #0dcaf0; /* Highlights with your cyan accent color on hover */
    border: 1px solid #1a1a1a;
}

/* Specific styling for the grid containers to ensure they look good */
.waiting-grid::-webkit-scrollbar-track, 
.queue-container::-webkit-scrollbar-track {
    background: #252525; /* Slightly lighter track for internal containers */
    margin: 5px 0; /* Add some spacing at top/bottom */
}

/* Neon Green Glow */
.socket-connected {
    color: #00ff00; /* Bright Neon Green */
    text-shadow: 
        0 0 5px #00ff00, 
        0 0 10px #00ff00, 
        0 0 20px #00ff00; /* Multiple layers for intense glow */
    font-weight: 600;
    font-size: 13px;
    letter-spacing: 0.5px;
}

    </style>
</head>
<body>

    <div class="header">
        <div class="header-title">
            <span class="live-dot"></span> 
            <img src="<?= base_url('uploads/logo/cropped.png') ?>" alt="Logo" style="height: 40px; vertical-align: middle;">
        </div>
        <div class="clock" id="clock">00:00:00</div>
    </div>

    <div class="main-content">
        <div class="serving-section">
            <div class="section-title"><i class="fas fa-bullhorn me-2"></i> Now Serving</div>
            <div id="serving-container">
                </div>
        </div>

        <div class="waiting-section">
            <div class="section-title">
                <i class="fas fa-users me-2"></i> Next in Queue 
                <span class="badge bg-secondary ms-2" id="waiting-count">0</span>
            </div>
            <div id="waiting-container" class="waiting-grid">
                </div>
        </div>
    </div>

    <div class="footer">
    <span class="text-white">Status Updates:</span> <span class="text-yellow">Pending</span> &#8594; <span class="text-primary">In Progress</span>
    
    <span class="ms-3 socket-connected">
        <i class="fas fa-bolt"></i> Realtime Socket Connected
    </span>
</div>

    <script src="https://cdn.socket.io/4.6.0/socket.io.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <script>
        // 1. Initial Data from PHP
        const initialTickets = <?= json_encode(array_merge($assignedTickets, $unassignedTickets)); ?>;
        const currentUserId = <?= json_encode($user_id); ?>;
        
        class QueueManager {
            constructor(tickets) {
                this.tickets = tickets;
                this.socket = null;
                this.init();
            }

            init() {
                this.render();
                this.updateClock();
                this.connectSocket();
                setInterval(() => this.updateClock(), 1000);
            }

            connectSocket() {
                // Connect to Node.js Server
                this.socket = io('http://localhost:3001');

                this.socket.on('connect', () => {
                    console.log('✅ Connected to Queue Socket');
                });

                // --- EVENT LISTENERS ---

                // 1. New Ticket Created
                this.socket.on('ticket_created', (data) => {
                    console.log('New Ticket:', data);
                    // Check if already exists to avoid dupes
                    if (!this.tickets.find(t => t.id == data.ticket.id)) {
                        this.tickets.push(data.ticket);
                        this.render();
                    }
                });

                // 2. Ticket Claimed (Pending -> In Progress)
                this.socket.on('ticket_claimed', (data) => {
                    console.log('Ticket Claimed:', data);
                    this.updateLocalTicket(data.ticket_id, { 
                        status: 'in-progress', 
                        assigned_to: data.tsr.id,
                        // If backend sends TSR name, we can use it, otherwise generic update
                        assigned_tsr_name: data.tsr.name 
                    });
                });

                // 3. Status Changed (e.g. Resolved)
                this.socket.on('ticket_status_changed', (data) => {
                    console.log('Status Changed:', data);
                    // If resolved or closed, we might want to remove it or update it
                    this.updateLocalTicket(data.ticket.id, { status: data.ticket.status });
                });
                
                // 4. Generic Update
                this.socket.on('ticket_updated', (ticket) => {
                     this.updateLocalTicket(ticket.id, ticket);
                });
            }

            updateLocalTicket(id, updates) {
                const index = this.tickets.findIndex(t => t.id == id);
                if (index !== -1) {
                    this.tickets[index] = { ...this.tickets[index], ...updates };
                    this.render();
                } else {
                    // If we don't have it (maybe created while offline), reload or ignore
                    // Ideally fetch it, but for now let's ignore to prevent errors
                }
            }

            getServing() {
                return this.tickets.filter(t => t.status === 'in-progress');
            }

            getWaiting() {
                return this.tickets.filter(t => t.status === 'pending')
                    .sort((a, b) => a.id - b.id); // FIFO Sorting
            }

            render() {
                const serving = this.getServing();
                const waiting = this.getWaiting();

                // Render Serving
                const servingContainer = document.getElementById('serving-container');
                if (serving.length === 0) {
                    servingContainer.innerHTML = `
                        <div class="empty-zone">
                            <i class="fas fa-coffee me-2"></i> Agents are available / No active calls
                        </div>`;
                } else {
                    servingContainer.innerHTML = `<div class="serving-grid">
                        ${serving.map(t => this.renderServingCard(t)).join('')}
                    </div>`;
                }

                // Render Waiting
                const waitingContainer = document.getElementById('waiting-container');
                document.getElementById('waiting-count').textContent = waiting.length;

                if (waiting.length === 0) {
                    waitingContainer.innerHTML = `
                        <div class="empty-zone" style="height: 100%; grid-column: 1/-1;">
                            <i class="fas fa-check-circle me-2"></i> Queue is Empty
                        </div>`;
                } else {
                    waitingContainer.innerHTML = waiting.map(t => this.renderWaitingCard(t)).join('');
                }
            }

            renderServingCard(t) {
                const tsrName = (t.assigned_to == currentUserId) ? "YOU" : "AGENT";
                // Add fade-in animation for new elements
                return `
                <div class="serving-card animate__animated animate__fadeIn">
                    <div class="serving-left">
                        <span class="serving-label">Ticket Number</span>
                        <span class="serving-id">#${t.id}</span>
                        <span class="serving-customer"><i class="fas fa-user me-2"></i>${this.escapeHtml(t.customer_name || 'Customer')}</span>
                    </div>
                    <div class="serving-right">
                        <span class="serving-tsr-label">TSR: </span>
                        <div class="serving-tsr">${tsrName}</div>
                    </div>
                </div>`;
            }

            renderWaitingCard(t) {
                const date = new Date(t.created_at);
                const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                return `
                <div class="waiting-card priority-${t.priority} animate__animated animate__fadeIn" onclick="openTicket(${t.id})">
                    <div class="waiting-header">
                        <span class="waiting-id">#${t.id}</span>
                        <span class="waiting-badge">${t.priority}</span>
                    </div>
                    <div class="waiting-subject">${this.escapeHtml(t.subject)}</div>
                    <div class="waiting-meta">
                        <span>${this.escapeHtml(t.customer_name || 'Customer')}</span>
                        <span>${timeStr}</span>
                    </div>
                </div>`;
            }

            updateClock() {
                const now = new Date();
                document.getElementById('clock').textContent = now.toLocaleTimeString('en-US', { hour12: false });
            }

            escapeHtml(text) {
                if(!text) return '';
                return text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        }

        // Global function to open ticket
        function openTicket(id) {
            const url = '<?= base_url("tsr/ticket/") ?>' + '/' + id;
            if (window.opener && !window.opener.closed) {
                window.opener.location.href = url;
                window.opener.focus();
            } else {
                window.open(url, '_blank');
            }
        }

        // Initialize Logic
        document.addEventListener("DOMContentLoaded", () => {
            const queue = new QueueManager(initialTickets);
        });

    </script>
</body>
</html>