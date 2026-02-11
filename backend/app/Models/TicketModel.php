<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketModel extends Model
{
    protected $table = 'tickets';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'created_by', 'subject', 'description', 'category', 'priority', 
        'status', 'assigned_to', 'created_at', 'updated_at', 'closed_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation Rules
    protected $validationRules = [
        'subject'     => 'required|min_length[3]|max_length[255]',
        'description' => 'required|min_length[5]',
        'priority'    => 'required|in_list[low,medium,high,urgent]',
        'category'    => 'required|max_length[100]',
        // UPDATED: Added 'closed' to allow admin/system closure
        'status'      => 'permit_empty|in_list[pending,in-progress,resolved,closed]', 
        'created_by'  => 'required|integer',
        'assigned_to' => 'permit_empty|integer'
    ];

    protected $validationMessages = [
        'subject' => [
            'required'   => 'Subject is required',
            'min_length' => 'Subject must be at least 3 characters long',
            'max_length' => 'Subject cannot exceed 255 characters'
        ],
        'description' => [
            'required'   => 'Description is required',
            'min_length' => 'Description must be at least 5 characters long'
        ],
        'priority' => [
            'required' => 'Priority is required',
            'in_list'  => 'Priority must be one of: low, medium, high, urgent'
        ],
        'status' => [
            'in_list' => 'Status must be one of: pending, in-progress, resolved, closed'
        ]
    ];

    /**
     * Get tickets for TSR dashboard with filters
     */
    public function getTicketsForTSR($tsrId = null, $filters = [])
    {
        $builder = $this->db->table($this->table . ' t');
        $builder->select('t.*, u.username as customer_name, u.nickname as customer_nickname, u.avatar as customer_avatar, 
                          tsr.username as assigned_tsr_name, tsr.nickname as assigned_tsr_nickname');
        $builder->join('users u', 't.created_by = u.id', 'left');
        $builder->join('users tsr', 't.assigned_to = tsr.id', 'left');
        $builder->where('t.is_deleted', 0);

        // Apply filters
        if (isset($filters['status']) && !empty($filters['status'])) {
            $builder->where('t.status', $filters['status']);
        }

        if (isset($filters['priority']) && !empty($filters['priority'])) {
            $builder->where('t.priority', $filters['priority']);
        }

        if (isset($filters['assigned_to'])) {
            if ($filters['assigned_to'] === 'unassigned') {
                $builder->where('t.assigned_to IS NULL');
            } elseif ($filters['assigned_to'] === 'assigned' && $tsrId) {
                $builder->where('t.assigned_to', $tsrId);
            }
        }

        if (isset($filters['category']) && !empty($filters['category'])) {
            $builder->where('t.category', $filters['category']);
        }

        $builder->orderBy('FIELD(t.priority, "urgent", "high", "medium", "low")', '', false);
        $builder->orderBy('t.updated_at', 'DESC');

        $tickets = $builder->get()->getResultArray();

        // Format the results
        foreach ($tickets as &$ticket) {
            $ticket['customer'] = [
                'name'   => $ticket['customer_nickname'] ?: $ticket['customer_name'],
                'avatar' => $ticket['customer_avatar'] ?: 'default-avatar.png'
            ];
            
            if ($ticket['assigned_to']) {
                $ticket['assigned_tsr'] = [
                    'name' => $ticket['assigned_tsr_nickname'] ?: $ticket['assigned_tsr_name']
                ];
            }

            // Check if ticket has unread messages for TSR
            $ticket['unread'] = $this->hasUnreadMessages($ticket['id'], $tsrId);
        }

        return $tickets;
    }

    /**
     * Assign ticket to TSR
     */
    public function assignToTSR($ticketId, $tsrId)
    {
        $data = [
            'assigned_to' => $tsrId,
            'status'      => 'in-progress'
        ];

        $result = $this->update($ticketId, $data);

        if ($result) {
            // Log status change (Assuming old status was 'pending')
            $this->logStatusChange($ticketId, 'pending', 'in-progress', $tsrId, 'Ticket assigned to TSR');
        }

        return $result;
    }

    /**
     * Update ticket status (Fixed for Closed status)
     */
    public function updateStatus($ticketId, $newStatus, $userId, $reason = null)
    {
        $ticket = $this->find($ticketId);
        if (!$ticket) {
            return false;
        }

        $oldStatus = $ticket['status'];
        $data = ['status' => $newStatus];

        // Set closed_at timestamp if resolved or closed
        if ($newStatus === 'resolved' || $newStatus === 'closed') {
            $data['closed_at'] = date('Y-m-d H:i:s');
        }

        $result = $this->update($ticketId, $data);

        if ($result) {
            // Log status change
            $this->logStatusChange($ticketId, $oldStatus, $newStatus, $userId, $reason);
        }

        return $result;
    }

    /**
     * Log status changes to history table
     */
    private function logStatusChange($ticketId, $oldStatus, $newStatus, $changedBy, $reason = null)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('ticket_status_history');
        
        return $builder->insert([
            'ticket_id'  => $ticketId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'reason'     => $reason,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check if ticket has unread messages for user
     */
    private function hasUnreadMessages($ticketId, $userId)
    {
        if (!$userId) return false;

        $db = \Config\Database::connect();
        $builder = $db->table('ticket_messages tm');
        $builder->select('COUNT(*) as unread_count');
        $builder->where('tm.ticket_id', $ticketId);
        $builder->where('tm.sender_id !=', $userId);
        
        // Use subquery safely to find last read time
        $subQuery = $db->table('ticket_read_status')
                       ->select('last_read_at')
                       ->where('ticket_id', $ticketId)
                       ->where('user_id', $userId)
                       ->getCompiledSelect();

        // Compare message time against last read time (or default date if never read)
        $builder->where("tm.created_at > COALESCE(($subQuery), '1970-01-01')");

        $result = $builder->get()->getRowArray();
        return ($result && $result['unread_count'] > 0);
    }

    /**
     * Get ticket statistics for TSR
     */
    public function getTSRStats($tsrId)
    {
        $stats = [];

        // Assigned tickets count (Active only - excludes resolved AND closed)
        $stats['assigned'] = $this->where('assigned_to', $tsrId)
                                  ->where('status !=', 'resolved')
                                  ->where('status !=', 'closed')
                                  ->countAllResults();

        // Resolved today count
        $today = date('Y-m-d');
        $stats['resolved_today'] = $this->where('assigned_to', $tsrId)
                                        ->where('status', 'resolved')
                                        ->where('DATE(updated_at)', $today)
                                        ->countAllResults();

        // Average response time calculation
        $db = \Config\Database::connect();
        $query = $db->query("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, t.created_at, tm.created_at)) as avg_response_time
            FROM tickets t
            JOIN ticket_messages tm ON t.id = tm.ticket_id
            WHERE t.assigned_to = ? 
            AND tm.sender_type = 'tsr'
            AND tm.id = (
                SELECT MIN(id) FROM ticket_messages 
                WHERE ticket_id = t.id AND sender_type = 'tsr'
            )
            AND DATE(t.created_at) = ?
        ", [$tsrId, $today]);

        $result = $query->getRowArray();
        $stats['avg_response_time'] = round($result['avg_response_time'] ?? 0);

        return $stats;
    }

    /**
     * Get unassigned tickets
     */
    public function getUnassignedTickets()
    {
        return $this->select('tickets.*, users.username as customer_name, users.nickname as customer_nickname')
                    ->join('users', 'users.id = tickets.created_by')
                    ->where('tickets.assigned_to', null)
                    ->where('tickets.status', 'pending') // Only pending
                    ->orderBy('FIELD(tickets.priority, "urgent", "high", "medium", "low")', '', false)
                    ->orderBy('tickets.created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get assigned tickets for a specific TSR
     */
    public function getAssignedTickets($tsrId)
    {
        return $this->select('tickets.*, users.username as customer_name, users.nickname as customer_nickname')
                    ->join('users', 'users.id = tickets.created_by')
                    ->where('tickets.assigned_to', $tsrId)
                    ->where('tickets.status !=', 'resolved') // Hide resolved
                    ->where('tickets.status !=', 'closed')   // Hide closed
                    ->orderBy('FIELD(tickets.priority, "urgent", "high", "medium", "low")', '', false)
                    ->orderBy('tickets.created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get all tickets created by a specific user (Client)
     */
    public function getUserTickets($userId)
    {
        return $this->where('created_by', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }


}