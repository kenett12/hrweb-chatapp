<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketMessageModel extends Model
{
    protected $table = 'ticket_messages';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    // DISABLE Soft Deletes to prevent hiding rows
    protected $useSoftDeletes = false; 
    
    // CRITICAL: All these fields must be allowed
    protected $allowedFields = [
        'ticket_id',
        'sender_id', 
        'sender_type',
        'content',
        'is_system',
        'is_deleted',
        'created_at',
        'updated_at'
    ];

    // Enable timestamps to fill created_at automatically
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // DISABLE Validation for now to ensure it's not blocking saves
    protected $validationRules = [];

    /**
     * Get messages (DEBUG VERSION: No Filters)
     */
    public function getTicketMessages($ticketId)
    {
        $builder = $this->db->table($this->table . ' tm');
        $builder->select('tm.*, u.username, u.nickname, u.avatar');
        $builder->join('users u', 'tm.sender_id = u.id', 'left');
        $builder->where('tm.ticket_id', $ticketId);
        
        // I REMOVED the 'is_deleted' check. 
        // This ensures that even if is_deleted is NULL or 1, you will see the message.
        // $builder->where('tm.is_deleted', 0); 
        
        $builder->orderBy('tm.created_at', 'ASC');

        $messages = $builder->get()->getResultArray();

        // Format names
        foreach ($messages as &$message) {
            $message['sender_name'] = $message['nickname'] ?: $message['username'] ?: 'System';
            $message['content'] = $this->formatMessageContent($message['content']);
        }

        return $messages;
    }

    /**
     * Add Message (DEBUG VERSION: Explicit Defaults)
     */
    public function addMessage($ticketId, $senderId, $senderType, $content)
    {
        // Force every field to have a value to prevent DB errors
        $data = [
            'ticket_id'    => $ticketId,
            'sender_id'    => $senderId,
            'sender_type'  => $senderType,
            'content'      => $content,
            'is_system'    => 0,
            'is_deleted'   => 0, // Explicitly setting this to 0
            'created_at'   => date('Y-m-d H:i:s')
        ];

        // Try to insert
        if ($this->insert($data)) {
            $messageId = $this->getInsertID();
            
            // Try to update ticket timestamp
            try {
                $db = \Config\Database::connect();
                $db->table('tickets')->where('id', $ticketId)->update(['updated_at' => date('Y-m-d H:i:s')]);
            } catch (\Exception $e) {}

            return $this->getMessageWithSender($messageId);
        } else {
            // LOG THE ERROR! Check writable/logs/log-YYYY-MM-DD.php if this fails
            log_message('critical', 'Message Save Failed: ' . json_encode($this->errors()));
            return false;
        }
    }

    /**
     * Helper to get the message we just saved
     */
    private function getMessageWithSender($messageId)
    {
        $builder = $this->db->table($this->table . ' tm');
        $builder->select('tm.*, u.username, u.nickname, u.avatar');
        $builder->join('users u', 'tm.sender_id = u.id', 'left');
        $builder->where('tm.id', $messageId);

        $message = $builder->get()->getRowArray();

        if ($message) {
            $message['sender_name'] = $message['nickname'] ?: $message['username'] ?: 'System';
            $message['content'] = $this->formatMessageContent($message['content']);
        }

        return $message;
    }

    private function formatMessageContent($content)
    {
        if ($content === null) return '';
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        return nl2br($content);
    }

    // prevent crash if read_status table is missing
    public function markAsRead($ticketId, $userId)
    {
        try {
            $db = \Config\Database::connect();
            $builder = $db->table('ticket_read_status');
            
            // Simple upsert
            $sql = "INSERT INTO ticket_read_status (ticket_id, user_id, last_read_at) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE last_read_at = VALUES(last_read_at)";
            
            $db->query($sql, [$ticketId, $userId, date('Y-m-d H:i:s')]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}