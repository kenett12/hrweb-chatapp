<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\TicketModel;

class SAController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        $tsrs = [];
        if ($db->tableExists('users')) {
            $builder = $db->table('users');
            $builder->where("LOWER(TRIM(role)) = 'tsr'");
            $tsrs = $builder->get()->getResultArray();
        }

        $ticketsToday = 0;
        if ($db->tableExists('tickets')) {
            $ticketsToday = $db->table('tickets')
                               ->where('created_at >=', date('Y-m-d 00:00:00'))
                               ->where('is_deleted', 0)
                               ->countAllResults();
        }

        $activeNow = 0;
        foreach ($tsrs as $tsr) {
            $status = strtolower(trim($tsr['status'] ?? 'offline'));
            if ($status === 'online') {
                $activeNow++;
            }
        }

        $data = [
            'page_title' => 'HRWeb Inc. Administration',
            'stats' => [
                'total_tsrs'    => count($tsrs),
                'active_now'    => $activeNow,
                'tickets_today' => $ticketsToday,
                'avg_response'  => '1m 24s'
            ],
            'active_page' => 'dashboard'
        ];

        return view('sa/dashboard', $data);
    }

    // ==========================================
    // TSR MANAGEMENT
    // ==========================================

    public function tsrAccounts()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('users');
        $builder->where("LOWER(TRIM(role)) = 'tsr'");
        $tsrs = $builder->get()->getResultArray();

        $data = [
            'page_title' => 'TSR Master Data | HRWeb Inc.',
            'active_page' => 'tsr',
            'tsrs'       => $tsrs
        ];

        return view('sa/tsr_accounts', $data);
    }

