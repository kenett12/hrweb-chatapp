<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'sender_id', 
        'receiver_id', 
        'group_id', 
        'content', 
        'type', 
        'file_url', 
        'is_group',
        'is_read',
        'created_at',
        'reply_to_id',
        'reply_to_sender_id',
        'reply_to_content'
    ];

    // Validation
    protected $validationRules = [
        'sender_id' => 'required|integer',
        'content' => 'permit_empty',  // Changed from required_if to permit_empty
        'is_group' => 'required|integer|in_list[0,1]',
    ];
    
    protected $validationMessages = [
        'sender_id' => [
            'required' => 'Sender ID is required',
            'integer' => 'Sender ID must be an integer',
        ],
        'is_group' => [
            'required' => 'Message type (group or direct) is required',
            'integer' => 'Message type must be an integer',
            'in_list' => 'Message type must be 0 (direct) or 1 (group)',
        ],
    ];
    
    protected $skipValidation = false;
    
    // Callbacks
    protected $beforeInsert = ['setCreatedAt', 'validateReplyData'];
    
    protected function setCreatedAt(array $data)
    {
        if (!isset($data['data']['created_at'])) {
            // Set the timezone to Asia/Manila (Philippines)
            date_default_timezone_set('Asia/Manila');
            $data['data']['created_at'] = date('Y-m-d H:i:s');
        }
        
        return $data;
    }
    
    // New callback to validate and fix reply data
    protected function validateReplyData(array $data)
    {
        // If this is a reply message, ensure the reply_to_sender_id is valid
        if (isset($data['data']['reply_to_id']) && $data['data']['reply_to_id']) {
            // If reply_to_sender_id is missing or 0, try to determine it
            if (empty($data['data']['reply_to_sender_id'])) {
                // Try to find the original message
                $originalMessage = $this->find($data['data']['reply_to_id']);
                if ($originalMessage) {
                    // Use the sender of the original message
                    $data['data']['reply_to_sender_id'] = $originalMessage['sender_id'];
                } else if (isset($data['data']['is_group']) && $data['data']['is_group'] == 0 && isset($data['data']['receiver_id'])) {
                    // For direct messages, if we can't find the original message, use the receiver ID
                    $data['data']['reply_to_sender_id'] = $data['data']['receiver_id'];
                } else if (isset($data['data']['sender_id'])) {
                    // As a last resort, use the current sender's ID
                    $data['data']['reply_to_sender_id'] = $data['data']['sender_id'];
                }
            }
            
            // Clean the reply_to_content if it exists
            if (isset($data['data']['reply_to_content'])) {
                $content = $data['data']['reply_to_content'];
                // Remove timestamps like 16:49
                $content = preg_replace('/\d{1,2}:\d{2}/', '', $content);
                // Remove "Sent" and "Seen" text
                $content = preg_replace('/\s*Sent\s*/', '', $content);
                $content = preg_replace('/\s*Seen\s*/', '', $content);
                // Remove dates like 19/05/2025
                $content = preg_replace('/\s*\d{1,2}\/\d{1,2}\/\d{4},\s*/', '', $content);
                // Remove usernames like Sean1
                $content = preg_replace('/Sean\d*\s*/', '', $content);
                // Remove "Unknown User |" text
                $content = preg_replace('/Unknown User\s*\|\s*/', '', $content);
                // Clean up extra spaces
                $content = preg_replace('/\s+/', ' ', $content);
                $data['data']['reply_to_content'] = trim($content);
            }
        
            // Make sure we have a valid reply_to_sender_name
            if (empty($data['data']['reply_to_sender_name']) || $data['data']['reply_to_sender_name'] === 'Unknown User') {
                // Try to get the sender name from the database
                if (!empty($data['data']['reply_to_sender_id'])) {
                    $userModel = new \App\Models\UserModel();
                    $user = $userModel->find($data['data']['reply_to_sender_id']);
                    if ($user) {
                        $data['data']['reply_to_sender_name'] = $user['nickname'] ?? $user['username'];
                    } else {
                        $data['data']['reply_to_sender_name'] = 'User';
                    }
                } else {
                    $data['data']['reply_to_sender_name'] = 'User';
                }
            }
        }

        if (empty($data['data']['reply_to_content']) || $data['data']['reply_to_content'] === 'Original message') {
            // Try to find the original message to get its content
            if (!empty($data['data']['reply_to_id'])) {
                $originalMessage = $this->find($data['data']['reply_to_id']);
                if ($originalMessage && !empty($originalMessage['content'])) {
                    // Extract just the text content without HTML
                    $content = $originalMessage['content'];
                    // If content has HTML, strip it to get just the text
                    if (strpos($content, '<') !== false) {
                        $content = strip_tags($content);
                    }
                    $data['data']['reply_to_content'] = $content;
                } else {
                    $data['data']['reply_to_content'] = 'This message';
                }
            } else {
                $data['data']['reply_to_content'] = 'This message';
            }
        }
        
        return $data;
    }

    /**
     * Get direct messages with reply sender information
     */
    public function getDirectMessagesWithReplies($userId, $receiverId)
    {
        $messages = $this->where('is_group', 0)
            ->where('is_deleted', 0)
            ->groupStart()
                ->where('sender_id', $userId)
                ->where('receiver_id', $receiverId)
            ->groupEnd()
            ->orGroupStart()
                ->where('sender_id', $receiverId)
                ->where('receiver_id', $userId)
            ->groupEnd()
            ->orderBy('created_at', 'ASC')
            ->findAll();

        // Add reply sender information
        $userModel = new \App\Models\UserModel();
        
        foreach ($messages as &$message) {
            if (!empty($message['reply_to_id'])) {
                // If reply_to_sender_id is 0, null, or empty, determine the sender based on the message context
                if (empty($message['reply_to_sender_id'])) {
                    // Try to find the original message
                    $originalMessage = $this->find($message['reply_to_id']);
                    if ($originalMessage) {
                        $message['reply_to_sender_id'] = $originalMessage['sender_id'];
                    } else {
                        // If we can't find the original message, determine based on context
                        if ($message['sender_id'] == $userId) {
                            // If the current message is from the current user, assume they're replying to the other user
                            $message['reply_to_sender_id'] = $receiverId;
                        } else {
                            // Otherwise, assume they're replying to the current user
                            $message['reply_to_sender_id'] = $userId;
                        }
                    }
                }
                
                // Now get the sender name
                $replySender = $userModel->find($message['reply_to_sender_id']);
                if ($replySender) {
                    $message['reply_to_sender_name'] = $replySender['nickname'] ?? $replySender['username'];
                } else {
                    // If we can't find the user, check if it's the current user
                    if ($message['reply_to_sender_id'] == $userId) {
                        $currentUser = $userModel->find($userId);
                        $message['reply_to_sender_name'] = $currentUser['nickname'] ?? $currentUser['username'];
                    } else if ($message['reply_to_sender_id'] == $receiverId) {
                        $otherUser = $userModel->find($receiverId);
                        $message['reply_to_sender_name'] = $otherUser['nickname'] ?? $otherUser['username'];
                    } else {
                        $message['reply_to_sender_name'] = 'User';
                    }
                }
            }
        }
        
        return $messages;
    }
    
    /**
     * Get group messages with reply sender information
     */
    public function getGroupMessagesWithReplies($groupId)
    {
        $messages = $this->where('is_group', 1)
            ->where('group_id', $groupId)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        // Add reply sender information
        $userModel = new \App\Models\UserModel();
        
        foreach ($messages as &$message) {
            if (!empty($message['reply_to_id'])) {
                // If reply_to_sender_id is 0, null, or empty, determine the sender based on the message context
                if (empty($message['reply_to_sender_id'])) {
                    // Try to find the original message
                    $originalMessage = $this->find($message['reply_to_id']);
                    if ($originalMessage) {
                        $message['reply_to_sender_id'] = $originalMessage['sender_id'];
                    } else {
                        // If we can't find the original message, use the current message sender as a fallback
                        $message['reply_to_sender_id'] = $message['sender_id'];
                    }
                }
                
                // Now get the sender name
                $replySender = $userModel->find($message['reply_to_sender_id']);
                if ($replySender) {
                    $message['reply_to_sender_name'] = $replySender['nickname'] ?? $replySender['username'];
                } else {
                    $message['reply_to_sender_name'] = 'User';
                }
            }
        }
        
        return $messages;
    }
}
