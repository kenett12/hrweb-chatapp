<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    // ADDED 'role' here. Without this, the role 'tsr' is ignored during insert.
    protected $allowedFields = ['username', 'email', 'password', 'nickname', 'avatar', 'status', 'role', 'last_active'];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    protected $validationRules = [
        'username' => 'required|min_length[4]|is_unique[users.username,id,{id}]',
        'email'    => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[6]',
        'role'     => 'required' // Ensure role is always set
    ];
    
    protected $validationMessages = [
        'username' => [
            'required' => 'Username is required',
            'is_unique' => 'Username is already taken'
        ],
        'email' => [
            'required' => 'Email is required',
            'is_unique' => 'Email is already registered'
        ]
    ];

    public function updateStatus($userId, $status)
    {
        return $this->builder()
            ->where('id', $userId)
            ->update(['status' => $status]);
    }
}