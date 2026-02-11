<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Files\File;

class Auth extends BaseController
{
    protected $userModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
        helper(['form', 'url']); // Load helpers to ensure functions work
    }
    
    public function login()
    {
        if (session()->get('isLoggedIn')) {
            return $this->redirectUserByRole(session()->get('role'));
        }
        return view('auth/login');
    }
    
    public function attemptLogin()
    {
        // 1. Update Rules to reflect "Username OR Email"
        $rules = [
            'username' => [
                'label'  => 'Username or Email',
                'rules'  => 'required',
                'errors' => [
                    'required' => 'Please enter your username or email address.'
                ]
            ],
            'password' => [
                'rules'  => 'required',
                'errors' => [
                    'required' => 'Please enter your password.'
                ]
            ]
        ];
        
        // 2. Validate Inputs
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        
        try {
            // Get the input (which could be a username OR an email)
            $loginInput = $this->request->getPost('username');
            $password   = $this->request->getPost('password');
            
            // 3. UPDATED QUERY: Check both 'username' AND 'email' columns
            $user = $this->userModel
                         ->groupStart()
                             ->where('username', $loginInput)
                             ->orWhere('email', $loginInput)
                         ->groupEnd()
                         ->first();
            
            // 4. Verify User and Password
            if (!$user || !password_verify($password, $user['password'])) {
                return redirect()->back()->withInput()->with('error', 'Incorrect username/email or password.');
            }
            
            // 5. Set Session
            $userData = [
                'id'         => $user['id'],
                'username'   => $user['username'],
                'nickname'   => $user['nickname'] ?? $user['username'],
                'avatar'     => $user['avatar'] ?? 'default-avatar.png',
                'email'      => $user['email'],
                'role'       => $user['role'] ?? 'user',
                'status'     => 'online',
                'isLoggedIn' => true
            ];
            
            session()->set($userData);

            // 6. Update Status
            $this->userModel->update($user['id'], [
                'status'      => 'online',
                'last_active' => date('Y-m-d H:i:s')
            ]);
            
            return $this->redirectUserByRole($userData['role']);

        } catch (\Exception $e) {
            log_message('error', '[Auth::attemptLogin] ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'A system error occurred. Please try again later.');
        }
    }

    private function redirectUserByRole($role)
    {
        switch ($role) {
            case 'superadmin':
                return redirect()->to('/sa/dashboard');
            case 'tsr':
                return redirect()->to('/tsr/dashboard');
            case 'user':
            default:
                return redirect()->to('/chat');
        }
    }
    
    public function register()
    {
        return view('auth/register');
    }
    
    public function attemptRegister()
    {
        // 1. Define Robust Rules with Custom, User-Friendly Messages
        $rules = [
            'username' => [
                'rules'  => 'required|min_length[4]|max_length[20]|is_unique[users.username]',
                'errors' => [
                    'required'   => 'A username is required.',
                    'min_length' => 'Username must be at least 4 characters long.',
                    'is_unique'  => 'This username is already taken. Please choose another one.'
                ]
            ],
            'email' => [
                'rules'  => 'required|valid_email|is_unique[users.email]',
                'errors' => [
                    'required'    => 'We need your email address to create an account.',
                    'valid_email' => 'Please enter a valid email address.',
                    'is_unique'   => 'This email is already registered. Please log in instead.'
                ]
            ],
            // UPDATED: Strict Password Rules
            'password' => [
                // min_length[8]: Enforce 8 chars
                // regex_match: checks for 1 uppercase, 1 lowercase, 1 number, and 1 special char
                'rules'  => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/]',
                'errors' => [
                    'required'    => 'Please create a password.',
                    'min_length'  => 'Password must be at least 8 characters long.',
                    'regex_match' => 'Password must contain at least one uppercase letter, one number, and one special character (@$!%*?&).'
                ]
            ],
            'confirm_password' => [
                'rules'  => 'required|matches[password]',
                'errors' => [
                    'required' => 'Please confirm your password.',
                    'matches'  => 'The password confirmation does not match the password entered above.'
                ]
            ],
            'avatar' => [
                'rules' => 'permit_empty|is_image[avatar]|mime_in[avatar,image/jpg,image/jpeg,image/png,image/webp]|max_size[avatar,2048]',
                'errors' => [
                    'is_image' => 'The uploaded file must be a valid image.',
                    'mime_in'  => 'Only JPG, PNG, or WEBP images are allowed.',
                    'max_size' => 'The image size must not exceed 2MB.'
                ]
            ]
        ];
        
        // 2. Run Validation
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        
        try {
            $avatar = 'default-avatar.png';
            
            // 3. Handle File Upload safely
            $file = $this->request->getFile('avatar');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                // Ensure the directory exists
                if (!is_dir(ROOTPATH . 'public/uploads/avatars')) {
                    mkdir(ROOTPATH . 'public/uploads/avatars', 0777, true);
                }
                $file->move(ROOTPATH . 'public/uploads/avatars', $newName);
                $avatar = $newName;
            }
            
            // 4. Save User
            $save = $this->userModel->save([
                'username'   => $this->request->getPost('username'),
                'email'      => $this->request->getPost('email'),
                'password'   => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
                'nickname'   => $this->request->getPost('username'),
                'avatar'     => $avatar,
                'role'       => 'user',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$save) {
                // Determine if it was a DB error that didn't throw an exception
                return redirect()->back()->withInput()->with('error', 'Failed to create account. Please try again.');
            }
            
            return redirect()->to('/login')->with('success', 'Registration successful! You can now log in.');

        } catch (\Exception $e) {
            // Log the actual error for the developer
            log_message('error', '[Auth::attemptRegister] ' . $e->getMessage());
            
            // Show a friendly message to the user
            return redirect()->back()->withInput()->with('error', 'An unexpected error occurred during registration. Please try again later.');
        }
    }
    
    public function logout()
    {
        try {
            if (session()->get('id')) {
                $this->userModel->update(session()->get('id'), [
                    'status' => 'offline'
                ]);
            }
        } catch (\Exception $e) {
            // Ignore logout errors, just destroy session
            log_message('error', '[Auth::logout] ' . $e->getMessage());
        }
        
        session()->destroy();
        return redirect()->to('/login');
    }
}