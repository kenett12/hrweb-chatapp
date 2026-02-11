<?php

namespace App\Controllers;

use App\Models\TicketModel;
use App\Models\TicketMessageModel;
use CodeIgniter\Controller;

class CustomerTickets extends Controller
{
    public function chat($ticketId)
    {
        $session = session();
        $userId = $session->get('id');
        
        $ticketModel = new TicketModel();
        $ticket = $ticketModel->where('id', $ticketId)
                             ->where('created_by', $userId)
                             ->first();
        
        if (!$ticket) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Ticket not found');
        }
        
        $data = [
            'user' => [
                'id' => $session->get('id'),
                'username' => $session->get('username')
            ],
            'ticket' => $ticket
        ];
        
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
            return $this->response->setJSON(['error' => 'Unauthorized'], 403);
        }
        
        $messageModel = new TicketMessageModel();
        $messages = $messageModel->select('ticket_messages.*, users.username as sender_name')
                                ->join('users', 'users.id = ticket_messages.sender_id')
                                ->where('ticket_id', $ticketId)
                                ->orderBy('created_at', 'ASC')
                                ->findAll();
        
        return $this->response->setJSON($messages);
    }

    public function sendMessage()
    {
        $session = session();
        $userId = $session->get('id');
        $ticketId = $this->request->getPost('ticket_id');
        $message = $this->request->getPost('message');
        
        // Verify ticket belongs to user
        $ticketModel = new TicketModel();
        $ticket = $ticketModel->where('id', $ticketId)
                             ->where('created_by', $userId)
                             ->first();
        
        if (!$ticket) {
            return $this->response->setJSON(['error' => 'Unauthorized'], 403);
        }
        
        $messageModel = new TicketMessageModel();
        
        $messageData = [
            'ticket_id' => $ticketId,
            'sender_id' => $userId,
            'sender_type' => 'customer',
            'content' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle file uploads
        $uploadedFile = $this->request->getFile('files.0');
        if ($uploadedFile && $uploadedFile->isValid()) {
            $fileName = $uploadedFile->getRandomName();
            $uploadPath = WRITEPATH . 'uploads/tickets/';
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            if ($uploadedFile->move($uploadPath, $fileName)) {
                $messageData['file_path'] = '/writable/uploads/tickets/' . $fileName;
                $messageData['original_filename'] = $uploadedFile->getClientName();
            }
        }
        
        $messageId = $messageModel->insert($messageData);
        
        if ($messageId) {
            // Update ticket's updated_at timestamp
            $ticketModel->update($ticketId, ['updated_at' => date('Y-m-d H:i:s')]);
            
            $messageData['id'] = $messageId;
            $messageData['sender_name'] = $session->get('username');
            
            return $this->response->setJSON($messageData);
        }
        
        return $this->response->setJSON(['error' => 'Failed to send message'], 500);
    }
}
