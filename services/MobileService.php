<?php
declare(strict_types=1);

namespace Services;

class MobileService {
    private Database $db;
    private array $deviceInfo;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->deviceInfo = $this->detectDevice();
    }
    
    private function detectDevice(): array {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return [
            'is_mobile' => $this->isMobile($userAgent),
            'is_tablet' => $this->isTablet($userAgent),
            'is_desktop' => !$this->isMobile($userAgent) && !$this->isTablet($userAgent),
            'os' => $this->getOperatingSystem($userAgent),
            'browser' => $this->getBrowser($userAgent),
            'screen_size' => $this->getScreenSize(),
            'touch_support' => $this->hasTouchSupport(),
            'pwa_support' => $this->hasPWASupport(),
            'user_agent' => $userAgent
        ];
    }
    
    private function isMobile(string $userAgent): bool {
        return preg_match('/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);
    }
    
    private function isTablet(string $userAgent): bool {
        return preg_match('/iPad|Android.*Tablet|Kindle|Silk/i', $userAgent);
    }
    
    private function getOperatingSystem(string $userAgent): string {
        if (preg_match('/Windows/i', $userAgent)) return 'Windows';
        if (preg_match('/Mac/i', $userAgent)) return 'macOS';
        if (preg_match('/Linux/i', $userAgent)) return 'Linux';
        if (preg_match('/Android/i', $userAgent)) return 'Android';
        if (preg_match('/iPhone|iPad|iPod/i', $userAgent)) return 'iOS';
        if (preg_match('/BlackBerry/i', $userAgent)) return 'BlackBerry';
        
        return 'Unknown';
    }
    
    private function getBrowser(string $userAgent): string {
        if (preg_match('/Chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/Firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/Safari/i', $userAgent)) return 'Safari';
        if (preg_match('/Edge/i', $userAgent)) return 'Edge';
        if (preg_match('/Opera/i', $userAgent)) return 'Opera';
        
        return 'Unknown';
    }
    
    private function getScreenSize(): string {
        // This would typically be detected via JavaScript
        // For now, return based on device type
        if ($this->deviceInfo['is_mobile']) {
            return 'small';
        } elseif ($this->deviceInfo['is_tablet']) {
            return 'medium';
        } else {
            return 'large';
        }
    }
    
    private function hasTouchSupport(): bool {
        return $this->deviceInfo['is_mobile'] || $this->deviceInfo['is_tablet'];
    }
    
    private function hasPWASupport(): bool {
        // Check if browser supports PWA features
        $userAgent = $this->deviceInfo['user_agent'];
        return preg_match('/Chrome|Firefox|Safari|Edge/i', $userAgent);
    }
    
    public function getDeviceInfo(): array {
        return $this->deviceInfo;
    }
    
    public function isMobile(): bool {
        return $this->deviceInfo['is_mobile'];
    }
    
    public function isTablet(): bool {
        return $this->deviceInfo['is_tablet'];
    }
    
    public function isDesktop(): bool {
        return $this->deviceInfo['is_desktop'];
    }
    
    public function getResponsiveClass(): string {
        if ($this->isMobile()) {
            return 'mobile';
        } elseif ($this->isTablet()) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
    
    public function getViewportMeta(): string {
        $viewport = 'width=device-width, initial-scale=1.0';
        
        if ($this->isMobile()) {
            $viewport .= ', maximum-scale=1.0, user-scalable=no';
        }
        
        return $viewport;
    }
    
    public function getMobileCSS(): array {
        $css = [];
        
        if ($this->isMobile()) {
            $css[] = '/assets/css/mobile.css';
            $css[] = '/assets/css/touch.css';
        }
        
        if ($this->isTablet()) {
            $css[] = '/assets/css/tablet.css';
        }
        
        if ($this->hasTouchSupport()) {
            $css[] = '/assets/css/touch.css';
        }
        
        return $css;
    }
    
    public function getMobileJS(): array {
        $js = [];
        
        if ($this->isMobile()) {
            $js[] = '/assets/js/mobile.js';
            $js[] = '/assets/js/touch.js';
        }
        
        if ($this->hasTouchSupport()) {
            $js[] = '/assets/js/touch.js';
        }
        
        if ($this->hasPWASupport()) {
            $js[] = '/assets/js/pwa.js';
        }
        
        return $js;
    }
    
    public function optimizeForMobile(): array {
        $optimizations = [];
        
        if ($this->isMobile()) {
            $optimizations['lazy_loading'] = true;
            $optimizations['image_compression'] = true;
            $optimizations['minimal_js'] = true;
            $optimizations['touch_gestures'] = true;
            $optimizations['swipe_navigation'] = true;
            $optimizations['pull_to_refresh'] = true;
        }
        
        if ($this->isTablet()) {
            $optimizations['hybrid_layout'] = true;
            $optimizations['touch_gestures'] = true;
        }
        
        return $optimizations;
    }
    
    public function getMobileNavigation(): array {
        $navigation = [];
        
        if ($this->isMobile()) {
            $navigation = [
                'type' => 'bottom_tabs',
                'items' => [
                    ['icon' => 'home', 'label' => 'Home', 'url' => '/'],
                    ['icon' => 'forums', 'label' => 'Forums', 'url' => '/forums'],
                    ['icon' => 'search', 'label' => 'Search', 'url' => '/search'],
                    ['icon' => 'messages', 'label' => 'Messages', 'url' => '/messages'],
                    ['icon' => 'profile', 'label' => 'Profile', 'url' => '/profile']
                ]
            ];
        } else {
            $navigation = [
                'type' => 'sidebar',
                'items' => [
                    ['icon' => 'home', 'label' => 'Home', 'url' => '/'],
                    ['icon' => 'forums', 'label' => 'Forums', 'url' => '/forums'],
                    ['icon' => 'users', 'label' => 'Users', 'url' => '/users'],
                    ['icon' => 'search', 'label' => 'Search', 'url' => '/search'],
                    ['icon' => 'messages', 'label' => 'Messages', 'url' => '/messages'],
                    ['icon' => 'notifications', 'label' => 'Notifications', 'url' => '/notifications'],
                    ['icon' => 'bookmarks', 'label' => 'Bookmarks', 'url' => '/bookmarks'],
                    ['icon' => 'settings', 'label' => 'Settings', 'url' => '/settings']
                ]
            ];
        }
        
        return $navigation;
    }
    
    public function getTouchGestures(): array {
        if (!$this->hasTouchSupport()) {
            return [];
        }
        
        return [
            'swipe_left' => 'navigate_back',
            'swipe_right' => 'navigate_forward',
            'swipe_up' => 'scroll_up',
            'swipe_down' => 'scroll_down',
            'pinch_zoom' => 'zoom_content',
            'double_tap' => 'like_post',
            'long_press' => 'show_context_menu',
            'pull_down' => 'refresh_content'
        ];
    }
    
    public function getMobileSettings(): array {
        return [
            'enable_touch_gestures' => $this->hasTouchSupport(),
            'enable_swipe_navigation' => $this->isMobile(),
            'enable_pull_to_refresh' => $this->isMobile(),
            'enable_offline_mode' => true,
            'enable_push_notifications' => $this->hasPWASupport(),
            'enable_background_sync' => $this->hasPWASupport(),
            'enable_install_prompt' => $this->hasPWASupport(),
            'optimize_images' => $this->isMobile(),
            'lazy_load_content' => $this->isMobile(),
            'minimize_data_usage' => $this->isMobile()
        ];
    }
    
    public function trackMobileUsage(): void {
        if (!$this->isMobile() && !$this->isTablet()) {
            return;
        }
        
        $this->db->insert('mobile_usage', [
            'user_id' => Auth::getUserId(),
            'device_type' => $this->getResponsiveClass(),
            'os' => $this->deviceInfo['os'],
            'browser' => $this->deviceInfo['browser'],
            'screen_size' => $this->deviceInfo['screen_size'],
            'touch_support' => $this->deviceInfo['touch_support'],
            'pwa_support' => $this->deviceInfo['pwa_support'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $this->deviceInfo['user_agent'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getMobileAnalytics(): array {
        $analytics = [];
        
        // Device distribution
        $analytics['device_distribution'] = $this->db->fetchAll(
            "SELECT device_type, COUNT(*) as count 
             FROM mobile_usage 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY device_type"
        );
        
        // OS distribution
        $analytics['os_distribution'] = $this->db->fetchAll(
            "SELECT os, COUNT(*) as count 
             FROM mobile_usage 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY os"
        );
        
        // Browser distribution
        $analytics['browser_distribution'] = $this->db->fetchAll(
            "SELECT browser, COUNT(*) as count 
             FROM mobile_usage 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY browser"
        );
        
        // PWA adoption
        $analytics['pwa_adoption'] = $this->db->fetchAll(
            "SELECT pwa_support, COUNT(*) as count 
             FROM mobile_usage 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY pwa_support"
        );
        
        return $analytics;
    }
    
    public function optimizeImages(array $images): array {
        if (!$this->isMobile()) {
            return $images;
        }
        
        $optimized = [];
        
        foreach ($images as $image) {
            $optimized[] = [
                'src' => $image['src'],
                'srcset' => $this->generateSrcSet($image['src']),
                'sizes' => $this->getImageSizes(),
                'loading' => 'lazy',
                'alt' => $image['alt'] ?? ''
            ];
        }
        
        return $optimized;
    }
    
    private function generateSrcSet(string $src): string {
        $basePath = pathinfo($src, PATHINFO_DIRNAME);
        $filename = pathinfo($src, PATHINFO_FILENAME);
        $extension = pathinfo($src, PATHINFO_EXTENSION);
        
        $sizes = [320, 640, 768, 1024, 1280];
        $srcset = [];
        
        foreach ($sizes as $size) {
            $srcset[] = "{$basePath}/{$filename}-{$size}w.{$extension} {$size}w";
        }
        
        return implode(', ', $srcset);
    }
    
    private function getImageSizes(): string {
        if ($this->isMobile()) {
            return '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw';
        } elseif ($this->isTablet()) {
            return '(max-width: 1024px) 100vw, 50vw';
        } else {
            return '(max-width: 1024px) 100vw, 33vw';
        }
    }
    
    public function getMobileMetaTags(): array {
        $metaTags = [];
        
        if ($this->isMobile()) {
            $metaTags[] = '<meta name="mobile-web-app-capable" content="yes">';
            $metaTags[] = '<meta name="apple-mobile-web-app-capable" content="yes">';
            $metaTags[] = '<meta name="apple-mobile-web-app-status-bar-style" content="default">';
            $metaTags[] = '<meta name="format-detection" content="telephone=no">';
        }
        
        if ($this->hasPWASupport()) {
            $metaTags[] = '<meta name="theme-color" content="#667eea">';
            $metaTags[] = '<link rel="manifest" href="/manifest.json">';
        }
        
        return $metaTags;
    }
}