<?php

//core/Functions.php
// Helper functions

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

function validate_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function format_date($date, $format = 'F j, Y, g:i a') {
    return date($format, strtotime($date));
}

function truncate($text, $chars = 150) {
    if (strlen($text) > $chars) {
        $text = substr($text, 0, $chars) . '...';
    }
    return $text;
}

function slugify($text) {
    // Replace non-letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    
    // Trim
    $text = trim($text, '-');
    
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    
    // Lowercase
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

function get_gravatar($email, $size = 80) {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/$hash?s=$size&d=mp";
}

function paginate($page, $totalItems, $itemsPerPage, $urlPattern) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    $pagination = [
        'current' => $page,
        'pages' => $totalPages,
        'previous' => $page > 1 ? $page - 1 : null,
        'next' => $page < $totalPages ? $page + 1 : null,
        'items' => []
    ];
    
    // Calculate range of pages to show
    $range = 2;
    $start = max(1, $page - $range);
    $end = min($totalPages, $page + $range);
    
    for ($i = $start; $i <= $end; $i++) {
        $pagination['items'][] = [
            'page' => $i,
            'url' => str_replace('{page}', $i, $urlPattern),
            'active' => $i == $page
        ];
    }
    
    return $pagination;
}

function is_active($path) {
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return strpos($currentPath, $path) !== false ? 'active' : '';
}
?>