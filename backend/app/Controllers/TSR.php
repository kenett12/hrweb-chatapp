<?php

namespace App\Controllers;

use App\Models\TicketModel;
use App\Models\TicketMessageModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

class TSR extends BaseController
{
    use ResponseTrait;

    protected $ticketModel;
    protected $ticketMessageModel;
    protected $userModel;
    
    public function __construct()
    {
        $this->ticketModel = new TicketModel();
        $this->ticketMessageModel = new TicketMessageModel();
        $this->userModel = new UserModel();
    }
    
    public function index()
    {
        return redirect()->to('/tsr/dashboard');
    }
    
    public function dashboard()
    {
        try {
            $session = session();
            $userId = $session->get('id');
            $userRole = $session->get('role');
            
            log_message('debug', "TSR Dashboard: User ID: {$userId}, Role: {$userRole}");
            
            if (!$userId) {
                log_message('error', 'TSR Dashboard: No user ID in session');
                return redirect()->to('/login')->with('error', 'Session expired. Please log in again.');
            }
            
            // Get database connection with better error handling
            try {
                $db = \Config\Database::connect();
                
                // Test the connection
                $testQuery = $db->query('SELECT 1 as test');
                if (!$testQuery) {
                    throw new \Exception('Database query failed');
                }
                
                log_message('debug', 'TSR Dashboard: Database connected successfully');
                
            } catch (\Exception $e) {
                log_message('error', 'TSR Dashboard: Database connection failed: ' . $e->getMessage());
                
                // Return dashboard with error message but don't crash
                $data = [
                    'error' => 'Database connection failed. Please check your database configuration.',
                    'openTickets' => 0,
                    'inProgressTickets' => 0,
                    'resolvedTickets' => 0,
                    'unassignedTickets' => [],
                    'assignedTickets' => [],
                    'user' => [
                        'id' => $userId,
                        'role' => $userRole,
                        'username' => $session->get('username')
                    ]
                ];
                
                return view('tsr/dashboard', $data);
            }
            
            // Get ticket statistics with error handling
            try {
                // Check if tickets table exists
                if (!$db->tableExists('tickets')) {
                    throw new \Exception('Tickets table does not exist. Please run database migrations.');
                }
                
                $openTickets = $db->table('tickets')->where('status', 'open')->where('is_deleted', 0)->countAllResults();
                $inProgressTickets = $db->table('tickets')->where('status', 'in-progress')->where('is_deleted', 0)->countAllResults();
                $resolvedTickets = $db->table('tickets')->where('status', 'resolved')->where('is_deleted', 0)->countAllResults();
                
                log_message('debug', "TSR Dashboard: Stats - Open: {$openTickets}, In Progress: {$inProgressTickets}, Resolved: {$resolvedTickets}");
            } catch (\Exception $e) {
                log_message('error', 'TSR Dashboard: Error getting ticket stats: ' . $e->getMessage());
                $openTickets = $inProgressTickets = $resolvedTickets = 0;
            }
            
            // Get unassigned tickets with proper priority ordering
            try {
                if ($db->tableExists('tickets') && $db->tableExists('users')) {
                    $unassignedTickets = $db->table('tickets t')
                        ->select('t.*, u.username as customer_name')
                        ->join('users u', 'u.id = t.created_by')
                        ->where('t.assigned_to IS NULL')
                        ->where('t.is_deleted', 0)
                        ->orderBy('FIELD(t.priority, "urgent", "high", "medium", "low")', '', false)
                        ->orderBy('t.created_at', 'ASC')
                        ->get()
                        ->getResultArray();
                
                    log_message('debug', 'TSR Dashboard: Found ' . count($unassignedTickets) . ' unassigned tickets');
                } else {
                    throw new \Exception('Required tables (tickets, users) do not exist');
                }
            } catch (\Exception $e) {
                log_message('error', 'TSR Dashboard: Error getting unassigned tickets: ' . $e->getMessage());
                $unassignedTickets = [];
            }
            
            // Get tickets assigned to this TSR
            try {
                if ($db->tableExists('tickets') && $db->tableExists('users')) {
                    $assignedTickets = $db->table('tickets t')
                        ->select('t.*, u.username as customer_name')
                        ->join('users u', 'u.id = t.created_by')
                        ->where('t.assigned_to', $userId)
                        ->where('t.is_deleted', 0)
                        ->orderBy('FIELD(t.priority, "urgent", "high", "medium", "low")', '', false)
                        ->orderBy('t.created_at', 'ASC')
                        ->get()
                        ->getResultArray();
                
                    log_message('debug', 'TSR Dashboard: Found ' . count($assignedTickets) . ' assigned tickets');
                } else {
                    throw new \Exception('Required tables (tickets, users) do not exist');
                }
            } catch (\Exception $e) {
                log_message('error', 'TSR Dashboard: Error getting assigned tickets: ' . $e->getMessage());
                $assignedTickets = [];
            }
            
            $data = [
                'openTickets' => $openTickets,
                'inProgressTickets' => $inProgressTickets,
                'resolvedTickets' => $resolvedTickets,
                'unassignedTickets' => $unassignedTickets,
                'assignedTickets' => $assignedTickets,
                'user' => [
                    'id' => $userId,
                    'role' => $userRole,
                    'username' => $session->get('username')
                ]
            ];
            
            log_message('debug', 'TSR Dashboard: Rendering view with data');
            return view('tsr/dashboard', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'TSR Dashboard: Fatal error: ' . $e->getMessage());
            log_message('error', 'TSR Dashboard: Stack trace: ' . $e->getTraceAsString());
            
            // Return error page instead of blank page
            $data = [
                'error' => 'An error occurred while loading the dashboard: ' . $e->getMessage(),
                'openTickets' => 0,
                'inProgressTickets' => 0,
                'resolvedTickets' => 0,
                'unassignedTickets' => [],
                'assignedTickets' => []
            ];
            
            return view('tsr/dashboard', $data);
        }
    }
    
