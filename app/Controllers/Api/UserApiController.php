<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\User;

/**
 * API User Controller
 */
class UserApiController extends BaseController
{
    /**
     * Get all users
     */
    public function index()
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        $userModel = new User();
        $users = $userModel->getAll($page, $perPage);
        
        // Remove sensitive data
        foreach ($users as &$user) {
            unset($user['password']);
        }
        
        $this->success('Users retrieved', ['users' => $users]);
    }

    /**
     * Get specific user
     */
    public function show($id)
    {
        $userModel = new User();
        $user = $userModel->find($id);
        
        if (!$user) {
            $this->error('User not found', 404);
        }
        
        // Remove sensitive data
        unset($user['password']);
        
        $this->success('User retrieved', ['user' => $user]);
    }

    /**
     * Update user
     */
    public function update($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        // Users can only update their own profile unless they're admin
        $currentUser = $this->getCurrentUser();
        if ($currentUser['id'] != $id && !$this->isAdmin()) {
            $this->error('Unauthorized', 403);
        }
        
        $userModel = new User();
        $user = $userModel->find($id);
        
        if (!$user) {
            $this->error('User not found', 404);
        }
        
        $data = [];
        
        if (isset($_POST['display_name'])) {
            $data['display_name'] = $this->sanitize($_POST['display_name']);
        }
        
        if (isset($_POST['email'])) {
            $email = $this->sanitize($_POST['email']);
            if ($this->validateEmail($email)) {
                $data['email'] = $email;
            } else {
                $this->error('Invalid email format', 400);
            }
        }
        
        if (isset($_POST['password'])) {
            $password = $_POST['password'];
            $passwordErrors = $this->validatePassword($password);
            if (empty($passwordErrors)) {
                $data['password'] = $password;
            } else {
                $this->error(implode(', ', $passwordErrors), 400);
            }
        }
        
        if (empty($data)) {
            $this->error('No data to update', 400);
        }
        
        $result = $userModel->update($id, $data);
        
        if ($result) {
            $this->success('User updated successfully');
        } else {
            $this->error('Failed to update user', 500);
        }
    }

    /**
     * Get user posts
     */
    public function posts($id)
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        $postModel = new \App\Models\Post();
        $posts = $postModel->getByUser($id, $page, $perPage);
        
        $this->success('User posts retrieved', ['posts' => $posts]);
    }

    /**
     * Get user threads
     */
    public function threads($id)
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        $threadModel = new \App\Models\Thread();
        $threads = $threadModel->getByUser($id, $page, $perPage);
        
        $this->success('User threads retrieved', ['threads' => $threads]);
    }
}