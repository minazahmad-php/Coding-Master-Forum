<?php
declare(strict_types=1);

/**
 * Modern Forum - View Class
 * Handles rendering of views and layouts
 */

namespace Core;

class View
{
    private static string $layout = 'layouts/app';
    private static array $sharedData = [];
    
    /**
     * Render a view
     */
    public static function render(string $view, array $data = []): void
    {
        $data = array_merge(self::$sharedData, $data);
        
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewFile = VIEWS_PATH . '/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: $viewFile");
        }
        
        include $viewFile;
        
        // Get the content
        $content = ob_get_clean();
        
        // Include layout if specified
        if (self::$layout) {
            $layoutFile = VIEWS_PATH . '/' . self::$layout . '.php';
            
            if (file_exists($layoutFile)) {
                include $layoutFile;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }
    
    /**
     * Set the layout
     */
    public static function setLayout(string $layout): void
    {
        self::$layout = $layout;
    }
    
    /**
     * Share data with all views
     */
    public static function share(string $key, $value): void
    {
        self::$sharedData[$key] = $value;
    }
    
    /**
     * Get shared data
     */
    public static function getShared(string $key, $default = null)
    {
        return self::$sharedData[$key] ?? $default;
    }
    
    /**
     * Render a partial view
     */
    public static function partial(string $view, array $data = []): string
    {
        $data = array_merge(self::$sharedData, $data);
        extract($data);
        
        ob_start();
        
        $viewFile = VIEWS_PATH . '/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \Exception("Partial view file not found: $viewFile");
        }
        
        include $viewFile;
        
        return ob_get_clean();
    }
    
    /**
     * Render JSON response
     */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Render XML response
     */
    public static function xml(string $xml, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/xml');
        echo $xml;
        exit;
    }
    
    /**
     * Render plain text response
     */
    public static function text(string $text, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/plain');
        echo $text;
        exit;
    }
    
    /**
     * Redirect to a URL
     */
    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: $url");
        exit;
    }
    
    /**
     * Redirect back to previous page
     */
    public static function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::redirect($referer);
    }
    
    /**
     * Escape HTML
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Generate CSRF token
     */
    public static function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Generate CSRF token input field
     */
    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }
    
    /**
     * Format date
     */
    public static function formatDate(string $date, string $format = 'M j, Y'): string
    {
        return date($format, strtotime($date));
    }
    
    /**
     * Format date with relative time
     */
    public static function timeAgo(string $date): string
    {
        $time = time() - strtotime($date);
        
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
            return self::formatDate($date);
        }
    }
    
    /**
     * Truncate text
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Generate pagination links
     */
    public static function pagination(int $currentPage, int $totalPages, string $baseUrl = ''): string
    {
        if ($totalPages <= 1) {
            return '';
        }
        
        $html = '<nav class="pagination">';
        
        // Previous button
        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $html .= '<a href="' . $baseUrl . '?page=' . $prevPage . '" class="pagination-btn">Previous</a>';
        }
        
        // Page numbers
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        if ($start > 1) {
            $html .= '<a href="' . $baseUrl . '?page=1" class="pagination-btn">1</a>';
            if ($start > 2) {
                $html .= '<span class="pagination-ellipsis">...</span>';
            }
        }
        
        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $currentPage ? ' active' : '';
            $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="pagination-btn' . $active . '">' . $i . '</a>';
        }
        
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $html .= '<span class="pagination-ellipsis">...</span>';
            }
            $html .= '<a href="' . $baseUrl . '?page=' . $totalPages . '" class="pagination-btn">' . $totalPages . '</a>';
        }
        
        // Next button
        if ($currentPage < $totalPages) {
            $nextPage = $currentPage + 1;
            $html .= '<a href="' . $baseUrl . '?page=' . $nextPage . '" class="pagination-btn">Next</a>';
        }
        
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Generate breadcrumbs
     */
    public static function breadcrumbs(array $items): string
    {
        $html = '<nav class="breadcrumb">';
        
        foreach ($items as $index => $item) {
            if ($index > 0) {
                $html .= '<span class="breadcrumb-separator">/</span>';
            }
            
            if (isset($item['url']) && $index < count($items) - 1) {
                $html .= '<a href="' . $item['url'] . '" class="breadcrumb-link">' . self::escape($item['title']) . '</a>';
            } else {
                $html .= '<span class="breadcrumb-current">' . self::escape($item['title']) . '</span>';
            }
        }
        
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Generate alert message
     */
    public static function alert(string $message, string $type = 'info'): string
    {
        return '<div class="alert alert-' . $type . '">' . self::escape($message) . '</div>';
    }
    
    /**
     * Generate form field
     */
    public static function formField(string $name, string $label, string $type = 'text', array $options = []): string
    {
        $value = $options['value'] ?? '';
        $placeholder = $options['placeholder'] ?? '';
        $required = isset($options['required']) && $options['required'] ? 'required' : '';
        $class = $options['class'] ?? '';
        $id = $options['id'] ?? $name;
        
        $html = '<div class="form-group">';
        $html .= '<label for="' . $id . '" class="form-label">' . self::escape($label) . '</label>';
        
        if ($type === 'textarea') {
            $html .= '<textarea name="' . $name . '" id="' . $id . '" class="form-textarea ' . $class . '" placeholder="' . self::escape($placeholder) . '" ' . $required . '>' . self::escape($value) . '</textarea>';
        } elseif ($type === 'select') {
            $html .= '<select name="' . $name . '" id="' . $id . '" class="form-select ' . $class . '" ' . $required . '>';
            foreach ($options['options'] ?? [] as $optionValue => $optionLabel) {
                $selected = $value === $optionValue ? 'selected' : '';
                $html .= '<option value="' . self::escape($optionValue) . '" ' . $selected . '>' . self::escape($optionLabel) . '</option>';
            }
            $html .= '</select>';
        } else {
            $html .= '<input type="' . $type . '" name="' . $name . '" id="' . $id . '" class="form-input ' . $class . '" value="' . self::escape($value) . '" placeholder="' . self::escape($placeholder) . '" ' . $required . '>';
        }
        
        if (isset($options['help'])) {
            $html .= '<div class="form-help">' . self::escape($options['help']) . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate button
     */
    public static function button(string $text, string $type = 'button', array $options = []): string
    {
        $class = $options['class'] ?? 'btn';
        $onclick = isset($options['onclick']) ? 'onclick="' . self::escape($options['onclick']) . '"' : '';
        $disabled = isset($options['disabled']) && $options['disabled'] ? 'disabled' : '';
        
        return '<button type="' . $type . '" class="' . $class . '" ' . $onclick . ' ' . $disabled . '>' . self::escape($text) . '</button>';
    }
    
    /**
     * Generate link
     */
    public static function link(string $url, string $text, array $options = []): string
    {
        $class = $options['class'] ?? '';
        $target = isset($options['target']) ? 'target="' . self::escape($options['target']) . '"' : '';
        $title = isset($options['title']) ? 'title="' . self::escape($options['title']) . '"' : '';
        
        return '<a href="' . self::escape($url) . '" class="' . $class . '" ' . $target . ' ' . $title . '>' . self::escape($text) . '</a>';
    }
}