<?php
/**
 * Forum Project - API
 * Free Hosting Optimized
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

// Load functions
require_once '../includes/functions.php';

// Database connection
$db_config = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'u123456789_forum',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? ''
];

try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Route API requests
switch ($endpoint) {
    case 'categories':
        if ($method === 'GET') {
            $categories = get_categories($pdo);
            echo json_encode(['success' => true, 'data' => $categories]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case 'topics':
        if ($method === 'GET') {
            $category_id = $_GET['category_id'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $topics = get_topics($pdo, $category_id, $limit, $offset);
            echo json_encode(['success' => true, 'data' => $topics]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case 'topic':
        $topic_id = $_GET['id'] ?? null;
        if (!$topic_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Topic ID required']);
            break;
        }
        
        if ($method === 'GET') {
            $topic = get_topic_by_id($pdo, $topic_id);
            if ($topic) {
                // Update views
                update_topic_views($pdo, $topic_id);
                echo json_encode(['success' => true, 'data' => $topic]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Topic not found']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case 'replies':
        $topic_id = $_GET['topic_id'] ?? null;
        if (!$topic_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Topic ID required']);
            break;
        }
        
        if ($method === 'GET') {
            $replies = get_replies($pdo, $topic_id);
            echo json_encode(['success' => true, 'data' => $replies]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case 'stats':
        if ($method === 'GET') {
            $stats = [
                'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
                'topics' => $pdo->query("SELECT COUNT(*) FROM topics WHERE status = 'active'")->fetchColumn(),
                'replies' => $pdo->query("SELECT COUNT(*) FROM replies WHERE status = 'active'")->fetchColumn(),
                'categories' => $pdo->query("SELECT COUNT(*) FROM categories WHERE status = 'active'")->fetchColumn()
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}
?>