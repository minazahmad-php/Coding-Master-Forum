<?php

namespace App\Controllers;

use App\Models\User;

/**
 * User Controller
 * Handles user-related operations
 */
class UserController extends BaseController
{
    /**
     * Show user profile
     */
    public function profile($id)
    {
        $userModel = new User();
        $user = $userModel->find($id);
        
        if (!$user) {
            $this->view->error(404, 'User not found');
        }
        
        $stats = $userModel->getStats($id);
        
        $data = [
            'title' => $user['display_name'] . ' - Profile',
            'user' => $user,
            'stats' => $stats
        ];
        
        echo $this->view->render('user/profile', $data);
    }

    /**
     * Show user settings
     */
    public function settings()
    {
        $this->requireAuth();
        
        $data = [
            'title' => 'User Settings',
            'user' => $this->getCurrentUser()
        ];
        
        echo $this->view->render('user/settings', $data);
    }

    /**
     * Update user settings
     */
    public function updateSettings()
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $user = $this->getCurrentUser();
        $userModel = new User();
        
        $data = [
            'display_name' => $this->sanitize($_POST['display_name'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? '')
        ];
        
        $result = $userModel->update($user['id'], $data);
        
        if ($result) {
            $this->redirectWithMessage('/settings', 'success', 'Settings updated successfully!');
        } else {
            $this->redirectWithMessage('/settings', 'error', 'Failed to update settings.');
        }
    }

    /**
     * Show change password form
     */
    public function changePassword()
    {
        $this->requireAuth();
        
        $data = [
            'title' => 'Change Password'
        ];
        
        echo $this->view->render('user/change_password', $data);
    }

    /**
     * Update password
     */
    public function updatePassword()
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $user = $this->getCurrentUser();
        $userModel = new User();
        
        if (!$userModel->verifyPassword($currentPassword, $user['password'])) {
            $this->redirectWithMessage('/change-password', 'error', 'Current password is incorrect.');
            return;
        }
        
        if ($newPassword !== $confirmPassword) {
            $this->redirectWithMessage('/change-password', 'error', 'New passwords do not match.');
            return;
        }
        
        $passwordErrors = $this->validatePassword($newPassword);
        if (!empty($passwordErrors)) {
            $this->redirectWithMessage('/change-password', 'error', implode('<br>', $passwordErrors));
            return;
        }
        
        $result = $userModel->update($user['id'], ['password' => $newPassword]);
        
        if ($result) {
            $this->redirectWithMessage('/settings', 'success', 'Password changed successfully!');
        } else {
            $this->redirectWithMessage('/change-password', 'error', 'Failed to change password.');
        }
    }
}