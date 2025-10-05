<?php

namespace App\Controllers;

/**
 * Notification Controller
 * Handles user notifications
 */
class NotificationController extends BaseController
{
    /**
     * Show notifications
     */
    public function index()
    {
        $this->requireAuth();
        
        $userId = $this->getCurrentUser()['id'];
        
        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
        $notifications = $this->db->fetchAll($sql, [$userId]);
        
        $data = [
            'title' => 'Notifications',
            'notifications' => $notifications
        ];
        
        echo $this->view->render('user/notifications', $data);
    }
}