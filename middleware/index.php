<?php
declare(strict_types=1);

// Middleware Index - Load all middleware classes
require_once __DIR__ . '/AuthMiddleware.php';
require_once __DIR__ . '/AdminMiddleware.php';
require_once __DIR__ . '/ModeratorMiddleware.php';
require_once __DIR__ . '/CSRFMiddleware.php';
require_once __DIR__ . '/RateLimitMiddleware.php';
require_once __DIR__ . '/SecurityHeadersMiddleware.php';
require_once __DIR__ . '/LoggingMiddleware.php';
require_once __DIR__ . '/CorsMiddleware.php';
require_once __DIR__ . '/ApiMiddleware.php';
require_once __DIR__ . '/ApiAuthMiddleware.php';
require_once __DIR__ . '/ApiModeratorMiddleware.php';
require_once __DIR__ . '/ApiAdminMiddleware.php';