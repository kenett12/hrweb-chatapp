<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;

/**
 * Debug controller for testing API endpoints
 */
class Debug extends Controller
{
    use ResponseTrait;
    
    public function index()
{
    helper('test');

    $apiTest = test_api_connection();
    $userId = session()->get('id');
    $receiverId = $this->request->getGet('test_message_to');

    $data = [
        'title' => 'API Debug Utility',
        'apiTest' => $apiTest,
        'userId' => $userId,
        'baseUrl' => base_url(),
        'apiBaseUrl' => base_url('api'),
        'phpVersion' => phpversion(),
        'ciVersion' => \CodeIgniter\CodeIgniter::CI_VERSION,
        'serverInfo' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'routes' => \Config\Services::routes()->getRoutes(),
        'test_message_to' => $receiverId, // ✅ add this line
    ];

    if ($userId && $receiverId) {
        $data['messageTest1'] = test_message_saving('api/messages', $userId, $receiverId);
        $data['messageTest2'] = test_message_saving('api/saveMessage', $userId, $receiverId);
    }

    return view('debug/api_test', $data);
}

    
    /**
     * Method to test JSON input handling
     */
    public function testJsonInput()
    {
        $json = $this->request->getJSON(true);
        
        // Log the received JSON
        log_message('debug', 'testJsonInput received: ' . json_encode($json));
        
        return $this->respond([
            'success' => true,
            'received' => $json,
            'contentType' => $this->request->getHeaderLine('Content-Type'),
            'method' => $this->request->getMethod(),
        ]);
    }
}
