<?php

namespace App\Controllers;

use App\Models\Forum;
use App\Models\Thread;
use App\Models\Post;
use App\Models\User;

/**
 * Home Controller
 * Handles homepage and general site functionality
 */
class HomeController extends BaseController
{
    /**
     * Show homepage
     */
    public function index()
    {
        $forumModel = new Forum();
        $threadModel = new Thread();
        $postModel = new Post();
        $userModel = new User();
        
        // Get forums
        $forums = $forumModel->getAll();
        
        // Get recent threads
        $recentThreads = $threadModel->getRecent(10);
        
        // Get recent posts
        $recentPosts = $postModel->getRecent(10);
        
        // Get online users
        $onlineUsers = $userModel->getOnlineUsers(20);
        
        // Get forum statistics
        $stats = [
            'total_forums' => count($forums),
            'total_threads' => $this->getTotalThreads(),
            'total_posts' => $this->getTotalPosts(),
            'total_users' => $userModel->count(),
            'online_users' => count($onlineUsers)
        ];
        
        $data = [
            'forums' => $forums,
            'recent_threads' => $recentThreads,
            'recent_posts' => $recentPosts,
            'online_users' => $onlineUsers,
            'stats' => $stats
        ];
        
        echo $this->view->render('home', $data);
    }

    /**
     * Show forum list
     */
    public function forums()
    {
        $forumModel = new Forum();
        $forums = $forumModel->getAll();
        
        $data = [
            'forums' => $forums
        ];
        
        echo $this->view->render('forum_list', $data);
    }

    /**
     * Show forum details
     */
    public function forum($id)
    {
        $forumModel = new Forum();
        $forum = $forumModel->getWithThreads($id);
        
        if (!$forum) {
            $this->view->error(404, 'Forum not found');
        }
        
        $data = [
            'forum' => $forum
        ];
        
        echo $this->view->render('forum_view', $data);
    }

    /**
     * Show thread details
     */
    public function thread($id)
    {
        $threadModel = new Thread();
        $thread = $threadModel->getWithPosts($id);
        
        if (!$thread) {
            $this->view->error(404, 'Thread not found');
        }
        
        // Increment view count
        $threadModel->incrementViews($id);
        
        $data = [
            'thread' => $thread
        ];
        
        echo $this->view->render('thread_view', $data);
    }

    /**
     * Show search results
     */
    public function search()
    {
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'all';
        $page = (int)($_GET['page'] ?? 1);
        
        if (empty($query)) {
            echo $this->view->render('search_results', [
                'query' => $query,
                'type' => $type,
                'results' => [],
                'total' => 0
            ]);
            return;
        }
        
        $results = [];
        $total = 0;
        
        if ($type === 'all' || $type === 'threads') {
            $threadModel = new Thread();
            $threadResults = $threadModel->search($query, $page, 20);
            $results['threads'] = $threadResults;
            $total += count($threadResults);
        }
        
        if ($type === 'all' || $type === 'posts') {
            $postModel = new Post();
            $postResults = $postModel->search($query, $page, 20);
            $results['posts'] = $postResults;
            $total += count($postResults);
        }
        
        if ($type === 'all' || $type === 'users') {
            $userModel = new User();
            $userResults = $userModel->search($query, $page, 20);
            $results['users'] = $userResults;
            $total += count($userResults);
        }
        
        $data = [
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'total' => $total
        ];
        
        echo $this->view->render('search_results', $data);
    }

    /**
     * Show advanced search form
     */
    public function advancedSearch()
    {
        $forumModel = new Forum();
        $forums = $forumModel->getAll();
        
        $data = [
            'forums' => $forums
        ];
        
        echo $this->view->render('advanced_search', $data);
    }

    /**
     * Show members list
     */
    public function members()
    {
        $userModel = new User();
        $page = (int)($_GET['page'] ?? 1);
        
        $users = $userModel->getAll($page, 20);
        $totalUsers = $userModel->count();
        
        $data = [
            'users' => $users,
            'total' => $totalUsers,
            'page' => $page
        ];
        
        echo $this->view->render('members', $data);
    }

    /**
     * Show online users
     */
    public function onlineUsers()
    {
        $userModel = new User();
        $onlineUsers = $userModel->getOnlineUsers(100);
        
        $data = [
            'online_users' => $onlineUsers
        ];
        
        echo $this->view->render('online_users', $data);
    }

    /**
     * Show statistics
     */
    public function statistics()
    {
        $stats = [
            'total_users' => $this->getTotalUsers(),
            'total_threads' => $this->getTotalThreads(),
            'total_posts' => $this->getTotalPosts(),
            'total_forums' => $this->getTotalForums(),
            'online_users' => $this->getOnlineUserCount()
        ];
        
        $data = [
            'stats' => $stats
        ];
        
        echo $this->view->render('statistics', $data);
    }

    /**
     * Show rules page
     */
    public function rules()
    {
        echo $this->view->render('rules');
    }

    /**
     * Show contact page
     */
    public function contact()
    {
        if ($_POST) {
            $this->handleContactForm();
            return;
        }
        
        echo $this->view->render('contact');
    }

    /**
     * Handle contact form submission
     */
    private function handleContactForm()
    {
        $this->validateCsrf();
        
        $name = $this->sanitize($_POST['name'] ?? '');
        $email = $this->sanitize($_POST['email'] ?? '');
        $subject = $this->sanitize($_POST['subject'] ?? '');
        $message = $this->sanitize($_POST['message'] ?? '');
        
        $errors = $this->validateRequired(['name', 'email', 'subject', 'message'], $_POST);
        
        if (!$this->validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($errors)) {
            // Send email notification
            $this->sendContactEmail($name, $email, $subject, $message);
            
            $this->redirectWithMessage('/', 'success', 'Your message has been sent successfully.');
        } else {
            $this->redirectWithMessage('/contact', 'error', implode('<br>', $errors));
        }
    }

    /**
     * Send contact email
     */
    private function sendContactEmail($name, $email, $subject, $message)
    {
        // Implementation for sending contact email
        $this->logActivity('Contact form submitted', [
            'name' => $name,
            'email' => $email,
            'subject' => $subject
        ]);
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
     * Get total users count
     */
    private function getTotalUsers()
    {
        $sql = "SELECT COUNT(*) as count FROM users";
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