<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class UserController extends Controller
{
    public function updateStatus()
    {
        $model = new UserModel();
        
        // Use this to debug: if you see this in the Network tab, the route is WORKING
        // return $this->response->setJSON(['debug' => 'Reached the controller']);

        $userId = $this->request->getPost('user_id');
        $status = $this->request->getPost('status');

        if ($model->updateStatus($userId, $status)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false], 500);
    }
}