<?php

return [
    'version' => 'v1',
    'prefix' => 'api',
    'rate_limit' => [
        'enabled' => true,
        'requests_per_minute' => 60,
        'burst_limit' => 100,
    ],
    'authentication' => [
        'methods' => ['jwt', 'api_key'],
        'jwt' => [
            'secret' => 'your-jwt-secret-key',
            'algorithm' => 'HS256',
            'expire' => 3600, // 1 hour
        ],
        'api_key' => [
            'header' => 'X-API-Key',
            'expire' => 2592000, // 30 days
        ],
    ],
    'cors' => [
        'enabled' => true,
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
        'max_age' => 86400,
    ],
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],
    'response_format' => [
        'success' => [
            'status' => 'success',
            'data' => null,
            'message' => null,
        ],
        'error' => [
            'status' => 'error',
            'error' => null,
            'message' => null,
        ],
    ],
];