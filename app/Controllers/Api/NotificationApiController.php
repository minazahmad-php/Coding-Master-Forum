<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

/**
 * API Notification Controller
 */
class NotificationApiController extends BaseController
{
    /**
     * Get user notifications
     */
    public function index()
    {
        $this->requireAuth();
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $userId = $this->getCurrentUser()['id'];
        
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $notifications = $this->db->fetchAll($sql, [$userId, $perPage, $offset]);
        
        $this->success('Notifications retrieved', ['notifications' => $notifications]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $this->requireAuth();
        
        $userId = $this->getCurrentUser()['id'];
        
        $result = $this->db->update(
            'notifications',
            ['is_read' => 1],
            'id = ? AND user_id = ?',
            [$id, $userId]
        );
        
        if ($result) {
            $this->success('Notification marked as read');
        } else {
            $this->error('Failed to mark notification as read', 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $this->requireAuth();
        
        $userId = $this->getCurrentUser()['id'];
        
        $result = $this->db->update(
            'notifications',
            ['is_read' => 1],
            'user_id = ?',
            [$userId]
        );
        
        if ($result) {
            $this->success('All notifications marked as read');
        } else {
            $this->error('Failed to mark notifications as read', 500);
        }
    }
}