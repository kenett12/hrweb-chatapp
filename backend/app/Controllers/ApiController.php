<?php

namespace App\Controllers;

use App\Models\GroupModel;
use App\Models\GroupMemberModel;
use App\Models\UserModel;
use App\Models\MessageModel;
use App\Models\MessageStatusModel;
use App\Models\TicketModel;
use App\Models\TicketMessageModel;
use CodeIgniter\API\ResponseTrait;
use DateTime;

class ApiController extends BaseController
{
    use ResponseTrait;

    protected $groupModel;
    protected $groupMemberModel;
    protected $userModel;
    protected $messageModel;
    protected $messageStatusModel;
    protected $db;

    public function __construct()
    {
        $this->groupModel = new GroupModel();
        $this->groupMemberModel = new GroupMemberModel();
        $this->userModel = new UserModel();
        $this->messageModel = new MessageModel();
        $this->messageStatusModel = new MessageStatusModel();
        $this->db = \Config\Database::connect();
        
        helper(['form', 'url']);
        
        // For debugging
        log_message('debug', 'ApiController initialized');
    }

    protected function setCorsHeaders()
    {
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');

        return $this->response;
    }

    // Handle OPTIONS requests for CORS preflight
    public function options()
    {
        return $this->setCorsHeaders()->setStatusCode(200);
    }

    // --- FIX: Added getGroups alias to match the api/groups route ---
    public function getGroups()
    {
        // Reuse the existing logic from getUserGroups
        return $this->getUserGroups();
    }

