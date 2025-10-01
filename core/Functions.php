<?php
declare(strict_types=1);

// Security Functions
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function sanitizeHtml(string $input): string {
    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateUrl(string $url): bool {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function generateCsrfToken(): string {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCsrfToken() . '">';
}

// Legacy CSRF functions for compatibility
function csrf_token(): string {
    return generateCsrfToken();
}

function validate_csrf(string $token): bool {
    return verifyCsrfToken($token);
}

// File Upload Functions
function validateFileUpload(array $file, array $allowedTypes = [], int $maxSize = 0): array {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed';
        return ['valid' => false, 'errors' => $errors];
    }
    
    if ($maxSize > 0 && $file['size'] > $maxSize) {
        $errors[] = 'File size exceeds maximum allowed size';
    }
    
    if (!empty($allowedTypes)) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain'
    ];
    
    if (!empty($allowedTypes)) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (isset($allowedMimeTypes[$extension]) && $mimeType !== $allowedMimeTypes[$extension]) {
            $errors[] = 'File type mismatch';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

function uploadFile(array $file, string $directory, string $filename = null): array {
    if (!$filename) {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
    }
    
    $uploadPath = UPLOADS_PATH . '/' . $directory;
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    $filePath = $uploadPath . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $filePath,
            'url' => '/uploads/' . $directory . '/' . $filename
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Failed to upload file'
    ];
}

// String Functions
function slugify(string $text): string {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

function excerpt(string $text, int $length = 150): string {
    $text = strip_tags($text);
    return truncate($text, $length);
}

function highlightSearch(string $text, string $search): string {
    if (empty($search)) {
        return $text;
    }
    
    return preg_replace(
        '/(' . preg_quote($search, '/') . ')/i',
        '<mark>$1</mark>',
        $text
    );
}

// Date Functions
function timeAgo(string $datetime): string {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', strtotime($datetime));
    }
}

function formatDate(string $datetime, string $format = 'Y-m-d H:i:s'): string {
    return date($format, strtotime($datetime));
}

function format_date(string $date, string $format = 'F j, Y, g:i a'): string {
    return formatDate($date, $format);
}

function isToday(string $datetime): bool {
    return date('Y-m-d', strtotime($datetime)) === date('Y-m-d');
}

function isYesterday(string $datetime): bool {
    return date('Y-m-d', strtotime($datetime)) === date('Y-m-d', strtotime('-1 day'));
}

// Pagination Functions
function paginate(int $currentPage, int $totalItems, int $itemsPerPage, string $baseUrl): array {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    $pagination = [
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
        'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null,
        'pages' => []
    ];
    
    // Generate page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $pagination['pages'][] = [
            'number' => $i,
            'url' => str_replace('{page}', $i, $baseUrl),
            'current' => $i === $currentPage
        ];
    }
    
    return $pagination;
}

// URL Functions
function url(string $path = ''): string {
    $baseUrl = rtrim(SITE_URL, '/');
    $path = ltrim($path, '/');
    return $baseUrl . ($path ? '/' . $path : '');
}

function asset(string $path): string {
    return url('public/' . ltrim($path, '/'));
}

function route(string $name, array $params = []): string {
    // This would be implemented with a route name mapping
    // For now, just return the path
    return '/' . ltrim($name, '/');
}

function redirect(string $url, int $statusCode = 302): void {
    http_response_code($statusCode);
    header('Location: ' . $url);
    exit;
}

// Array Functions
function arrayGet(array $array, string $key, mixed $default = null): mixed {
    $keys = explode('.', $key);
    $value = $array;
    
    foreach ($keys as $k) {
        if (!is_array($value) || !array_key_exists($k, $value)) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

function arraySet(array &$array, string $key, mixed $value): void {
    $keys = explode('.', $key);
    $current = &$array;
    
    foreach ($keys as $k) {
        if (!isset($current[$k]) || !is_array($current[$k])) {
            $current[$k] = [];
        }
        $current = &$current[$k];
    }
    
    $current = $value;
}

// Cache Functions
function cache(string $key, callable $callback, int $ttl = 3600): mixed {
    if (!CACHE_ENABLED) {
        return $callback();
    }
    
    $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        return unserialize(file_get_contents($cacheFile));
    }
    
    $data = $callback();
    file_put_contents($cacheFile, serialize($data));
    
    return $data;
}

function cacheForget(string $key): void {
    $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
}

function cacheFlush(): void {
    $files = glob(CACHE_PATH . '/*.cache');
    foreach ($files as $file) {
        unlink($file);
    }
}

// Logging Functions
function logError(string $message, array $context = []): void {
    $logMessage = date('Y-m-d H:i:s') . ' ERROR: ' . $message;
    if (!empty($context)) {
        $logMessage .= ' ' . json_encode($context);
    }
    $logMessage .= PHP_EOL;
    
    file_put_contents(LOGS_PATH . '/error.log', $logMessage, FILE_APPEND | LOCK_EX);
}

function logInfo(string $message, array $context = []): void {
    $logMessage = date('Y-m-d H:i:s') . ' INFO: ' . $message;
    if (!empty($context)) {
        $logMessage .= ' ' . json_encode($context);
    }
    $logMessage .= PHP_EOL;
    
    file_put_contents(LOGS_PATH . '/app.log', $logMessage, FILE_APPEND | LOCK_EX);
}

// Validation Functions
function validateRequired(array $data, array $fields): array {
    $errors = [];
    
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            $errors[$field] = ucfirst($field) . ' is required';
        }
    }
    
    return $errors;
}

function validateLength(string $value, int $min = 0, int $max = 0): bool {
    $length = strlen($value);
    
    if ($min > 0 && $length < $min) {
        return false;
    }
    
    if ($max > 0 && $length > $max) {
        return false;
    }
    
    return true;
}

function validateRegex(string $value, string $pattern): bool {
    return preg_match($pattern, $value) === 1;
}

// Response Functions
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function back(): void {
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect($referer);
}

// Utility Functions
function config(string $key, mixed $default = null): mixed {
    $config = [
        'site.name' => SITE_NAME,
        'site.url' => SITE_URL,
        'site.description' => SITE_DESCRIPTION,
        'database.path' => DB_PATH,
        'upload.max_size' => MAX_FILE_SIZE,
        'pagination.posts_per_page' => POSTS_PER_PAGE,
        'pagination.threads_per_page' => THREADS_PER_PAGE,
        'cache.enabled' => CACHE_ENABLED,
        'cache.lifetime' => CACHE_LIFETIME
    ];
    
    return arrayGet($config, $key, $default);
}

function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? $default;
}

function dd(mixed $var): void {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}

function dump(mixed $var): void {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

// Legacy functions for compatibility
function get_gravatar(string $email, int $size = 80): string {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/$hash?s=$size&d=mp";
}

function is_active(string $path): string {
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return strpos($currentPath, $path) !== false ? 'active' : '';
}
?>