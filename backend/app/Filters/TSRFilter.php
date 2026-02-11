<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class TSRFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Please log in to access this area.');
        }

        $userRole = session()->get('role');
        if ($userRole !== 'tsr' && $userRole !== 'admin') {
            // Log unauthorized access attempt
            log_message('warning', 'Unauthorized TSR access attempt by user ID: ' . session()->get('id'));
            
            return redirect()->to('/chat')->with('error', 'Access denied. You do not have permission to access the TSR dashboard.');
        }
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
