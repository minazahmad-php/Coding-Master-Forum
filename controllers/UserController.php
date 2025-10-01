<?php

//controllers/UserController.php

class UserController {
    private $userModel;
    private $auth;
    
    public function __construct() {
        $this->userModel = new User();
        $this->auth = new Auth();
        Middleware::auth();
    }
    
    public function dashboard() {
        $user = Auth::getUser();
        
        $threads = $this->threadModel->findByUser($user['id'], 5, 0);
        $posts = $this->postModel->findByUser($user['id'], 5, 0);
        
        include VIEWS_PATH . '/user/dashboard.php';
    }
    
    public function profile() {
        $user = Auth::getUser();
        include VIEWS_PATH . '/user/profile.php';
    }
    
    public function updateProfile() {
        $user = Auth::getUser();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName = sanitize($_POST['full_name']);
            $bio = sanitize($_POST['bio']);
            $location = sanitize($_POST['location']);
            $website = sanitize($_POST['website']);
            $birthday = sanitize($_POST['birthday']);
            $gender = sanitize($_POST['gender']);
            
            $data = [
                'full_name' => $fullName,
                'bio' => $bio,
                'location' => $location,
                'website' => $website,
                'birthday' => $birthday,
                'gender' => $gender
            ];
            
            // Handle avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $avatar = $this->handleAvatarUpload($_FILES['avatar']);
                if ($avatar) {
                    $data['avatar'] = $avatar;
                }
            }
            
            $success = $this->userModel->update($user['id'], $data);
            
            if ($success) {
                // Update session user data
                $updatedUser = $this->userModel->findById($user['id']);
                unset($updatedUser['password']);
                $_SESSION['user'] = $updatedUser;
                
                $_SESSION['success'] = 'Profile updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update profile';
            }
            
            header('Location: /user/profile');
            exit;
        }
    }
    
    public function settings() {
        $user = Auth::getUser();
        include VIEWS_PATH . '/user/settings.php';
    }
    
    public function updateSettings() {
        $user = Auth::getUser();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            $errors = [];
            
            // Verify current password
            $userData = $this->userModel->findById($user['id']);
            if (!password_verify($currentPassword, $userData['password'])) {
                $errors[] = 'Current password is incorrect';
            }
            
            if (empty($newPassword)) {
                $errors[] = 'New password is required';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters';
            }
            
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match';
            }
            
            if (empty($errors)) {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $success = $this->userModel->update($user['id'], ['password' => $hash]);
                
                if ($success) {
                    $_SESSION['success'] = 'Password updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update password';
                }
            } else {
                $_SESSION['error'] = implode('<br>', $errors);
            }
            
            header('Location: /user/settings');
            exit;
        }
    }
    
    private function handleAvatarUpload($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error'] = 'Only JPG, PNG and GIF images are allowed';
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            $_SESSION['error'] = 'Image size must be less than 2MB';
            return false;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $destination = UPLOADS_PATH . '/avatars/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $_SESSION['error'] = 'Failed to upload avatar';
            return false;
        }
        
        return $filename;
    }
}
?>