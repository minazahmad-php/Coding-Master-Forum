<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

/**
 * API Statistics Controller
 */
class StatisticsApiController extends BaseController
{
    /**
     * Get general statistics
     */
    public function index()
    {
        $stats = [
            'total_users' => $this->getTotalUsers(),
            'total_threads' => $this->getTotalThreads(),
            'total_posts' => $this->getTotalPosts(),
            'total_forums' => $this->getTotalForums(),
            'online_users' => $this->getOnlineUserCount()
        ];
        
        $this->success('Statistics retrieved', ['statistics' => $stats]);
    }

    /**
     * Get forum statistics
     */
    public function forums()
    {
        $sql = "SELECT f.*, 
                    (SELECT COUNT(*) FROM threads WHERE forum_id = f.id) as thread_count,
                    (SELECT COUNT(*) FROM posts p 
                     JOIN threads t ON p.thread_id = t.id 
                     WHERE t.forum_id = f.id) as post_count
                FROM forums f 
                WHERE f.status = 'active'
                ORDER BY f.sort_order ASC";
        
        $forums = $this->db->fetchAll($sql);
        
        $this->success('Forum statistics retrieved', ['forums' => $forums]);
    }

    /**
     * Get user statistics
     */
    public function users()
    {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
                    COUNT(CASE WHEN status = 'banned' THEN 1 END) as banned_users,
                    COUNT(CASE WHEN last_activity > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as users_last_24h,
                    COUNT(CASE WHEN last_activity > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as users_last_7d,
                    COUNT(CASE WHEN last_activity > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as users_last_30d
                FROM users";
        
        $stats = $this->db->fetch($sql);
        
        $this->success('User statistics retrieved', ['statistics' => $stats]);
    }

    /**
     * Get total users count
     */
    private function getTotalUsers()
    {
        $sql = "SELECT COUNT(*) as count FROM users";
        $result = $this->db->fetch($sql);
        return $result['count'];
    }

    /**
     * Get total threads count
     */
    private function getTotalThreads()
    {
        $sql = "SELECT COUNT(*) as count FROM threads";
        $result = $this->db->fetch($sql);
        return $result['count'];
    }

    /**
     * Get total posts count
     */
    private function getTotalPosts()
    {
        $sql = "SELECT COUNT(*) as count FROM posts";
        $result = $this->db->fetch($sql);
        return $result['count'];
    }

    /**
     * Get total forums count
     */
    private function getTotalForums()
    {
        $sql = "SELECT COUNT(*) as count FROM forums";
        $result = $this->db->fetch($sql);
        return $result['count'];
    }

    /**
     * Get online users count
     */
    private function getOnlineUserCount()
    {
        $sql = "SELECT COUNT(*) as count FROM users 
                WHERE last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        $result = $this->db->fetch($sql);
        return $result['count'];
    }
}