    public function viewTicket($ticketId)
    {
        try {
            $session = session();
            $userId = $session->get('id');
            
            // Get database connection
            $db = \Config\Database::connect();
            
            // --- ORIGINAL LOGIC: Get Ticket Details ---
            $ticket = $db->table('tickets t')
                ->select('t.*, creator.username as creator_name, creator.nickname as creator_nickname, assignee.username as assignee_name')
                ->join('users creator', 'creator.id = t.created_by')
                ->join('users assignee', 'assignee.id = t.assigned_to', 'left')
                ->where('t.id', $ticketId)
                ->where('t.is_deleted', 0)
                ->get()
                ->getRowArray();
            
            if (!$ticket) {
                return redirect()->to('/tsr/dashboard')->with('error', 'Ticket not found');
            }
            
            // --- ORIGINAL LOGIC: Get Ticket Messages ---
            $messages = $db->table('ticket_messages tm')
                ->select('tm.*, u.username as sender_name, u.nickname as sender_nickname')
                ->join('users u', 'u.id = tm.sender_id', 'left')
                ->where('tm.ticket_id', $ticketId)
                ->where('tm.is_deleted', 0)
                ->orderBy('tm.created_at', 'ASC')
                ->get()
                ->getResultArray();

            // --- NEW LOGIC: Queue Viewer Data (From Dashboard) ---
            
            // 1. Unassigned Queue
            $unassignedTickets = $db->table('tickets t')
                ->select('t.*, u.username as customer_name')
                ->join('users u', 'u.id = t.created_by')
                ->where('t.assigned_to IS NULL')
                ->where('t.is_deleted', 0)
                ->orderBy('FIELD(t.priority, "urgent", "high", "medium", "low")', '', false)
                ->orderBy('t.created_at', 'ASC')
                ->get()
                ->getResultArray();

            // 2. My Assigned Queue
            $assignedTickets = $db->table('tickets t')
                ->select('t.*, u.username as customer_name')
                ->join('users u', 'u.id = t.created_by')
                ->where('t.assigned_to', $userId)
                ->where('t.is_deleted', 0)
                ->orderBy('FIELD(t.priority, "urgent", "high", "medium", "low")', '', false)
                ->orderBy('t.created_at', 'ASC')
                ->get()
                ->getResultArray();
            
            $data = [
                'ticket' => $ticket,
                'messages' => $messages,
                'user' => [
                    'id' => $userId,
                    'username' => $session->get('username'),
                    'role' => $session->get('role')
                ],
                // New Data for View
                'unassignedTickets' => $unassignedTickets,
                'assignedTickets' => $assignedTickets
            ];
            
            return view('tsr/ticket', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'TSR viewTicket error: ' . $e->getMessage());
            return redirect()->to('/tsr/dashboard')->with('error', 'Error loading ticket: ' . $e->getMessage());
        }
    }

