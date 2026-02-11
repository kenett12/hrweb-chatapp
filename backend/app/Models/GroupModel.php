<?php

namespace App\Models;

use CodeIgniter\Model;

class GroupModel extends Model
{
    protected $table = 'groups';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['name', 'description', 'image', 'created_by'];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    protected $validationRules = [
        'name' => 'required|min_length[3]',
        'description' => 'permit_empty',
        'image' => 'permit_empty',
        'created_by' => 'required|numeric'
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'Group name is required',
            'min_length' => 'Group name must be at least 3 characters long'
        ],
        'created_by' => [
            'required' => 'Group creator is required',
            'numeric' => 'Invalid creator ID'
        ]
    ];
}

