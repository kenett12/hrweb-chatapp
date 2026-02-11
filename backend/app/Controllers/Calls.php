<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel; // Ensure you use your actual User Model

class Calls extends BaseController
{
    public function audio($receiverId)
    {
        // 1. Check Login
        if (!session()->get('id')) {
            return "Please login first.";
        }

        // 2. Get Receiver Info
        $userModel = new UserModel();
        $receiver = $userModel->find($receiverId);

        if (!$receiver) {
            return "User not found.";
        }

        // 3. Pass data to the popup window
        $data = [
            'receiver' => $receiver,
            'user_id' => session()->get('id'),
            'base_url' => base_url(),
            'call_type' => 'audio'
        ];

        return view('calls/audio', $data);
    }

    public function video($receiverId)
    {
        // Same logic for video (we will implement the view later)
        return $this->audio($receiverId); 
    }
}