    public function queue()
    {
        try {
            $session = session();
            $userId = $session->get('id');
            $db = \Config\Database::connect();

            // 1. Unassigned Queue -> Tickets that are strictly 'pending'
            $unassignedTickets = $db->table('tickets t')
                ->select('t.*, u.username as customer_name')
                ->join('users u', 'u.id = t.created_by')
                ->where('t.assigned_to IS NULL')
                ->where('t.status', 'pending') 
                ->where('t.is_deleted', 0)
                ->orderBy('FIELD(t.priority, "urgent", "high", "medium", "low")', '', false)
                ->orderBy('t.created_at', 'ASC')
                ->get()
                ->getResultArray();

            // 2. My Assigned Queue -> Tickets that are 'in-progress' or 'pending' (if assigned but not started)
            // We explicitly EXCLUDE 'resolved' because they shouldn't be on the live board
            $assignedTickets = $db->table('tickets t')
                ->select('t.*, u.username as customer_name')
                ->join('users u', 'u.id = t.created_by')
                ->where('t.assigned_to', $userId)
                ->whereIn('t.status', ['pending', 'in-progress']) 
                ->where('t.is_deleted', 0)
                ->orderBy('FIELD(t.priority, "urgent", "high", "medium", "low")', '', false)
                ->orderBy('t.created_at', 'ASC')
                ->get()
                ->getResultArray();

            $data = [
                'unassignedTickets' => $unassignedTickets,
                'assignedTickets' => $assignedTickets,
                'user_id' => $userId
            ];

            return view('tsr/queue_viewer', $data);

        } catch (\Exception $e) {
            log_message('error', 'Queue View Error: ' . $e->getMessage());
            return "Error loading queue.";
        }
    }
    
    public function ticketChat($ticketId)
    {
        try {
            $session = session();
            $userId = $session->get('id');
            
            // Get database connection
            $db = \Config\Database::connect();
            
            // Get ticket details
            $ticket = $db->table('tickets t')
                ->select('t.*, creator.username as creator_name, assignee.username as assignee_name')
                ->join('users creator', 'creator.id = t.created_by')
                ->join('users assignee', 'assignee.id = t.assigned_to', 'left')
                ->where('t.id', $ticketId)
                ->get()
                ->getRowArray();
            
            if (!$ticket) {
                return redirect()->to('/tsr/dashboard')->with('error', 'Ticket not found');
            }
            
            // Check if TSR is assigned to this ticket or is admin
            if ($ticket['assigned_to'] != $userId && $session->get('role') != 'admin') {
                return redirect()->to('/tsr/dashboard')->with('error', 'You are not assigned to this ticket');
            }
            
            // Get ticket messages
            $messages = $db->table('ticket_messages tm')
                ->select('tm.*, u.username as sender_name')
                ->join('users u', 'u.id = tm.sender_id', 'left')
                ->where('tm.ticket_id', $ticketId)
                ->where('tm.is_deleted', 0)
                ->orderBy('tm.created_at', 'ASC')
                ->get()
                ->getResultArray();
            
            // Format messages for display compatibility
            foreach ($messages as &$message) {
                $message['message'] = $message['content']; // Map content to message for compatibility
            }
            
            $data = [
                'ticket' => $ticket,
                'messages' => $messages,
                'user' => [
                    'id' => $userId,
                    'username' => $session->get('username'),
                    'role' => $session->get('role')
                ]
            ];
            
            return view('tsr/ticket-chat', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'TSR ticketChat error: ' . $e->getMessage());
            return redirect()->to('/tsr/dashboard')->with('error', 'Error loading ticket chat: ' . $e->getMessage());
        }
    }
    
