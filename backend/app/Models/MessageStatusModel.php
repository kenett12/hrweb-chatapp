<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageStatusModel extends Model
{
    protected $table = 'message_status';
    protected $primaryKey = 'id';
    protected $allowedFields = ['message_id', 'user_id', 'status', 'seen_at', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getMessageSeenUsers($messageId)
    {
        // Check if the table exists before running the query
        try {
            $db = \Config\Database::connect();
            
            // If the table doesn't exist, return an empty array
            if (!in_array('message_status', $db->listTables())) {
                log_message('warning', 'message_status table does not exist');
                return [];
            }
            
            // Use direct query building for better control
            $builder = $db->table('message_status');
            $builder->select('message_status.user_id, message_status.created_at, message_status.updated_at, message_status.seen_at, users.username, users.nickname, users.avatar');
            $builder->join('users', 'users.id = message_status.user_id');
            $builder->where('message_status.message_id', $messageId);
            $builder->where('message_status.status', 'seen');
            
            $results = $builder->get()->getResultArray();
            
            log_message('debug', 'Found ' . count($results) . ' users who have seen message ' . $messageId);
            
            return $results;
        } catch (\Exception $e) {
            log_message('error', 'Error in getMessageSeenUsers: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return [];
        }
    }
}