public function createTsr()
{
    $rules = [
        'username' => [
            'rules'  => 'required|min_length[4]|is_unique[users.username]',
            'errors' => [
                'is_unique' => 'This username is already in use.'
            ]
        ],
        'name' => 'required|min_length[2]',
        'password' => [
            'rules'  => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/]',
            'errors' => [
                'required'    => 'Password is required.',
                'min_length'  => 'Password must be at least 8 characters long.',
                'regex_match' => 'Password must contain at least one uppercase letter, one number, and one special character (@$!%*?&).'
            ]
        ]
    ];

    if (!$this->validate($rules)) {
        $validationErrors = implode(' ', $this->validator->getErrors());
        return redirect()->back()->withInput()->with('error', 'Validation Error: ' . $validationErrors);
    }

    $userModel = new \App\Models\UserModel();
    $username = $this->request->getPost('username');
    $name = $this->request->getPost('name');
    $rawPassword = $this->request->getPost('password');

    $tsrCount = $userModel->where('role', 'tsr')->countAllResults();
    $nextNumber = $tsrCount + 1;

    $data = [
        'username' => $username,
        'nickname' => $name,
        'email'    => strtolower($username) . '_tsr' . $nextNumber . '@hrweb.ph', 
        'password' => password_hash($rawPassword, PASSWORD_DEFAULT),
        'role'     => 'tsr',
        'status'   => 'offline'
    ];

    if ($userModel->insert($data)) {
        return redirect()->to(base_url('sa/tsr-accounts'))->with('success', 'TSR Account provisioned successfully.');
    } else {
        $errorString = implode(' ', $userModel->errors());
        return redirect()->back()->withInput()->with('error', 'Failed: ' . $errorString);
    }
}

    // ==========================================
    // CLIENT MANAGEMENT (NEW)
    // ==========================================

   // ==========================================
    // CLIENT MANAGEMENT
    // ==========================================

    // Matches route: $routes->get('client-accounts', 'SAController::clients');
   public function clients()
    {
        $userModel = new \App\Models\UserModel();
        
        // Fetch users with role 'user'
        $rawClients = $userModel->where('role', 'user') 
                                ->orderBy('created_at', 'ASC') 
                                ->findAll();

        // Assign Virtual IDs
        $clients = [];
        foreach ($rawClients as $index => $client) {
            $client['client_number'] = $index + 1; 
            $clients[] = $client;
        }

        // Sort newest first
        usort($clients, function($a, $b) {
            return $b['id'] - $a['id']; 
        });
                                     
        $data['page_title'] = 'Client Management | HRWeb Inc.';
        $data['active_page'] = 'clients'; 
        $data['clients'] = $clients;
        
        return view('sa/clients', $data); 
    }

    // Matches route: $routes->post('create-client', 'SAController::createClient');
   public function createClient()
    {
        // 1. Validation
        $rules = [
            'username' => 'required|min_length[4]|is_unique[users.username]',
            'name'     => 'required|min_length[3]', // Form field name is 'name'
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Validation Failed: ' . implode(', ', $this->validator->getErrors()));
        }

        $userModel = new \App\Models\UserModel();
        
        // 2. Prepare Data (Matching your Database Columns)
        $username = $this->request->getPost('username');
        $generatedEmail = strtolower($username) . '_client@hrweb.ph';
        
        // Get current timestamp for created_at (if Model doesn't handle it automatically)
        $currentDate = date('Y-m-d H:i:s');

        $data = [
            'username'   => $username,
            'email'      => $generatedEmail,
            'password'   => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'nickname'   => $this->request->getPost('name'), // Map Input 'name' to DB 'nickname'
            'avatar'     => 'default.png',
            'status'     => 'active',
            'role'       => 'user', // As requested
            'created_at' => $currentDate,
            'updated_at' => $currentDate
        ];

        // 3. Insert
        if ($userModel->insert($data)) {
            return redirect()->to('sa/client-accounts')->with('success', "Client created! Login Email: $generatedEmail");
        } else {
            // Log the error to see what went wrong
            log_message('error', 'Create Client Error: ' . json_encode($userModel->errors()));
            return redirect()->back()->withInput()->with('error', 'Failed to save to database. Check logs.');
        }
    }

    // Delete a single client
    public function deleteClient($id)
    {
        $userModel = new \App\Models\UserModel();
        $db = \Config\Database::connect();

        // 1. Verify we are deleting a 'user' (client), not an admin
        $user = $userModel->find($id);
        
        if ($user && $user['role'] === 'user') {
            
            // 2. Delete the specific user
            $userModel->delete($id);

            $db->query("ALTER TABLE users AUTO_INCREMENT = 1");

            return redirect()->back()->with('success', 'Client deleted and ID counter reset.');
        }
        
        return redirect()->back()->with('error', 'Cannot delete: User not found or permission denied.');
    }


    // ==========================================
    // SYSTEM LOGS & AUDIT
    // ==========================================

    public function auditTrail()
    {
        $db = \Config\Database::connect();
        $logs = [];
        
        if ($db->tableExists('audit_logs')) {
            $logs = $db->table('audit_logs')
                        ->orderBy('created_at', 'DESC')
                        ->limit(50)
                        ->get()
                        ->getResultArray();
        }

        $data = [
            'page_title' => 'System Audit Trail',
            'active_page' => 'audit',
            'logs'       => $logs
        ];

        return view('sa/audit_trail', $data);
    }

    public function tickets()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tickets');
        
        // Select ticket info + join Client and TSR names
        $builder->select('
            tickets.*, 
            client.username as client_name, 
            client.nickname as client_nickname,
            tsr.username as tsr_name, 
            tsr.nickname as tsr_nickname
        ');
        
        // Join Users table: Once for Client (created_by), Once for Agent (assigned_to)
        $builder->join('users as client', 'client.id = tickets.created_by', 'left');
        $builder->join('users as tsr', 'tsr.id = tickets.assigned_to', 'left');
        
        // Remove this line if you want to see deleted tickets too
        $builder->where('tickets.is_deleted', 0);
        
        // Show newest updates first
        $builder->orderBy('tickets.updated_at', 'DESC');
        
        $data = [
            'page_title' => 'Support Ticket Logs',
            'active_page' => 'tickets', // Matches sidebar key
            'tickets'    => $builder->get()->getResultArray()
        ];

        // Ensure this view file exists at: app/Views/sa/tickets.php
        return view('sa/tickets', $data); 
    }

   public function ticketLogs()
    {
        $db = \Config\Database::connect();
        $tickets = [];
        $categories = [];

        // 1. GET TICKETS
        if ($db->tableExists('tickets')) {
            $builder = $db->table('tickets');
            $builder->select('tickets.*, tsr.username as tsr_name, client.username as client_name, client.nickname as client_nickname');
            $builder->join('users as tsr', 'tsr.id = tickets.assigned_to', 'left');
            $builder->join('users as client', 'client.id = tickets.created_by', 'left');
            $builder->where('tickets.is_deleted', 0);
            $builder->orderBy('tickets.updated_at', 'DESC');
            $tickets = $builder->get()->getResultArray();
        }

        // 2. GET CATEGORIES (Direct Table Query - Fixes the error)
        if ($db->tableExists('ticket_categories')) {
            $categories = $db->table('ticket_categories')
                             ->orderBy('id', 'DESC')
                             ->get()
                             ->getResultArray();
        }

        $data = [
            'page_title' => 'Support Ticket Logs',
            'active_page' => 'tickets',
            'tickets'    => $tickets,
            'categories' => $categories 
        ];

        return view('sa/ticket_logs', $data);
    }

    public function saveCategory()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        
        $data = [
            'name' => $this->request->getPost('name'), // Matches your DB column
            'description' => 'User created category', // Default description
            'color' => '#3b82f6', // Default color (Blue)
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($id)) {
            unset($data['created_at']); // Don't reset date on edit
            $db->table('ticket_categories')->where('id', $id)->update($data);
            $msg = 'Category updated.';
        } else {
            $db->table('ticket_categories')->insert($data);
            $msg = 'Category added.';
        }

        return redirect()->to(base_url('sa/tickets'))->with('success', $msg);
    }

   public function toggleCategory()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        $status = $this->request->getPost('status');
        
        $db->table('ticket_categories')->where('id', $id)->update(['is_active' => $status == 1 ? 0 : 1]);
        return redirect()->to(base_url('sa/tickets'));
    }

    public function deleteCategory()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        $db->table('ticket_categories')->where('id', $id)->delete();
        return redirect()->to(base_url('sa/tickets'))->with('success', 'Category deleted.');
    }

    public function exportTickets()
    {
        $filename = 'ticket_logs_' . date('Y-m-d_His') . '.csv';
        
        $db = \Config\Database::connect();
        $builder = $db->table('tickets');
        $builder->select('
            tickets.id, 
            tickets.subject, 
            client.username as client_name, 
            tsr.username as tsr_name, 
            tickets.priority, 
            tickets.status, 
            tickets.created_at, 
            tickets.updated_at
        ');
        
        $builder->join('users as client', 'client.id = tickets.created_by', 'left');
        $builder->join('users as tsr', 'tsr.id = tickets.assigned_to', 'left');
        $builder->where('tickets.is_deleted', 0);
        $builder->orderBy('tickets.id', 'DESC');
        
        $tickets = $builder->get()->getResultArray();

        // Set Headers for Download
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Type: application/csv;");

        $file = fopen('php://output', 'w');

        // CSV Column Headers
        fputcsv($file, ['ID', 'Subject', 'Client', 'Assigned TSR', 'Priority', 'Status', 'Created Date', 'Last Updated']);

        foreach ($tickets as $t) {
            $line = [
                $t['id'],
                $t['subject'],
                $t['client_name'] ?? 'Unknown',
                $t['tsr_name'] ?? 'Unassigned',
                ucfirst($t['priority']),
                ucfirst($t['status']),
                $t['created_at'],
                $t['updated_at']
            ];
            fputcsv($file, $line);
        }

        fclose($file);
        exit;
    }

    public function backups()
    {
        helper('number');
        $backupPath = WRITEPATH . 'backups/';
        $backups = [];

        if (is_dir($backupPath)) {
            $files = array_diff(scandir($backupPath), ['.', '..']);
            foreach ($files as $file) {
                $filePath = $backupPath . $file;
                $backups[] = [
                    'filename'   => $file,
                    'size'       => round(filesize($filePath) / 1024 / 1024, 2),
                    'type'       => pathinfo($file, PATHINFO_EXTENSION),
                    'created_by' => 'System',
                    'created_at' => date("Y-m-d H:i:s", filemtime($filePath))
                ];
            }
        }

        $data = [
            'page_title' => 'Database Backups',
            'active_page' => 'backups',
            'backups'    => $backups
        ];

        return view('sa/backups', $data);
    }

    // ==========================================
    // KNOWLEDGE BASE (FEEDBACK MANAGER)
    // ==========================================

    public function feedback()
    {
        $db = \Config\Database::connect();
        
        $entries = $db->table('kb_entries')
                      ->orderBy('id', 'DESC')
                      ->get()
                      ->getResultArray();
                      
        $stats = [
            'total'  => count($entries),
            'active' => 0,
            'drafts' => 0
        ];
        
        foreach($entries as $e) {
            if($e['approved'] == 1) $stats['active']++;
            else $stats['drafts']++;
        }

        $data = [
            'page_title' => 'Knowledge Base Manager',
            'active_page' => 'feedback',
            'entries' => $entries,
            'stats' => $stats
        ];

        return view('sa/feedback', $data);
    }

    public function saveKbEntry()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        
        $data = [
            'question' => $this->request->getPost('question'),
            'intent'   => $this->request->getPost('intent'),
            'answer'   => $this->request->getPost('answer'),
            'approved' => $this->request->getPost('approved'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($id) {
            $db->table('kb_entries')->where('id', $id)->update($data);
            $msg = 'Entry updated successfully.';
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['confidence'] = 1.0;
            $db->table('kb_entries')->insert($data);
            $msg = 'New entry added successfully.';
        }

        return redirect()->to(base_url('sa/feedback'))->with('success', $msg);
    }
    
    public function deleteKbEntry($id)
    {
        $db = \Config\Database::connect();
        $db->table('kb_entries')->where('id', $id)->delete();
        $db->query("ALTER TABLE kb_entries AUTO_INCREMENT = 1");

        return redirect()->to(base_url('sa/feedback'))->with('success', 'Entry deleted and ID sequence reset.');
    }

    // ==========================================
    // CHAT & GROUP MANAGEMENT
    // ==========================================

    public function chatManager()
    {
        $db = \Config\Database::connect();
        $groups = [];

        // Fetch Groups
        if ($db->tableExists('groups')) {
            $groups = $db->table('groups')
                         ->select('groups.*, COUNT(group_members.id) as member_count')
                         ->join('group_members', 'group_members.group_id = groups.id', 'left')
                         ->groupBy('groups.id')
                         ->orderBy('groups.created_at', 'DESC')
                         ->get()
                         ->getResultArray();
        }

        $data = [
            'page_title'  => 'Chat Manager',
            'active_page' => 'chats',
            'groups'      => $groups
        ];

        return view('sa/chat_manager', $data);
    }

    public function createChatGroup()
    {
        $groupModel = new \App\Models\GroupModel();
        
        $data = [
            'name'        => $this->request->getPost('group_name'),
            'description' => $this->request->getPost('description'),
            'created_by'  => session()->get('id'),
            'image'       => 'default-group.png'
        ];

        if ($id = $groupModel->insert($data)) {
            // Auto-add creator as admin
            $db = \Config\Database::connect();
            $db->table('group_members')->insert([
                'group_id' => $id,
                'user_id'  => session()->get('id'),
                'is_admin' => 1,
                'joined_at' => date('Y-m-d H:i:s')
            ]);
            return redirect()->to(base_url('sa/chat-manager'))->with('success', 'Group created.');
        }

        return redirect()->back()->with('error', implode(' ', $groupModel->errors()));
    }

    public function deleteChatGroup()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        
        // 1. Delete Members first (Constraint)
        $db->table('group_members')->where('group_id', $id)->delete();
        
        // 2. Delete the Group (Table: 'groups', NOT 'chat_groups')
        $db->table('groups')->where('id', $id)->delete();

        return $this->response->setJSON(['success' => true]);
    }

    // ==========================================
    // MEMBER MANAGEMENT (AJAX)
    // ==========================================

    public function getGroupMembers($groupId)
    {
        $db = \Config\Database::connect();
        
        // Join users table to fetch Role and Avatar
        $members = $db->table('group_members')
                      ->select('group_members.id as membership_id, users.id as user_id, users.username, users.nickname, users.role, users.avatar, group_members.is_admin')
                      ->join('users', 'users.id = group_members.user_id')
                      ->where('group_members.group_id', $groupId)
                      ->get()
                      ->getResultArray();

        return $this->response->setJSON($members);
    }

    public function searchChatUsers()
    {
        $db = \Config\Database::connect();
        $query = $this->request->getGet('q');
        $groupId = $this->request->getGet('group_id');

        if (!$query || strlen($query) < 2) return $this->response->setJSON([]);

        // 1. Get IDs of users already in this group (to exclude them)
        $subQuery = $db->table('group_members')
                       ->select('user_id')
                       ->where('group_id', $groupId)
                       ->getCompiledSelect();

        // 2. Main Search Query
        $users = $db->table('users')
                    ->select('id, username, nickname, role, avatar')
                    
                    // Filter: Exclude existing members
                    ->where("id NOT IN ($subQuery)", null, false)
                    
                    // Filter: ONLY allow 'user' and 'tsr' roles (No superadmin)
                    ->whereIn('role', ['user', 'tsr']) 
                    
                    // Search Logic: Grouping is required so OR doesn't break the Role filter
                    ->groupStart()
                        ->like('username', $query)
                        ->orLike('nickname', $query)
                    ->groupEnd()
                    
                    ->limit(5)
                    ->get()
                    ->getResultArray();

        return $this->response->setJSON($users);
    }
    public function addGroupMember()
    {
        $model = new \App\Models\GroupMemberModel();
        
        $data = [
            'group_id' => $this->request->getPost('group_id'),
            'user_id'  => $this->request->getPost('user_id'),
            'is_admin' => 0,
            'joined_at' => date('Y-m-d H:i:s')
        ];

        // Check if already exists
        $exists = $model->where('group_id', $data['group_id'])
                        ->where('user_id', $data['user_id'])
                        ->first();

        if (!$exists) {
            $model->insert($data);
            return $this->response->setJSON(['success' => true]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'User already added']);
    }

    public function removeGroupMember()
    {
        $model = new \App\Models\GroupMemberModel();
        $id = $this->request->getPost('membership_id');
        
        $model->delete($id);
        return $this->response->setJSON(['success' => true]);
    }

    // In SAController.php
public function addMembersBatch()
    {
        $db = \Config\Database::connect();
        $groupId = $this->request->getPost('group_id');
        $userIdsStr = $this->request->getPost('user_ids'); 
        $currentUserId = session()->get('id');
        $currentUserName = session()->get('username'); // Assumes username is in session

        if (empty($userIdsStr)) return $this->response->setJSON(['success' => false]);

        $userIds = explode(',', $userIdsStr);
        $addedNames = [];
        $builder = $db->table('group_members');
        $userBuilder = $db->table('users');

        // 1. Add Members
        foreach ($userIds as $uid) {
            $exists = $builder->where('group_id', $groupId)->where('user_id', $uid)->countAllResults();
            if (!$exists) {
                $builder->insert([
                    'group_id' => $groupId,
                    'user_id'  => $uid,
                    'is_admin' => 0,
                    'joined_at' => date('Y-m-d H:i:s')
                ]);
                
                // Get name for the system message
                $u = $userBuilder->select('username')->where('id', $uid)->get()->getRow();
                if($u) $addedNames[] = $u->username;
            }
        }

        // 2. Create System Message
        $systemMsgData = null;
        if (!empty($addedNames)) {
            $namesStr = implode(', ', $addedNames);
            $content = "$currentUserName added $namesStr to the group.";
            
            $systemMsgData = [
                'group_id'   => $groupId,
                'sender_id'  => $currentUserId,
                'content'    => $content,
                'type'       => 'system',
                'created_at' => date('Y-m-d H:i:s'),
                'is_read'    => 0
            ];
            
            $db->table('messages')->insert($systemMsgData);
            $systemMsgData['id'] = $db->insertID(); // Get new ID
            $systemMsgData['nickname'] = 'System'; // Dummy name for UI
            $systemMsgData['avatar'] = 'default.png';
        }

        return $this->response->setJSON([
            'success' => true, 
            'system_message' => $systemMsgData
        ]);
    }

    
}