<?php

namespace App\Controllers;

use App\Models\TicketModel;
use App\Models\TicketMessageModel;
use CodeIgniter\Controller;

class CustomerTickets extends Controller
{
    public function index()
    {
        $session = session();
        $userId = $session->get('id');
        
        $data = [
            'user' => [
                'id' => $userId,
                'username' => $session->get('username')
            ]
        ];
        
        return view('tickets/index', $data);
    }

    public function chat($ticketId)
    {
        $session = session();
        $userId = $session->get('id');
        
        $ticketModel = new TicketModel();
        
        // Get ticket with TSR information
        $ticket = $ticketModel->select('tickets.*, tsr.username as assigned_tsr_name, tsr.nickname as assigned_tsr_nickname')
                             ->join('users tsr', 'tickets.assigned_to = tsr.id', 'left')
                             ->where('tickets.id', $ticketId)
                             ->where('tickets.created_by', $userId)
                             ->first();
        
        if (!$ticket) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Ticket not found');
        }
        
        // Get messages for this ticket
        $messageModel = new TicketMessageModel();
        $messages = $messageModel->getTicketMessages($ticketId);
        
        $data = [
            'user' => [
                'id' => $session->get('id'),
                'username' => $session->get('username')
            ],
            'ticket' => $ticket,
            'messages' => $messages
        ];
        
        // Mark messages as read
        $messageModel->markAsRead($ticketId, $userId);
        
        return view('tickets/chat', $data);
    }

    public function getMessages($ticketId)
    {
        $session = session();
        $userId = $session->get('id');
        
        // Verify ticket belongs to user
        $ticketModel = new TicketModel();
        $ticket = $ticketModel->where('id', $ticketId)
                             ->where('created_by', $userId)
                             ->first();
        
        if (!$ticket) {
            return $this->response->setJSON(['error' => 'Unauthorized', 'success' => false], 403);
        }
        
        // Get since parameter for polling
        $since = $this->request->getGet('since') ?? 0;
        $sinceDate = date('Y-m-d H:i:s', $since / 1000);
        
        $messageModel = new TicketMessageModel();
        
        // Get messages newer than the since timestamp
        if ($since > 0) {
            $messages = $messageModel->where('ticket_id', $ticketId)
                                    ->where('created_at >', $sinceDate)
                                    ->orderBy('created_at', 'ASC')
                                    ->findAll();
        } else {
            $messages = $messageModel->getTicketMessages($ticketId);
        }
        
        // Mark messages as read
        $messageModel->markAsRead($ticketId, $userId);
        
        return $this->response->setJSON([
            'success' => true,
            'messages' => $messages
        ]);
    }

    public function sendMessage()
    {
        $session = session();
        $userId = $session->get('id');
        
        // Get request data
        $json = $this->request->getJSON();
        if ($json) {
            $ticketId = $json->ticket_id ?? null;
            $message = $json->message ?? null;
        } else {
            $ticketId = $this->request->getPost('ticket_id');
            $message = $this->request->getPost('message');
        }
        
        if (!$ticketId || !$message) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Missing required fields'
            ], 400);
        }
        
        // Verify ticket belongs to user
        $ticketModel = new TicketModel();
        $ticket = $ticketModel->where('id', $ticketId)
                             ->where('created_by', $userId)
                             ->first();
        
        if (!$ticket) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403);
        }
        
        // Check if ticket is closed or resolved
        if ($ticket['status'] === 'closed' || $ticket['status'] === 'resolved') {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'This ticket is closed. You cannot send new messages.'
            ], 400);
        }
        
        $messageModel = new TicketMessageModel();
        
        // Add the message
        $newMessage = $messageModel->addMessage($ticketId, $userId, 'customer', $message);
        
        if ($newMessage) {
            // Update ticket's updated_at timestamp
            $ticketModel->update($ticketId, ['updated_at' => date('Y-m-d H:i:s')]);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => $newMessage
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'error' => 'Failed to send message'
        ], 500);
    }

    public function getTicketStatus($ticketId)
    {
        $session = session();
        $userId = $session->get('id');
        
        $ticketModel = new TicketModel();
        $ticket = $ticketModel->select('tickets.*, users.username as tsr_name, users.nickname as tsr_nickname')
                             ->join('users', 'users.id = tickets.assigned_to', 'left')
                             ->where('tickets.id', $ticketId)
                             ->where('tickets.created_by', $userId)
                             ->first();
        
        if (!$ticket) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Ticket not found'
            ], 404);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'ticket' => [
                'id' => $ticket['id'],
                'status' => $ticket['status'],
                'assigned_to' => $ticket['assigned_to'],
                'assigned_tsr_name' => $ticket['tsr_nickname'] ?: $ticket['tsr_name'],
                'updated_at' => $ticket['updated_at']
            ]
        ]);
    }
}