    public function claimTicket()
    {
        try {
            $session = session();
            $userId = $session->get('id');
            
            // Get POST data
            $ticketId = $this->request->getPost('ticket_id');
            
            if (!$ticketId) {
                return $this->response->setJSON(['success' => false, 'message' => 'No ticket ID provided']);
            }
            
            // Get database connection
            $db = \Config\Database::connect();
            
            // Check if ticket exists and is unassigned
            $ticket = $db->table('tickets')->where('id', $ticketId)->get()->getRowArray();
            
            if (!$ticket) {
                return $this->response->setJSON(['success' => false, 'message' => 'Ticket not found']);
            }
            
            if ($ticket['assigned_to'] !== null) {
                return $this->response->setJSON(['success' => false, 'message' => 'Ticket is already assigned']);
            }
            
            // Get TSR info
            $tsr = $db->table('users')->where('id', $userId)->get()->getRowArray();
            
            // Assign ticket to TSR
            $db->table('tickets')->where('id', $ticketId)->update([
                'assigned_to' => $userId,
                'status' => 'in-progress',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Add system message about assignment
            $db->table('ticket_messages')->insert([
                'ticket_id' => $ticketId,
                'sender_id' => $userId,
                'sender_type' => 'system',
                'content' => 'Ticket assigned to ' . ($tsr['nickname'] ?: $tsr['username']),
                'is_system' => 1,
                'is_deleted' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Emit Socket.IO events for real-time updates
            $this->emitSocketEvent('ticket_claimed', [
                'ticket_id' => $ticketId,
                'status' => 'in-progress',
                'tsr' => [
                    'id' => $userId,
                    'name' => $tsr['nickname'] ?: $tsr['username'],
                    'username' => $tsr['username'],
                    'avatar' => $tsr['avatar'] ?: 'default-avatar.png'
                ]
            ]);
            
            $this->emitSocketEvent('tsr_joined', [
                'ticket_id' => $ticketId,
                'tsr_name' => $tsr['nickname'] ?: $tsr['username']
            ]);
            
            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Ticket claimed successfully',
                'redirect' => base_url("tsr/ticket/{$ticketId}/chat")
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'TSR claimTicket error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Error claiming ticket: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Emit Socket.IO event via HTTP request to socket server
     */
    private function emitSocketEvent($event, $data)
    {
        try {
            $socketUrl = 'http://localhost:3001/emit-message';
        
            $postData = json_encode([
                'event' => $event,
                'data' => $data
            ]);
        
            $client = \Config\Services::curlrequest();
            $response = $client->request('POST', $socketUrl, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => $postData,
                'timeout' => 5
            ]);
        
            $httpCode = $response->getStatusCode();
            if ($httpCode !== 200) {
                log_message('error', "Failed to emit socket event: HTTP {$httpCode}");
            } else {
                log_message('debug', "Socket event '{$event}' emitted successfully");
            }
        
        } catch (\Exception $e) {
            log_message('error', 'Socket emit error: ' . $e->getMessage());
        }
    }
}
