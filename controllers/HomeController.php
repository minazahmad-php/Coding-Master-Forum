<?php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Models\Forum;
use Models\Thread;
use Models\Post;
use Models\User;
use Models\Notification;

class HomeController {
    private Database $db;
    private Forum $forumModel;
    private Thread $threadModel;
    private Post $postModel;
    private User $userModel;
    private Notification $notificationModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->forumModel = new Forum();
        $this->threadModel = new Thread();
        $this->postModel = new Post();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
    }
    
    public function index(): void {
        // Get featured content
        $featuredThreads = $this->threadModel->getFeaturedThreads(5);
        $latestThreads = $this->threadModel->getLatestThreads(10);
        $popularThreads = $this->threadModel->getPopularThreads(10);
        $topUsers = $this->userModel->getTopUsers(10);
        $stats = $this->getSiteStats();
        
        // Get announcements
        $announcements = $this->threadModel->getAnnouncements(3);
        
        // Get trending topics
        $trendingTopics = $this->getTrendingTopics();
        
        // Get recent activity
        $recentActivity = $this->getRecentActivity();
        
        $data = [
            'featured_threads' => $featuredThreads,
            'latest_threads' => $latestThreads,
            'popular_threads' => $popularThreads,
            'top_users' => $topUsers,
            'stats' => $stats,
            'announcements' => $announcements,
            'trending_topics' => $trendingTopics,
            'recent_activity' => $recentActivity,
            'page_title' => 'Welcome to ' . SITE_NAME,
            'meta_description' => SITE_DESCRIPTION
        ];
        
        include VIEWS_PATH . '/home.php';
    }
    
    public function about(): void {
        $data = [
            'page_title' => 'About Us - ' . SITE_NAME,
            'meta_description' => 'Learn more about our community and mission.'
        ];
        
        include VIEWS_PATH . '/about.php';
    }
    
    public function contact(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->sendContact();
            return;
        }
        
        $data = [
            'page_title' => 'Contact Us - ' . SITE_NAME,
            'meta_description' => 'Get in touch with us for any questions or support.'
        ];
        
        include VIEWS_PATH . '/contact.php';
    }
    
    public function sendContact(): void {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        
        $errors = [];
        
        if (empty($name)) $errors[] = 'Name is required';
        if (empty($email) || !validateEmail($email)) $errors[] = 'Valid email is required';
        if (empty($subject)) $errors[] = 'Subject is required';
        if (empty($message)) $errors[] = 'Message is required';
        
        if (empty($errors)) {
            // Save contact message
            $this->db->insert('contact_messages', [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Send email notification to admin
            if (class_exists('Services\\EmailService')) {
                $emailService = new \Services\EmailService();
                $emailService->sendContactNotification($name, $email, $subject, $message);
            }
            
            $_SESSION['success'] = 'Thank you for your message. We will get back to you soon!';
            redirect('/contact');
        } else {
            $_SESSION['errors'] = $errors;
            redirect('/contact');
        }
    }
    
    public function privacy(): void {
        $data = [
            'page_title' => 'Privacy Policy - ' . SITE_NAME,
            'meta_description' => 'Our privacy policy and how we protect your data.'
        ];
        
        include VIEWS_PATH . '/privacy.php';
    }
    
    public function terms(): void {
        $data = [
            'page_title' => 'Terms of Service - ' . SITE_NAME,
            'meta_description' => 'Terms and conditions for using our platform.'
        ];
        
        include VIEWS_PATH . '/terms.php';
    }
    
    public function help(): void {
        $data = [
            'page_title' => 'Help Center - ' . SITE_NAME,
            'meta_description' => 'Get help and support for using our platform.'
        ];
        
        include VIEWS_PATH . '/help.php';
    }
    
    public function faq(): void {
        $faqs = $this->db->fetchAll("SELECT * FROM faqs WHERE status = 'active' ORDER BY sort_order ASC");
        
        $data = [
            'faqs' => $faqs,
            'page_title' => 'Frequently Asked Questions - ' . SITE_NAME,
            'meta_description' => 'Find answers to common questions about our platform.'
        ];
        
        include VIEWS_PATH . '/faq.php';
    }
    
    private function getSiteStats(): array {
        return [
            'total_users' => $this->db->count('users', 'status = "active"'),
            'total_threads' => $this->db->count('threads'),
            'total_posts' => $this->db->count('posts'),
            'total_forums' => $this->db->count('forums'),
            'online_users' => $this->getOnlineUsersCount(),
            'new_today' => $this->getNewTodayCount()
        ];
    }
    
    private function getOnlineUsersCount(): int {
        $onlineTime = date('Y-m-d H:i:s', time() - 900); // 15 minutes ago
        return $this->db->count('users', 'last_activity > :online_time', ['online_time' => $onlineTime]);
    }
    
    private function getNewTodayCount(): array {
        $today = date('Y-m-d');
        return [
            'users' => $this->db->count('users', 'DATE(created_at) = :today', ['today' => $today]),
            'threads' => $this->db->count('threads', 'DATE(created_at) = :today', ['today' => $today]),
            'posts' => $this->db->count('posts', 'DATE(created_at) = :today', ['today' => $today])
        ];
    }
    
    private function getTrendingTopics(): array {
        $weekAgo = date('Y-m-d H:i:s', time() - (7 * 24 * 3600));
        
        return $this->db->fetchAll("
            SELECT t.*, f.name as forum_name, f.slug as forum_slug,
                   COUNT(p.id) as post_count, u.username, u.avatar
            FROM threads t
            JOIN forums f ON t.forum_id = f.id
            JOIN users u ON t.user_id = u.id
            LEFT JOIN posts p ON t.id = p.thread_id AND p.created_at > :week_ago
            WHERE t.created_at > :week_ago
            GROUP BY t.id
            ORDER BY post_count DESC, t.views DESC
            LIMIT 10
        ", ['week_ago' => $weekAgo]);
    }
    
    private function getRecentActivity(): array {
        return $this->db->fetchAll("
            SELECT 'thread' as type, t.id, t.title, t.created_at, u.username, u.avatar, f.name as forum_name
            FROM threads t
            JOIN users u ON t.user_id = u.id
            JOIN forums f ON t.forum_id = f.id
            WHERE t.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            
            UNION ALL
            
            SELECT 'post' as type, p.id, CONCAT('Reply to: ', t.title) as title, p.created_at, u.username, u.avatar, f.name as forum_name
            FROM posts p
            JOIN threads t ON p.thread_id = t.id
            JOIN users u ON p.user_id = u.id
            JOIN forums f ON t.forum_id = f.id
            WHERE p.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            
            ORDER BY created_at DESC
            LIMIT 20
        ");
    }
}
?>