    // Update the getDirectMessages method to include the message_status information
    public function getDirectMessages($otherUserId)
    {
        $userId = session()->get('id');
        
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            // Log the request parameters for debugging
            log_message('debug', "Getting direct messages between user $userId and $otherUserId");
            
            // Get direct messages with explicit ordering and conditions
            $messages = $this->messageModel
                ->select('messages.*, users.username, users.nickname, users.avatar, message_status.status')
                ->join('users', 'users.id = messages.sender_id')
                ->join('message_status', 'messages.id = message_status.message_id', 'left')
                ->where('messages.is_group', 0)
                ->groupStart()
                    ->groupStart()
                        ->where('messages.sender_id', $userId)
                        ->where('messages.receiver_id', $otherUserId)
                    ->groupEnd()
                    ->orGroupStart()
                        ->where('messages.sender_id', $otherUserId)
                        ->where('messages.receiver_id', $userId)
                    ->groupEnd()
                ->groupEnd()
                ->orderBy('messages.created_at', 'ASC')
                ->findAll();
            
            // Log the SQL query for debugging
            log_message('debug', 'Last query: ' . $this->messageModel->getLastQuery()->getQuery());
            
            // Mark messages as read
            $this->markDirectMessagesAsRead($userId, $otherUserId);
            
            // Log the found messages for debugging
            log_message('debug', 'Found ' . count($messages) . ' direct messages');
            
            return $this->setCorsHeaders()->setJSON($messages);
        } catch (\Exception $e) {
            log_message('error', 'Exception getting direct messages: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to load messages: ' . $e->getMessage()]);
        }
    }

    // New method to mark direct messages as read
    private function markDirectMessagesAsRead($userId, $otherUserId)
    {
        try {
            // Get all unread messages from the other user to current user
            $unreadMessages = $this->messageModel
                ->where('sender_id', $otherUserId)
                ->where('receiver_id', $userId)
                ->where('is_group', 0)
                ->where('is_read', 0)
                ->findAll();
            
            // Mark all messages as read
            $this->messageModel->where('sender_id', $otherUserId)
                ->where('receiver_id', $userId)
                ->where('is_group', 0)
                ->set(['is_read' => 1])
                ->update();
                
            log_message('debug', "Marked messages from user $otherUserId to $userId as read");
            
            // For each message, also update the message_status table and emit socket events
            foreach ($unreadMessages as $message) {
                // Add entry to message_status table
                try {
                    $statusData = [
                        'message_id' => $message['id'],
                        'user_id' => $userId,
                        'status' => 'seen',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Check if entry already exists
                    $existingStatus = $this->messageStatusModel
                        ->where('message_id', $message['id'])
                        ->where('user_id', $userId)
                        ->first();
                        
                    if ($existingStatus) {
                        $this->messageStatusModel->update($existingStatus['id'], [
                            'status' => 'seen',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        $this->messageStatusModel->insert($statusData);
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Error updating message_status: ' . $e->getMessage());
                    // Continue execution
                }
                
                // Emit socket event for each message
                $this->emitMessageSeenEvent($message['id'], $userId, $otherUserId, false, '');
            }
            
            // Emit socket event to notify about read messages
            $this->emitMessagesReadEvent($userId, $otherUserId, false);
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error marking messages as read: ' . $e->getMessage());
            return false;
        }
    }

    // New method to mark group messages as read
    private function markGroupMessagesAsRead($userId, $groupId)
    {
        try {
            $this->messageModel->where('group_id', $groupId)
                ->where('sender_id !=', $userId)
                ->where('is_group', 1)
                ->set(['is_read' => 1])
                ->update();
                
            log_message('debug', "Marked messages in group $groupId as read for user $userId");
            
            // Emit socket event to notify about read messages
            $this->emitMessagesReadEvent($userId, $groupId, true);
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error marking group messages as read: ' . $e->getMessage());
            return false;
        }
    }

    // New method to emit messages read event to socket server
    private function emitMessagesReadEvent($readerId, $chatId, $isGroup)
    {
        try {
            // Socket server URL
            $socketServerUrl = 'http://localhost:3001/emit-message';
            
            // Prepare message data for socket server
            $socketData = [
                'event' => 'messages_read',
                'data' => [
                    'reader_id' => $readerId,
                    'chat_id' => $chatId,
                    'is_group' => $isGroup,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            
            // Log the data being sent to socket server
            log_message('debug', 'Sending messages_read event to socket server: ' . json_encode($socketData));
            
            // Send HTTP request to socket server
            $client = \Config\Services::curlrequest();
            $response = $client->request('POST', $socketServerUrl, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $socketData,
                'timeout' => 3, // Short timeout to avoid blocking
            ]);
            
            // Log the response for debugging
            log_message('debug', 'Socket server response: ' . $response->getBody());
            
        } catch (\Exception $e) {
            // Log error but don't fail the request
            log_message('error', 'Failed to send messages_read event to socket server: ' . $e->getMessage());
        }
    }

    // Add a new API endpoint to mark messages as read
    public function markMessagesAsRead()
    {
        $userId = session()->get('id');
        
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        // Get request data
        $json = $this->request->getJSON(true);
        if (empty($json)) {
            return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'Invalid request data']);
        }
        
        $chatId = $json['chat_id'] ?? null;
        $isGroup = isset($json['is_group']) ? (bool)$json['is_group'] : false;
        
        if (!$chatId) {
            return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'Chat ID is required']);
        }
        
        try {
            if ($isGroup) {
                $success = $this->markGroupMessagesAsRead($userId, $chatId);
            } else {
                $success = $this->markDirectMessagesAsRead($userId, $chatId);
            }
            
            if ($success) {
                return $this->setCorsHeaders()->setJSON([
                    'success' => true,
                    'chat_id' => $chatId,
                    'is_group' => $isGroup
                ]);
            } else {
                return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to mark messages as read']);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception marking messages as read: ' . $e->getMessage());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to mark messages as read: ' . $e->getMessage()]);
        }
    }

    // Update the saveMessage method to better handle reply sender names
    public function saveMessage()
    {
        // Set CORS headers first
        $this->setCorsHeaders();
        
        try {
            // Get the user ID from session
            $userId = session()->get('id');
            
            if (!$userId) {
                log_message('error', 'User not authenticated in saveMessage');
                return $this->response->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
            }
            
            // Log the content type
            $contentType = $this->request->getHeaderLine('Content-Type');
            log_message('debug', 'Content-Type: ' . $contentType);
            
            // Check if this is a JSON request or form data
            if (strpos($contentType, 'application/json') !== false) {
                // Handle JSON request
                $rawInput = file_get_contents('php://input');
                log_message('debug', 'Raw JSON input received: ' . $rawInput);
                
                // Parse JSON data
                $data = json_decode($rawInput, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    log_message('error', 'JSON parse error: ' . json_last_error_msg());
                    return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
                }
            } else {
                // Handle form data
                log_message('debug', 'Processing form data');
                
                // Get post data
                $data = [
                    'sender_id' => $this->request->getPost('sender_id') ?? $userId,
                    'content' => $this->request->getPost('content'),
                    'type' => $this->request->getPost('type') ?? 'text',
                    'is_group' => $this->request->getPost('is_group') == '1' ? 1 : 0,
                    'group_id' => $this->request->getPost('group_id'),
                    'receiver_id' => $this->request->getPost('receiver_id'),
                    'file_url' => $this->request->getPost('file_url'),
                    'reply_to_id' => $this->request->getPost('reply_to_id'),
                    'reply_to_sender_id' => $this->request->getPost('reply_to_sender_id'),
                    'reply_to_content' => $this->request->getPost('reply_to_content'),
                    'reply_to_sender_name' => $this->request->getPost('reply_to_sender_name')
                ];
                
                // Log all POST data for debugging
                log_message('debug', 'POST data: ' . json_encode($_POST));
                
                // Handle file upload if present
                $file = $this->request->getFile('file');
                if ($file && $file->isValid() && !$file->hasMoved()) {
                    // Get original filename and sanitize it
                    $originalName = $file->getClientName();
                    $safeOriginalName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
                    
                    // FIXED: Use getClientName() to keep original filename
                    $newName = $file->getClientName();
                    
                    // Create directory if it doesn't exist
                    $uploadPath = ROOTPATH . 'public/uploads/messages';
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }
                    
                    // Move the file
                    $file->move($uploadPath, $newName);
                    
                    // Store both the system filename and the original filename
                    $data['file_url'] = $newName;
                    $data['original_filename'] = $safeOriginalName;
                    $data['file_size'] = $file->getSize();
                    $data['type'] = $file->getClientMimeType() && strpos($file->getClientMimeType(), 'image/') === 0 ? 'image' : 'file';
                    
                    // Log file upload details for debugging
                    log_message('debug', "File uploaded: {$newName}, Original: {$safeOriginalName}, Size: {$file->getSize()}, Type: {$data['type']}");
                }
            }
            
            // Log the processed data
            log_message('debug', 'Processed message data: ' . json_encode($data));
            
            if (empty($data)) {
                log_message('error', 'No data received in saveMessage');
                return $this->response->setStatusCode(400)->setJSON(['error' => 'No data received']);
            }
            
            // Extract data
            $isGroup = isset($data['is_group']) ? ($data['is_group'] == 1 ? 1 : 0) : 0;
            $groupId = $data['group_id'] ?? null;
            $receiverId = $data['receiver_id'] ?? null;
            $content = $data['content'] ?? '';
            $type = $data['type'] ?? 'text';
            $fileUrl = $data['file_url'] ?? null;
            
            // If this is a reply, process the reply information
            $replyToId = $data['reply_to_id'] ?? null;
            $replyToSenderId = $data['reply_to_sender_id'] ?? null;
            $replyToContent = $data['reply_to_content'] ?? null;
            $replyToSenderName = $data['reply_to_sender_name'] ?? null;

            if ($replyToId) {
                // Make sure content is never "undefined" or "Original message"
                if (empty($replyToContent) || $replyToContent === 'undefined' || $replyToContent === 'Original message') {
                    // Try to get the original message content
                    $originalMessage = $this->messageModel->find($replyToId);
                    if ($originalMessage && !empty($originalMessage['content'])) {
                        // If content has HTML, strip it to get just the text
                        $content = $originalMessage['content'];
                        if (strpos($content, '<') !== false) {
                            $content = strip_tags($content);
                        }
                        $replyToContent = $content;
                    } else {
                        $replyToContent = 'This message';
                    }
                } else {
                    // Clean the reply content
                    $replyToContent = preg_replace('/\d{1,2}:\d{2}/', '', $replyToContent); // Remove timestamps
                    $replyToContent = preg_replace('/\s*Sent\s*/', '', $replyToContent); // Remove "Sent" text
                    $replyToContent = preg_replace('/\s*Seen\s*/', '', $replyToContent); // Remove "Seen" text
                    $replyToContent = preg_replace('/\s*\d{1,2}\/\d{1,2}\/\d{4},\s*/', '', $replyToContent); // Remove dates
                    $replyToContent = preg_replace('/Sean\d*\s*/', '', $replyToContent); // Remove usernames
                    $replyToContent = preg_replace('/Unknown User\s*\|\s*/', '', $replyToContent); // Remove "Unknown User |" text
                    $replyToContent = preg_replace('/\s+/', ' ', $replyToContent); // Clean up extra spaces
                    $replyToContent = trim($replyToContent);
                }
                
                // CRITICAL FIX: Always force a valid sender ID regardless of what was sent
                // Log the original value for debugging
                log_message('debug', 'Original reply_to_sender_id received: ' . ($replyToSenderId ?? 'null'));
                
                // For direct messages, we know it's either the current user or the other user
                if (!$isGroup) {
                    // If we're replying to our own message, use our ID
                    $originalMessage = $this->messageModel->find($replyToId);
                    if ($originalMessage) {
                        // Use the original message's sender ID
                        $replyToSenderId = $originalMessage['sender_id'];
                        log_message('debug', 'Using original message sender ID: ' . $replyToSenderId);
                    } else {
                        // If we can't find the original message, check if the sender name matches the current user
                        if ($replyToSenderName == session()->get('username') || $replyToSenderName == session()->get('nickname')) {
                            $replyToSenderId = $userId;
                            log_message('debug', 'Using current user ID based on name match: ' . $userId);
                        } else {
                            // Otherwise, use the receiver ID
                            $replyToSenderId = $receiverId;
                            log_message('debug', 'Using receiver ID: ' . $receiverId);
                        }
                    }
                } else {
                    // For group messages, try to find the original message
                    $originalMessage = $this->messageModel->find($replyToId);
                    if ($originalMessage) {
                        $replyToSenderId = $originalMessage['sender_id'];
                        log_message('debug', 'Found original message sender ID for group: ' . $originalMessage['sender_id']);
                    } else {
                        // If we can't find the original message, use the current user's ID
                        $replyToSenderId = $userId;
                        log_message('debug', 'Using current user ID as fallback for group: ' . $userId);
                    }
                }
                
                // Ensure it's a valid integer
                $replyToSenderId = (int)$replyToSenderId;
                
                // Final validation - if still invalid, use current user ID
                if (empty($replyToSenderId) || $replyToSenderId <= 0) {
                    $replyToSenderId = (int)$userId;
                    log_message('debug', 'Using current user ID as final fallback: ' . $userId);
                }
                
                log_message('debug', 'Final reply_to_sender_id: ' . $replyToSenderId);
            }
            
            // Log reply data for debugging
            log_message('debug', "Reply data: ID={$replyToId}, SenderID={$replyToSenderId}, SenderName={$replyToSenderName}, Content={$replyToContent}");
            
            // Basic validation
            if (empty($content) && $type === 'text') {
                log_message('error', 'Message content is required');
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Message content is required']);
            }
            
            if ($isGroup && empty($groupId)) {
                log_message('error', 'Group ID is required for group messages');
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Group ID is required for group messages']);
            }
            
            if (!$isGroup && empty($receiverId)) {
                log_message('error', 'Receiver ID is required for direct messages');
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Receiver ID is required for direct messages']);
            }
            
            // Prepare data for insertion
            $messageData = [
                'sender_id' => $userId,
                'content' => $content,
                'type' => $type,
                'file_url' => $fileUrl,
                'is_group' => $isGroup,
                'created_at' => date('Y-m-d H:i:s', time() + (8 * 3600)), // Explicitly add 8 hours for Philippines time
                'is_read' => 0, // Default to unread
                'reply_to_id' => $replyToId,
                'reply_to_sender_id' => $replyToSenderId,
                'reply_to_content' => $replyToContent,
                'reply_to_sender_name' => $replyToSenderName
            ];

            // Set content based on whether it's a file or text message
            if ($type === 'text') {
                $messageData['content'] = $content;
            } else if ($type === 'image' || $type === 'file') {
                // For file messages, store the original filename in content
                if (isset($data['original_filename']) && !empty($data['original_filename'])) {
                    $messageData['content'] = 'File: ' . $data['original_filename'];
                } else if (isset($selectedFile) && isset($selectedFile['name'])) {
                    $messageData['content'] = 'File: ' . $selectedFile['name'];
                } else if (!empty($content)) {
                    $messageData['content'] = $content;
                } else {
                    $messageData['content'] = 'File: Untitled file';
                }
            }

            // Set receiver_id or group_id based on message type
            if ($isGroup) {
                $messageData['group_id'] = $groupId;
                $messageData['receiver_id'] = null; // Explicitly set to null for group messages
            } else {
                $messageData['receiver_id'] = $receiverId; // Make sure receiver_id is set for direct messages
                $messageData['group_id'] = null; // Explicitly set to null for direct messages
            }
            
            // Log the data being inserted
            log_message('debug', 'Inserting message data: ' . json_encode($messageData));
            
            // Check if the messages table has all required columns
            try {
                $db = \Config\Database::connect();
                $fields = $db->getFieldData('messages');
                $fieldNames = array_column($fields, 'name');
                log_message('debug', 'Available fields in messages table: ' . json_encode($fieldNames));
                
                // Remove any fields that don't exist in the table
                foreach (array_keys($messageData) as $field) {
                    if (!in_array($field, $fieldNames)) {
                        log_message('warning', "Field '$field' doesn't exist in messages table, removing it");
                        unset($messageData[$field]);
                    }
                }
            } catch (\Exception $e) {
                log_message('error', 'Error checking table structure: ' . $e->getMessage());
            }
            
            // Insert message
            $messageId = $this->messageModel->insert($messageData);
            
            if (!$messageId) {
                log_message('error', 'Failed to insert message: ' . json_encode($this->messageModel->errors()));
                return $this->response->setStatusCode(500)->setJSON([
                    'error' => 'Failed to save message', 
                    'details' => $this->messageModel->errors()
                ]);
            }
            
            // Get user info for the response
            $user = $this->userModel->find($userId);
            
            $userData = [
                'username' => $user['username'],
                'nickname' => $user['nickname'] ?? $user['username'],
                'avatar' => $user['avatar'] ?? 'default-avatar.png',
            ];
            
            // Return message data for the frontend
            $message = [
                'id' => $messageId,
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s'),
                'type' => $type,
                'is_group' => $isGroup,
                'group_id' => $groupId ?? null,
                'file_url' => $fileUrl ?? null,
                'original_filename' => $data['original_filename'] ?? null,
                'file_size' => $data['file_size'] ?? null,
                'avatar' => $userData['avatar'] ?? 'default-avatar.png',
                'username' => $userData['username'] ?? 'User',
                'nickname' => $userData['nickname'] ?? null,
                'status' => 'sent',
                'reply_to_id' => $replyToId,
                'reply_to_sender_id' => $replyToSenderId,
                'reply_to_content' => $replyToContent,
                'reply_to_sender_name' => $replyToSenderName
            ];
            
            log_message('debug', 'Message saved successfully with ID: ' . $messageId);
            
            // Try to send the message to the socket server
            $this->sendMessageToSocketServer($message);
            
            return $this->response->setJSON($message);
            
        } catch (\Exception $e) {
            log_message('error', 'Exception in saveMessage: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to save message: ' . $e->getMessage()]);
        }
    }

    // Add a file upload method
    public function uploadFile()
    {
        $currentUserId = session()->get('id');
        
        if (!$currentUserId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            $file = $this->request->getFile('file');
            
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'Invalid file upload']);
            }
            
            // Get original filename and sanitize it
            $originalName = $file->getClientName();
            $safeOriginalName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
            
            // FIXED: Use getClientName() to keep original filename
            $newName = $file->getClientName();
            
            // Create directory if it doesn't exist
            $uploadPath = ROOTPATH . 'public/uploads/messages';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            $file->move($uploadPath, $newName);
            
            return $this->setCorsHeaders()->setJSON([
                'success' => true,
                'fileUrl' => '/uploads/messages/' . $newName,
                'fileName' => $originalName,
                'fileSize' => $file->getSize(),
                'fileType' => $file->getClientMimeType()
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Exception in uploadFile: ' . $e->getMessage());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to upload file: ' . $e->getMessage()]);
        }
    }

    // Add a new method to send messages to the socket server
    private function sendMessageToSocketServer($message)
    {
        try {
            // Socket server URL
            $socketServerUrl = 'http://localhost:3001/emit-message';
            
            // Prepare message data for socket server
            $socketData = [
                'event' => 'new_message',
                'data' => [
                    'id' => $message['id'],
                    'sender_id' => $message['sender_id'],
                    'receiver_id' => $message['receiver_id'] ?? null,
                    'group_id' => $message['group_id'] ?? null,
                    'is_group' => $message['is_group'],
                    'content' => $message['content'],
                    'type' => $message['type'],
                    'file_url' => $message['file_url'],
                    'created_at' => $message['created_at'],
                    'username' => $message['username'],
                    'nickname' => $message['nickname'] ?? $message['username'],
                    'avatar' => $message['avatar'],
                    'reply_to_id' => $message['reply_to_id'] ?? null,
                    'reply_to_sender_id' => $message['reply_to_sender_id'] ?? null,
                    'reply_to_content' => $message['reply_to_content'] ?? null,
                    'reply_to_sender_name' => $message['reply_to_sender_name'] ?? null
                ]
            ];
            
            // Log the data being sent to socket server
            log_message('debug', 'Sending message to socket server: ' . json_encode($socketData));
            
            // Send HTTP request to socket server
            $client = \Config\Services::curlrequest();
            $response = $client->request('POST', $socketServerUrl, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $socketData,
                'timeout' => 3, // Short timeout to avoid blocking
            ]);
            
            // Log the response for debugging
            log_message('debug', 'Socket server response: ' . $response->getBody());
            
        } catch (\Exception $e) {
            // Log error but don't fail the request
            log_message('error', 'Failed to send message to socket server: ' . $e->getMessage());
        }
    }

    // Find the getGroupMessages method in ApiController.php and update it to properly fetch all messages

    public function getGroupMessages($groupId)
    {
        $userId = session()->get('id');
        
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            // Check if user is member of the group
            $isMember = $this->groupMemberModel
                ->where('group_id', $groupId)
                ->where('user_id', $userId)
                ->first();
            
            if (!$isMember) {
                return $this->setCorsHeaders()->setStatusCode(403)->setJSON(['error' => 'You are not a member of this group']);
            }
            
            // Get group messages - MODIFIED to ensure we get ALL messages
            $messages = $this->messageModel
                ->select('messages.*, users.username, users.nickname, users.avatar')
                ->join('users', 'users.id = messages.sender_id')
                ->where('messages.group_id', $groupId)
                ->where('messages.is_group', 1)
                ->orderBy('messages.created_at', 'ASC')
                ->findAll();
            
            // Log the query and results for debugging
            log_message('debug', 'Group messages query: ' . $this->messageModel->getLastQuery()->getQuery());
            log_message('debug', 'Found ' . count($messages) . ' messages for group ' . $groupId);
            
            // Mark messages as read
            $this->markGroupMessagesAsRead($userId, $groupId);
            
            return $this->setCorsHeaders()->setJSON($messages);
        } catch (\Exception $e) {
            log_message('error', 'Exception getting group messages: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to load messages: ' . $e->getMessage()]);
        }
    }

    // Update the getUsers method to include last message info and unread counts
    public function getUsers()
    {
        $currentUserId = session()->get('id');
        
        if (!$currentUserId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            // Get all users except current user
            $users = $this->userModel
                ->where('id !=', $currentUserId)
                ->findAll();
            
            // Add last message and unread count for each user
            foreach ($users as &$user) {
                // Get last message between current user and this user
                $lastMessage = $this->messageModel
                    ->select('messages.*')
                    ->where('messages.is_group', 0)
                    ->groupStart()
                        ->groupStart()
                            ->where('messages.sender_id', $currentUserId)
                            ->where('messages.receiver_id', $user['id'])
                        ->groupEnd()
                        ->orGroupStart()
                            ->where('messages.sender_id', $user['id'])
                            ->where('messages.receiver_id', $currentUserId)
                        ->groupEnd()
                    ->groupEnd()
                    ->orderBy('messages.created_at', 'DESC')
                    ->first();
                
                if ($lastMessage) {
                    $user['last_message'] = $lastMessage;
                }
                
                // Get unread message count for this user - handle missing is_read column
                try {
                    $unreadCount = $this->messageModel
                        ->where('sender_id', $user['id'])
                        ->where('receiver_id', $currentUserId)
                        ->where('is_group', 0)
                        ->where('is_read', 0)
                        ->countAllResults();
                    
                    $user['unread_count'] = $unreadCount;
                } catch (\Exception $e) {
                    // If is_read column doesn't exist, set unread count to 0
                    $user['unread_count'] = 0;
                    log_message('error', 'Error getting unread count: ' . $e->getMessage());
                }
                
                // Set online status (placeholder - in a real app, this would be determined by socket server)
                $user['online'] = false;
            }
            
            // Log the users data for debugging
            log_message('debug', 'Users data: ' . json_encode($users));
            
            return $this->setCorsHeaders()->setJSON($users);
        } catch (\Exception $e) {
            log_message('error', 'Exception in getUsers: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to load users: ' . $e->getMessage()]);
        }
    }

    // Update the getUserGroups method to include last message info and unread counts
    public function getUserGroups()
    {
        $currentUserId = session()->get('id');
        
        if (!$currentUserId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            // Get groups the user is a member of
            $groups = $this->groupModel
                ->select('groups.*, COUNT(group_members.id) as member_count')
                ->join('group_members', 'groups.id = group_members.group_id')
                ->where('group_members.user_id', $currentUserId)
                ->groupBy('groups.id')
                ->findAll();
            
            // Add last message and unread count for each group
            foreach ($groups as &$group) {
                // Get last message in this group
                $lastMessage = $this->messageModel
                    ->select('messages.*, users.username, users.nickname')
                    ->join('users', 'users.id = messages.sender_id')
                    ->where('messages.group_id', $group['id'])
                    ->where('messages.is_group', 1)
                    ->orderBy('messages.created_at', 'DESC')
                    ->first();
                
                if ($lastMessage) {
                    // Format the content to include sender name
                    if ($lastMessage['sender_id'] != $currentUserId) {
                        $senderName = $lastMessage['nickname'] ?? $lastMessage['username'];
                        $lastMessage['content'] = $senderName . ': ' . $lastMessage['content'];
                    }
                    $group['last_message'] = $lastMessage;
                }
                
                // Get unread message count for this group - handle missing is_read column
                try {
                    $unreadCount = $this->messageModel
                        ->where('group_id', $group['id'])
                        ->where('is_group', 1)
                        ->where('sender_id !=', $currentUserId)
                        ->where('is_read', 0)
                        ->countAllResults();
                    
                    $group['unread_count'] = $unreadCount;
                } catch (\Exception $e) {
                    // If is_read column doesn't exist, set unread count to 0
                    $group['unread_count'] = 0;
                    log_message('error', 'Error getting unread count: ' . $e->getMessage());
                }
            }
            
            // Log the groups data for debugging
            log_message('debug', 'Groups data: ' . json_encode($groups));
            
            return $this->setCorsHeaders()->setJSON($groups);
        } catch (\Exception $e) {
            log_message('error', 'Exception in getUserGroups: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to load groups: ' . $e->getMessage()]);
        }
    }

    // Add this new method to update user status
    public function updateUserStatus()
    {
        $userId = session()->get('id');
        
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        // Get request data
        $json = $this->request->getJSON(true);
        if (empty($json)) {
            return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'Invalid request data']);
        }
        
        $status = $json['status'] ?? null;
        
        if (!$status) {
            return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'Status is required']);
        }
        
        // Validate status value
        $validStatuses = ['online', 'away', 'busy', 'offline'];
        if (!in_array($status, $validStatuses)) {
            return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'Invalid status value']);
        }
        
        try {
            // CRITICAL FIX: Use direct SQL query to ensure we're only updating the current user's status
            $db = \Config\Database::connect();
            
            // Prepare the SQL query with explicit WHERE clause
            $sql = "UPDATE users SET status = ? WHERE id = ?";
            $params = [$status, $userId];
            
            // Execute the query
            $result = $db->query($sql, $params);
            
            // Log the SQL query for debugging
            log_message('debug', "Executed SQL: UPDATE users SET status = '$status' WHERE id = $userId");
            log_message('debug', "Affected rows: " . $db->affectedRows());
            
            // Update session
            session()->set('status', $status);
            
            // Log the status update for debugging
            log_message('debug', "Updated status for user $userId to $status");
            
            // Emit socket event if socket server is available
            $socketResult = $this->emitStatusChangeEvent($userId, $status);
            
            return $this->setCorsHeaders()->setJSON([
                'success' => true,
                'status' => $status,
                'user_id' => $userId, // Include user ID in response for clarity
                'socket_event_sent' => $socketResult,
                'affected_rows' => $db->affectedRows() // Add affected rows count for debugging
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Exception updating user status: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to update status: ' . $e->getMessage()]);
        }
    }

    // Helper method to emit status change event
    private function emitStatusChangeEvent($userId, $status)
    {
        try {
            // Socket server URL
            $socketServerUrl = 'http://localhost:3001/emit-message';
            
            // Ensure userId is an integer
            $userId = (int)$userId;
            
            // Prepare data for socket server
            $socketData = [
                'event' => 'status_change',
                'data' => [
                    'user_id' => $userId, // Ensure it's an integer
                    'status' => $status,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            
            // Log the data being sent to socket server
            log_message('debug', 'Sending status_change event to socket server: ' . json_encode($socketData));
            
            // Send HTTP request to socket server
            $client = \Config\Services::curlrequest();
            $response = $client->request('POST', $socketServerUrl, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $socketData,
                'timeout' => 3, // Short timeout to avoid blocking
            ]);
            
            // Log the response for debugging
            log_message('debug', 'Socket server response: ' . $response->getBody());
            
            return true;
        } catch (\Exception $e) {
            // Log error but don't fail the request
            log_message('error', 'Failed to send status_change event to socket server: ' . $e->getMessage());
            return false;
        }
    }

    // Add this new method to the ApiController class
    public function getAllUserStatuses()
    {
        $currentUserId = session()->get('id');
        
        if (!$currentUserId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            // Get all users with their statuses
            $users = $this->userModel
                ->select('id, username, nickname, status')
                ->findAll();
            
            // Format the response
            $statuses = [];
            foreach ($users as $user) {
                $statuses[$user['id']] = $user['status'] ?? 'offline';
            }
            
            return $this->setCorsHeaders()->setJSON([
                'success' => true,
                'statuses' => $statuses
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Exception getting user statuses: ' . $e->getMessage());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to load user statuses: ' . $e->getMessage()]);
        }
    }

    public function addGroupMember($groupId)
    {
        $currentUserId = session()->get('id');
        
        if (!$currentUserId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        $userId = $this->request->getPost('user_id');
        
        // Check if current user is admin
        $isAdmin = $this->groupMemberModel
            ->where('group_id', $groupId)
            ->where('user_id', $currentUserId)
            ->where('is_admin', 1)
            ->first();
        
        if (!$isAdmin) {
            return $this->setCorsHeaders()->setStatusCode(403)->setJSON(['error' => 'Only admins can add members']);
        }
        
        // Check if user is already a member
        $isMember = $this->groupMemberModel
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();
        
        if ($isMember) {
            return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'User is already a member']);
        }
        
        // Add user to group
        $this->groupMemberModel->insert([
            'group_id' => $groupId,
            'user_id' => $userId,
            'is_admin' => 0,
            'joined_at' => date('Y-m-d H:i:s')
        ]);
        
        // Get user details
        $user = $this->userModel->find($userId);
        
        return $this->setCorsHeaders()->setJSON([
            'success' => true,
            'user' => $user
        ]);
    }

    public function removeGroupMember($groupId)
    {
        $currentUserId = session()->get('id');
        
        if (!$currentUserId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        $userId = $this->request->getPost('user_id');
        
        // Check if current user is admin
        $isAdmin = $this->groupMemberModel
            ->where('group_id', $groupId)
            ->where('user_id', $currentUserId)
            ->where('is_admin', 1)
            ->first();
        
        if (!$isAdmin) {
            return $this->setCorsHeaders()->setStatusCode(403)->setJSON(['error' => 'Only admins can remove members']);
        }
        
        // Check if user is a member
        $member = $this->groupMemberModel
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();
        
        if (!$member) {
            return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'User is not a member']);
        }
        
        // Check if user is the only admin
        if ($member['is_admin']) {
            $adminCount = $this->groupMemberModel
                ->where('group_id', $groupId)
                ->where('is_admin', 1)
                ->countAllResults();
            
            if ($adminCount <= 1) {
                return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'Cannot remove the only admin']);
            }
        }
        
        // Remove user from group
        $this->groupMemberModel
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->delete();
        
        return $this->setCorsHeaders()->setJSON([
            'success' => true,
            'user_id' => $userId
        ]);
    }

    // Add or update the leaveGroup method in ApiController.php
    public function leaveGroup($groupId)
    {
        $currentUserId = session()->get('id');
        
        if (!$currentUserId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            // Check if user is a member
            $member = $this->groupMemberModel
                ->where('group_id', $groupId)
                ->where('user_id', $currentUserId)
                ->first();
            
            if (!$member) {
                return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'You are not a member of this group']);
            }
            
            // Check if user is the only admin
            if ($member['is_admin']) {
                $adminCount = $this->groupMemberModel
                    ->where('group_id', $groupId)
                    ->where('is_admin', 1)
                    ->countAllResults();
                
                if ($adminCount <= 1) {
                    // Make another member admin
                    $otherMember = $this->groupMemberModel
                        ->where('group_id', $groupId)
                        ->where('user_id !=', $currentUserId)
                        ->first();
                    
                    if ($otherMember) {
                        $this->groupMemberModel->update($otherMember['id'], [
                            'is_admin' => 1
                        ]);
                        
                        // Log the action
                        log_message('info', "User {$currentUserId} left group {$groupId}. User {$otherMember['user_id']} was promoted to admin.");
                    } else {
                        // Delete group if no other members
                        $this->groupModel->delete($groupId);
                        
                        // Log the action
                        log_message('info', "User {$currentUserId} left group {$groupId}. Group was deleted as there were no other members.");
                        
                        return $this->setCorsHeaders()->setJSON([
                            'success' => true,
                            'message' => 'You left the group. The group was deleted as you were the only member.',
                            'group_deleted' => true
                        ]);
                    }
                }
            }
            
            // Remove user from group
            $this->groupMemberModel
                ->where('group_id', $groupId)
                ->where('user_id', $currentUserId)
                ->delete();
            
            // Log the action
            log_message('info', "User {$currentUserId} left group {$groupId}");
            
            // Emit socket event if socket server is available
            $this->emitGroupMemberLeftEvent($groupId, $currentUserId);
            
            return $this->setCorsHeaders()->setJSON([
                'success' => true,
                'message' => 'You have successfully left the group'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Exception in leaveGroup: ' . $e->getMessage());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON([
                'error' => 'Failed to leave group: ' . $e->getMessage()
            ]);
        }
    }

    // Add this method to emit a socket event when a user leaves a group
    private function emitGroupMemberLeftEvent($groupId, $userId)
    {
        try {
            // Socket server URL
            $socketServerUrl = 'http://localhost:3001/emit-message';
            
            // Get user info
            $user = $this->userModel->find($userId);
            $username = $user ? ($user['nickname'] ?? $user['username']) : 'User';
            
            // Prepare data for socket server
            $socketData = [
                'event' => 'group_member_left',
                'data' => [
                    'group_id' => $groupId,
                    'user_id' => $userId,
                    'username' => $username,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            
            // Send HTTP request to socket server
            $client = \Config\Services::curlrequest();
            $response = $client->request('POST', $socketServerUrl, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $socketData,
                'timeout' => 3 // Short timeout to avoid blocking
            ]);
            
            log_message('debug', 'Group member left event sent to socket server');
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Failed to send group_member_left event: ' . $e->getMessage());
            return false;
        }
    }


    public function searchUsers()
    {
        // Check if user is logged in
        if (!session()->has('id')) {
            return $this->failUnauthorized('You must be logged in to search users');
        }

        $query = $this->request->getGet('q');
        if (empty($query)) {
            return $this->respond(['users' => []], 200);
        }

        $userModel = new UserModel();
        $users = $userModel->like('username', $query)
                        ->orLike('nickname', $query)
                        ->where('id !=', session()->get('id'))
                        ->select('id, username, nickname, avatar, status')
                        ->findAll(10);

        return $this->respond(['users' => $users], 200);
    }

    // Add this test method at the end of the class, before the closing brace
    public function testEndpoint()
    {
        log_message('debug', 'Test endpoint called');
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'API is working',
            'time' => date('Y-m-d H:i:s'),
            'session_user_id' => session()->get('id')
        ]);
    }

    public function getUsersList()
    {
        $userModel = new UserModel();
        $currentUserId = session()->get('id');
        
        // Get all users except the current user
        $users = $userModel->select('users.id, users.username, users.nickname, users.avatar, users.status, users.last_active')
                        ->where('users.id !=', $currentUserId)
                        ->findAll();
        
        // Process users to add additional information
        foreach ($users as &$user) {
            // Determine if user is online based on last_active
            $lastActive = new DateTime($user['last_active']);
            $now = new DateTime();
            $interval = $now->diff($lastActive);
            $minutesSinceActive = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
            
            // If status is set to offline, respect that
            // Otherwise, determine online status based on activity
            if ($user['status'] !== 'offline') {
                $user['online'] = $minutesSinceActive < 5; // Consider online if active in last 5 minutes
            } else {
                $user['online'] = false;
            }
            
            // Get last message between current user and this user
            $user['last_message'] = $this->getLastMessageBetweenUsers($currentUserId, $user['id']);
            
            // Get unread count
            $user['unread_count'] = $this->getUnreadMessageCount($currentUserId, $user['id']);
        }
        
        return $this->response->setJSON($users);
    }

    private function getLastMessageBetweenUsers($currentUserId, $otherUserId)
        {
            return $this->messageModel
                ->select('messages.*')
                ->where('messages.is_group', 0)
                ->groupStart()
                    ->groupStart()
                        ->where('messages.sender_id', $currentUserId)
                        ->where('messages.receiver_id', $otherUserId)
                    ->groupEnd()
                    ->orGroupStart()
                        ->where('messages.sender_id', $otherUserId)
                        ->where('messages.receiver_id', $currentUserId)
                    ->groupEnd()
                ->groupEnd()
                ->orderBy('messages.created_at', 'DESC')
                ->first();
        }

        private function getUnreadMessageCount($currentUserId, $otherUserId)
        {
            try {
                return $this->messageModel
                    ->where('sender_id', $otherUserId)
                    ->where('receiver_id', $currentUserId)
                    ->where('is_group', 0)
                    ->where('is_read', 0)
                    ->countAllResults();
            } catch (\Exception $e) {
                // If is_read column doesn't exist, return 0
                log_message('error', 'Error getting unread count: ' . $e->getMessage());
                return 0;
            }
        }

    // Add this method to check if the message_status table exists
    public function checkMessageStatusTable()
    {
        try {
            $db = \Config\Database::connect();
            
            // Check if the message_status table exists
            $tables = $db->listTables();
            $tableExists = in_array('message_status', $tables);
            
            // Check if the messages table has an is_read column
            $messagesColumns = [];
            try {
                $fields = $db->getFieldData('messages');
                $messagesColumns = array_column($fields, 'name');
            } catch (\Exception $e) {
                log_message('error', 'Error getting messages table fields: ' . $e->getMessage());
            }
            
            $hasIsReadColumn = in_array('is_read', $messagesColumns);
            
            return $this->response->setJSON([
                'success' => true,
                'message_status_table_exists' => $tableExists,
                'messages_has_is_read_column' => $hasIsReadColumn,
                'tables' => $tables,
                'messages_columns' => $messagesColumns
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error checking message_status table: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Failed to check database structure: ' . $e->getMessage()
            ]);
        }
    }

        /**
        * Mark a batch of messages as seen
        * This is a new optimized endpoint for batch processing
        */
        public function markMessagesBatchAsSeen()
        {
            // Get request data
            $data = $this->request->getJSON(true);
            
            if (empty($data['message_ids']) || !is_array($data['message_ids']) || 
                empty($data['user_id']) || empty($data['other_user_id'])) {
                return $this->respond([
                    'success' => false,
                    'error' => 'Invalid request data'
                ]);
            }
            
            $messageIds = $data['message_ids'];
            $userId = $data['user_id'];
            $otherUserId = $data['other_user_id'];
            $isGroup = !empty($data['is_group']) ? $data['is_group'] : false;
            
            // Initialize models
            $messageModel = new MessageModel();
            $messageStatusModel = new MessageStatusModel();
            
            // Process each message in the batch
            $processedIds = [];
            $socketEventSent = false;
            
            foreach ($messageIds as $messageId) {
                // Check if message exists and belongs to the other user
                $message = $messageModel->find($messageId);
                
                if (!$message) {
                    continue;
                }
                
                // For direct messages, check if the message is from the other user to the current user
                if (!$isGroup && $message['sender_id'] != $otherUserId) {
                    continue;
                }
                
                // For group messages, check if the message is in the specified group
                if ($isGroup && $message['group_id'] != $otherUserId) {
                    continue;
                }
                
                // Check if status already exists
                $existingStatus = $messageStatusModel->where([
                    'message_id' => $messageId,
                    'user_id' => $userId
                ])->first();
                
                if ($existingStatus) {
                    // Status already exists, update it
                    $messageStatusModel->update($existingStatus['id'], [
                        'is_read' => 1,
                        'read_at' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    // Create new status
                    $messageStatusModel->insert([
                        'message_id' => $messageId,
                        'user_id' => $userId,
                        'is_read' => 1,
                        'read_at' => date('Y-m-d H:i:s')
                    ]);
                }
                
                // Update the message's is_read field
                $messageModel->update($messageId, ['is_read' => 1]);
                
                // Add to processed IDs
                $processedIds[] = $messageId;
            }
            
            // Emit socket event for the batch (only once)
            if (!empty($processedIds)) {
                $this->emitMessagesBatchSeenEvent($processedIds, $userId, $otherUserId, $isGroup);
                $socketEventSent = true;
            }
            
            return $this->respond([
                'success' => true,
                'message_ids' => $processedIds,
                'user_id' => $userId,
                'other_user_id' => $otherUserId,
                'socket_event_sent' => $socketEventSent
            ]);
        }
        /**
        * Emit socket event for batch seen messages
        */
        private function emitMessagesBatchSeenEvent($messageIds, $userId, $otherUserId, $isGroup = false)
        {
            // Prepare event data
            $eventData = [
                'message_ids' => $messageIds,
                'user_id' => $userId,
                'other_user_id' => $otherUserId,
                'is_group' => $isGroup,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Get socket server URL from config
            $socketUrl = getenv('SOCKET_SERVER_URL') ?: 'http://localhost:3001';
            
            // Emit event to socket server
            try {
                $client = \Config\Services::curlrequest();
                $response = $client->request('POST', $socketUrl . '/emit', [
                    'json' => [
                        'event' => 'messages_batch_seen',
                        'data' => $eventData
                    ],
                    'timeout' => 1, // Short timeout to prevent blocking
                    'connect_timeout' => 1
                ]);
                
                return true;
            } catch (\Exception $e) {
                log_message('error', 'Failed to emit socket event: ' . $e->getMessage());
                return false;
            }
        }

    // Add this new method to the ApiController class
    public function getMessageSeenUsers($messageId)
    {
        $userId = session()->get('id');
        
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            // If this is a test call (test1, test2, etc), return fake data
            if ($messageId === 'test' || preg_match('/test\d+/', $messageId)) {
                return $this->setCorsHeaders()->setJSON([
                    'message_id' => $messageId,
                    'seen_users' => [],
                    'seen_count' => 0,
                    'note' => 'Test endpoint response'
                ]);
            }
            
            // Get the message to verify it exists
            $message = $this->messageModel->find($messageId);
            
            if (!$message) {
                return $this->setCorsHeaders()->setJSON([
                    'message_id' => $messageId,
                    'seen_users' => [],
                    'seen_count' => 0,
                    'error' => 'Message not found'
                ]);
            }
            
            // Check if message_status table exists
            $db = \Config\Database::connect();
            $tables = $db->listTables();
            
            if (!in_array('message_status', $tables)) {
                // If table doesn't exist, return empty array instead of error
                return $this->setCorsHeaders()->setJSON([
                    'message_id' => $messageId,
                    'seen_users' => [],
                    'seen_count' => 0,
                    'note' => 'message_status table does not exist'
                ]);
            }
            
            // Get users who have seen the message
            $seenUsers = $this->messageStatusModel->getMessageSeenUsers($messageId);
            
            // Format the response
            $formattedUsers = [];
            foreach ($seenUsers as $user) {
                $formattedUsers[] = [
                    'id' => $user['user_id'],
                    'username' => $user['username'],
                    'nickname' => $user['nickname'] ?? $user['username'],
                    'avatar' => $user['avatar'] ?? 'default-avatar.png',
                    'seen_at' => $user['seen_at'] ?? $user['updated_at']
                ];
            }
            
            return $this->setCorsHeaders()->setJSON([
                'message_id' => $messageId,
                'seen_users' => $formattedUsers,
                'seen_count' => count($formattedUsers)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Exception getting message seen users: ' . $e->getMessage());
            // Return empty array instead of error
            return $this->setCorsHeaders()->setJSON([
                'message_id' => $messageId,
                'seen_users' => [],
                'seen_count' => 0,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Replace the markMessageAsSeen method with this fixed version that properly handles group messages

    public function markMessageAsSeen()
    {
        $userId = session()->get('id');
        
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        // Get request data
        $json = $this->request->getJSON(true);
        if (empty($json)) {
            return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'Invalid request data']);
        }
        
        $messageId = $json['message_id'] ?? null;
        $otherUserId = $json['other_user_id'] ?? null;
        $isGroup = isset($json['is_group']) ? (bool)$json['is_group'] : false;
        
        if (!$messageId) {
            return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'Message ID is required']);
        }
        
        try {
            // Log the request for debugging
            log_message('debug', "Marking message $messageId as seen by user $userId (is_group: " . ($isGroup ? 'true' : 'false') . ")");
            
            // Check if message exists
            $message = $this->messageModel->find($messageId);
            if (!$message) {
                log_message('error', "Message $messageId not found");
                return $this->setCorsHeaders()->setStatusCode(404)->setJSON(['error' => 'Message not found']);
            }
            
            // 1. Update is_read in messages table
            $this->messageModel->update($messageId, [
                'is_read' => 1
            ]);
            log_message('debug', "Updated is_read column for message $messageId");
            
            // 2. Insert or update in message_status table with seen_at timestamp
            $now = date('Y-m-d H:i:s');
            
            // Check if message_status table exists
            $db = \Config\Database::connect();
            $tables = $db->listTables();
            
            if (in_array('message_status', $tables)) {
                try {
                    // Check if entry already exists
                    $existingStatus = $this->messageStatusModel
                        ->where('message_id', $messageId)
                        ->where('user_id', $userId)
                        ->first();
                        
                    if ($existingStatus) {
                        // Update existing status
                        $this->messageStatusModel->update($existingStatus['id'], [
                            'status' => 'seen',
                            'updated_at' => $now,
                            'seen_at' => $now
                        ]);
                        log_message('debug', "Updated existing message status for message $messageId");
                    } else {
                        // Create new status
                        $statusData = [
                            'message_id' => $messageId,
                            'user_id' => $userId,
                            'status' => 'seen',
                            'created_at' => $now,
                            'updated_at' => $now,
                            'seen_at' => $now
                        ];
                        
                        // Insert directly into the table
                        $result = $db->table('message_status')->insert($statusData);
                        log_message('debug', "Created new message status for message $messageId. Insert result: " . ($result ? 'success' : 'failed'));
                        
                        if (!$result) {
                            log_message('error', "Failed to insert into message_status table. Last error: " . $db->error()['message']);
                        }
                    }
                } catch (\Exception $e) {
                    log_message('error', "Error with message_status table: " . $e->getMessage());
                    log_message('error', "Stack trace: " . $e->getTraceAsString());
                }
            } else {
                log_message('warning', "message_status table does not exist");
            }
            
            // Get user info for the response
            $user = $this->userModel->find($userId);
            
            // Emit socket event
            $socketResult = $this->emitMessageSeenEvent($messageId, $userId, $otherUserId, $isGroup, $user['username'] ?? 'User');
            
            // Get all users who have seen this message (if table exists)
            $seenUsers = [];
            if (in_array('message_status', $tables)) {
                $seenUsers = $this->messageStatusModel->getMessageSeenUsers($messageId);
            }
            
            return $this->setCorsHeaders()->setJSON([
                'success' => true,
                'message_id' => $messageId,
                'user_id' => $userId,
                'other_user_id' => $otherUserId,
                'is_group' => $isGroup,
                'socket_event_sent' => $socketResult,
                'is_read' => 1,
                'seen_users' => $seenUsers,
                'seen_count' => count($seenUsers),
                'message_status_table_exists' => in_array('message_status', $tables)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Exception marking message as seen: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to mark message as seen: ' . $e->getMessage()]);
        }
    }

    // Enhanced emitMessageSeenEvent method with more user information
    private function emitMessageSeenEvent($messageId, $userId, $otherUserId, $isGroup, $username)
    {
        try {
            // Socket server URL
            $socketServerUrl = 'http://localhost:3001/emit-message';
            
            // Get more user information to include in the event
            $userModel = new \App\Models\UserModel();
            $user = $userModel->find($userId);
            
            // Prepare user info
            $userInfo = [
                'id' => $userId,
                'username' => $user['username'] ?? $username,
                'nickname' => $user['nickname'] ?? $user['username'] ?? $username,
                'avatar' => $user['avatar'] ?? 'default-avatar.png'
            ];
            
            // Prepare data for socket server
            $socketData = [
                'event' => 'message_seen',
                'data' => [
                    'message_id' => $messageId,
                    'user_id' => $userId,
                    'other_user_id' => $otherUserId,
                    'is_group' => $isGroup,
                    'username' => $username,
                    'user_info' => $userInfo,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            
            // Log the data being sent to socket server
            log_message('debug', 'Sending enhanced message_seen event to socket server: ' . json_encode($socketData));
            
            // Send HTTP request to socket server
            $client = \Config\Services::curlrequest();
            $response = $client->request('POST', $socketServerUrl, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $socketData,
                'timeout' => 5
            ]);
            
            // Log the response for debugging
            log_message('debug', 'Socket server response: ' . $response->getBody());
            
            return true;
        } catch (\Exception $e) {
            // Log error but don't fail the request
            log_message('error', 'Failed to send message_seen event to socket server: ' . $e->getMessage());
            return false;
        }
    }

    public function getGroupMembers($groupId)
    {
        $userId = session()->get('id');
        
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            // Check if user is member of the group
            $isMember = $this->groupMemberModel
                ->where('group_id', $groupId)
                ->where('user_id', $userId)
                ->first();
            
            if (!$isMember) {
                return $this->setCorsHeaders()->setStatusCode(403)->setJSON(['error' => 'You are not a member of this group']);
            }
            
            // Get group members with user details
            $members = $this->userModel
                ->select('users.id, users.username, users.nickname, users.avatar, users.status, group_members.is_admin')
                ->join('group_members', 'users.id = group_members.user_id')
                ->where('group_members.group_id', $groupId)
                ->findAll();
            
            // Remove sensitive data and set defaults
            foreach ($members as &$member) {
                unset($member['password']);
                // Set default values for null fields
                $member['nickname'] = $member['nickname'] ?? $member['username'];
                $member['avatar'] = $member['avatar'] ?? 'default-avatar.png';
                $member['status'] = $member['status'] ?? 'offline';
            }
            
            // Log the members for debugging
            log_message('debug', 'Group members: ' . json_encode($members));
            
            return $this->setCorsHeaders()->setJSON($members);
        } catch (\Exception $e) {
            log_message('error', 'Exception getting group members: ' . $e->getMessage());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to load group members: ' . $e->getMessage()]);
        }
    }

    public function getNonGroupMembers($groupId)
    {
        $currentUserId = session()->get('id');
        
        if (!$currentUserId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        // Get IDs of users who are already members
        $memberIds = $this->groupMemberModel
            ->select('user_id')
            ->where('group_id', $groupId)
            ->findAll();
        
        $memberIdArray = array_column($memberIds, 'user_id');
        
        // Get users who are not members
        $nonMembers = $this->userModel
            ->whereNotIn('id', $memberIdArray)
            ->where('id !=', $currentUserId)
            ->findAll();
        
        return $this->setCorsHeaders()->setJSON($nonMembers);
    }

    // Function to update group information
    public function updateGroup($groupId)
    {
        $currentUserId = session()->get('id');
        
        if (!$currentUserId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        // Check if user is admin
        $isAdmin = $this->groupMemberModel
            ->where('group_id', $groupId)
            ->where('user_id', $currentUserId)
            ->where('is_admin', 1)
            ->first();
        
        if (!$isAdmin) {
            return $this->setCorsHeaders()->setStatusCode(403)->setJSON(['error' => 'Only admins can update the group']);
        }
        
        // Get request data
        $name = $this->request->getPost('name');
        $description = $this->request->getPost('description');
        
        // Handle image upload
        $image = null;
        $file = $this->request->getFile('image');
        
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            
            // Create directory if it doesn't exist
            $uploadPath = ROOTPATH . 'public/uploads/groups';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            $file->move($uploadPath, $newName);
            $image = $newName;
        }
        
        // Update group data
        $groupData = [
            'name' => $name,
            'description' => $description
        ];
        
        if ($image) {
            $groupData['image'] = $image;
        }
        
        $this->groupModel->update($groupId, $groupData);
        
        // Get updated group
        $group = $this->groupModel->find($groupId);
        
        return $this->setCorsHeaders()->setJSON($group);
    }

    // Add this test method for the seen avatars to check
    public function testMessageSeenUsers()
    {
        // Return a simple success response for testing
        return $this->setCorsHeaders()->setJSON([
            'success' => true,
            'message_id' => 'test',
            'seen_users' => [],
            'seen_count' => 0,
            'note' => 'Test endpoint for seen avatars'
        ]);
    }

    // Add this method to create the message_status table if it doesn't exist

    public function createMessageStatusTable()
    {
        try {
            $db = \Config\Database::connect();
            
            // Check if the table already exists
            $tables = $db->listTables();
            if (in_array('message_status', $tables)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'message_status table already exists'
                ]);
            }
            
            // Create the table
            $db->query("
                CREATE TABLE IF NOT EXISTS `message_status` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `message_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `status` enum('sent','delivered','seen') NOT NULL DEFAULT 'sent',
                    `seen_at` datetime DEFAULT NULL,
                    `created_at` datetime DEFAULT NULL,
                    `updated_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `message_id` (`message_id`),
                    KEY `user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'message_status table created successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error creating message_status table: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'Failed to create message_status table: ' . $e->getMessage()
            ]);
        }
    }

    // Add a new method to get group information
    public function getGroupInfo($groupId)
    {
        // Set CORS headers
        $this->setCorsHeaders();
        
        try {
            // Get the group
            $group = $this->groupModel->find($groupId);
            
            if (!$group) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Group not found']);
            }
            
            // Format the response
            $response = [
                'id' => $group['id'],
                'name' => $group['name'],
                'description' => $group['description'],
                'image' => $group['image'],
                'created_at' => $group['created_at'],
                'updated_at' => $group['updated_at']
            ];
            
            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            log_message('error', 'Exception getting group info: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to get group info: ' . $e->getMessage()]);
        }
    }

    // ==========================================
    // TICKET LOGIC
    // ==========================================

    /**
    * Get user's tickets
    */
    public function getUserTickets()
    {
        $userId = session()->get('id');
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'Authentication required']);
        }

        try {
            $ticketModel = new \App\Models\TicketModel();
            $tickets = $ticketModel->where('created_by', $userId)
                                   ->where('is_deleted', 0)
                                   ->orderBy('created_at', 'DESC')
                                   ->findAll();

            // Format tickets for frontend & Normalize Status
            foreach ($tickets as &$ticket) {
                $ticket['time_ago'] = $this->getTimeAgo($ticket['created_at']);
                
                // --- FIX: Normalize legacy statuses for display ---
                if ($ticket['status'] === 'open') {
                    $ticket['status'] = 'pending';
                } elseif ($ticket['status'] === 'closed') {
                    $ticket['status'] = 'resolved';
                }
            }

            return $this->setCorsHeaders()->setJSON($tickets);
        } catch (\Exception $e) {
            log_message('error', 'Error getting user tickets: ' . $e->getMessage());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to load tickets']);
        }
    }

    /**
    * Create new ticket
    */
    public function createTicket()
    {
        // Set CORS headers first
        $this->setCorsHeaders();
        
        $userId = session()->get('id');
        if (!$userId) {
            log_message('error', 'User not authenticated for ticket creation');
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'error' => 'Authentication required'
            ]);
        }

        try {
            // Handle both JSON and form data
            $contentType = $this->request->getHeaderLine('Content-Type');
            
            if (strpos($contentType, 'application/json') !== false) {
                // Handle JSON request
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error' => 'Invalid JSON data: ' . json_last_error_msg()
                    ]);
                }
            } else {
                // Handle form data
                $data = [
                    'subject' => $this->request->getPost('subject'),
                    'description' => $this->request->getPost('description'),
                    'priority' => $this->request->getPost('priority'),
                    'category' => $this->request->getPost('category')
                ];
            }
            
            if (empty($data)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => 'No data received'
                ]);
            }

            // Validate required fields
            if (empty($data['subject']) || empty($data['description'])) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => 'Subject and description are required'
                ]);
            }

            $ticketData = [
                'subject' => trim($data['subject']),
                'description' => trim($data['description']),
                'priority' => $data['priority'] ?? 'medium',
                'category' => $data['category'] ?? 'general',
                // FIX: Default status is now 'pending' (was 'open')
                'status' => 'pending', 
                'created_by' => $userId,
                'is_deleted' => 0
            ];

            // Check if TicketModel exists
            if (!class_exists('\App\Models\TicketModel')) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error' => 'Ticket system not properly configured'
                ]);
            }

            // Ensure tickets table exists
            $this->ensureTicketsTableExists();

            $ticketModel = new \App\Models\TicketModel();
            
            // Disable validation temporarily to debug if needed, or rely on model validation
            // $ticketModel->skipValidation(false);
            
            $ticketId = $ticketModel->insert($ticketData);
            
            if ($ticketId) {
                // Get the created ticket
                $ticket = $ticketModel->find($ticketId);
                
                if (!$ticket) {
                    return $this->response->setStatusCode(500)->setJSON([
                        'success' => false,
                        'error' => 'Ticket created but could not be retrieved'
                    ]);
                }
                
                // Add time_ago field
                $ticket['time_ago'] = $this->getTimeAgo($ticket['created_at']);
                
                // Add initial message if TicketMessageModel exists
                try {
                    if (class_exists('\App\Models\TicketMessageModel')) {
                        $messageModel = new \App\Models\TicketMessageModel();
                        $messageModel->insert([
                            'ticket_id' => $ticketId,
                            'sender_id' => $userId,
                            'sender_type' => 'customer',
                            'content' => $ticketData['description'],
                            'message_type' => 'text',
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Error creating initial ticket message: ' . $e->getMessage());
                }

                // Emit socket event for real-time updates
                $this->emitTicketCreatedEvent($ticket);
                
                return $this->response->setJSON([
                    'success' => true,
                    'ticket' => $ticket,
                    'message' => 'Ticket created successfully'
                ]);
            } else {
                $errors = $ticketModel->errors();
                log_message('error', 'Ticket creation failed: ' . json_encode($errors));
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error' => 'Failed to create ticket',
                    'validation_errors' => $errors
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception creating ticket: ' . $e->getMessage());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'Failed to create ticket: ' . $e->getMessage()
            ]);
        }
    }

    /**
    * Ensure tickets table exists
    */
    private function ensureTicketsTableExists()
    {
        try {
            $db = \Config\Database::connect();
            
            // Check if tickets table exists
            $tables = $db->listTables();
            if (!in_array('tickets', $tables)) {
                log_message('info', 'Creating tickets table...');
                
                // Create tickets table with NEW status enum
                $db->query("
                    CREATE TABLE IF NOT EXISTS `tickets` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `subject` varchar(255) NOT NULL,
                    `description` text NOT NULL,
                    `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
                    `category` varchar(100) NOT NULL DEFAULT 'general',
                    `status` enum('pending','in-progress','resolved') NOT NULL DEFAULT 'pending',
                    `created_by` int(11) NOT NULL,
                    `assigned_to` int(11) DEFAULT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    `closed_at` datetime DEFAULT NULL,
                    `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`),
                    KEY `idx_created_by` (`created_by`),
                    KEY `idx_assigned_to` (`assigned_to`),
                    KEY `idx_status` (`status`),
                    KEY `idx_priority` (`priority`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                
                log_message('info', 'Tickets table created successfully');
            }
            
            // Check if ticket_categories table exists
            if (!in_array('ticket_categories', $tables)) {
                log_message('info', 'Creating ticket_categories table...');
                
                $db->query("
                    CREATE TABLE IF NOT EXISTS `ticket_categories` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) NOT NULL,
                    `description` text,
                    `is_active` tinyint(1) NOT NULL DEFAULT 1,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                
                // Insert default categories
                $db->query("
                    INSERT IGNORE INTO `ticket_categories` (`name`, `description`) VALUES
                    ('technical', 'Technical issues and bugs'),
                    ('account', 'Account related questions'),
                    ('billing', 'Billing and payment issues'),
                    ('feature', 'Feature requests and suggestions'),
                    ('bug', 'Bug reports'),
                    ('general', 'General inquiries');
                ");
                
                log_message('info', 'Ticket categories table created successfully');
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Error ensuring tickets table exists: ' . $e->getMessage());
        }
    }

    /**
    * Get ticket statistics
    */
    public function getTicketStats()
    {
        $userId = session()->get('id');
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'Authentication required']);
        }

        try {
            $ticketModel = new \App\Models\TicketModel();
            
            // Get basic stats
            $totalTickets = $ticketModel->where('created_by', $userId)
                                        ->where('is_deleted', 0)
                                        ->countAllResults();
            
            // FIX: Count 'pending' tickets (New Logic)
            $pendingTickets = $ticketModel->where('created_by', $userId)
                                          ->where('status', 'pending')
                                          ->where('is_deleted', 0)
                                          ->countAllResults();
            
            $resolvedTickets = $ticketModel->where('created_by', $userId)
                                           ->where('status', 'resolved')
                                           ->where('is_deleted', 0)
                                           ->countAllResults();
            
            $stats = [
                'total_tickets' => $totalTickets,
                // Map 'pending' count to 'open_tickets' for frontend compatibility
                'open_tickets' => $pendingTickets, 
                'resolved_tickets' => $resolvedTickets,
                'avg_response_time' => '2.5h', 
                'resolution_rate' => $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100) . '%' : '0%'
            ];

            return $this->setCorsHeaders()->setJSON($stats);
        } catch (\Exception $e) {
            log_message('error', 'Error getting ticket stats: ' . $e->getMessage());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to load stats']);
        }
    }

    /**
    * Emit ticket created event to socket server
    */
    private function emitTicketCreatedEvent($ticket)
    {
        try {
            $socketServerUrl = 'http://localhost:3001/emit-message';
            
            $socketData = [
                'event' => 'ticket_created',
                'data' => [
                    'ticket' => $ticket,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            
            $client = \Config\Services::curlrequest();
            $response = $client->request('POST', $socketServerUrl, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $socketData,
                'timeout' => 3
            ]);
            
            log_message('debug', 'Ticket created event sent to socket server');
        } catch (\Exception $e) {
            log_message('error', 'Failed to send ticket created event: ' . $e->getMessage());
        }
    }

    /**
    * Helper method to calculate time ago
    */
    private function getTimeAgo($timestamp)
    {
        $now = new \DateTime();
        $date = new \DateTime($timestamp);
        $interval = $now->diff($date);
        
        if ($interval->days > 0) {
            return $interval->days . 'd ago';
        } elseif ($interval->h > 0) {
            return $interval->h . 'h ago';
        } elseif ($interval->i > 0) {
            return $interval->i . 'm ago';
        } else {
            return 'Just now';
        }
    }

    /**
    * Get ticket status for customer
    */
    public function getTicketStatus($ticketId)
    {
        $userId = session()->get('id');
        
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            $db = \Config\Database::connect();
            
            // Get ticket with TSR info
            $ticket = $db->table('tickets t')
                ->select('t.*, tsr.username as assigned_tsr_name, tsr.nickname as assigned_tsr_nickname')
                ->join('users tsr', 't.assigned_to = tsr.id', 'left')
                ->where('t.id', $ticketId)
                ->where('t.created_by', $userId)
                ->get()
                ->getRowArray();
            
            if (!$ticket) {
                return $this->setCorsHeaders()->setStatusCode(404)->setJSON(['error' => 'Ticket not found']);
            }

            // --- FIX: Normalize Status for Frontend Compatibility ---
            // This ensures the frontend only ever sees 'pending', 'in-progress', or 'resolved'
            // even if the database still holds legacy values like 'open' or 'closed'.
            if ($ticket['status'] === 'open') {
                $ticket['status'] = 'pending';
            } elseif ($ticket['status'] === 'closed') {
                $ticket['status'] = 'resolved';
            }
            
            return $this->setCorsHeaders()->setJSON([
                'success' => true,
                'ticket' => $ticket
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Exception getting ticket status: ' . $e->getMessage());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to get ticket status']);
        }
    }

    // Add this method to handle unread notifications count
    public function getNotifications()
{
    $userId = session()->get('id');
    
    if (!$userId) {
        return $this->response->setJSON([]);
    }

    $db = \Config\Database::connect();
    $builder = $db->table('notifications');
    $builder->where('user_id', $userId);
    $builder->orderBy('created_at', 'DESC');
    $builder->limit(20);
    $query = $builder->get();
    
    return $this->response->setJSON($query->getResultArray());
}

public function markNotificationRead()
{
    $userId = session()->get('id');
    $json = $this->request->getJSON();
    $notifId = $json->id ?? null;

    if ($userId && $notifId) {
        $db = \Config\Database::connect();
        $db->table('notifications')
           ->where('id', $notifId)
           ->where('user_id', $userId)
           ->update(['read_at' => date('Y-m-d H:i:s')]);
           
        return $this->response->setJSON(['success' => true]);
    }
    return $this->response->setJSON(['success' => false]);
}

public function unreadNotificationsCount()
{
    $userId = session()->get('id');
    
    if (!$userId) {
        return $this->response->setJSON(['count' => 0]);
    }

    $db = \Config\Database::connect();
    $count = $db->table('notifications')
                ->where('user_id', $userId)
                ->where('read_at', null)
                ->countAllResults();

    return $this->response->setJSON(['count' => $count]);
}

    /**
    * Get customer ticket messages - ENHANCED for real-time chat
    */
    public function getCustomerTicketMessages($ticketId)
    {
        $userId = session()->get('id');
        
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            // Verify ticket belongs to user
            $ticketModel = new TicketModel();
            $ticket = $ticketModel->where('id', $ticketId)
                                ->where('created_by', $userId)
                                ->first();
            
            if (!$ticket) {
                return $this->setCorsHeaders()->setStatusCode(403)->setJSON(['error' => 'Access denied']);
            }
            
            // Get since parameter for polling
            $since = $this->request->getGet('since');
            
            $messageModel = new TicketMessageModel();
            
            if ($since && is_numeric($since)) {
                // Convert timestamp to date
                $sinceDate = date('Y-m-d H:i:s', $since / 1000);
                
                // Get messages newer than the since timestamp
                $messages = $messageModel->select('ticket_messages.*, users.username as sender_name, users.nickname')
                                        ->join('users', 'users.id = ticket_messages.sender_id', 'left')
                                        ->where('ticket_messages.ticket_id', $ticketId)
                                        ->where('ticket_messages.created_at >', $sinceDate)
                                        ->where('ticket_messages.is_deleted', 0)
                                        ->orderBy('ticket_messages.created_at', 'ASC')
                                        ->findAll();
            } else {
                // Get all messages
                $messages = $messageModel->getTicketMessages($ticketId);
            }
            
            // Format messages for frontend
            foreach ($messages as &$message) {
                $message['sender_name'] = $message['nickname'] ?: $message['username'] ?: $message['sender_name'] ?: 'Unknown';
            }
            
            // Mark messages as read
            $messageModel->markAsRead($ticketId, $userId);
            
            return $this->setCorsHeaders()->setJSON($messages);
        } catch (\Exception $e) {
            log_message('error', 'Exception getting customer ticket messages: ' . $e->getMessage());
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => 'Failed to get messages']);
        }
    }

    public function sendCustomerTicketMessage()
    {
        // Set CORS headers first
        $this->setCorsHeaders();
        
        $userId = session()->get('id');
        
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }
        
        try {
            // Get JSON data
            $json = $this->request->getJSON(true);
            if (empty($json)) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request data']);
            }
            
            $ticketId = $json['ticket_id'] ?? null;
            $message = $json['message'] ?? null;
            $senderType = $json['sender_type'] ?? 'customer';
            
            if (!$ticketId || !$message) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Ticket ID and message are required']);
            }
            
            // Verify user owns the ticket
            $ticketModel = new TicketModel();
            $ticket = $ticketModel->where('id', $ticketId)->where('created_by', $userId)->first();
            
            if (!$ticket) {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
            }
            
            // Check if ticket is closed
            if ($ticket['status'] === 'closed' || $ticket['status'] === 'resolved') {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'This ticket is closed. You cannot send new messages.']);
            }
            
            $messageModel = new TicketMessageModel();
            
            // Add the message
            $newMessage = $messageModel->addMessage($ticketId, $userId, $senderType, $message);
            
            if ($newMessage) {
                // Update ticket timestamp
                $ticketModel->update($ticketId, ['updated_at' => date('Y-m-d H:i:s')]);
                
                // Emit socket event for real-time updates
                $this->emitTicketMessageEvent($newMessage);
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => $newMessage
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to send message']);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Exception sending customer ticket message: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to send message: ' . $e->getMessage()]);
        }
    }

    public function getCustomerTicketStatus($ticketId)
    {
        return $this->getTicketStatus($ticketId);
    }

    private function emitTicketMessageEvent($messageData)
    {
        try {
            $socketServerUrl = 'http://localhost:3001/emit-message';
            
            $socketData = [
                'event' => 'new_ticket_message',
                'data' => [
                    'ticket_id' => $messageData['ticket_id'],
                    'message' => $messageData
                ]
            ];
            
            $client = \Config\Services::curlrequest();
            $response = $client->request('POST', $socketServerUrl, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $socketData,
                'timeout' => 3
            ]);
            
            log_message('debug', 'Ticket message event sent to socket server');
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Failed to send ticket message event: ' . $e->getMessage());
            return false;
        }
    }

    public function getCallLogs()
    {
        $userId = session()->get('id');
        if (!$userId) {
            return $this->setCorsHeaders()->setStatusCode(401)->setJSON(['error' => 'User not authenticated']);
        }

        try {
            $db = \Config\Database::connect();
            $logs = $db->table('call_logs')
                ->select("
                    call_logs.*, 
                    IF(call_logs.caller_id = $userId, r.nickname, c.nickname) as other_name,
                    IF(call_logs.caller_id = $userId, r.avatar, c.avatar) as other_avatar,
                    IF(call_logs.caller_id = $userId, r.id, c.id) as other_id,
                    CASE 
                        WHEN call_logs.status = 'missed' THEN 'Missed call'
                        WHEN call_logs.status = 'rejected' THEN 'Declined'
                        WHEN call_logs.caller_id = $userId THEN 'Outgoing call'
                        ELSE 'Incoming call'
                    END as status_label
                ")
                ->join('users c', 'c.id = call_logs.caller_id', 'left')
                ->join('users r', 'r.id = call_logs.receiver_id', 'left')
                ->groupStart()
                    ->where('call_logs.caller_id', $userId)
                    ->orWhere('call_logs.receiver_id', $userId)
                ->groupEnd()
                ->orderBy('call_logs.created_at', 'DESC')
                ->limit(20)
                ->get()
                ->getResult();

            return $this->setCorsHeaders()->setJSON($logs);
        } catch (\Exception $e) {
            return $this->setCorsHeaders()->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }

// ==========================================
    // SMART KNOWLEDGE BASE (v4.0 - Aggressive)
    // ==========================================

    public function searchKnowledgeBase() {
        $userInput = $this->request->getGet('q');
        
        if (empty($userInput)) {
            return $this->setCorsHeaders()->setJSON(['status' => 'empty', 'answer' => '']);
        }

        // 1. EXPANDED DICTIONARY (The "Brain")
        $dictionary = [
            // Fixes for your specific issue
            'salary' => 'payday', 'wage' => 'payday', 'pay' => 'payday', 'money' => 'payday',
            'delayed' => 'payday', // Key fix: "delayed" -> "payday"
            'missing' => 'payday',

            // General Typos
            'pwd' => 'password', 'pass' => 'password', 'reset' => 'password',
            'login' => 'log in', 'signin' => 'log in', 'cant' => 'cannot',
            'wont' => 'will not', 'idk' => 'i do not know',

            // HR Terms
            'leave' => 'file leave', 'vl' => 'file leave', 'sl' => 'file leave', 
            'vacation' => 'file leave', 'absent' => 'file leave',
            'slow' => 'performance', 'lag' => 'performance'
        ];

        // Clean & Normalize
        $cleanInput = strtolower(trim(preg_replace('/[^a-z0-9 ]/i', '', $userInput)));
        $words = explode(' ', $cleanInput);
        
        // Apply Synonyms
        $mappedWords = [];
        foreach ($words as $word) {
            $mappedWords[] = $dictionary[$word] ?? $word;
        }

        // 2. BROAD SEARCH
        $builder = $this->db->table('kb_entries')->where('approved', 1);
        
        $builder->groupStart();
            foreach ($mappedWords as $word) {
                // Skip useless words
                if (strlen($word) > 2 && !in_array($word, ['the', 'how', 'can', 'and', 'not', 'what', 'why', 'is', 'my'])) {
                    $builder->orLike('question', $word);
                    $builder->orLike('intent', $word);
                    $builder->orLike('answer', $word); // Search inside the answer too!
                }
            }
        $builder->groupEnd();
        
        $candidates = $builder->limit(5)->get()->getResultArray();

        // 3. AGGRESSIVE SCORING
        $bestMatch = null;
        $highestScore = 0;

        foreach ($candidates as $row) {
            $score = 0;
            $dbIntent = strtolower($row['intent']);
            $dbQuestion = strtolower($row['question']);

            foreach ($mappedWords as $word) {
                if (strlen($word) <= 2) continue;

                // HUGE Bonus if keyword matches the Intent ID (e.g. 'payday')
                if (strpos($dbIntent, $word) !== false) $score += 50;
                
                // Medium Bonus if keyword is in the Question
                if (strpos($dbQuestion, $word) !== false) $score += 30;
            }

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $row;
            }
        }

        // 4. LOWER THRESHOLD (Because we trust our keywords)
        // Score > 20 usually means at least one important keyword matched perfectly
        if ($bestMatch && $highestScore >= 20) {
            return $this->setCorsHeaders()->setJSON([
                'status' => 'found',
                'id'     => $bestMatch['id'],
                'answer' => $bestMatch['answer'],
                'score'  => $highestScore
            ]);
        } 

        // 5. ESCALATION & LEARNING LOG
        // Save the failed query so you can "Teach" the bot later
        $exists = $this->db->table('kb_entries')->where('question', $cleanInput)->countAllResults();
        
        if (!$exists) {
            $this->db->table('kb_entries')->insert([
                'question'   => $cleanInput, // Save what the user actually typed
                'answer'     => 'Awaiting response...',
                'approved'   => 0, // Not visible to users yet
                'confidence' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        return $this->setCorsHeaders()->setJSON([
            'status' => 'escalated',
            'answer' => "I didn't quite catch that. Could you rephrase it? If it's urgent, I can connect you to an agent."
        ]);
    }
    
    public function submitKbFeedback() 
    {
        $json = $this->request->getJSON(true);
        if (!$json || !isset($json['kb_entry_id'])) {
            return $this->setCorsHeaders()->setStatusCode(400)->setJSON(['error' => 'Invalid data']);
        }

        $this->db->table('kb_feedback')->insert([
            'kb_entry_id' => $json['kb_entry_id'],
            'user_id'     => session()->get('id'),
            'helpful'     => $json['helpful'],
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        if ($json['helpful']) {
            $this->db->table('kb_entries')
                ->where('id', $json['kb_entry_id'])
                ->set('confidence', 'confidence + 0.02', false)
                ->update();
        }

        return $this->setCorsHeaders()->setJSON(['success' => true]);
    }
}