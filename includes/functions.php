<?php
/**
 * Forum Project - Helper Functions
 * Free Hosting Optimized
 */

// Security functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_password($password) {
    return strlen($password) >= 6;
}

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Database functions
function get_user_by_id($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_user_by_username($pdo, $username) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function get_user_by_email($pdo, $email) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function create_user($pdo, $username, $email, $password) {
    $hashed_password = hash_password($password);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    return $stmt->execute([$username, $email, $hashed_password]);
}

function login_user($pdo, $username, $password) {
    $user = get_user_by_username($pdo, $username);
    if ($user && verify_password($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function logout_user() {
    session_destroy();
    header('Location: index.php');
    exit;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_moderator() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'moderator']);
}

// Category functions
function get_categories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
    return $stmt->fetchAll();
}

function get_category_by_id($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function create_category($pdo, $name, $description, $slug) {
    $stmt = $pdo->prepare("INSERT INTO categories (name, description, slug) VALUES (?, ?, ?)");
    return $stmt->execute([$name, $description, $slug]);
}

// Topic functions
function get_topics($pdo, $category_id = null, $limit = 20, $offset = 0) {
    $sql = "SELECT t.*, u.username, c.name as category_name 
            FROM topics t 
            JOIN users u ON t.user_id = u.id 
            JOIN categories c ON t.category_id = c.id 
            WHERE t.status = 'active'";
    
    $params = [];
    if ($category_id) {
        $sql .= " AND t.category_id = ?";
        $params[] = $category_id;
    }
    
    $sql .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_topic_by_id($pdo, $id) {
    $stmt = $pdo->prepare("SELECT t.*, u.username, c.name as category_name 
                          FROM topics t 
                          JOIN users u ON t.user_id = u.id 
                          JOIN categories c ON t.category_id = c.id 
                          WHERE t.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function create_topic($pdo, $title, $content, $user_id, $category_id) {
    $stmt = $pdo->prepare("INSERT INTO topics (title, content, user_id, category_id) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$title, $content, $user_id, $category_id]);
}

function update_topic_views($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE topics SET views = views + 1 WHERE id = ?");
    $stmt->execute([$id]);
}

// Reply functions
function get_replies($pdo, $topic_id) {
    $stmt = $pdo->prepare("SELECT r.*, u.username 
                          FROM replies r 
                          JOIN users u ON r.user_id = u.id 
                          WHERE r.topic_id = ? AND r.status = 'active' 
                          ORDER BY r.created_at ASC");
    $stmt->execute([$topic_id]);
    return $stmt->fetchAll();
}

function create_reply($pdo, $content, $user_id, $topic_id) {
    $stmt = $pdo->prepare("INSERT INTO replies (content, user_id, topic_id) VALUES (?, ?, ?)");
    return $stmt->execute([$content, $user_id, $topic_id]);
}

// Utility functions
function generate_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function format_content($content) {
    // Basic formatting
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    $content = nl2br($content);
    
    // Convert URLs to links
    $content = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank">$1</a>', $content);
    
    return $content;
}

function paginate($current_page, $total_pages, $base_url) {
    $pagination = '<nav><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page - 1) . '">Previous</a></li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page + 1) . '">Next</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    return $pagination;
}

// Error handling
function handle_error($message, $redirect = null) {
    $_SESSION['error'] = $message;
    if ($redirect) {
        header('Location: ' . $redirect);
        exit;
    }
}

function handle_success($message, $redirect = null) {
    $_SESSION['success'] = $message;
    if ($redirect) {
        header('Location: ' . $redirect);
        exit;
    }
}

function display_messages() {
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-exclamation-triangle me-2"></i>' . $_SESSION['error'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-check-circle me-2"></i>' . $_SESSION['success'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        unset($_SESSION['success']);
    }
}
?>