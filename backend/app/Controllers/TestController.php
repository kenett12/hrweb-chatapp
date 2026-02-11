<?php

namespace App\Controllers;

use App\Models\TicketModel;
use App\Models\TicketMessageModel;
use CodeIgniter\API\ResponseTrait;

class TicketController extends BaseController
{
    use ResponseTrait;

    public function getTSRTickets()
    {
        $ticketModel = new TicketModel();
        
        // Get all tickets assigned to TSRs or unassigned
        $tickets = $ticketModel->select('tickets.*, users.username as customer_name')
                              ->join('users', 'users.id = tickets.customer_id')
                              ->where('tickets.status !=', 'closed')
                              ->orderBy('tickets.updated_at', 'DESC')
                              ->findAll();
        
        return $this->response->setJSON($tickets);
    }

    public function sendTicketMessage($ticketId = null)
    {
        // Disable any output buffering and error display that might corrupt JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set JSON content type header
        $this->response->setContentType('application/json');
        
        try {
            // Get ticket ID from URL parameter if not provided
            if (!$ticketId) {
                $ticketId = $this->request->getPost('ticket_id');
            }
        
            $message = $this->request->getPost('message');
            $senderType = $this->request->getPost('sender_type') ?? 'tsr';
        
            // Log the received data for debugging
            log_message('debug', 'Received ticket message data: ' . json_encode([
                'ticket_id' => $ticketId,
                'message' => $message,
                'sender_type' => $senderType,
                'url_segment' => $this->request->getUri()->getSegment(3)
            ]));
        
            if (!$ticketId || !$message) {
                return $this->response->setJSON([
                    'success' => false, 
                    'message' => 'Ticket ID and message are required'
                ], 400);
            }
        
            $session = session();
            $userId = $session->get('user_id');
        
            // For testing, if no user_id in session, use a default
            if (!$userId) {
                $userId = 1; // Use a default user ID for testing
                log_message('warning', 'No user_id in session, using default: ' . $userId);
            }
        
            // Use direct database connection
            $db = \Config\Database::connect();
        
            $messageData = [
                'ticket_id' => (int)$ticketId,
                'sender_id' => (int)$userId,
                'sender_type' => $senderType,
                'content' => $message,
                'is_system' => 0,
                'is_deleted' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        
            log_message('debug', 'Attempting to insert message: ' . json_encode($messageData));
        
            // Insert message
            $result = $db->table('ticket_messages')->insert($messageData);
        
            if ($result) {
                $messageId = $db->insertID();
                log_message('debug', 'Message inserted successfully with ID: ' . $messageId);
            
                // Update ticket's updated_at timestamp
                $db->table('tickets')->where('id', $ticketId)->update(['updated_at' => date('Y-m-d H:i:s')]);
            
                $messageData['id'] = $messageId;
                $messageData['sender_name'] = $session->get('username') ?? 'TSR Agent';
            
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => $messageData
                ]);
            } else {
                $error = $db->error();
                log_message('error', 'Database insert failed: ' . json_encode($error));
                return $this->response->setJSON([
                    'success' => false, 
                    'message' => 'Database insert failed: ' . ($error['message'] ?? 'Unknown error')
                ], 500);
            }
        
        } catch (\Exception $e) {
            log_message('error', 'Exception in sendTicketMessage: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTicketMessages($ticketId)
    {
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        $db = \Config\Database::connect();
        
        try {
            $messages = $db->table('ticket_messages tm')
                          ->select('tm.*, u.username as sender_name, tm.content as message')
                          ->join('users u', 'u.id = tm.sender_id', 'left')
                          ->where('tm.ticket_id', $ticketId)
                          ->where('tm.is_deleted', 0)
                          ->orderBy('tm.created_at', 'ASC')
                          ->get()
                          ->getResultArray();
            
            log_message('debug', 'Retrieved ' . count($messages) . ' messages for ticket ' . $ticketId);
            return $this->response->setJSON($messages);
        } catch (\Exception $e) {
            log_message('error', 'Error getting messages: ' . $e->getMessage());
            return $this->response->setJSON(['error' => 'Failed to get messages: ' . $e->getMessage()], 500);
        }
    }

    public function updateTicketStatus($ticketId)
    {
        $input = $this->request->getJSON();
        $status = $input->status ?? null;
        
        if (!$status) {
            return $this->response->setJSON(['success' => false, 'message' => 'Status is required'], 400);
        }
        
        $db = \Config\Database::connect();
        
        try {
            $updated = $db->table('tickets')
                         ->where('id', $ticketId)
                         ->update([
                             'status' => $status,
                             'updated_at' => date('Y-m-d H:i:s')
                         ]);
            
            if ($updated) {
                return $this->response->setJSON(['success' => true]);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Failed to update status'], 500);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function getTSRTicketList()
    {
        $ticketModel = new TicketModel();
        $session = session();
        $userId = $session->get('user_id');

        // Get tickets assigned to the TSR or available tickets
        $tickets = $ticketModel->select('tickets.*, users.username as customer_name')
            ->join('users', 'users.id = tickets.customer_id')
            ->where('tickets.status !=', 'closed')
            ->orderBy('tickets.updated_at', 'DESC')
            ->findAll();

        return $this->response->setJSON($tickets);
    }

    public function getTicketDetails($ticketId)
    {
        $ticketModel = new TicketModel();

        $ticket = $ticketModel->select('tickets.*, users.username as customer_name')
            ->join('users', 'users.id = tickets.customer_id')
            ->find($ticketId);

        if ($ticket) {
            return $this->response->setJSON($ticket);
        } else {
            return $this->response->setJSON(['error' => 'Ticket not found'], 404);
        }
    }
}
