<?php
declare(strict_types=1);

namespace Middleware;

use Core\Logger;

class SecurityHeadersMiddleware
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    public function handle($request, $next)
    {
        if (!$this->isSecurityHeadersEnabled()) {
            return $next($request);
        }

        $this->setSecurityHeaders();
        
        return $next($request);
    }

    private function setSecurityHeaders(): void
    {
        // HSTS (HTTP Strict Transport Security)
        if (HSTS_ENABLED) {
            header('Strict-Transport-Security: max-age=' . HSTS_MAX_AGE . '; includeSubDomains; preload');
        }

        // X-Frame-Options
        header('X-Frame-Options: ' . X_FRAME_OPTIONS);

        // X-Content-Type-Options
        header('X-Content-Type-Options: ' . X_CONTENT_TYPE_OPTIONS);

        // Referrer-Policy
        header('Referrer-Policy: ' . REFERRER_POLICY);

        // Permissions-Policy
        $this->setPermissionsPolicy();

        // Content Security Policy
        if (CSP_ENABLED) {
            $this->setContentSecurityPolicy();
        }

        // X-XSS-Protection (for older browsers)
        header('X-XSS-Protection: 1; mode=block');

        // Cache-Control for sensitive pages
        if ($this->isSensitivePage()) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }

    private function setPermissionsPolicy(): void
    {
        $policies = [
            'geolocation' => '()',
            'microphone' => '()',
            'camera' => '()',
            'payment' => '()',
            'usb' => '()',
            'magnetometer' => '()',
            'gyroscope' => '()',
            'accelerometer' => '()',
            'ambient-light-sensor' => '()',
            'autoplay' => '()',
            'battery' => '()',
            'bluetooth' => '()',
            'display-capture' => '()',
            'document-domain' => '()',
            'encrypted-media' => '()',
            'fullscreen' => '()',
            'midi' => '()',
            'notifications' => '()',
            'picture-in-picture' => '()',
            'publickey-credentials-get' => '()',
            'screen-wake-lock' => '()',
            'sync-xhr' => '()',
            'web-share' => '()',
            'xr-spatial-tracking' => '()'
        ];

        $policyString = implode(', ', array_map(
            fn($feature, $allowlist) => $feature . '=' . $allowlist,
            array_keys($policies),
            $policies
        ));

        header('Permissions-Policy: ' . $policyString);
    }

    private function setContentSecurityPolicy(): void
    {
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https: blob:",
            "media-src 'self' https: blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "connect-src 'self' https: wss: ws:",
            "worker-src 'self' blob:",
            "child-src 'self' blob:",
            "manifest-src 'self'"
        ];

        // Add nonce for inline scripts if needed
        if (isset($_SESSION['csp_nonce'])) {
            $csp[1] .= " 'nonce-" . $_SESSION['csp_nonce'] . "'";
        }

        header('Content-Security-Policy: ' . implode('; ', $csp));
    }

    private function isSensitivePage(): bool
    {
        $sensitivePaths = ['/admin', '/dashboard', '/settings', '/profile', '/messages'];
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($sensitivePaths as $path) {
            if (strpos($currentPath, $path) === 0) {
                return true;
            }
        }
        
        return false;
    }

    private function isSecurityHeadersEnabled(): bool
    {
        return SECURITY_HEADERS_ENABLED;
    }

    public static function generateCSPNonce(): string
    {
        $nonce = base64_encode(random_bytes(16));
        $_SESSION['csp_nonce'] = $nonce;
        return $nonce;
    }

    public static function getCSPNonce(): ?string
    {
        return $_SESSION['csp_nonce'] ?? null;
    }
}