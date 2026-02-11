<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\GroupModel;
use App\Models\GroupMemberModel;
use App\Models\MessageModel;

class Chat extends BaseController
{
    protected $userModel;
    protected $groupModel;
    protected $groupMemberModel;
    protected $messageModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->groupModel = new GroupModel();
        $this->groupMemberModel = new GroupMemberModel();
        $this->messageModel = new MessageModel();
        
        helper('form');
    }
    
    public function index()
    {
        return view('chat/index');
    }
    
    public function users()
    {
        return view('chat/users');
    }
    
    public function groups()
    {
        return view('chat/groups');
    }
    
    public function directChat($userId)
    {
        // Check if user exists
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return redirect()->to('/chat')->with('error', 'User not found');
        }
        
        // Get messages between current user and selected user
        $currentUserId = session()->get('id');
        
        $messages = $this->messageModel
            ->select('messages.*, users.username, users.nickname, users.avatar')
            ->join('users', 'users.id = messages.sender_id')
            ->where('messages.is_group', 0)
            ->groupStart()
                ->groupStart()
                    ->where('messages.sender_id', $currentUserId)
                    ->where('messages.receiver_id', $userId)
                ->groupEnd()
                ->orGroupStart()
                    ->where('messages.sender_id', $userId)
                    ->where('messages.receiver_id', $currentUserId)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('messages.created_at', 'ASC')
            ->findAll();
        
        if (!session()->has('status')) {
            // Default to online if not set
            session()->set('status', 'online');
        }

        // Add user status to the view data
        $data['userStatus'] = session()->get('status');

        $data = [
            'otherUser' => $user,
            'messages' => $messages  // Now passing actual messages
        ];
        
        return view('chat/direct_chat', $data);
    }
    
    public function groupChat($groupId)
    {
        // Check if group exists
        $group = $this->groupModel->find($groupId);
        
        if (!$group) {
            return redirect()->to('/chat')->with('error', 'Group not found');
        }
        
        // Check if user is a member of the group
        $currentUserId = session()->get('id');
        $isMember = $this->groupMemberModel
            ->where('group_id', $groupId)
            ->where('user_id', $currentUserId)
            ->first();
        
        if (!$isMember) {
            return redirect()->to('/chat')->with('error', 'You are not a member of this group');
        }
        
        // Get member count
        $memberCount = $this->groupMemberModel
            ->where('group_id', $groupId)
            ->countAllResults();
        
        // Get group members
        $members = $this->userModel
            ->select('users.id, users.username, users.nickname, users.avatar, group_members.is_admin')
            ->join('group_members', 'users.id = group_members.user_id')
            ->where('group_members.group_id', $groupId)
            ->findAll();
        
        // Check if current user is admin
        $isAdmin = false;
        foreach ($members as $member) {
            if ($member['id'] == $currentUserId && $member['is_admin'] == 1) {
                $isAdmin = true;
                break;
            }
        }
        
        // Add member count to group data
        $group['member_count'] = $memberCount;
        
        $data = [
            'group' => $group,
            'members' => $members,
            'isAdmin' => $isAdmin,
            'messages' => []
        ];
        
        // Log the data being passed to the view
        log_message('debug', 'Group data: ' . json_encode($group));
        log_message('debug', 'Is admin: ' . ($isAdmin ? 'yes' : 'no'));
        
        return view('chat/group_chat', $data);
    }
    
    public function createGroup()
    {
        // Log the request data for debugging
        log_message('debug', 'Create group request: ' . json_encode($this->request->getPost()));
        log_message('debug', 'Files: ' . json_encode($_FILES));
        
        // Check if this is an AJAX request
        $isAjax = $this->request->isAJAX();
        
        // Validate request
        $rules = [
            'name' => 'required|min_length[3]|max_length[50]'
        ];
        
        if (!$this->validate($rules)) {
            log_message('error', 'Validation failed: ' . json_encode($this->validator->getErrors()));
            
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $this->validator->getErrors()
                ]);
            }
            
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        
        // Get form data
        $name = $this->request->getPost('name');
        $description = $this->request->getPost('description');
        
        // Handle image upload
        $image = 'default-group.png';
        $file = $this->request->getFile('image');
        
        if ($file && $file->isValid() && !$file->hasMoved()) {
            // FIX: Use getClientName() instead of getRandomName()
            $newName = $file->getClientName();
            
            // Create directory if it doesn't exist
            $uploadPath = ROOTPATH . 'public/uploads/groups';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            $file->move($uploadPath, $newName);
            $image = $newName;
            
            log_message('debug', 'Group image uploaded: ' . $newName);
        } else if ($file) {
            log_message('error', 'File upload error: ' . $file->getErrorString() . ' (' . $file->getError() . ')');
        }
        
        // Create group
        $groupData = [
            'name' => $name,
            'description' => $description,
            'image' => $image,
            'created_by' => session()->get('id'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $groupModel = new \App\Models\GroupModel();
        $groupId = $groupModel->insert($groupData);
        
        if (!$groupId) {
            log_message('error', 'Failed to create group: ' . json_encode($groupModel->errors()));
            
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to create group'
                ]);
            }
            
            return redirect()->back()->withInput()->with('error', 'Failed to create group');
        }
        
        log_message('debug', 'Group created with ID: ' . $groupId);
        
        // Add creator as admin
        $memberData = [
            'group_id' => $groupId,
            'user_id' => session()->get('id'),
            'is_admin' => 1,
            'joined_at' => date('Y-m-d H:i:s')
        ];
        
        $groupMemberModel = new \App\Models\GroupMemberModel();
        $groupMemberModel->insert($memberData);
        
        // Add selected members
        $members = $this->request->getPost('members');
        
        if (is_array($members)) {
            foreach ($members as $memberId) {
                $memberData = [
                    'group_id' => $groupId,
                    'user_id' => $memberId,
                    'is_admin' => 0,
                    'joined_at' => date('Y-m-d H:i:s')
                ];
                
                $groupMemberModel->insert($memberData);
                log_message('debug', 'Added member ' . $memberId . ' to group ' . $groupId);
            }
        }
        
        if ($isAjax) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Group created successfully',
                'groupId' => $groupId
            ]);
        }
        
        // Redirect to the group chat
        return redirect()->to('/chat/group/' . $groupId)->with('success', 'Group created successfully');
    }
    
    public function updateGroup($groupId)
    {
        // Check if group exists
        $group = $this->groupModel->find($groupId);
        
        if (!$group) {
            return redirect()->to('/chat')->with('error', 'Group not found');
        }
        
        // Check if user is admin
        $currentUserId = session()->get('id');
        $isAdmin = $this->groupMemberModel
            ->where('group_id', $groupId)
            ->where('user_id', $currentUserId)
            ->where('is_admin', 1)
            ->first();
        
        if (!$isAdmin) {
            return redirect()->to('/chat/group/' . $groupId)->with('error', 'Only admins can update the group');
        }
        
        // Validate request
        $rules = [
            'name' => 'required|min_length[3]|max_length[50]'
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        
        // Get form data
        $name = $this->request->getPost('name');
        $description = $this->request->getPost('description');
        
        // Handle image upload
        $image = $group['image'];
        $file = $this->request->getFile('image');
        
        if ($file && $file->isValid() && !$file->hasMoved()) {
            // FIX: Use getClientName() instead of getRandomName()
            $newName = $file->getClientName();
            
            // Create directory if it doesn't exist
            $uploadPath = ROOTPATH . 'public/uploads/groups';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            $file->move($uploadPath, $newName);
            $image = $newName;
        }
        
        // Update group data
        $groupData = [
            'name' => $name,
            'description' => $description,
            'image' => $image
        ];
        
        $this->groupModel->update($groupId, $groupData);
        
        return redirect()->to('/chat/group/' . $groupId)->with('success', 'Group updated successfully');
    }
    
    public function updateProfile()
    {
        // Log the request data for debugging
        log_message('debug', 'Profile update request: ' . json_encode($this->request->getPost()));
        log_message('debug', 'Files: ' . json_encode($_FILES));
        
        // Validate request
        $rules = [
            'nickname' => 'required|min_length[3]|max_length[50]',
            'email' => 'required|valid_email'
        ];
        
        if (!$this->validate($rules)) {
            log_message('error', 'Validation failed: ' . json_encode($this->validator->getErrors()));
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        
        // Get form data
        $nickname = $this->request->getPost('nickname');
        $email = $this->request->getPost('email');
        $status = $this->request->getPost('status') ?? session()->get('status') ?? 'online';
        
        // Handle avatar upload
        $avatar = session()->get('avatar');
        $file = $this->request->getFile('avatar');
        
        if ($file && $file->isValid() && !$file->hasMoved()) {
            // FIX: Use getClientName() instead of getRandomName()
            $newName = $file->getClientName();
            
            // Create directory if it doesn't exist
            $uploadPath = ROOTPATH . 'public/uploads/avatars';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            $file->move($uploadPath, $newName);
            $avatar = $newName;
        }
        
        // Update user
        $userData = [
            'nickname' => $nickname,
            'email' => $email,
            'avatar' => $avatar,
            'status' => $status
        ];
        
        $userId = session()->get('id');
        $result = $this->userModel->update($userId, $userData);
        
        if (!$result) {
            log_message('error', 'Failed to update user: ' . json_encode($this->userModel->errors()));
            return redirect()->back()->with('error', 'Failed to update profile');
        }
        
        // Update session data
        session()->set('nickname', $nickname);
        session()->set('email', $email);
        session()->set('avatar', $avatar);
        session()->set('status', $status);
        
        // Get the referer URL to redirect back to the same page
        $referer = $this->request->getServer('HTTP_REFERER');
        if (empty($referer)) {
            $referer = base_url('chat');
        }
        
        return redirect()->to($referer)->with('success', 'Profile updated successfully');
    }

    public function mark_as_read()
    {
        // Get request data
        $userId = $this->request->getPost('user_id');
        $chatId = $this->request->getPost('chat_id');
        $isGroup = $this->request->getPost('is_group');
        
        // Create model instance
        $messageModel = new \App\Models\MessageModel();
        
        if ($isGroup) {
            // Mark group messages as read
            $messageModel->markGroupMessagesAsRead($userId, $chatId);
        } else {
            // Mark direct messages as read
            $messageModel->markDirectMessagesAsRead($userId, $chatId);
        }
        
        return $this->response->setJSON(['success' => true]);
    }

    public function delete_message()
    {
        // Get request data
        $messageId = $this->request->getPost('message_id');
        $userId = $this->request->getPost('user_id');
        
        // Create model instance
        $messageModel = new \App\Models\MessageModel();
        
        // Check if user is the sender of this message
        $message = $messageModel->where('id', $messageId)->first();
        
        if (!$message || $message['sender_id'] != $userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'You can only delete your own messages'
            ]);
        }
        
        // Soft delete the message
        $messageModel->update($messageId, [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->response->setJSON(['success' => true]);



        
    }
}