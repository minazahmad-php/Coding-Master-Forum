<?php

return [
    'csrf' => [
        'enabled' => true,
        'token_name' => '_token',
        'expire' => 3600, // 1 hour
    ],
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
        'expire_days' => 90,
    ],
    'session' => [
        'lifetime' => 120, // 2 hours
        'secure' => true,
        'httponly' => true,
        'same_site' => 'strict',
    ],
    'rate_limiting' => [
        'login_attempts' => 5,
        'login_window' => 900, // 15 minutes
        'api_requests' => 100,
        'api_window' => 3600, // 1 hour
    ],
    'file_upload' => [
        'max_size' => 10485760, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'],
        'scan_uploads' => true,
    ],
    'headers' => [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;",
    ],
    'encryption' => [
        'cipher' => 'AES-256-CBC',
        'key' => 'base64:' . base64_encode(random_bytes(32)),
    ],
];