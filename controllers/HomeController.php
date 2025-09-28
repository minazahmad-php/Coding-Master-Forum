<?php
declare(strict_types=1);

/**
 * Modern Forum - Home Controller
 * Handles the main page and basic site functionality
 */

namespace Controllers;

use Core\Controller;
use Core\View;
use Models\User;
use Models\Post;
use Models\Category;

class HomeController extends Controller
{
    public function index(): void
    {
        try {
            // Get featured posts
            $featuredPosts = Post::featured(6);
            
            // Get latest posts
            $latestPosts = Post::all(10);
            
            // Get trending posts
            $trendingPosts = Post::trending(5);
            
            // Get categories
            $categories = Category::all();
            
            // Get statistics
            $stats = [
                'total_users' => User::count(),
                'total_posts' => Post::count(),
                'total_categories' => count($categories),
                'online_users' => $this->getOnlineUsersCount()
            ];
            
            $data = [
                'title' => 'Welcome to Modern Forum',
                'featured_posts' => $featuredPosts,
                'latest_posts' => $latestPosts,
                'trending_posts' => $trendingPosts,
                'categories' => $categories,
                'stats' => $stats,
                'meta_description' => 'Join our modern forum community for discussions, news, and connections.',
                'meta_keywords' => 'forum, community, discussion, modern, social'
            ];
            
            View::render('home/index', $data);
            
        } catch (Exception $e) {
            $this->logger->error('Home page error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            View::render('errors/500', [
                'title' => 'Server Error',
                'message' => 'Something went wrong. Please try again later.'
            ]);
        }
    }
    
    public function about(): void
    {
        $data = [
            'title' => 'About Us',
            'meta_description' => 'Learn more about our modern forum platform and community.',
            'meta_keywords' => 'about, forum, community, platform'
        ];
        
        View::render('home/about', $data);
    }
    
    public function contact(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleContactForm();
            return;
        }
        
        $data = [
            'title' => 'Contact Us',
            'meta_description' => 'Get in touch with us for support or inquiries.',
            'meta_keywords' => 'contact, support, help, inquiry'
        ];
        
        View::render('home/contact', $data);
    }
    
    private function handleContactForm(): void
    {
        try {
            // Validate CSRF token
            if (!$this->validateCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->session->setFlash('error', 'Invalid security token. Please try again.');
                $this->redirect('/contact');
                return;
            }
            
            // Validate form data
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');
            
            $errors = [];
            
            if (empty($name)) {
                $errors[] = 'Name is required';
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email is required';
            }
            
            if (empty($subject)) {
                $errors[] = 'Subject is required';
            }
            
            if (empty($message)) {
                $errors[] = 'Message is required';
            }
            
            if (!empty($errors)) {
                $this->session->setFlash('error', implode('<br>', $errors));
                $this->redirect('/contact');
                return;
            }
            
            // Send email
            $this->mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $this->mail->addAddress(ADMIN_EMAIL, 'Admin');
            $this->mail->setSubject('Contact Form: ' . $subject);
            $this->mail->setBody("
                <h2>Contact Form Submission</h2>
                <p><strong>Name:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <p><strong>Message:</strong></p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            ");
            
            if ($this->mail->send()) {
                $this->session->setFlash('success', 'Thank you for your message. We will get back to you soon!');
            } else {
                $this->session->setFlash('error', 'Failed to send message. Please try again.');
            }
            
        } catch (Exception $e) {
            $this->logger->error('Contact form error', [
                'error' => $e->getMessage(),
                'post_data' => $_POST
            ]);
            
            $this->session->setFlash('error', 'An error occurred. Please try again.');
        }
        
        $this->redirect('/contact');
    }
    
    private function getOnlineUsersCount(): int
    {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM sessions 
                WHERE last_activity > datetime('now', '-15 minutes')
            ");
            
            $result = $stmt->fetch();
            return (int) ($result['count'] ?? 0);
            
        } catch (Exception $e) {
            $this->logger->error('Failed to get online users count', [
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }
    
    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        
        try {
            $posts = Post::all(1000);
            $categories = Category::all();
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            
            // Home page
            $xml .= '<url>';
            $xml .= '<loc>' . APP_URL . '</loc>';
            $xml .= '<lastmod>' . date('Y-m-d') . '</lastmod>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>1.0</priority>';
            $xml .= '</url>';
            
            // Categories
            foreach ($categories as $category) {
                $xml .= '<url>';
                $xml .= '<loc>' . APP_URL . '/forum/' . $category->slug . '</loc>';
                $xml .= '<lastmod>' . date('Y-m-d') . '</lastmod>';
                $xml .= '<changefreq>weekly</changefreq>';
                $xml .= '<priority>0.8</priority>';
                $xml .= '</url>';
            }
            
            // Posts
            foreach ($posts as $post) {
                $xml .= '<url>';
                $xml .= '<loc>' . APP_URL . '/post/' . $post->slug . '</loc>';
                $xml .= '<lastmod>' . date('Y-m-d', strtotime($post->updated_at)) . '</lastmod>';
                $xml .= '<changefreq>monthly</changefreq>';
                $xml .= '<priority>0.6</priority>';
                $xml .= '</url>';
            }
            
            $xml .= '</urlset>';
            
            echo $xml;
            
        } catch (Exception $e) {
            $this->logger->error('Sitemap generation error', [
                'error' => $e->getMessage()
            ]);
            
            http_response_code(500);
            echo '<?xml version="1.0" encoding="UTF-8"?><error>Sitemap generation failed</error>';
        }
    }
    
    public function robots(): void
    {
        header('Content-Type: text/plain');
        
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /admin/\n";
        echo "Disallow: /api/\n";
        echo "Disallow: /storage/\n";
        echo "Disallow: /config/\n";
        echo "Disallow: /core/\n";
        echo "Disallow: /models/\n";
        echo "Disallow: /controllers/\n";
        echo "Disallow: /middleware/\n";
        echo "Disallow: /services/\n";
        echo "Disallow: /migrations/\n";
        echo "Disallow: /routes/\n";
        echo "Disallow: /views/\n";
        echo "Disallow: /vendor/\n";
        echo "Disallow: /node_modules/\n";
        echo "Disallow: /.env\n";
        echo "Disallow: /composer.json\n";
        echo "Disallow: /composer.lock\n";
        echo "Disallow: /package.json\n";
        echo "Disallow: /package-lock.json\n";
        echo "Disallow: /yarn.lock\n";
        echo "Disallow: /.git/\n";
        echo "Disallow: /.gitignore\n";
        echo "Disallow: /README.md\n";
        echo "Disallow: /LICENSE\n";
        echo "Disallow: /CHANGELOG.md\n";
        echo "Disallow: /CONTRIBUTING.md\n";
        echo "Disallow: /install.php\n";
        echo "Disallow: /setup-db.php\n";
        echo "Disallow: /test-db.php\n";
        echo "Disallow: /migrate.php\n";
        echo "Disallow: /db-setup.php\n";
        echo "\n";
        echo "Sitemap: " . APP_URL . "/sitemap.xml\n";
    }
    
    public function health(): void
    {
        header('Content-Type: application/json');
        
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => date('c'),
                'version' => '1.0.0',
                'environment' => APP_ENV,
                'database' => $this->checkDatabaseHealth(),
                'services' => [
                    'session' => $this->checkSessionHealth(),
                    'mail' => $this->checkMailHealth(),
                    'storage' => $this->checkStorageHealth()
                ]
            ];
            
            echo json_encode($health, JSON_PRETTY_PRINT);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'unhealthy',
                'timestamp' => date('c'),
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }
    
    private function checkDatabaseHealth(): array
    {
        try {
            $stmt = $this->db->query("SELECT 1");
            $stmt->fetch();
            
            return [
                'status' => 'healthy',
                'connection' => 'ok'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function checkSessionHealth(): array
    {
        try {
            $this->session->start();
            return ['status' => 'healthy'];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function checkMailHealth(): array
    {
        try {
            // Basic mail configuration check
            if (defined('MAIL_HOST') && !empty(MAIL_HOST)) {
                return ['status' => 'healthy'];
            }
            
            return [
                'status' => 'warning',
                'message' => 'Mail not configured'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function checkStorageHealth(): array
    {
        try {
            $directories = [
                STORAGE_PATH,
                UPLOADS_PATH,
                CACHE_PATH,
                LOG_PATH
            ];
            
            $status = 'healthy';
            $issues = [];
            
            foreach ($directories as $dir) {
                if (!is_dir($dir)) {
                    $status = 'unhealthy';
                    $issues[] = "Directory $dir does not exist";
                } elseif (!is_writable($dir)) {
                    $status = 'warning';
                    $issues[] = "Directory $dir is not writable";
                }
            }
            
            return [
                'status' => $status,
                'issues' => $issues
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
}