<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Forum;
use App\Models\Thread;
use App\Models\Post;

/**
 * Admin Controller
 * Handles admin panel functionality
 */
class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    /**
     * Admin dashboard
     */
    public function dashboard()
    {
        $userModel = new User();
        $forumModel = new Forum();
        $threadModel = new Thread();
        $postModel = new Post();
        
        $stats = [
            'total_users' => $userModel->count(),
            'total_forums' => $this->getTotalForums(),
            'total_threads' => $this->getTotalThreads(),
            'total_posts' => $this->getTotalPosts(),
            'online_users' => $this->getOnlineUserCount(),
            'recent_users' => $userModel->getAll(1, 5),
            'recent_threads' => $threadModel->getRecent(5),
            'recent_posts' => $postModel->getRecent(5)
        ];
        
        $data = [
            'title' => 'Admin Dashboard',
            'stats' => $stats
        ];
        
        echo $this->view->render('admin/dashboard', $data);
    }

    /**
     * User management
     */
    public function users()
    {
        $userModel = new User();
        $page = (int)($_GET['page'] ?? 1);
        
        $users = $userModel->getAll($page, 20);
        $totalUsers = $userModel->count();
        
        $data = [
            'title' => 'User Management',
            'users' => $users,
            'total' => $totalUsers,
            'page' => $page
        ];
        
        echo $this->view->render('admin/users/index', $data);
    }

    /**
     * Forum management
     */
    public function forums()
    {
        $forumModel = new Forum();
        $forums = $forumModel->getAll();
        
        $data = [
            'title' => 'Forum Management',
            'forums' => $forums
        ];
        
        echo $this->view->render('admin/forums/index', $data);
    }

    /**
     * Settings management
     */
    public function settings()
    {
        $data = [
            'title' => 'Site Settings'
        ];
        
        echo $this->view->render('admin/settings/general', $data);
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