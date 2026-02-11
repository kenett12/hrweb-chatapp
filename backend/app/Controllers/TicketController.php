<?php

namespace App\Controllers;

use App\Models\TicketModel;
use CodeIgniter\API\ResponseTrait;

class TicketController extends BaseController
{
    use ResponseTrait;

    /**
     * CREATE TICKET
     * Handles the POST request from the frontend
     */
    public function create()
    {
        try {
            // Get JSON data
            $json = $this->request->getJSON();

            // Basic Validation
            if (!$json || empty($json->subject)) {
                return $this->fail('Subject is required', 400);
            }

            $session = session();
            $userId = $session->get('id') ?? $session->get('user_id');

            if (!$userId) {
                return $this->failUnauthorized('User not logged in');
            }

            // === FIX: Use TicketModel instead of raw DB calls ===
            // This ensures validation rules from the Model are applied automatically
            $ticketModel = new TicketModel();

            $ticketData = [
                'created_by'  => (int)$userId, // FIXED: Changed customer_id to created_by
                'subject'     => $json->subject,
                'description' => $json->description,
                'category'    => $json->category,
                'priority'    => $json->priority,
                'status'      => 'pending', // Default status
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s')
            ];

            // Use Model to Insert (Triggering Validation)
            if ($ticketModel->insert($ticketData)) {
                
                $ticketData['id'] = $ticketModel->getInsertID();
                $ticketData['customer_name'] = $session->get('nickname') ?? $session->get('username');
                
                return $this->respond([
                    'success' => true, 
                    'ticket' => $ticketData,
                    'message' => 'Ticket created successfully'
                ]);
            } else {
                // Return Model Validation Errors
                return $this->fail($ticketModel->errors(), 400);
            }

        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Get a single ticket's details
     */
    public function getTicketDetails($ticketId)
    {
        try {
            $db = \Config\Database::connect();
            $ticket = $db->table('tickets')
                         ->select('tickets.*, users.username as customer_name, users.nickname as customer_nickname')
                         ->join('users', 'users.id = tickets.created_by', 'left') // FIXED: join on created_by
                         ->where('tickets.id', $ticketId)
                         ->get()
                         ->getRowArray();

            if ($ticket) {
                return $this->respond($ticket);
            }
            return $this->failNotFound('Ticket not found');
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Get all messages for a specific ticket
     */
    public function getTicketMessages($ticketId)
    {
        try {
            $db = \Config\Database::connect();
            $messages = $db->table('ticket_messages tm')
                           ->select('tm.*, u.username as sender_name, tm.content as message')
                           ->join('users u', 'u.id = tm.sender_id', 'left')
                           ->where('tm.ticket_id', $ticketId)
                           ->where('tm.is_deleted', 0)
                           ->orderBy('tm.created_at', 'ASC')
                           ->get()
                           ->getResultArray();
            
            return $this->respond($messages);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to get messages');
        }
    }

    /**
     * Send a message
     */
    public function sendTicketMessage()
    {
        try {
            // Support both JSON and POST data
            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            
            $ticketId   = $input['ticket_id'] ?? null;
            $message    = $input['message'] ?? null;
            $senderType = $input['sender_type'] ?? 'tsr';
            
            if (!$ticketId || !$message) {
                return $this->fail('Missing ticket_id or message', 400);
            }
            
            $session = session();
            $userId = $session->get('id') ?? $session->get('user_id');
            
            $db = \Config\Database::connect();
            $messageData = [
                'ticket_id'   => (int)$ticketId,
                'sender_id'   => (int)$userId,
                'sender_type' => $senderType,
                'content'     => $message,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s')
            ];
            
            if ($db->table('ticket_messages')->insert($messageData)) {
                // Update parent ticket timestamp
                $db->table('tickets')->where('id', $ticketId)->update(['updated_at' => date('Y-m-d H:i:s')]);
                
                return $this->respond([
                    'success' => true, 
                    'data' => $messageData
                ]);
            }

            return $this->fail('Insert failed');
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Update ticket status
     */
    public function updateTicketStatus($ticketId)
    {
        $input = $this->request->getJSON();
        $status = $input->status ?? null;
        
        if (!$status) {
            return $this->fail('Status is required', 400);
        }
        
        try {
            $ticketModel = new TicketModel();
            
            // Use the Model's method to handle history logging automatically
            // Note: pass user ID from session
            $session = session();
            $userId = $session->get('id') ?? $session->get('user_id');
            
            if ($ticketModel->updateStatus($ticketId, $status, $userId)) {
                 return $this->respond(['success' => true]);
            }
            
            return $this->fail('Failed to update status');
            
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Get list of tickets for TSR
     */
    public function getTSRTicketList()
    {
        try {
            $db = \Config\Database::connect();
            
            // === FIX: Use created_by instead of customer_id ===
            $tickets = $db->table('tickets')
                ->select('tickets.*, users.username as customer_name')
                ->join('users', 'users.id = tickets.created_by') // FIXED HERE
                ->where('tickets.status !=', 'closed')
                ->orderBy('tickets.updated_at', 'DESC')
                ->get()
                ->getResultArray();

            return $this->respond($tickets);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

}