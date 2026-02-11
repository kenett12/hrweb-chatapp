<?php

namespace App\Models;

use CodeIgniter\Model;

class GroupMemberModel extends Model
{
    protected $table = 'group_members';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['group_id', 'user_id', 'is_admin', 'joined_at'];
    
    protected $useTimestamps = false;
    
    protected $validationRules = [
        'group_id' => 'required|numeric',
        'user_id' => 'required|numeric',
        'is_admin' => 'permit_empty|in_list[0,1]',
        'joined_at' => 'permit_empty'
    ];
    
    protected $validationMessages = [
        'group_id' => [
            'required' => 'Group ID is required',
            'numeric' => 'Invalid group ID'
        ],
        'user_id' => [
            'required' => 'User ID is required',
            'numeric' => 'Invalid user ID'
        ],
        'is_admin' => [
            'in_list' => 'Invalid admin status'
        ]
    ];
}

